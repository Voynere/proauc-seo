"""WordPress writer — dry-run and real import."""

from __future__ import annotations

import json
import logging
import subprocess
from typing import Any

import requests

from .acf import build_photos_meta, build_wp_meta_updates
from .config import AppConfig
from .media import MediaSideloader
from .schema import NormalizedListing

logger = logging.getLogger(__name__)


class WordPressWriter:
    def __init__(self, config: AppConfig, http: Any = None) -> None:
        self.config = config
        self.wp = config.wordpress
        self.dry_run = config.import_.dry_run
        self._http = http
        self._media: MediaSideloader | None = None

    def _get_media(self) -> MediaSideloader:
        if self._media is None:
            if self._http is None:
                raise RuntimeError("HttpClient required for media sideload")
            self._media = MediaSideloader(self.config, self._http)
        return self._media

    def upsert(self, listing: NormalizedListing) -> dict[str, Any]:
        media_result: dict[str, Any] | None = None
        if self.config.import_.sideload_images and listing.photos:
            media_result = self._get_media().process(listing)

        if self.dry_run:
            result = self._dry_run(listing)
            if media_result:
                result["media"] = media_result
            return result

        existing = self.find_existing(listing.source, listing.source_id)
        if existing:
            result = self._update(existing, listing)
        else:
            result = self._create(listing)

        if media_result:
            result["media"] = media_result
        return result

    def _dry_run(self, listing: NormalizedListing) -> dict[str, Any]:
        acf_meta = build_wp_meta_updates(listing)
        payload = {
            "action": "dry_run",
            "post_type": self.wp.post_type,
            "category_id": self.wp.category_id,
            "title": listing.title,
            "meta": listing.wp_meta(),
            "acf": listing.acf_payload(),
            "acf_meta_keys": list(acf_meta.keys()),
            "source_url": listing.source_url,
            "sold_out": listing.sold_out,
        }
        logger.info("DRY-RUN: would upsert %s / %s", listing.source, listing.source_id)
        return payload

    def find_existing(self, source: str, source_id: str) -> int | None:
        if self._wp_cli_available():
            return self._find_via_wp_cli(source_id)
        return self._find_via_rest(source, source_id)

    def _find_via_wp_cli(self, source_id: str) -> int | None:
        cmd = self._wp_cmd(
            "post",
            "list",
            f"--post_type={self.wp.post_type}",
            "--meta_key=_source_id",
            f"--meta_value={source_id}",
            "--format=ids",
        )
        try:
            result = self._run_wp(cmd)
            if result.returncode != 0:
                logger.warning("wp-cli dedup failed: %s", result.stderr.strip())
                return None
            ids = result.stdout.strip()
            if not ids:
                return None
            return int(ids.split()[0])
        except FileNotFoundError:
            return None

    def _find_via_rest(self, source: str, source_id: str) -> int | None:
        if not self.wp.user or not self.wp.app_password:
            logger.warning("REST dedup skipped: no credentials")
            return None
        url = f"{self.wp.url.rstrip('/')}/wp-json/wp/v2/{self.wp.post_type}"
        params = {
            "meta_key": "_source_id",
            "meta_value": source_id,
            "per_page": 1,
        }
        response = requests.get(
            url,
            params=params,
            auth=(self.wp.user, self.wp.app_password),
            timeout=30,
        )
        if response.status_code != 200:
            logger.warning("REST dedup failed: HTTP %s", response.status_code)
            return None
        posts = response.json()
        if not posts:
            return None
        return int(posts[0]["id"])

    def _create(self, listing: NormalizedListing) -> dict[str, Any]:
        if self._wp_cli_available() or self.wp.ssh_host:
            return self._create_via_wp_cli(listing)
        return self._create_via_rest(listing)

    def _update(self, post_id: int, listing: NormalizedListing) -> dict[str, Any]:
        logger.info("Update existing post %s for %s/%s", post_id, listing.source, listing.source_id)
        self._apply_acf_meta(post_id, listing)
        return {
            "action": "update",
            "post_id": post_id,
            "title": listing.title,
            "meta": listing.wp_meta(),
            "acf": listing.acf_payload(),
        }

    def _create_via_wp_cli(self, listing: NormalizedListing) -> dict[str, Any]:
        cmd = self._wp_cmd(
            "post",
            "create",
            f"--post_type={self.wp.post_type}",
            f"--post_title={listing.title}",
            "--post_status=publish",
            f"--post_category={self.wp.category_id}",
            "--porcelain",
        )
        result = self._run_wp(cmd)
        if result.returncode != 0:
            raise RuntimeError(f"wp post create failed: {result.stderr}")
        post_id = int(result.stdout.strip())
        self._set_meta_wp_cli(post_id, listing)
        self._apply_acf_meta(post_id, listing)
        return {"action": "create", "post_id": post_id, "method": "wp-cli"}

    def _set_meta_wp_cli(self, post_id: int, listing: NormalizedListing) -> None:
        for key, value in listing.wp_meta().items():
            self._run_wp(
                self._wp_cmd("post", "meta", "update", str(post_id), key, value),
                check=True,
            )

    def _apply_acf_meta(self, post_id: int, listing: NormalizedListing) -> None:
        meta = build_wp_meta_updates(listing)
        attachment_ids = [int(p) for p in listing.photos if str(p).isdigit()]
        if attachment_ids:
            meta.update(build_photos_meta(attachment_ids))
            featured_id = attachment_ids[0]
            self._run_wp(
                self._wp_cmd("post", "meta", "update", str(post_id), "_thumbnail_id", str(featured_id)),
                check=False,
            )

        for key, value in meta.items():
            self._run_wp(
                self._wp_cmd("post", "meta", "update", str(post_id), key, str(value)),
                check=False,
            )

    def _create_via_rest(self, listing: NormalizedListing) -> dict[str, Any]:
        if not self.wp.user or not self.wp.app_password:
            raise RuntimeError("WP credentials required for REST import (or use wp-cli)")
        url = f"{self.wp.url.rstrip('/')}/wp-json/wp/v2/{self.wp.post_type}"
        body = {
            "title": listing.title,
            "status": "publish",
            "categories": [self.wp.category_id],
            "meta": listing.wp_meta(),
        }
        response = requests.post(
            url,
            json=body,
            auth=(self.wp.user, self.wp.app_password),
            timeout=60,
        )
        if response.status_code not in (200, 201):
            raise RuntimeError(f"REST create failed: {response.status_code} {response.text[:300]}")
        post = response.json()
        post_id = int(post["id"])
        logger.warning(
            "REST create OK for post %s — ACF flat meta not applied via REST; use wp-cli or app password + SSH",
            post_id,
        )
        return {
            "action": "create",
            "post_id": post_id,
            "method": "rest",
            "note": "ACF meta requires wp-cli follow-up",
        }

    def _wp_cmd(self, *args: str) -> list[str]:
        cmd = ["wp", *args, f"--path={self.wp.wp_path}"]
        return cmd

    def _run_wp(self, cmd: list[str], *, check: bool = False) -> subprocess.CompletedProcess[str]:
        if self.wp.ssh_host:
            remote_cmd = " ".join(cmd)
            return subprocess.run(
                ["ssh", self.wp.ssh_host, remote_cmd],
                capture_output=True,
                text=True,
                check=check,
            )
        return subprocess.run(cmd, capture_output=True, text=True, check=check)

    def _wp_cli_available(self) -> bool:
        if not self.wp.wp_path:
            return False
        if self.wp.ssh_host:
            try:
                result = subprocess.run(
                    ["ssh", self.wp.ssh_host, f"wp --info --path={self.wp.wp_path}"],
                    capture_output=True,
                    text=True,
                    check=False,
                )
                return result.returncode == 0
            except FileNotFoundError:
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

    @staticmethod
    def format_listing_json(listing: NormalizedListing, result: dict[str, Any] | None = None) -> str:
        output = {
            "listing": listing.to_dict(),
            "wp_result": result,
        }
        return json.dumps(output, ensure_ascii=False, indent=2)
