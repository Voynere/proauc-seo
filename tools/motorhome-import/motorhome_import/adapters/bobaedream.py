"""Bobaedream camp category adapter.

List URL: https://www.bobaedream.co.kr/cyber/CyberCar.php?features=camp
Encoding: UTF-8 (Content-Type header, verified 2026-07-09).
Pagination: ?page=N works server-side (~70 listings/page); UI also uses javascript:pageClick(N).
Listings are server-rendered in div.list-inner rows (no Playwright required for list).
Detail pages are server-rendered HTML (gallery, specs, seller description).
"""

from __future__ import annotations

import logging
import re
from typing import Any, Iterable
from urllib.parse import urljoin

from bs4 import BeautifulSoup

from ..config import AppConfig
from ..http import HttpClient
from ..normalizer import (
    apply_ko_spec_table,
    build_title,
    finalize_listing,
    ko_spec_row_to_parameters,
    map_engine_type,
    parse_capacity_ko,
    parse_mileage,
    parse_price_krw,
    parse_year,
)
from ..schema import ListingParameter, ListingProperties, NormalizedListing
from .base import BaseAdapter

logger = logging.getLogger(__name__)

BASE_URL = "https://www.bobaedream.co.kr"
DEFAULT_LIST_URL = f"{BASE_URL}/cyber/CyberCar.php?features=camp"

MANWON_RE = re.compile(r"([\d,]+)\s*만원")
KRW_RE = re.compile(r"([\d,]+)\s*원")
KM_RE = re.compile(r"([\d,.]+)\s*(?:만\s*)?km", re.I)
YEAR_RE = re.compile(r"(\d{2})/(\d{2})")
NO_RE = re.compile(r"no=(\d+)")


def probe_page(http: HttpClient, url: str, *, fetch_page2: bool = True) -> dict[str, Any]:
    """Document HTML structure for Bobaedream camp listings."""
    html = http.get_text(url)
    soup = BeautifulSoup(html, "html.parser")
    rows = soup.select("div.list-inner")
    listings = [_parse_list_inner(row) for row in rows]
    listings = [item for item in listings if item]

    page_links = [
        (a.get_text(strip=True), a.get("href", ""))
        for a in soup.select("a[href*='pageClick'], a[href*='page=']")
    ]

    summary: dict[str, Any] = {
        "url": url,
        "encoding": "UTF-8",
        "content_type_note": "Response header charset=utf-8 (not EUC-KR)",
        "title": soup.title.string.strip() if soup.title and soup.title.string else None,
        "listings_on_page": len(listings),
        "row_selector": "div.list-inner",
        "cells": ["thumb", "title", "year", "fuel", "km", "price", "seller"],
        "detail_url_pattern": "/cyber/CyberCar_view.php?no={id}&gubun=K|I",
        "pagination": {
            "ui": "javascript:pageClick(N) anchors",
            "server_side": "?page=N query param works",
            "sample_page_links": page_links[:12],
        },
        "playwright_required": False,
        "playwright_note": "List and detail HTML are server-rendered; Playwright not needed.",
        "sample_listings": listings[:3],
    }

    if listings:
        first = listings[0]
        detail_url = urljoin(BASE_URL, first["detail_path"])
        try:
            summary["detail_probe"] = probe_detail(http, detail_url)
        except Exception as exc:
            summary["detail_probe"] = {"error": str(exc), "url": detail_url}

    if fetch_page2:
        sep = "&" if "?" in url else "?"
        page2_url = f"{url}{sep}page=2"
        try:
            html2 = http.get_text(page2_url)
            soup2 = BeautifulSoup(html2, "html.parser")
            ids_page1 = {item["source_id"] for item in listings if item}
            ids_page2 = {
                item["source_id"]
                for row in soup2.select("div.list-inner")
                for item in [_parse_list_inner(row)]
                if item
            }
            summary["pagination"]["page2_url"] = page2_url
            summary["pagination"]["page2_listings"] = len(ids_page2)
            summary["pagination"]["overlap_with_page1"] = len(ids_page1 & ids_page2)
        except Exception as exc:
            summary["pagination"]["page2_error"] = str(exc)

    return summary


