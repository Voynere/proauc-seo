"""Image download and WordPress media sideload."""

from __future__ import annotations

import logging
import re
import shutil
import subprocess
import tempfile
from pathlib import Path
from typing import Any
from urllib.parse import urlparse

import requests

from .config import AppConfig
from .http import HttpClient
from .schema import NormalizedListing

logger = logging.getLogger(__name__)

SAFE_FILENAME_RE = re.compile(r"[^\w.\-]+", re.UNICODE)


class MediaSideloader:
    def __init__(self, config: AppConfig, http: HttpClient) -> None:
        self.config = config
        self.http = http
        self.wp = config.wordpress
        self.dry_run = config.import_.dry_run
        self.temp_root = Path(tempfile.gettempdir()) / "motorhome-import"

    def process(self, listing: NormalizedListing) -> dict[str, Any]:
        urls = [u for u in listing.photos if u.startswith("http")]
        if not urls:
            return {"photos": [], "action": "none"}

        if self.dry_run:
            logger.info(
                "DRY-RUN: would sideload %s photo(s) for %s/%s",
                len(urls),
                listing.source,
                listing.source_id,
            )
            for index, url in enumerate(urls, start=1):
                logger.info("  photo[%s]: %s", index, url)
            return {"action": "dry_run", "urls": urls, "count": len(urls)}

        attachment_ids = self._sideload_urls(listing, urls)
        if attachment_ids:
            listing.photos = [str(i) for i in attachment_ids]
        return {"action": "sideload", "attachment_ids": attachment_ids, "count": len(attachment_ids)}

    def _sideload_urls(self, listing: NormalizedListing, urls: list[str]) -> list[int]:
        work_dir = self.temp_root / f"{listing.source}_{listing.source_id}"
        work_dir.mkdir(parents=True, exist_ok=True)
        local_files: list[Path] = []

        try:
            for index, url in enumerate(urls, start=1):
                local_path = self._download(url, work_dir, index)
                if local_path:
                    local_files.append(local_path)

            if not local_files:
                return []

            if self._wp_cli_local_available():
                return self._import_via_local_wp_cli(local_files)

            if self.wp.ssh_host:
                return self._import_via_ssh_wp_cli(local_files)

            if self.wp.user and self.wp.app_password:
                return self._import_via_rest(local_files)

            logger.warning("No media import method available (wp-cli, ssh, or REST)")
            return []
        finally:
            shutil.rmtree(work_dir, ignore_errors=True)

    def _download(self, url: str, work_dir: Path, index: int) -> Path | None:
        try:
            response = self.http.get(url, stream=True)
        except Exception:
            logger.exception("Failed to download %s", url)
            return None

        ext = Path(urlparse(url).path).suffix or ".jpg"
        if ext.lower() not in {".jpg", ".jpeg", ".png", ".webp", ".gif"}:
            ext = ".jpg"
        filename = SAFE_FILENAME_RE.sub("_", f"{index:03d}{ext}")
        dest = work_dir / filename

        with open(dest, "wb") as fh:
            for chunk in response.iter_content(chunk_size=65536):
                if chunk:
                    fh.write(chunk)

        logger.debug("Downloaded %s → %s", url, dest)
        return dest

    def _wp_cli_local_available(self) -> bool:
        if not self.wp.wp_path:
            return False
        try:
            result = subprocess.run(
                ["wp", "--info", f"--path={self.wp.wp_path}"],
                capture_output=True,
                text=True,
                check=False,
            )
            return result.returncode == 0
        except FileNotFoundError:
            return False

    def _import_via_local_wp_cli(self, files: list[Path]) -> list[int]:
        ids: list[int] = []
        for index, path in enumerate(files):
            cmd = [
                "wp",
                "media",
                "import",
                str(path),
                f"--path={self.wp.wp_path}",
                "--porcelain",
            ]
            if index == 0:
                cmd.append("--featured_image")
            result = subprocess.run(cmd, capture_output=True, text=True, check=False)
            if result.returncode != 0:
                logger.error("wp media import failed: %s", result.stderr.strip())
                continue
            try:
                ids.append(int(result.stdout.strip()))
            except ValueError:
                logger.error("Unexpected wp media import output: %r", result.stdout)
        return ids

    def _import_via_ssh_wp_cli(self, files: list[Path]) -> list[int]:
        remote_dir = f"/tmp/motorhome-import-{files[0].parent.name}"
        ssh_host = self.wp.ssh_host
        wp_path = self.wp.wp_path

        subprocess.run(
            ["ssh", ssh_host, f"mkdir -p {remote_dir}"],
            check=True,
            capture_output=True,
            text=True,
        )

        remote_files: list[str] = []
        for path in files:
            remote_path = f"{remote_dir}/{path.name}"
            subprocess.run(
                ["scp", str(path), f"{ssh_host}:{remote_path}"],
                check=True,
                capture_output=True,
                text=True,
            )
            remote_files.append(remote_path)

        ids: list[int] = []
        for index, remote_path in enumerate(remote_files):
            featured = "--featured_image" if index == 0 else ""
            cmd = (
                f"wp media import {remote_path} --path={wp_path} --porcelain {featured}".strip()
            )
            result = subprocess.run(
                ["ssh", ssh_host, cmd],
                capture_output=True,
                text=True,
                check=False,
            )
            if result.returncode != 0:
                logger.error("Remote wp media import failed: %s", result.stderr.strip())
                continue
            try:
                ids.append(int(result.stdout.strip()))
            except ValueError:
                logger.error("Unexpected remote wp output: %r", result.stdout)

        subprocess.run(
            ["ssh", ssh_host, f"rm -rf {remote_dir}"],
            check=False,
            capture_output=True,
            text=True,
        )
        return ids

    def _import_via_rest(self, files: list[Path]) -> list[int]:
        ids: list[int] = []
        base = self.wp.url.rstrip("/")
        auth = (self.wp.user, self.wp.app_password)

        for path in files:
            with open(path, "rb") as fh:
                files_payload = {"file": (path.name, fh, "image/jpeg")}
                response = requests.post(
                    f"{base}/wp-json/wp/v2/media",
                    files=files_payload,
                    auth=auth,
                    timeout=120,
                )
            if response.status_code not in (200, 201):
                logger.error("REST media upload failed: HTTP %s %s", response.status_code, response.text[:200])
                continue
            media = response.json()
            ids.append(int(media["id"]))
        return ids
