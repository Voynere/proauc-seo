"""Shared parsing and field-mapping utilities."""

from __future__ import annotations

import re

from .acf import load_acf_mapping, map_drive_type_acf, normalize_parameters
from .schema import ListingParameter, ListingProperties, NormalizedListing

YEAR_RE = re.compile(r"\((\d{4})年\)")
YEAR_KO_RE = re.compile(r"(20\d{2}|19\d{2})")
YEAR_KO_SHORT_RE = re.compile(r"(\d{2})년")
MILEAGE_RE = re.compile(r"([\d,]+)\s*km", re.I)
MAN_RE = re.compile(r"([\d,.]+)\s*万円")
YEN_RE = re.compile(r"([\d,]+)\s*円")
MANWON_KO_RE = re.compile(r"([\d,]+)\s*만원")
KRW_KO_RE = re.compile(r"([\d,]+)\s*원")
CC_KO_RE = re.compile(r"([\d,]+)\s*cc", re.I)

ENGINE_MAP = {
    "ガソリン": "Бензин",
    "gasoline": "Бензин",
    "가솔린": "Бензин",
    "diesel": "Дизель",
    "ディーゼル": "Дизель",
    "디젤": "Дизель",
    "軽油": "Дизель",
    "hybrid": "Гибрид",
    "ハイブリッド": "Гибрид",
    "LPG": "Бензин",
}

DRIVE_MAP = {
    "4WD": "Полный",
    "2WD": "Задний",
    "FF": "Передний",
    "FR": "Задний",
}


def parse_year(text: str) -> int | None:
    match = YEAR_RE.search(text)
    if match:
        return int(match.group(1))
    match = YEAR_KO_RE.search(text)
    if match:
        return int(match.group(1))
    match = YEAR_KO_SHORT_RE.search(text)
    if match:
        yy = int(match.group(1))
        return 2000 + yy if yy < 70 else 1900 + yy
    return None


def parse_price_krw(text: str) -> int | None:
    text = text.strip()
    if not text:
        return None
    match = MANWON_KO_RE.search(text)
    if match:
        return int(match.group(1).replace(",", "")) * 10_000
    match = KRW_KO_RE.search(text)
    if match:
        return int(match.group(1).replace(",", ""))
    return None


def parse_capacity_ko(text: str) -> float | None:
    match = CC_KO_RE.search(text)
    if not match:
        return None
    cc = int(match.group(1).replace(",", ""))
    return round(cc / 1000, 1)


def parse_mileage(text: str) -> int | None:
    match = MILEAGE_RE.search(text)
    if not match:
        return None
    return int(match.group(1).replace(",", ""))


def parse_price_jpy(text: str) -> int | None:
    text = text.strip()
    if not text or "SOLD OUT" in text.upper():
        return None
    match = MAN_RE.search(text)
    if match:
        value = float(match.group(1).replace(",", ""))
        return int(value * 10_000)
    match = YEN_RE.search(text)
    if match:
        return int(match.group(1).replace(",", ""))
    return None


def jpy_to_rub(jpy: int | None, rate: float) -> int | None:
    if jpy is None or rate <= 0:
        return None
    return int(jpy * rate)


def map_engine_type(raw: str) -> str | None:
    raw = raw.strip()
    for key, label in ENGINE_MAP.items():
        if key.lower() in raw.lower():
            return label
    return raw or None


def map_drive_type(raw: str) -> str | None:
    """Map source drive text to ACF slug (front / rear / 4wd)."""
    return map_drive_type_acf(raw, load_acf_mapping())


def parse_capacity_cc(text: str) -> float | None:
    match = re.search(r"([\d,]+)\s*cc", text, re.I)
    if not match:
        return None
    cc = int(match.group(1).replace(",", ""))
    return round(cc / 1000, 1)


def build_title(car_type: str | None, grade: str | None, fallback: str = "") -> str:
    parts = [p.strip() for p in (car_type, grade) if p and p.strip()]
    if parts:
        return " ".join(parts)
    return fallback.strip() or "Motorhome"


def finalize_listing(listing: NormalizedListing) -> NormalizedListing:
    """Apply cross-source normalizations after adapter mapping."""
    if listing.year is None and listing.properties.year is not None:
        listing.year = listing.properties.year
    listing.parameters = normalize_parameters(listing.parameters)
    return listing


def spec_row_to_parameters(specs: dict[str, str]) -> list[ListingParameter]:
    """Convert leftover spec fields to ACF parameters repeater entries."""
    skip = {"年式", "走行", "排気量"}
    params: list[ListingParameter] = []
    for key, value in specs.items():
        if key in skip or not value:
            continue
        params.append(ListingParameter(name=key, value=value))
    return params


KO_SPEC_CORE = {"연식", "배기량", "주행거리", "변속기", "연료", "색상", "확인사항"}


def apply_ko_spec_table(properties: ListingProperties, specs: dict[str, str]) -> ListingProperties:
    if "연식" in specs and properties.year is None:
        properties.year = parse_year(specs["연식"])
    if "주행거리" in specs and properties.mileage is None:
        properties.mileage = parse_mileage(specs["주행거리"])
    if "배기량" in specs and properties.capacity is None:
        properties.capacity = parse_capacity_ko(specs["배기량"])
    if "연료" in specs and not properties.engine_type:
        properties.engine_type = map_engine_type(specs["연료"])
    return properties


def ko_spec_row_to_parameters(specs: dict[str, str]) -> list[ListingParameter]:
    params: list[ListingParameter] = []
    for key, value in specs.items():
        if key in KO_SPEC_CORE or not value:
            continue
        params.append(ListingParameter(name=key, value=value))
    return params


def apply_spec_table(properties: ListingProperties, specs: dict[str, str]) -> ListingProperties:
    if "年式" in specs and properties.year is None:
        properties.year = parse_year(specs["年式"])
    if "走行" in specs and properties.mileage is None:
        properties.mileage = parse_mileage(specs["走行"])
    if "排気量" in specs and properties.capacity is None:
        properties.capacity = parse_capacity_cc(specs["排気量"])
    if "シフト/燃料/駆動" in specs:
        parts = [p.strip() for p in specs["シフト/燃料/駆動"].split("/")]
        if len(parts) >= 2 and not properties.engine_type:
            properties.engine_type = map_engine_type(parts[1])
        if len(parts) >= 3 and not properties.drive_type:
            properties.drive_type = map_drive_type(parts[2])
    if "修復歴" in specs and not properties.grade:
        properties.grade = specs["修復歴"]
    return properties
