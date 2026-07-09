"""Price conversion — proauc get-price.php (landed cost) or simple rate fallback."""

from __future__ import annotations

import logging
from typing import Any
from urllib.parse import urlencode

from .http import HttpClient

logger = logging.getLogger(__name__)


def parse_currency_rates(currency_str: str) -> dict[str, float]:
    rates: dict[str, float] = {}
    for part in currency_str.split(";"):
        part = part.strip()
        if not part or ":" not in part:
            continue
        key, value = part.split(":", 1)
        try:
            rates[key.strip()] = float(value.strip())
        except ValueError:
            continue
    return rates


def fetch_landed_price_rub(
    http: HttpClient,
    *,
    api_url: str,
    country: str,
    source_price: int,
    year: int | None,
    volume: float | None,
    stat: int | None = None,
) -> dict[str, Any] | None:
    """
    Call proauc /api/get-price.php and return RUB landed price.

    Mirrors page-car-lot.php: priceRu = result.sum * USDRUB_system.
    """
    if source_price <= 0:
        return None

    params: dict[str, Any] = {
        "country": country,
        "price": source_price,
    }
    if year:
        params["year"] = year
    if volume:
        params["volume"] = volume
    if stat is not None:
        params["stat"] = stat

    url = f"{api_url.rstrip('/')}?{urlencode(params)}"
    logger.debug("Pricing API: %s", url)

    try:
        response = http.get(url)
        data = response.json()
    except Exception:
        logger.exception("Pricing API request failed")
        return None

    result = data.get("result") if isinstance(data, dict) else None
    if not result:
        logger.warning("Pricing API empty result: %s", str(data)[:200])
        return None

    info = (result.get("info") or [{}])[0]
    currency_str = info.get("currency", "")
    rates = parse_currency_rates(currency_str)
    usd_rub = rates.get("USDRUB_system")
    if not usd_rub:
        logger.warning("Pricing API missing USDRUB_system")
        return None

    try:
        sum_usd = float(result.get("sum", 0))
    except (TypeError, ValueError):
        return None

    price_rub = int(sum_usd * usd_rub)
    return {
        "price_rub": price_rub,
        "sum_usd": sum_usd,
        "usd_rub": usd_rub,
        "rates": rates,
        "api_url": url,
    }


def apply_pricing(
    listing: Any,
    *,
    http: HttpClient,
    pricing_enabled: bool,
    pricing_api_url: str,
    pricing_country: str,
    jpy_to_rub_rate: float = 0.0,
) -> Any:
    """Populate listing.properties.price_rub from API or fallback rate."""
    if listing.properties.price_rub is not None:
        return listing

    source_price = listing.raw.get("price_jpy") or listing.raw.get("price_krw")
    if not isinstance(source_price, int) or source_price <= 0:
        return listing

    country = pricing_country
    if listing.raw.get("price_krw") and not listing.raw.get("price_jpy"):
        country = "korea"

    if pricing_enabled and pricing_api_url:
        year = listing.year or listing.properties.year
        volume = listing.properties.capacity
        quote = fetch_landed_price_rub(
            http,
            api_url=pricing_api_url,
            country=country,
            source_price=source_price,
            year=year,
            volume=volume,
        )
        if quote:
            listing.properties.price_rub = quote["price_rub"]
            listing.raw["pricing"] = quote
            return listing

    if jpy_to_rub_rate > 0 and listing.raw.get("price_jpy"):
        listing.properties.price_rub = int(source_price * jpy_to_rub_rate)
        listing.raw["pricing"] = {"method": "jpy_to_rub_rate", "rate": jpy_to_rub_rate}

    return listing