def probe_detail(http: HttpClient, url: str) -> dict[str, Any]:
    """Document detail page structure for one listing."""
    html = http.get_text(url)
    soup = BeautifulSoup(html, "html.parser")
    parsed = parse_detail_page(soup)
    return {
        "url": url,
        "title": parsed.get("title"),
        "price_krw": parsed.get("price_krw"),
        "spec_keys": list((parsed.get("specs") or {}).keys()),
        "check_keys": list((parsed.get("check_specs") or {}).keys()),
        "photos": len(parsed.get("photos") or []),
        "description_chars": len(parsed.get("description") or ""),
        "equipment_groups": list((parsed.get("equipment") or {}).keys()),
        "playwright_required": False,
        "selectors": {
            "title": "div.info-price div.title-area",
            "price": "div.info-price div.price-area, span.price",
            "specs": "div.detail-section table, div.info-basic table",
            "description": "div.detail-explanation div.explanation-box",
            "gallery": "div.gallery-view img, div.js-gallery-view img",
            "equipment": "div.detail-option-container table, table (외관/내장 rows)",
        },
        "sample_photos": (parsed.get("photos") or [])[:3],
        "description_preview": (parsed.get("description") or "")[:200],
    }


def parse_detail_page(soup: BeautifulSoup) -> dict[str, Any]:
    """Parse Bobaedream detail HTML into structured fields."""
    title = _parse_detail_title(soup)
    price_krw = _parse_detail_price(soup)
    specs = _parse_spec_tables(soup)
    check_specs = _parse_check_table(soup)
    description = _parse_description(soup)
    photos = _extract_gallery_photos(soup)
    equipment = _parse_equipment_table(soup)

    return {
        "title": title,
        "price_krw": price_krw,
        "specs": specs,
        "check_specs": check_specs,
        "description": description,
        "photos": photos,
        "equipment": equipment,
    }


def _parse_detail_title(soup: BeautifulSoup) -> str | None:
    title_el = soup.select_one("div.info-price div.title-area")
    if title_el:
        text = title_el.get_text(" ", strip=True)
        text = re.sub(r"\s*-\s*", " ", text)
        text = re.sub(r"\s+", " ", text).strip()
        if text:
            return text

    og = soup.select_one('meta[property="og:title"]')
    if og and og.get("content"):
        text = og["content"]
        text = re.sub(r"\s*\|.*$", "", text)
        text = re.sub(r"\s*중고차.*$", "", text)
        return text.strip() or None

    return None


def _parse_detail_price(soup: BeautifulSoup) -> int | None:
    for sel in ("div.info-price div.price-area", "span.price"):
        el = soup.select_one(sel)
        if el:
            price = parse_price_krw(el.get_text(" ", strip=True))
            if price:
                return price
    return None


def _parse_spec_tables(soup: BeautifulSoup) -> dict[str, str]:
    specs: dict[str, str] = {}
    for table in soup.select("div.detail-section table, div.info-basic table, div.wrap-detail-spec table"):
        for row in table.select("tr"):
            cells = row.find_all(["th", "td"])
            i = 0
            while i + 1 < len(cells):
                key = cells[i].get_text(strip=True)
                value = cells[i + 1].get_text(" ", strip=True)
                if key and value and "도움말" not in key:
                    specs[key] = value
                i += 2
    return specs


def _parse_check_table(soup: BeautifulSoup) -> dict[str, str]:
    specs: dict[str, str] = {}
    for row in soup.select("div.info-check table tr"):
        cells = [c.get_text(" ", strip=True) for c in row.find_all(["th", "td"])]
        i = 0
        while i + 1 < len(cells):
            key, value = cells[i], cells[i + 1]
            if key and value:
                specs[key] = value
            i += 2
    return specs


