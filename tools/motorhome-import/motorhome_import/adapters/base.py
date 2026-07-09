"""Source adapter interface."""

from __future__ import annotations

from abc import ABC, abstractmethod
from typing import Iterable

from ..schema import NormalizedListing


class BaseAdapter(ABC):
    source_name: str

    @abstractmethod
    def fetch_listings(self) -> Iterable[NormalizedListing]:
        """Yield normalized listings from the source."""
