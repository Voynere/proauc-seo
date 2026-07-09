"""HTTP client with rate limiting."""

from __future__ import annotations

import logging
import time
from typing import Any

import requests

logger = logging.getLogger(__name__)


class HttpClient:
    def __init__(
        self,
        *,
        user_agent: str,
        timeout: float = 30.0,
        rate_limit: float = 1.0,
    ) -> None:
        self.session = requests.Session()
        self.session.headers.update({"User-Agent": user_agent})
        self.timeout = timeout
        self.rate_limit = rate_limit
        self._last_request = 0.0

    def _throttle(self) -> None:
        if self.rate_limit <= 0:
            return
        elapsed = time.monotonic() - self._last_request
        if elapsed < self.rate_limit:
            time.sleep(self.rate_limit - elapsed)

    def get(self, url: str, **kwargs: Any) -> requests.Response:
        self._throttle()
        logger.debug("GET %s", url)
        response = self.session.get(url, timeout=self.timeout, **kwargs)
        self._last_request = time.monotonic()
        response.raise_for_status()
        return response

    def get_text(self, url: str, **kwargs: Any) -> str:
        return self.get(url, **kwargs).text
