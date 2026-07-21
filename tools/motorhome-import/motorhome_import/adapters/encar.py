"""Encar camping car adapter.

List UI is JS-heavy (EUC-KR shell), but public APIs work without Playwright:

  List:   GET https://api.encar.com/search/car/list/general
  Detail: GET https://api.encar.com/v1/readside/vehicle/{id}

Camping filter (server-side Ryvuss Badge nodes, confirmed 2026-07-21):
  (And.Hidden.N._.(Or.Badge.캠핑카._.Badge.4WD 캠핑카._.Badge.캠핑카/이동사무차.))

Client-side ``camping_only`` remains as a safety net (Badge/Model contains 캠핑).
Optional ``use_model_groups`` broadens to van platforms (스타리아 / 스타렉스 / …).
"""

from __future__ import annotations

import logging
import re
from typing import Any, Iterable
from urllib.parse import quote, urlencode

from ..config import AppConfig
from ..http import HttpClient
from ..normalizer import (
    build_title,
    finalize_listing,
    map_drive_type,
    map_engine_type,
)
from ..schema import ListingParameter, ListingProperties, NormalizedListing
from .base import BaseAdapter

logger = logging.getLogger(__name__)

ENCAR_API_BASE = "https://api.encar.com/search/car/list/general"
ENCAR_DETAIL_API = "https://api.encar.com/v1/readside/vehicle/{id}"
ENCAR_IMG_BASE = "https://ci.encar.com"
ENCAR_DETAIL_URL = "https://fem.encar.com/cars/detail/{id}"
ENCAR_DETAIL_URL_LEGACY = "https://www.encar.com/dc/dc_cardetailview.do?carid={id}"

# Server-side camping grades (exact Badge match via Ryvuss).
DEFAULT_QUERY = (
    "(And.Hidden.N._.(Or.Badge.캠핑카._.Badge.4WD 캠핑카._.Badge.캠핑카/이동사무차.))"
)
CAMPING_RE = re.compile(r"캠핑|camping", re.I)
DRIVE_RE = re.compile(r"\b(4WD|2WD|AWD|FF|FR)\b", re.I)

# Common Korean camper van model groups (Badge often omits "캠핑").
# Matching is space-insensitive: "그랜드스타렉스" ↔ "그랜드 스타렉스" / "더 뉴 그랜드 스타렉스".
CAMPER_MODEL_GROUPS = (
    "스타리아",
    "그랜드스타렉스",
    "스타렉스",
    "카운티",
    "e카운티",
    "포터",
    "봉고",
    "카니발",
    "베스타",
    "쏠라티",
    "마스터",
)