def _parse_description(soup: BeautifulSoup) -> str | None:
    box = soup.select_one("div.detail-explanation div.explanation-box")
    if not box:
        return None
    text = box.get_text("\n", strip=True)
    text = re.sub(r"\n{3,}", "\n\n", text).strip()
    return text or None


def _extract_gallery_photos(soup: BeautifulSoup) -> list[str]:
    urls: list[str] = []
    seen: set[str] = set()
    for img in soup.select("div.gallery-view img, div.js-gallery-view img"):
        src = img.get("src") or img.get("data-src") or ""
        if not src or "thum" in src.lower() or "/ico_" in src.lower():
            continue
        if src.startswith("//"):
            src = "https:" + src
        if src in seen:
            continue
        seen.add(src)
        urls.append(src)
    return urls


def _parse_equipment_table(soup: BeautifulSoup) -> dict[str, str]:
    equipment: dict[str, str] = {}
    table = soup.select_one("div.detail-option-container table")
    if not table:
        return equipment
    for row in table.select("tr"):
        cells = row.find_all(["th", "td"])
        if len(cells) >= 2:
            key = cells[0].get_text(strip=True)
            value = cells[1].get_text(" ", strip=True)
            if key in {"외관", "내장", "안전", "편의", "멀티미디어"} and value:
                equipment[key] = value
    return equipment


def _parse_list_inner(row: Any) -> dict[str, Any] | None:
    link = row.select_one("a[href*='CyberCar_view.php']")
    if not link or not link.get("href"):
        return None

    href = link["href"]
    match = NO_RE.search(href)
    if not match:
        return None

    gubun = "K"
    if "gubun=I" in href:
        gubun = "I"

    def cell_text(class_name: str) -> str:
        el = row.select_one(f"div.mode-cell.{class_name}")
        return el.get_text(" ", strip=True) if el else ""

    title = cell_text("title")
    year_text = cell_text("year")
    fuel = cell_text("fuel")
    km_text = cell_text("km")
    price_text = cell_text("price")

    img = row.select_one("img")
    thumb = ""
    if img:
        thumb = img.get("src") or img.get("data-src") or ""
        if thumb.startswith("//"):
            thumb = "https:" + thumb

    year = _parse_year(year_text)
    mileage = _parse_km(km_text)
    price_krw = _parse_price_krw(price_text)

    return {
        "source_id": match.group(1),
        "gubun": gubun,
        "title": title,
        "year": year,
        "fuel": fuel,
        "mileage": mileage,
        "price_krw": price_krw,
        "price_text": price_text,
        "detail_path": href,
        "thumbnail": thumb,
    }


def _parse_year(text: str) -> int | None:
    match = YEAR_RE.search(text)
    if match:
        yy = int(match.group(1))
        return 2000 + yy if yy < 70 else 1900 + yy
    return parse_year(text)


def _parse_km(text: str) -> int | None:
    match = KM_RE.search(text.replace(",", ""))
    if not match:
        return None
    value = match.group(1).replace(",", "")
    try:
        num = float(value)
    except ValueError:
        return None
    if "만" in text or num < 500:
        return int(num * 10_000)
    return int(num)


def _parse_price_krw(text: str) -> int | None:
    price = parse_price_krw(text)
    if price:
        return price
    match = MANWON_RE.search(text)
    if match:
        return int(match.group(1).replace(",", "")) * 10_000
    match = KRW_RE.search(text)
    if match:
        return int(match.group(1).replace(",", ""))
    return None


def _dedupe_parameters(params: list[ListingParameter]) -> list[ListingParameter]:
    seen: set[tuple[str, str]] = set()
    out: list[ListingParameter] = []
    for param in params:
        key = (param.name, param.value)
        if key in seen:
            continue
        seen.add(key)
        out.append(param)
    return out


