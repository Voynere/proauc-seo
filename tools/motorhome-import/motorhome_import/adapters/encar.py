"""Encar camping car adapter.

Encar list UI is JS-heavy (EUC-KR shell), but the public Ryvuss API works without Playwright:
  GET https://api.encar.com/search/car/list/general?count=true&q=(And.Hidden.N.)&sr=|ModifiedDate|0|N

Camping filter: no dedicated Category/Keyword node confirmed (2026-07-09).
Workaround: client-side filter on Badge/BadgeDetail/Model containing 캠핑 (+ optional model_groups).
"""

from __future__ import annotations

import logging
import re
from typing import Any, Iterable
from urllib.parse import quote, urlencode

from ..config import AppConfig
from ..http import HttpClient
from ..normalizer import build_title, finalize_listing, map_engine_type
from ..schema import ListingParameter, ListingProperties, NormalizedListing
from .base import BaseAdapter

logger = logging.getLogger(__name__)

ENCAR_API_BASE = "https://api.encar.com/search/car/list/general"
ENCAR_IMG_BASE = "https://ci.encar.com"
ENCAR_DETAIL_URL = "https://www.encar.com/dc/dc_cardetailview.do?carid={id}"

DEFAULT_QUERY = "(And.Hidden.N._.CarType.N.)"
CAMPING_RE = re.compile(r"캠핑|camping", re.I)

# Common Korean camper van model groups (Badge often omits "캠핑").
CAMPER_MODEL_GROUPS = (
    "스타리아",
    "그랜드스타렉스",
    "스타렉스",
    "카운티",
    "e-카운티",
    "포터",
    "봉고",
    "카니발",
    "베스타",
    "쏠라티",
)


def probe_encar(http: HttpClient, *, sample_size: int = 5) -> dict[str, Any]:
    """Document Encar API access and camping-filter blockers."""
    summary: dict[str, Any] = {
        "api_endpoint": ENCAR_API_BASE,
        "api_works_without_playwright": True,
        "list_ui_url": "https://www.encar.com/dc/dc_carsearchlist.do?carType=kor&keyword=캠핑카",
        "list_ui_note": "HTML shell is EUC-KR + JS app; inventory loaded via api.encar.com XHR",
        "ryvuss_base": "https://api.encar.com",
        "image_cdn": ENCAR_IMG_BASE,
        "detail_url_pattern": ENCAR_DETAIL_URL,
        "default_query": DEFAULT_QUERY,
        "playwright_required": False,
        "playwright_note": "API list works via requests; detail page may need Playwright for full specs (not verified).",
        "camping_filter": {
            "status": "no_server_filter_confirmed",
            "attempted_nodes": [
                "Keyword.캠핑카",
                "Category.Camping",
                "BadgeGroup.캠핑카",
                "ModelGroup.캠핑카",
            ],
            "workaround": "client-side filter: Badge/BadgeDetail/Model contains 캠핑, or model_groups config",
        },
        "sample_queries": {},
    }

    queries = {
        "all": "(And.Hidden.N.)",
        "domestic": "(And.Hidden.N._.CarType.N.)",
        "import": "(And.Hidden.N._.CarType.Y.)",
    }
    for name, q in queries.items():
        try:
            data = _api_search(http, q, offset=0, limit=2)
            summary["sample_queries"][name] = {
                "query": q,
                "count": data.get("Count"),
                "sample": _summarize_result((data.get("SearchResults") or [{}])[0]),
            }
        except Exception as exc:
            summary["sample_queries"][name] = {"query": q, "error": str(exc)}

    camping_hits = 0
    scanned = 0
    samples: list[dict[str, Any]] = []
    for offset in range(0, 200, 50):
        data = _api_search(http, DEFAULT_QUERY, offset=offset, limit=50)
        for item in data.get("SearchResults", []):
            scanned += 1
            if _is_camping_listing(item):
                camping_hits += 1
                if len(samples) < sample_size:
                    samples.append(_summarize_result(item))
    summary["camping_scan"] = {
        "query": DEFAULT_QUERY,
        "scanned": scanned,
        "camping_hits": camping_hits,
        "samples": samples,
    }

    return summary


def _api_search(http: HttpClient, query: str, *, offset: int, limit: int) -> dict[str, Any]:
    params = {
        "count": "true",
        "q": query,
        "sr": f"|ModifiedDate|{offset}|{limit}",
    }
    url = f"{ENCAR_API_BASE}?{urlencode(params, quote_via=quote)}"
    response = http.get(url, headers={"Accept": "application/json"})
    return response.json()


def _summarize_result(item: dict[str, Any]) -> dict[str, Any]:
    return {
        "id": item.get("Id"),
        "manufacturer": item.get("Manufacturer"),
        "model": item.get("Model"),
        "badge": item.get("Badge"),
        "badge_detail": item.get("BadgeDetail"),
        "price_manwon": item.get("Price"),
        "year": item.get("Year"),
        "mileage": item.get("Mileage"),
        "fuel": item.get("FuelType"),
        "photo": item.get("Photo"),
    }


