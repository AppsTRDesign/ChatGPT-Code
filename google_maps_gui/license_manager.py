"""Shared licensing utilities for the Google Maps GUI application."""
from __future__ import annotations

import hashlib
import json
import platform
import re
import uuid
from calendar import monthrange
from contextlib import suppress
from datetime import datetime, timedelta
from pathlib import Path
from typing import Dict, Optional, Tuple


class LicenseError(Exception):
    """Raised when a license action fails."""


class LicenseManager:
    """Simple offline license enforcement tied to the current machine."""

    SECRET = "MAPSBOT-LICENSE-2024"
    KEY_PATTERN = re.compile(r"^MAPS-(?P<years>\d+)Y-(?P<months>\d+)M-(?P<days>\d+)D-(?P<digest>[A-Z0-9]{16})$")

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

    def plan_components(self) -> Optional[Dict[str, int]]:
        if not self._data:
            return None
        try:
            years = int(self._data.get("plan_years", 0))
            months = int(self._data.get("plan_months", 0))
            days = int(self._data.get("plan_days", 0))
        except (TypeError, ValueError):
            return None
        if years == months == days == 0:
            return None
        return {"years": years, "months": months, "days": days}

    def activate(self, license_key: str) -> None:
        license_key = (license_key or "").strip().upper()
        years, months, days = self._extract_components(license_key)
        expected = self.expected_key_for_machine(self.machine_id(), years, months, days)
        if license_key != expected:
            raise LicenseError("invalid_key")
        expires = self._calculate_expiry(years, months, days)
        self._data = {
            "machine_id": self.machine_id(),
            "plan_years": years,
            "plan_months": months,
            "plan_days": days,
            "activated_at": datetime.utcnow().isoformat(),
            "expires_at": expires.isoformat(),
            "license_key": expected,
        }
        self._save()

    def license_data(self) -> Dict[str, object]:
        return dict(self._data)

    @classmethod
    def expected_key_for_machine(
        cls, machine_id: str, years: int, months: int, days: int
    ) -> str:
        cls._validate_components(years, months, days)
        payload = f"{machine_id}:{years}:{months}:{days}:{cls.SECRET}"
        digest = hashlib.sha256(payload.encode()).hexdigest()[:16].upper()
        return cls._format_key(years, months, days, digest)

    def _extract_components(self, key: str) -> Tuple[int, int, int]:
        match = self.KEY_PATTERN.match(key)
        if not match:
            raise LicenseError("invalid_key")
        years = int(match.group("years"))
        months = int(match.group("months"))
        days = int(match.group("days"))
        self._validate_components(years, months, days)
        return years, months, days

    @classmethod
    def _format_key(cls, years: int, months: int, days: int, digest: str) -> str:
        return f"MAPS-{years}Y-{months}M-{days}D-{digest}"

    @classmethod
    def _validate_components(cls, years: int, months: int, days: int) -> None:
        for value in (years, months, days):
            if value < 0:
                raise LicenseError("invalid_key")
        if years == months == days == 0:
            raise LicenseError("invalid_key")

    def _calculate_expiry(self, years: int, months: int, days: int) -> datetime:
        base = datetime.utcnow()
        target = self._add_calendar_duration(base, years, months)
        target += timedelta(days=days)
        return target

    @staticmethod
    def _add_calendar_duration(base: datetime, years: int, months: int) -> datetime:
        year = base.year + years
        month = base.month + months
        while month > 12:
            year += 1
            month -= 12
        while month < 1:
            year -= 1
            month += 12
        day = min(base.day, monthrange(year, month)[1])
        return base.replace(year=year, month=month, day=day)

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
