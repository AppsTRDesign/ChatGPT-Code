"""Automation backend patcher to swap Playwright with drop-in alternatives."""
from __future__ import annotations

import importlib
import os
from contextlib import contextmanager
from typing import Callable


def get_sync_backend() -> Callable:
    """
    Load the sync automation backend.

    Falls back to Playwright's ``sync_playwright`` but allows overriding via
    ``AUTOMATION_BACKEND`` env (module path exposing ``sync_playwright`` or
    ``get_sync``).
    """

    backend_path = os.environ.get('AUTOMATION_BACKEND', 'playwright.sync_api')
    try:
        module = importlib.import_module(backend_path)
    except Exception:  # pragma: no cover - safeguard for broken overrides
        from playwright.sync_api import sync_playwright  # type: ignore

        return sync_playwright

    for attr in ('sync_playwright', 'get_sync'):
        maybe = getattr(module, attr, None)
        if callable(maybe):
            return maybe  # type: ignore[return-value]

    # last resort fallback
    from playwright.sync_api import sync_playwright  # type: ignore

    return sync_playwright


__all__ = ["get_sync_backend"]