def _listing_text(item: dict[str, Any]) -> str:
    parts = [
        item.get("Manufacturer"),
        item.get("Model"),
        item.get("Badge"),
        item.get("BadgeDetail"),
        item.get("FuelType"),
    ]
    return " ".join(str(p) for p in parts if p)


def _is_camping_listing(item: dict[str, Any], *, model_groups: tuple[str, ...] = CAMPER_MODEL_GROUPS) -> bool:
    text = _listing_text(item)
    if CAMPING_RE.search(text):
        return True
    model = str(item.get("Model") or "")
    return any(group in model for group in model_groups)


def _parse_encar_year(value: Any) -> int | None:
    if value is None:
        return None
    if isinstance(value, (int, float)):
        s = str(int(value))
        if len(s) >= 4:
            return int(s[:4])
    match = re.search(r"(20\d{2}|19\d{2})", str(value))
    return int(match.group(1)) if match else None


def _encar_price_krw(item: dict[str, Any]) -> int | None:
    price = item.get("Price")
    if price is None:
        return None
    try:
        return int(float(price) * 10_000)
    except (TypeError, ValueError):
        return None


def _encar_photos(item: dict[str, Any]) -> list[str]:
    urls: list[str] = []
    seen: set[str] = set()
    for photo in item.get("Photos") or []:
        loc = photo.get("location") if isinstance(photo, dict) else None
        if not loc:
            continue
        url = loc if loc.startswith("http") else f"{ENCAR_IMG_BASE}{loc}"
        if url not in seen:
            seen.add(url)
            urls.append(url)
    if not urls and item.get("Photo"):
        base = str(item["Photo"])
        urls.append(f"{ENCAR_IMG_BASE}{base}001.jpg")
    return urls


class EncarAdapter(BaseAdapter):
    source_name = "encar"

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
        self.api_query = opts.get("api_query", DEFAULT_QUERY)
        self.max_pages = int(opts.get("max_pages", 3))
        self.page_size = int(opts.get("page_size", 50))
        self.camping_only = bool(opts.get("camping_only", True))
        model_groups = opts.get("model_groups")
        if model_groups:
            self.model_groups = tuple(model_groups)
        else:
            self.model_groups = CAMPER_MODEL_GROUPS

    def fetch_listings(self) -> Iterable[NormalizedListing]:
        limit = self.config.import_.limit
        count = 0
        seen: set[str] = set()

        for page in range(self.max_pages):
            offset = page * self.page_size
            logger.info(
                "Fetching Encar API offset=%s query=%s",
                offset,
                self.api_query[:60],
            )
            data = _api_search(self.http, self.api_query, offset=offset, limit=self.page_size)
            results = data.get("SearchResults") or []
            logger.info("Encar page %s: %s results (total Count=%s)", page + 1, len(results), data.get("Count"))

            if not results:
                break

            for item in results:
                source_id = str(item.get("Id") or "")
                if not source_id or source_id in seen:
                    continue

                if self.camping_only and not _is_camping_listing(item, model_groups=self.model_groups):
                    continue

                seen.add(source_id)
                listing = self._item_to_listing(item)
                listing = finalize_listing(listing)
                yield listing
                count += 1
                if limit and count >= limit:
                    return

    def _item_to_listing(self, item: dict[str, Any]) -> NormalizedListing:
        manufacturer = str(item.get("Manufacturer") or "")
        model = str(item.get("Model") or "")
        badge = str(item.get("Badge") or "")
        badge_detail = str(item.get("BadgeDetail") or "")

        title = build_title(
            f"{manufacturer} {model}".strip(),
            badge_detail or badge,
            f"{manufacturer} {model}".strip() or "Motorhome",
        )

        year = _parse_encar_year(item.get("Year") or item.get("FormYear"))
        mileage = item.get("Mileage")
        try:
            mileage_int = int(float(mileage)) if mileage is not None else None
        except (TypeError, ValueError):
            mileage_int = None

        properties = ListingProperties(
            year=year,
            mileage=mileage_int,
            engine_type=map_engine_type(str(item.get("FuelType") or "")),
            grade=badge_detail or None,
        )

        price_krw = _encar_price_krw(item)
        photos = _encar_photos(item)
        source_id = str(item.get("Id"))

        parameters: list[ListingParameter] = []
        if badge:
            parameters.append(ListingParameter(name="Badge", value=badge))
        if badge_detail:
            parameters.append(ListingParameter(name="BadgeDetail", value=badge_detail))
        if item.get("OfficeCityState"):
            parameters.append(ListingParameter(name="Region", value=str(item["OfficeCityState"])))

        return NormalizedListing(
            source=self.source_name,
            source_id=source_id,
            title=title,
            source_url=ENCAR_DETAIL_URL.format(id=source_id),
            properties=properties,
            year=year,
            photos=photos,
            parameters=parameters,
            raw={
                "price_krw": price_krw,
                "manufacturer": manufacturer,
                "model": model,
                "badge": badge,
                "badge_detail": badge_detail,
                "fuel_type": item.get("FuelType"),
                "form_year": item.get("FormYear"),
            },
        )
