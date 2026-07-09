"""ACF field mapping loaded from acf_mapping.yaml."""

from __future__ import annotations

from functools import lru_cache
from pathlib import Path
from typing import Any

import yaml

from .schema import ListingParameter, NormalizedListing

MAPPING_PATH = Path(__file__).resolve().parent.parent / "acf_mapping.yaml"


@lru_cache(maxsize=1)
def load_acf_mapping(path: str | Path | None = None) -> dict[str, Any]:
    mapping_path = Path(path) if path else MAPPING_PATH
    with open(mapping_path, encoding="utf-8") as fh:
        return yaml.safe_load(fh) or {}


def map_drive_type_acf(raw: str, mapping: dict[str, Any] | None = None) -> str | None:
    raw = (raw or "").strip()
    if not raw:
        return None
    drive_map = (mapping or load_acf_mapping()).get("drive_type_map", {})
    if raw in drive_map:
        return str(drive_map[raw])
    upper = raw.upper()
    if upper in drive_map:
        return str(drive_map[upper])
    for key, value in drive_map.items():
        if key.lower() in raw.lower() or raw.lower() in key.lower():
            return str(value)
    return raw


def map_param_name(raw: str, mapping: dict[str, Any] | None = None) -> str:
    param_map = (mapping or load_acf_mapping()).get("param_name_map", {})
    return str(param_map.get(raw, raw))


def php_serialize_string_list(values: list[str]) -> str:
    """Serialize a list of strings as PHP array (ACF gallery attachment IDs)."""
    parts = [f"a:{len(values)}:{{"]
    for index, value in enumerate(values):
        parts.append(f'i:{index};s:{len(value)}:"{value}";')
    parts.append("}")
    return "".join(parts)


def build_wp_meta_updates(listing: NormalizedListing, mapping: dict[str, Any] | None = None) -> dict[str, Any]:
    """Flat post_meta keys matching prod ACF storage (properties_* + field refs)."""
    mapping = mapping or load_acf_mapping()
    fields = mapping.get("fields", {})
    props_cfg = fields.get("properties", {})
    sub_fields = props_cfg.get("sub_fields", {})
    props = listing.properties
    meta: dict[str, Any] = {}

    def set_sub(name: str, value: Any) -> None:
        if value is None or value == "":
            return
        prefix = props_cfg.get("meta_prefix", "properties")
        meta_key = f"{prefix}_{name}"
        field_key = sub_fields.get(name, {}).get("key")
        meta[meta_key] = value
        if field_key:
            meta[f"_{meta_key}"] = field_key

    year_val = listing.year or props.year
    if year_val is not None:
        set_sub("year", str(year_val))
    if props.mileage is not None:
        set_sub("mileage", str(props.mileage))
    set_sub("grade", props.grade)
    set_sub("engine-type", props.engine_type)
    set_sub("capacity", props.capacity)
    set_sub("drive-type", props.drive_type)
    # Price omitted — theme shows «По запросу» for motorhome imports / category 1.

    group_key = props_cfg.get("key")
    if group_key:
        meta["_properties"] = group_key
        meta["properties"] = ""

    return meta


def build_photos_meta(attachment_ids: list[int], mapping: dict[str, Any] | None = None) -> dict[str, Any]:
    mapping = mapping or load_acf_mapping()
    photos_cfg = mapping.get("fields", {}).get("photos", {})
    ids = [str(i) for i in attachment_ids]
    meta: dict[str, Any] = {
        "photos": php_serialize_string_list(ids) if ids else "",
    }
    field_key = photos_cfg.get("key")
    if field_key:
        meta["_photos"] = field_key
    return meta


def acf_payload_for_log(listing: NormalizedListing) -> dict[str, Any]:
    """Human-readable ACF payload for dry-run JSON output."""
    props = listing.properties
    return {
        "properties": {
            "price": props.price_rub,
            "year": str(listing.year or props.year) if (listing.year or props.year) else None,
            "mileage": props.mileage,
            "engine-type": props.engine_type,
            "drive-type": props.drive_type,
            "capacity": props.capacity,
            "grade": props.grade,
        },
        "photos": listing.photos,
        "parameters": [
            {"param-name": p.name, "param-value": p.value}
            for p in listing.parameters
        ],
    }


def normalize_parameters(
    parameters: list[ListingParameter],
    mapping: dict[str, Any] | None = None,
) -> list[ListingParameter]:
    """Map source parameter names to ACF select choices where possible."""
    result: list[ListingParameter] = []
    for param in parameters:
        result.append(
            ListingParameter(
                name=map_param_name(param.name, mapping),
                value=param.value,
            )
        )
    return result
