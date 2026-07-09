"""Adapter registry."""

from __future__ import annotations

from typing import TYPE_CHECKING

from .base import BaseAdapter
from .bobaedream import BobaedreamAdapter
from .encar import EncarAdapter
from .fujicars import FujicarsAdapter

if TYPE_CHECKING:
    from ..config import AppConfig
    from ..http import HttpClient

ADAPTERS: dict[str, type[BaseAdapter]] = {
    "fujicars": FujicarsAdapter,
    "bobaedream": BobaedreamAdapter,
    "encar": EncarAdapter,
}


def get_adapter(source: str, config: AppConfig, http: HttpClient) -> BaseAdapter:
    cls = ADAPTERS.get(source)
    if cls is None:
        raise ValueError(f"Unknown source: {source}. Available: {', '.join(ADAPTERS)}")
    source_cfg = config.sources.get(source)
    options = source_cfg.options if source_cfg else {}
    return cls(config=config, http=http, options=options)