class BobaedreamAdapter(BaseAdapter):
    source_name = "bobaedream"

    def __init__(
        self,
        *,
        config: AppConfig,
        http: HttpClient,
        options: dict[str, Any] | None = None,
    ) -> None:
        self.config = config
        self.http = http
        opts = options or {}
        self.list_url = opts.get("list_url", DEFAULT_LIST_URL)
        self.max_pages = int(opts.get("max_pages", 1))

    def fetch_listings(self) -> Iterable[NormalizedListing]:
        seen: set[str] = set()
        limit = self.config.import_.limit
        count = 0

        for page in range(1, self.max_pages + 1):
            url = self._list_page_url(page)
            logger.info("Fetching Bobaedream list page %s: %s", page, url)
            html = self.http.get_text(url)
            soup = BeautifulSoup(html, "html.parser")
            rows = soup.select("div.list-inner")
            logger.info("Found %s list-inner rows on page %s", len(rows), page)

            for row in rows:
                card = _parse_list_inner(row)
                if not card:
                    continue
                source_id = card["source_id"]
                if source_id in seen:
                    continue
                seen.add(source_id)

                listing = self._card_to_listing(card)
                if self.config.import_.fetch_details and card.get("detail_path"):
                    try:
                        listing = self._enrich_from_detail(listing, card["detail_path"])
                    except Exception:
                        logger.exception("Detail fetch failed for %s", source_id)

                listing = finalize_listing(listing)
                yield listing
                count += 1
                if limit and count >= limit:
                    return

    def _list_page_url(self, page: int) -> str:
        if page <= 1:
            return self.list_url
        sep = "&" if "?" in self.list_url else "?"
        return f"{self.list_url}{sep}page={page}"

    def _card_to_listing(self, card: dict[str, Any]) -> NormalizedListing:
        title = build_title(card.get("title"), None, card.get("title", ""))
        detail_url = urljoin(BASE_URL, card["detail_path"])
        photos = []
        if card.get("thumbnail"):
            photos.append(card["thumbnail"])

        properties = ListingProperties(
            year=card.get("year"),
            mileage=card.get("mileage"),
            engine_type=map_engine_type(card.get("fuel") or ""),
        )

        return NormalizedListing(
            source=self.source_name,
            source_id=str(card["source_id"]),
            title=title,
            source_url=detail_url,
            properties=properties,
            year=card.get("year"),
            photos=photos,
            raw={
                "gubun": card.get("gubun"),
                "price_krw": card.get("price_krw"),
                "price_text": card.get("price_text"),
                "fuel": card.get("fuel"),
            },
        )

    def _enrich_from_detail(self, listing: NormalizedListing, detail_path: str) -> NormalizedListing:
        url = urljoin(BASE_URL, detail_path)
        html = self.http.get_text(url)
        soup = BeautifulSoup(html, "html.parser")
        parsed = parse_detail_page(soup)

        if parsed.get("title"):
            listing.title = parsed["title"]

        if parsed.get("price_krw"):
            listing.raw["price_krw"] = parsed["price_krw"]

        specs = parsed.get("specs") or {}
        listing.properties = apply_ko_spec_table(listing.properties, specs)
        listing.year = listing.properties.year or listing.year

        if parsed.get("description"):
            listing.description = parsed["description"]

        photos = parsed.get("photos") or []
        if photos:
            listing.photos = photos
        elif listing.photos:
            listing.photos = listing.photos

        listing.parameters.extend(ko_spec_row_to_parameters(specs))

        for key, value in (parsed.get("check_specs") or {}).items():
            listing.parameters.append(ListingParameter(name=key, value=value))

        for group, value in (parsed.get("equipment") or {}).items():
            listing.parameters.append(ListingParameter(name=group, value=value))

        listing.parameters = _dedupe_parameters(listing.parameters)

        if specs.get("배기량") and listing.properties.capacity is None:
            listing.properties.capacity = parse_capacity_ko(specs["배기량"])
        if specs.get("주행거리") and listing.properties.mileage is None:
            listing.properties.mileage = parse_mileage(specs["주행거리"])

        return listing
