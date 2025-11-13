"""Shared licensing utilities for the Google Maps GUI application."""
from __future__ import annotations

from contextlib import suppress
from datetime import datetime, timedelta
from pathlib import Path
from typing import Dict, Optional
import hashlib
import json
import platform
import uuid


class LicenseError(Exception):
    """Raised when a license action fails."""


class LicenseManager:
    """Simple offline license enforcement tied to the current machine."""

    PLAN_DURATIONS = {1: 30, 3: 90, 6: 180}
    SECRET = "MAPSBOT-LICENSE-2024"

    def __init__(self, license_path: Optional[Path] = None) -> None:
        self.license_path = license_path or Path(__file__).resolve().parent / "license.json"
        self._data: Dict[str, object] = {}
        self._load()

    def machine_id(self) -> str:
        raw = f"{platform.node()}-{uuid.getnode()}".encode()
        return hashlib.sha256(raw).hexdigest()[:16].upper()

    def is_valid(self) -> bool:
        data = self._data
        if not data:
            return False
        if data.get("machine_id") != self.machine_id():
            return False
        expires = self._parse_datetime(data.get("expires_at"))
        if not expires:
            return False
        return datetime.utcnow() < expires

    def remaining_days(self) -> Optional[int]:
        if not self.is_valid():
            return None
        expires = self._parse_datetime(self._data.get("expires_at"))
        if not expires:
            return None
        delta = expires - datetime.utcnow()
        return max(0, delta.days)

    def expires_at(self) -> Optional[str]:
        expires = self._parse_datetime(self._data.get("expires_at"))
        if not expires:
            return None
        return expires.strftime("%Y-%m-%d %H:%M")

    def plan_months(self) -> Optional[int]:
        value = self._data.get("plan_months")
        try:
            return int(value) if value is not None else None
        except (TypeError, ValueError):
            return None

    def activate(self, months: int, license_key: str) -> None:
        months = int(months)
        if months not in self.PLAN_DURATIONS:
            raise LicenseError("invalid_plan")
        expected = self._expected_key(months)
        if license_key.strip().upper() != expected:
            raise LicenseError("invalid_key")
        expires = datetime.utcnow() + timedelta(days=self.PLAN_DURATIONS[months])
        self._data = {
            "machine_id": self.machine_id(),
            "plan_months": months,
            "activated_at": datetime.utcnow().isoformat(),
            "expires_at": expires.isoformat(),
            "license_key": expected,
        }
        self._save()

    def license_data(self) -> Dict[str, object]:
        return dict(self._data)

    @classmethod
    def expected_key_for_machine(cls, machine_id: str, months: int) -> str:
        payload = f"{machine_id}:{months}:{cls.SECRET}"
        digest = hashlib.sha256(payload.encode()).hexdigest()[:16].upper()
        return f"MAPS-{months}-{digest}"

    def _expected_key(self, months: int) -> str:
        return self.expected_key_for_machine(self.machine_id(), months)

    def _load(self) -> None:
        if not self.license_path.exists():
            self._data = {}
            return
        try:
            with open(self.license_path, "r", encoding="utf-8") as source:
                self._data = json.load(source)
        except (OSError, json.JSONDecodeError):
            self._data = {}

    def _save(self) -> None:
        try:
            with open(self.license_path, "w", encoding="utf-8") as target:
                json.dump(self._data, target, ensure_ascii=False, indent=2)
        except OSError:
            pass

    @staticmethod
    def _parse_datetime(value: Optional[object]) -> Optional[datetime]:
        if not value:
            return None
        if isinstance(value, str):
            with suppress(ValueError):
                return datetime.fromisoformat(value)
        return None


__all__ = ["LicenseError", "LicenseManager"]
