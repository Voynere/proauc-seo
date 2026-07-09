"""Fujicars Japan camping car adapter.

Inventory lives at /search/list_en (not the marketing /english/ pages).
Filter body=9 → キャンピングカー (camping cars), then client-side subtype filter.

Fujicars body=9 mixes real motorhomes with van-conversion minivans (バンコン).
The list card field ``li.carName`` carries the camping-car subtype.
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
    apply_spec_table,
    build_title,
    finalize_listing,
    parse_mileage,
    parse_price_jpy,
    parse_year,
    spec_row_to_parameters,
)
from ..schema import ListingParameter, ListingProperties, NormalizedListing
from .base import BaseAdapter

logger = logging.getLogger(__name__)

# li.carName values on /search/list_en?body=9 (2026-07-09)
MOTORHOME_SUBTYPES = frozenset(
    {
        "キャブコン",  # cab-over motorhome
        "バスコン",  # bus motorhome
        "軽キャンパー",  # kei camper
        "トラックキャンパー",  # truck camper
    }
)
EXCLUDED_SUBTYPES = frozenset(
    {
        "バンコン",  # van / minivan conversion — not a motorhome
    }
)
EXCLUDED_TITLE_MARKERS = ("バンコン",)


def is_motorhome(
    *,
    body_subtype: str = "",
    title: str = "",
    grade: str = "",
) -> bool:
    """Return True when listing is a real motorhome (not van-con minivan).

    Rules (first match wins):
    1. ``body_subtype`` from list card ``li.carName`` — primary signal.
    2. Title / grade text — catches detail-page titles like ``キャンピングカー バンコン…``.
    3. Unknown subtype without motorhome markers → reject (safe default).
    """
    subtype = body_subtype.strip()
    if subtype in EXCLUDED_SUBTYPES:
        return False
    if subtype in MOTORHOME_SUBTYPES:
        return True

    text = f"{title} {grade}"
    if any(marker in text for marker in EXCLUDED_TITLE_MARKERS):
        return False
    if any(marker in text for marker in MOTORHOME_SUBTYPES):
        return True

    return False


def has_valid_price_jpy(listing: NormalizedListing) -> bool:
    """Reject listings with missing/zero JPY source price."""
    price_jpy = listing.raw.get("price_jpy")
    return isinstance(price_jpy, int) and price_jpy > 0


def has_valid_price_rub(listing: NormalizedListing) -> bool:
    """Reject listings where landed-cost pricing failed (0 or null RUB)."""
    price_rub = listing.properties.price_rub
    return isinstance(price_rub, int) and price_rub > 0

BASE_URL = "https://www.fujicars.jp/search/"
DETAIL_PATH_RE = re.compile(r"\./detail/(\d+)")
CAR_NO_RE = re.compile(r"car_no=(\d+)")
IMAGE_RE = re.compile(
    r"image_display\?car_no=(\d+)&(?:amp;)?image_name=(\d+)",
    re.I,
)


class FujicarsAdapter(BaseAdapter):
    source_name = "fujicars"

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
        self.list_url = opts.get("list_url", "https://www.fujicars.jp/search/list_en")
        self.body_type = int(opts.get("body_type", 9))
        self.max_pages = int(opts.get("max_pages", 1))

    def fetch_listings(self) -> Iterable[NormalizedListing]:
        seen: set[str] = set()
        limit = self.config.import_.limit
        count = 0

        for page in range(1, self.max_pages + 1):
            url = self._list_page_url(page)
            logger.info("Fetching Fujicars list page %s: %s", page, url)
            html = self.http.get_text(url)
            cards = self._parse_list_page(html)
            logger.info("Found %s cards on page %s", len(cards), page)

            for card in cards:
                source_id = card["source_id"]
                if source_id in seen:
                    continue
                seen.add(source_id)

                if card.get("sold_out") and self.config.import_.skip_sold_out:
                    logger.debug("Skipping sold out: %s", source_id)
                    continue

                listing = self._card_to_listing(card)
                if self.config.import_.fetch_details and card.get("detail_path"):
                    try:
                        listing = self._enrich_from_detail(listing, card["detail_path"])
                    except Exception:
                        logger.exception("Detail fetch failed for %s", source_id)

                listing = finalize_listing(listing)
                listing.raw["body_subtype"] = card.get("body_subtype", "")

                if not is_motorhome(
                    body_subtype=card.get("body_subtype", ""),
                    title=listing.title,
                    grade=card.get("grade", ""),
                ):
                    logger.info(
                        "Skipping non-motorhome %s (%s): %s",
                        source_id,
                        card.get("body_subtype", "?"),
                        listing.title[:80],
                    )
                    continue

                if not has_valid_price_jpy(listing):
                    logger.info(
                        "Skipping %s — no valid price (jpy=%s, rub=%s): %s",
                        source_id,
                        listing.raw.get("price_jpy"),
                        listing.properties.price_rub,
                        listing.title[:80],
                    )
                    continue

                yield listing
                count += 1
                if limit and count >= limit:
                    return

    def _list_page_url(self, page: int) -> str:
        params = f"view=1&body={self.body_type}&sort=publish2"
        if page > 1:
            params += f"&page={page}"
        separator = "&" if "?" in self.list_url else "?"
        return f"{self.list_url}{separator}{params}"

    def _parse_list_page(self, html: str) -> list[dict[str, Any]]:
        soup = BeautifulSoup(html, "html.parser")
        cards: list[dict[str, Any]] = []
        for box in soup.select("div.carDetailBox"):
            card = self._parse_card_box(box)
            if card:
                cards.append(card)
        return cards

    def _parse_card_box(self, box: Any) -> dict[str, Any] | None:
        thumb = box.select_one("div.carThumb a")
        if not thumb or not thumb.get("href"):
            return None

        href = thumb["href"]
        match = DETAIL_PATH_RE.search(href)
        if not match:
            return None
        detail_id = match.group(1)

        img = box.select_one("img")
        img_src = img.get("src", "") if img else ""
        car_no_match = CAR_NO_RE.search(img_src)
        car_no = car_no_match.group(1) if car_no_match else detail_id

        subtype_el = box.select_one("li.carName")
        grade_el = box.select_one("li.carGrade")
        year_el = box.select_one("li.carModelYearMilage")
        price_el = box.select_one("li.carPrice")

        body_subtype = subtype_el.get_text(strip=True) if subtype_el else ""
        grade = grade_el.get_text(strip=True) if grade_el else ""
        year_text = year_el.get_text(" ", strip=True) if year_el else ""
        price_text = price_el.get_text(" ", strip=True) if price_el else ""

        sold_out = "SOLD OUT" in price_text.upper()
        price_jpy = parse_price_jpy(price_text)

        return {
            "source_id": detail_id,
            "car_no": car_no,
            "detail_path": href,
            "body_subtype": body_subtype,
            "car_name": body_subtype,
            "grade": grade,
            "year": parse_year(year_text),
            "mileage": parse_mileage(year_text),
            "price_jpy": price_jpy,
            "sold_out": sold_out,
            "thumbnail": img_src,
            "title_alt": img.get("alt", "") if img else "",
        }

    def _card_to_listing(self, card: dict[str, Any]) -> NormalizedListing:
        title = build_title(card.get("car_name"), card.get("grade"), card.get("title_alt", ""))
        detail_url = urljoin(BASE_URL, card["detail_path"])
        photos = []
        if card.get("thumbnail"):
            photos.append(card["thumbnail"])

        properties = ListingProperties(
            year=card.get("year"),
            mileage=card.get("mileage"),
        )

        return NormalizedListing(
            source=self.source_name,
            source_id=str(card["source_id"]),
            title=title,
            source_url=detail_url,
            properties=properties,
            year=card.get("year"),
            photos=photos,
            sold_out=bool(card.get("sold_out")),
            raw={
                "car_no": card.get("car_no"),
                "price_jpy": card.get("price_jpy"),
                "car_name": card.get("car_name"),
                "grade": card.get("grade"),
            },
        )

    def _enrich_from_detail(self, listing: NormalizedListing, detail_path: str) -> NormalizedListing:
        url = urljoin(BASE_URL, detail_path)
        html = self.http.get_text(url)
        soup = BeautifulSoup(html, "html.parser")

        h2 = soup.select_one("h2 span")
        if h2:
            title_text = h2.get_text(strip=True)
            title_text = re.sub(r"\s*車両情報\s*$", "", title_text)
            if title_text:
                listing.title = title_text

        price_el = soup.select_one("p.price span")
        if price_el:
            price_jpy = parse_price_jpy(price_el.get_text(strip=True))
            if price_jpy:
                listing.raw["price_jpy"] = price_jpy
                if "SOLD" in price_el.get_text().upper():
                    listing.sold_out = True

        specs = self._parse_spec_table(soup)
        listing.properties = apply_spec_table(listing.properties, specs)
        listing.year = listing.properties.year or listing.year

        comment = soup.select_one("div.commentArea p#text, div.commentArea p")
        if comment:
            listing.description = comment.get_text("\n", strip=True)

        photos = self._extract_photos(soup, listing.raw.get("car_no"))
        if photos:
            listing.photos = photos

        extra_params = spec_row_to_parameters(specs)
        listing.parameters.extend(extra_params)

        equipment = self._parse_equipment(soup)
        for item in equipment:
            listing.parameters.append(ListingParameter(name="装備", value=item))

        return listing

    def _parse_spec_table(self, soup: BeautifulSoup) -> dict[str, str]:
        specs: dict[str, str] = {}
        for row in soup.select("table.specTable tr"):
            cells = row.find_all(["th", "td"])
            i = 0
            while i + 1 < len(cells):
                key = cells[i].get_text(strip=True)
                value = cells[i + 1].get_text(" ", strip=True)
                if key:
                    specs[key] = value
                i += 2
        return specs

    def _extract_photos(self, soup: BeautifulSoup, car_no: str | None) -> list[str]:
        urls: list[str] = []
        seen: set[str] = set()
        for img in soup.select("ul.slide_selector img, div.mainImgBox img"):
            src = img.get("src", "")
            if "image_display" not in src:
                continue
            src = src.replace("&amp;", "&")
            if src in seen:
                continue
            seen.add(src)
            urls.append(src)

        if not urls and car_no:
            urls.append(
                f"https://www.fujicars.jp/search/image_display?car_no={car_no}&image_name=0"
            )
        return urls

    def _parse_equipment(self, soup: BeautifulSoup) -> list[str]:
        items: list[str] = []
        skip = {"○", "－", "-", "×", "●", ""}
        for td in soup.select("table.equipmentTable td"):
            text = td.get_text(strip=True)
            if not text or text in skip or len(text) < 2:
                continue
            if text not in items:
                items.append(text)
        return items[:20]
