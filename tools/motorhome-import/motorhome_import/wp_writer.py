"""WordPress writer — dry-run and real import."""

from __future__ import annotations

import json
import logging
import os
import shlex
import subprocess
from typing import Any

import requests

from .acf import build_photos_meta, build_wp_meta_updates
from .config import AppConfig
from .media import MediaSideloader
from .schema import NormalizedListing

logger = logging.getLogger(__name__)


def _wp_allow_root() -> bool:
    """wp-cli refuses root unless --allow-root (CI/prod SSH often runs as root)."""
    try:
        return os.geteuid() == 0
    except AttributeError:
        return False


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
        if self.dry_run:
            media_result: dict[str, Any] | None = None
            if self.config.import_.sideload_images and listing.photos:
                media_result = self._get_media().process(listing)
            result = self._dry_run(listing)
            if media_result:
                result["media"] = media_result
            return result

        existing = self.find_existing(listing.source, listing.source_id)
        media_result = None
        # Skip re-sideload on update when gallery already present (major speed-up).
        need_media = bool(self.config.import_.sideload_images and listing.photos)
        if existing and need_media and self._post_has_photos(existing):
            need_media = False
            logger.info(
                "Skip media sideload for existing post %s (%s/%s)",
                existing,
                listing.source,
                listing.source_id,
            )
        if need_media:
            media_result = self._get_media().process(listing)

        if existing:
            result = self._update(existing, listing)
        else:
            result = self._create(listing)

        if media_result:
            result["media"] = media_result
        return result

    def _post_has_photos(self, post_id: int) -> bool:
        """True if ACF photos / thumbnail already set on the post."""
        thumb = self._get_post_meta(post_id, "_thumbnail_id")
        if thumb and str(thumb).isdigit() and int(thumb) > 0:
            return True
        photos = self._get_post_meta(post_id, "photos")
        return bool(photos and str(photos).strip() not in ("", "a:0:{}"))

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
        self._update_post_title(post_id, listing.title)
        self._apply_acf_meta(post_id, listing)
        return {
            "action": "update",
            "post_id": post_id,
            "title": listing.title,
            "meta": listing.wp_meta(),
            "acf": listing.acf_payload(),
        }

    def _update_post_title(self, post_id: int, title: str) -> None:
        self._run_wp(
            self._wp_cmd("post", "update", str(post_id), f"--post_title={title}"),
            check=False,
        )

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

        for key, value in meta.items():
            self._run_wp(
                self._wp_cmd("post", "meta", "update", str(post_id), key, str(value)),
                check=False,
            )

        # Clear stale «Оценка» when importer intentionally leaves grade empty
        # (Encar badges were previously written as Автодом).
        if not listing.properties.grade:
            self._run_wp(
                self._wp_cmd("post", "meta", "delete", str(post_id), "properties_grade"),
                check=False,
            )

        if attachment_ids:
            self._apply_photos_field(post_id, attachment_ids)

    def _apply_photos_field(self, post_id: int, attachment_ids: list[int]) -> None:
        """Set ACF gallery via update_field() — avoids wp-cli double-serialization."""
        ids_csv = ", ".join(str(i) for i in attachment_ids)
        php = (
            f'$ids = array({ids_csv}); '
            f'update_field("photos", $ids, {post_id}); '
            f'set_post_thumbnail({post_id}, {attachment_ids[0]});'
        )
        self._run_wp(self._wp_cmd("eval", php), check=False)

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
        if self.wp.ssh_host or _wp_allow_root():
            cmd.append("--allow-root")
        return cmd

    def _run_wp(self, cmd: list[str], *, check: bool = False) -> subprocess.CompletedProcess[str]:
        if self.wp.ssh_host:
            remote_cmd = " ".join(shlex.quote(arg) for arg in cmd)
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
                    ["ssh", self.wp.ssh_host, f"wp --info --path={self.wp.wp_path} --allow-root"],
                    capture_output=True,
                    text=True,
                    check=False,
                )
                return result.returncode == 0
            except FileNotFoundError:
                return False
        try:
            cmd = ["wp", "--info", f"--path={self.wp.wp_path}"]
            if _wp_allow_root():
                cmd.append("--allow-root")
            result = subprocess.run(
                cmd,
                capture_output=True,
                text=True,
                check=False,
            )
            return result.returncode == 0
        except FileNotFoundError:
            return False

    def retranslate_existing_posts(
        self,
        *,
        sources: tuple[str, ...] = ("fujicars", "bobaedream", "encar"),
        dry_run: bool | None = None,
    ) -> list[dict[str, Any]]:
        """Retranslate post_title and properties_grade for imported motorhome posts."""
        from .translate import is_non_grade_value, translate_grade, translate_title

        effective_dry_run = self.dry_run if dry_run is None else dry_run
        results: list[dict[str, Any]] = []

        for source in sources:
            post_ids = self._list_posts_by_source(source)
            for post_id in post_ids:
                title = self._get_post_field(post_id, "post_title")
                grade = self._get_post_meta(post_id, "properties_grade")
                new_title = translate_title(title)
                if grade and (is_non_grade_value(grade) or source == "encar"):
                    # Encar badges / «Автодом» must not stay in Оценка.
                    new_grade = translate_grade(grade)
                    clear_grade = True
                elif grade:
                    new_grade = translate_grade(grade)
                    clear_grade = new_grade is None and is_non_grade_value(grade)
                else:
                    new_grade = grade
                    clear_grade = False

                grade_changed = bool(grade) and (
                    clear_grade or (new_grade is not None and new_grade != grade)
                )
                changed = new_title != title or grade_changed
                entry = {
                    "post_id": post_id,
                    "source": source,
                    "title_before": title,
                    "title_after": new_title,
                    "grade_before": grade,
                    "grade_after": None if clear_grade else new_grade,
                    "changed": changed,
                }
                if changed and not effective_dry_run:
                    if new_title != title:
                        self._update_post_title(post_id, new_title)
                    if clear_grade and grade:
                        self._run_wp(
                            self._wp_cmd(
                                "post",
                                "meta",
                                "delete",
                                str(post_id),
                                "properties_grade",
                            ),
                            check=False,
                        )
                    elif grade and new_grade and new_grade != grade:
                        self._run_wp(
                            self._wp_cmd(
                                "post",
                                "meta",
                                "update",
                                str(post_id),
                                "properties_grade",
                                new_grade,
                            ),
                            check=False,
                        )
                    entry["updated"] = True
                elif changed:
                    entry["updated"] = False
                results.append(entry)

        return results

    def backfill_photos_meta(
        self,
        *,
        sources: tuple[str, ...] = ("fujicars", "bobaedream"),
        dry_run: bool | None = None,
    ) -> list[dict[str, Any]]:
        """Fix double-serialized ACF photos meta on imported posts."""
        effective_dry_run = self.dry_run if dry_run is None else dry_run
        results: list[dict[str, Any]] = []

        for source in sources:
            for post_id in self._list_posts_by_source(source):
                php = (
                    f'$raw = get_post_meta({post_id}, "photos", true); '
                    f'$fixed = 0; '
                    f'if (is_string($raw) && str_starts_with($raw, "a:")) {{ '
                    f'  $ids = @unserialize($raw); '
                    f'  if (is_array($ids) && $ids) {{ '
                    f'    if (!{int(effective_dry_run)}) {{ update_field("photos", array_map("intval", $ids), {post_id}); }} '
                    f'    $fixed = count($ids); '
                    f'  }} '
                    f'}} elseif (is_array($raw)) {{ $fixed = count($raw); }} '
                    f'echo (string) $fixed;'
                )
                result = self._run_wp(self._wp_cmd("eval", php))
                count = int(result.stdout.strip() or "0") if result.returncode == 0 else 0
                entry = {
                    "post_id": post_id,
                    "source": source,
                    "photo_count": count,
                    "fixed": count > 0 and not effective_dry_run,
                    "dry_run": effective_dry_run,
                }
                results.append(entry)

        return results

    def _list_posts_by_source(self, source: str) -> list[int]:
        cmd = self._wp_cmd(
            "post",
            "list",
            f"--post_type={self.wp.post_type}",
            "--post_status=publish",
            "--meta_key=_source",
            f"--meta_value={source}",
            "--posts_per_page=-1",
            "--format=ids",
        )
        result = self._run_wp(cmd)
        if result.returncode != 0 or not result.stdout.strip():
            return []
        return [int(x) for x in result.stdout.split()]

    def _get_post_field(self, post_id: int, field: str) -> str:
        cmd = self._wp_cmd("post", "get", str(post_id), f"--field={field}")
        result = self._run_wp(cmd)
        return result.stdout.strip() if result.returncode == 0 else ""

    def _get_post_meta(self, post_id: int, key: str) -> str | None:
        cmd = self._wp_cmd("post", "meta", "get", str(post_id), key)
        result = self._run_wp(cmd)
        if result.returncode != 0:
            return None
        value = result.stdout.strip()
        return value or None

    @staticmethod
    def format_listing_json(listing: NormalizedListing, result: dict[str, Any] | None = None) -> str:
        output = {
            "listing": listing.to_dict(),
            "wp_result": result,
        }
        return json.dumps(output, ensure_ascii=False, indent=2)