def probe_encar(http: HttpClient, *, sample_size: int = 5) -> dict[str, Any]:
    """Document Encar API access, camping Badge filter, and detail enrichment."""
    summary: dict[str, Any] = {
        "api_endpoint": ENCAR_API_BASE,
        "detail_api": ENCAR_DETAIL_API,
        "api_works_without_playwright": True,
        "list_ui_url": "https://www.encar.com/dc/dc_carsearchlist.do?carType=kor&keyword=캠핑카",
        "list_ui_note": "HTML shell is EUC-KR + JS app; inventory loaded via api.encar.com XHR",
        "ryvuss_base": "https://api.encar.com",
        "image_cdn": ENCAR_IMG_BASE,
        "detail_url_pattern": ENCAR_DETAIL_URL,
        "detail_url_legacy": ENCAR_DETAIL_URL_LEGACY,
        "default_query": DEFAULT_QUERY,
        "playwright_required": False,
        "playwright_note": "List + detail both via JSON APIs; Playwright not required.",
        "camping_filter": {
            "status": "server_badge_nodes",
            "query": DEFAULT_QUERY,
            "notes": [
                "Badge.캠핑카 / Badge.4WD 캠핑카 / Badge.캠핑카/이동사무차 are exact-match nodes",
                "Keyword.* / Category.Camping return 404 or 0",
                "Client camping_only keeps 캠핑 keyword safety net",
            ],
        },
        "sample_queries": {},
    }

    queries = {
        "camping_badges": DEFAULT_QUERY,
        "badge_camping_car": "(And.Hidden.N._.Badge.캠핑카.)",
        "badge_4wd_camping": "(And.Hidden.N._.Badge.4WD 캠핑카.)",
        "all": "(And.Hidden.N.)",
        "domestic_legacy_label": "(And.Hidden.N._.CarType.N.)",
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
    try:
        data = _api_search(http, DEFAULT_QUERY, offset=0, limit=50)
        for item in data.get("SearchResults") or []:
            scanned += 1
            if _is_camping_listing(item):
                camping_hits += 1
                if len(samples) < sample_size:
                    samples.append(_summarize_result(item))
        summary["camping_scan"] = {
            "query": DEFAULT_QUERY,
            "scanned": scanned,
            "camping_hits": camping_hits,
            "total_count": data.get("Count"),
            "samples": samples,
        }
    except Exception as exc:
        summary["camping_scan"] = {"query": DEFAULT_QUERY, "error": str(exc)}

    if samples and samples[0].get("id"):
        detail_id = str(samples[0]["id"])
        try:
            summary["detail_probe"] = probe_detail(http, detail_id)
        except Exception as exc:
            summary["detail_probe"] = {"id": detail_id, "error": str(exc)}

    return summary


def probe_detail(http: HttpClient, vehicle_id: str) -> dict[str, Any]:
    """Document readside detail payload for one listing."""
    data = _api_detail(http, vehicle_id)
    parsed = parse_detail_payload(data)
    return {
        "id": vehicle_id,
        "url": ENCAR_DETAIL_API.format(id=vehicle_id),
        "source_url": ENCAR_DETAIL_URL.format(id=vehicle_id),
        "title": parsed.get("title"),
        "price_krw": parsed.get("price_krw"),
        "year": parsed.get("year"),
        "mileage": parsed.get("mileage"),
        "capacity_l": parsed.get("capacity_l"),
        "fuel": parsed.get("fuel"),
        "drive_type": parsed.get("drive_type"),
        "photos": len(parsed.get("photos") or []),
        "description_chars": len(parsed.get("description") or ""),
        "spec_keys": list((parsed.get("specs") or {}).keys()),
        "playwright_required": False,
        "sample_photos": (parsed.get("photos") or [])[:3],
        "description_preview": (parsed.get("description") or "")[:200],
    }


def _api_search(http: HttpClient, query: str, *, offset: int, limit: int) -> dict[str, Any]:
    params = {
        "count": "true",
        "q": query,
        "sr": f"|ModifiedDate|{offset}|{limit}",
    }
    url = f"{ENCAR_API_BASE}?{urlencode(params, quote_via=quote)}"
    response = http.get(
        url,
        headers={
            "Accept": "application/json",
            "Referer": "https://fem.encar.com/",
            "Origin": "https://fem.encar.com",
        },
    )
    return response.json()


def _api_detail(http: HttpClient, vehicle_id: str) -> dict[str, Any]:
    url = ENCAR_DETAIL_API.format(id=vehicle_id)
    response = http.get(
        url,
        headers={
            "Accept": "application/json",
            "Referer": "https://fem.encar.com/",
            "Origin": "https://fem.encar.com",
        },
    )
    return response.json()


def parse_detail_payload(data: dict[str, Any]) -> dict[str, Any]:
    """Parse Encar readside vehicle JSON into structured fields."""
    category = data.get("category") or {}
    spec = data.get("spec") or {}
    advertisement = data.get("advertisement") or {}
    contents = data.get("contents") or {}
    contact = data.get("contact") or {}
    options = data.get("options") or {}

    manufacturer = str(category.get("manufacturerName") or "")
    model = str(category.get("modelName") or "")
    grade = str(category.get("gradeName") or "")
    grade_detail = str(category.get("gradeDetailName") or "")

    title = build_title(
        f"{manufacturer} {model}".strip(),
        grade_detail or grade,
        f"{manufacturer} {model}".strip() or "Motorhome",
    )

    year = _parse_encar_year(category.get("yearMonth") or category.get("formYear"))
    mileage = _as_int(spec.get("mileage"))
    displacement = _as_int(spec.get("displacement"))
    capacity_l = round(displacement / 1000, 1) if displacement else None
    fuel = str(spec.get("fuelName") or "") or None
    drive_raw = _extract_drive(f"{grade} {grade_detail}")
    price = advertisement.get("price")
    try:
        price_krw = int(float(price) * 10_000) if price is not None else None
    except (TypeError, ValueError):
        price_krw = None

    photos = _detail_photos(data.get("photos") or [])
    description = str(contents.get("text") or "").strip() or None

    specs: dict[str, str] = {}
    if spec.get("transmissionName"):
        specs["변속기"] = str(spec["transmissionName"])
    if fuel:
        specs["연료"] = fuel
    if spec.get("colorName"):
        specs["색상"] = str(spec["colorName"])
    if spec.get("seatCount") is not None:
        specs["인승"] = str(spec["seatCount"])
    if spec.get("bodyName"):
        specs["차체"] = str(spec["bodyName"])
    if displacement:
        specs["배기량"] = f"{displacement}cc"
    if mileage is not None:
        specs["주행거리"] = f"{mileage}km"
    if category.get("formYear"):
        specs["연식"] = str(category["formYear"])
    if contact.get("address"):
        specs["지역"] = str(contact["address"])
    if data.get("vehicleNo"):
        specs["차량번호"] = str(data["vehicleNo"])
    if advertisement.get("oneLineText"):
        specs["한줄소개"] = str(advertisement["oneLineText"])

    etc_text = _join_options_etc(options.get("etc") or [])
    if etc_text:
        specs["기타옵션"] = etc_text

    return {
        "title": title,
        "manufacturer": manufacturer,
        "model": model,
        "grade": grade,
        "grade_detail": grade_detail,
        "year": year,
        "mileage": mileage,
        "capacity_l": capacity_l,
        "fuel": fuel,
        "drive_type": drive_raw,
        "price_krw": price_krw,
        "photos": photos,
        "description": description,
        "specs": specs,
        "vehicle_id": data.get("vehicleId"),
    }


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


def _norm_model_key(text: str) -> str:
    """Collapse spaces/hyphens for model-group matching."""
    return re.sub(r"[\s\-]+", "", text or "").lower()


def _is_camping_listing(
    item: dict[str, Any],
    *,
    model_groups: tuple[str, ...] = CAMPER_MODEL_GROUPS,
    use_model_groups: bool = False,
) -> bool:
    text = _listing_text(item)
    if CAMPING_RE.search(text):
        return True
    if not use_model_groups:
        return False
    model_key = _norm_model_key(str(item.get("Model") or ""))
    return any(_norm_model_key(group) in model_key for group in model_groups)


def _parse_encar_year(value: Any) -> int | None:
    if value is None:
        return None
    if isinstance(value, (int, float)):
        s = str(int(value))
        if len(s) >= 4:
            return int(s[:4])
    match = re.search(r"(20\d{2}|19\d{2})", str(value))
    return int(match.group(1)) if match else None


def _as_int(value: Any) -> int | None:
    if value is None:
        return None
    try:
        return int(float(value))
    except (TypeError, ValueError):
        return None


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
        url = loc if str(loc).startswith("http") else f"{ENCAR_IMG_BASE}{loc}"
        if url not in seen:
            seen.add(url)
            urls.append(url)
    if not urls and item.get("Photo"):
        base = str(item["Photo"])
        urls.append(f"{ENCAR_IMG_BASE}{base}001.jpg")
    return urls


def _detail_photos(photos: list[Any]) -> list[str]:
    ordered = sorted(
        (p for p in photos if isinstance(p, dict) and p.get("path")),
        key=lambda p: (str(p.get("code") or "999"), str(p.get("path"))),
    )
    urls: list[str] = []
    seen: set[str] = set()
    for photo in ordered:
        path = str(photo["path"])
        url = path if path.startswith("http") else f"{ENCAR_IMG_BASE}{path}"
        if url in seen:
            continue
        seen.add(url)
        urls.append(url)
    return urls


def _extract_drive(text: str) -> str | None:
    match = DRIVE_RE.search(text or "")
    return match.group(1).upper() if match else None


def _join_options_etc(etc: list[Any]) -> str | None:
    if not etc:
        return None
    text = " ".join(str(x) for x in etc if x is not None)
    text = re.sub(r"\s+", " ", text).strip()
    return text or None


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
        self.max_pages = int(opts.get("max_pages", 5))
        self.page_size = int(opts.get("page_size", 50))
        self.camping_only = bool(opts.get("camping_only", True))
        self.use_model_groups = bool(opts.get("use_model_groups", False))
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
                self.api_query[:80],
            )
            data = _api_search(self.http, self.api_query, offset=offset, limit=self.page_size)
            results = data.get("SearchResults") or []
            logger.info(
                "Encar page %s: %s results (total Count=%s)",
                page + 1,
                len(results),
                data.get("Count"),
            )

            if not results:
                break

            for item in results:
                source_id = str(item.get("Id") or "")
                if not source_id or source_id in seen:
                    continue

                if self.camping_only and not _is_camping_listing(
                    item,
                    model_groups=self.model_groups,
                    use_model_groups=self.use_model_groups,
                ):
                    continue

                seen.add(source_id)
                listing = self._item_to_listing(item)

                if self.config.import_.fetch_details:
                    try:
                        listing = self._enrich_from_detail(listing, source_id)
                    except Exception:
                        logger.exception("Encar detail fetch failed for %s", source_id)

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
        mileage = _as_int(item.get("Mileage"))
        drive_raw = _extract_drive(f"{badge} {badge_detail}")

        properties = ListingProperties(
            year=year,
            mileage=mileage,
            engine_type=map_engine_type(str(item.get("FuelType") or "")),
            drive_type=map_drive_type(drive_raw) if drive_raw else None,
            grade=badge_detail or badge or None,
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

    def _enrich_from_detail(self, listing: NormalizedListing, vehicle_id: str) -> NormalizedListing:
        data = _api_detail(self.http, vehicle_id)
        parsed = parse_detail_payload(data)

        if parsed.get("title"):
            listing.title = parsed["title"]
        if parsed.get("price_krw"):
            listing.raw["price_krw"] = parsed["price_krw"]
        if parsed.get("description"):
            listing.description = parsed["description"]

        photos = parsed.get("photos") or []
        if photos:
            listing.photos = photos

        props = listing.properties
        if parsed.get("year"):
            props.year = parsed["year"]
            listing.year = parsed["year"]
        if parsed.get("mileage") is not None:
            props.mileage = parsed["mileage"]
        if parsed.get("capacity_l") is not None:
            props.capacity = parsed["capacity_l"]
        if parsed.get("fuel"):
            props.engine_type = map_engine_type(parsed["fuel"])
        if parsed.get("drive_type"):
            props.drive_type = map_drive_type(parsed["drive_type"])
        if parsed.get("grade_detail") or parsed.get("grade"):
            props.grade = parsed.get("grade_detail") or parsed.get("grade")

        listing.properties = props

        for key, value in (parsed.get("specs") or {}).items():
            if key in {"연식", "배기량", "주행거리", "연료"}:
                continue
            listing.parameters.append(ListingParameter(name=key, value=value))

        listing.parameters = _dedupe_parameters(listing.parameters)
        listing.raw["detail_vehicle_id"] = parsed.get("vehicle_id")
        return listing
