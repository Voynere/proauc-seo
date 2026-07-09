"""Canonical listing schema and helpers."""

from __future__ import annotations

from dataclasses import asdict, dataclass, field
from typing import Any


@dataclass
class ListingProperties:
    year: int | None = None
    capacity: float | None = None  # liters (engine displacement)
    mileage: int | None = None  # km
    engine_type: str | None = None  # e.g. "Бензин", "Дизель"
    drive_type: str | None = None  # e.g. "4WD", "2WD"
    grade: str | None = None
    price_rub: int | None = None  # final price in RUB for ACF properties.price


@dataclass
class ListingParameter:
    name: str
    value: str


@dataclass
class NormalizedListing:
    """Canonical schema mapped to WP avto + ACF."""

    source: str
    source_id: str
    title: str
    source_url: str
    properties: ListingProperties = field(default_factory=ListingProperties)
    year: int | None = None  # top-level ACF year field
    photos: list[str] = field(default_factory=list)
    parameters: list[ListingParameter] = field(default_factory=list)
    description: str | None = None
    sold_out: bool = False
    raw: dict[str, Any] = field(default_factory=dict)

    def to_dict(self) -> dict[str, Any]:
        data = asdict(self)
        return data

    def wp_meta(self) -> dict[str, str]:
        return {
            "_source": self.source,
            "_source_id": self.source_id,
        }

    def acf_payload(self) -> dict[str, Any]:
        from .acf import acf_payload_for_log

        return acf_payload_for_log(self)
