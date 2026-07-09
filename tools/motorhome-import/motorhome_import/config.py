"""Configuration loader."""

from __future__ import annotations

from dataclasses import dataclass, field
from pathlib import Path
from typing import Any

import yaml


@dataclass
class WordPressConfig:
    url: str = "https://proauc.ru"
    user: str = ""
    app_password: str = ""
    wp_path: str = ""
    ssh_host: str = ""  # e.g. proauc — remote wp-cli media import
    post_type: str = "avto"
    category_id: int = 1


@dataclass
class PricingConfig:
    enabled: bool = True
    api_url: str = "https://proauc.ru/api/get-price.php"
    country: str = "japan"


@dataclass
class ImportConfig:
    dry_run: bool = True
    limit: int = 10
    skip_sold_out: bool = True
    fetch_details: bool = True
    sideload_images: bool = True
    jpy_to_rub_rate: float = 0.0  # fallback when pricing API disabled
    pricing: PricingConfig = field(default_factory=PricingConfig)


@dataclass
class HttpConfig:
    user_agent: str = "Mozilla/5.0 (compatible; proauc-motorhome-import/0.1)"
    timeout_seconds: float = 30.0
    rate_limit: float = 1.0


@dataclass
class SourceConfig:
    enabled: bool = False
    options: dict[str, Any] = field(default_factory=dict)


@dataclass
class AppConfig:
    wordpress: WordPressConfig = field(default_factory=WordPressConfig)
    import_: ImportConfig = field(default_factory=ImportConfig)
    http: HttpConfig = field(default_factory=HttpConfig)
    sources: dict[str, SourceConfig] = field(default_factory=dict)
    logging_level: str = "INFO"


def load_config(path: str | Path) -> AppConfig:
    with open(path, encoding="utf-8") as fh:
        raw = yaml.safe_load(fh) or {}

    wp_raw = raw.get("wordpress", {})
    import_raw = raw.get("import", {})
    http_raw = raw.get("http", {})
    sources_raw = raw.get("sources", {})
    logging_raw = raw.get("logging", {})

    sources: dict[str, SourceConfig] = {}
    for name, src in sources_raw.items():
        if not isinstance(src, dict):
            continue
        enabled = bool(src.pop("enabled", False))
        sources[name] = SourceConfig(enabled=enabled, options=src)

    pricing_raw = import_raw.get("pricing", {}) or {}

    return AppConfig(
        wordpress=WordPressConfig(**{k: v for k, v in wp_raw.items() if hasattr(WordPressConfig, k)}),
        import_=ImportConfig(
            dry_run=import_raw.get("dry_run", True),
            limit=import_raw.get("limit", 10),
            skip_sold_out=import_raw.get("skip_sold_out", True),
            fetch_details=import_raw.get("fetch_details", True),
            sideload_images=import_raw.get("sideload_images", True),
            jpy_to_rub_rate=float(import_raw.get("jpy_to_rub_rate", 0) or 0),
            pricing=PricingConfig(
                enabled=bool(pricing_raw.get("enabled", True)),
                api_url=str(pricing_raw.get("api_url", PricingConfig.api_url)),
                country=str(pricing_raw.get("country", PricingConfig.country)),
            ),
        ),
        http=HttpConfig(
            user_agent=http_raw.get("user_agent", HttpConfig.user_agent),
            timeout_seconds=float(http_raw.get("timeout_seconds", 30)),
            rate_limit=float(http_raw.get("rate_limit", 1.0)),
        ),
        sources=sources,
        logging_level=logging_raw.get("level", "INFO"),
    )
