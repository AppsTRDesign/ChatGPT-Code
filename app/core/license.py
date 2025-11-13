from __future__ import annotations

import base64
import hashlib
import hmac
import json
import logging
import platform
import uuid
from dataclasses import dataclass, field
from datetime import datetime, timedelta, timezone
from pathlib import Path
from typing import Optional

from dateutil.relativedelta import relativedelta
from zoneinfo import ZoneInfo, ZoneInfoNotFoundError

logger = logging.getLogger(__name__)

_LICENSE_SECRET = "telegram-suite-license-secret"


@dataclass(frozen=True)
class LicenseDuration:
    years: int = 0
    months: int = 0
    days: int = 0

    def normalized(self) -> "LicenseDuration":
        return LicenseDuration(max(self.years, 0), max(self.months, 0), max(self.days, 0))

    def is_zero(self) -> bool:
        norm = self.normalized()
        return norm.years == 0 and norm.months == 0 and norm.days == 0

    def label(self) -> str:
        norm = self.normalized()
        parts: list[str] = []
        if norm.years:
            parts.append(f"{norm.years}Y")
        if norm.months:
            parts.append(f"{norm.months}M")
        if norm.days:
            parts.append(f"{norm.days}D")
        return " ".join(parts) or "0D"

    def to_dict(self) -> dict:
        norm = self.normalized()
        return {"years": norm.years, "months": norm.months, "days": norm.days}

    @classmethod
    def from_dict(cls, data: Optional[dict]) -> "LicenseDuration":
        if not data:
            return cls()
        return cls(int(data.get("years", 0)), int(data.get("months", 0)), int(data.get("days", 0)))

    def apply(self, start: datetime) -> datetime:
        norm = self.normalized()
        expires = start + relativedelta(years=norm.years, months=norm.months)
        return expires + timedelta(days=norm.days)


def _sign_payload(machine_id: str, expires_at: str, plan: str) -> str:
    message = f"{machine_id}|{expires_at}|{plan}".encode("utf-8")
    return hmac.new(_LICENSE_SECRET.encode("utf-8"), message, hashlib.sha256).hexdigest()


def _resolve_timezone(name: str) -> timezone:
    try:
        return ZoneInfo(name)
    except (ZoneInfoNotFoundError, ModuleNotFoundError, ValueError):
        return timezone.utc


def machine_fingerprint() -> str:
    components = [
        platform.node(),
        platform.system(),
        platform.release(),
        platform.version(),
        platform.machine(),
        hex(uuid.getnode()),
    ]
    payload = "|".join(component or "unknown" for component in components).lower()
    return hashlib.sha256(payload.encode("utf-8")).hexdigest()


@dataclass
class LicenseInfo:
    machine_id: str
    expires_at: datetime
    plan: str
    signature: str
    key: str = ""
    activated_at: Optional[datetime] = None
    duration: LicenseDuration = field(default_factory=LicenseDuration)

    def to_dict(self) -> dict:
        return {
            "machine_id": self.machine_id,
            "expires_at": self.expires_at.isoformat(),
            "plan": self.plan,
            "signature": self.signature,
            "key": self.key,
            "activated_at": self.activated_at.isoformat() if self.activated_at else None,
            "duration": self.duration.to_dict(),
        }

    @classmethod
    def from_dict(cls, data: dict) -> "LicenseInfo":
        expires = datetime.fromisoformat(data["expires_at"]).astimezone(timezone.utc)
        activated = data.get("activated_at")
        activated_at = datetime.fromisoformat(activated).astimezone(timezone.utc) if activated else None
        duration = LicenseDuration.from_dict(data.get("duration"))
        return cls(
            machine_id=data["machine_id"],
            expires_at=expires,
            plan=data.get("plan", ""),
            signature=data.get("signature", ""),
            key=data.get("key", ""),
            activated_at=activated_at,
            duration=duration,
        )

    def remaining(self) -> timedelta:
        delta = self.expires_at - datetime.now(timezone.utc)
        if delta.total_seconds() < 0:
            return timedelta(seconds=0)
        return delta

    def is_active(self, expected_machine_id: Optional[str] = None) -> bool:
        if expected_machine_id and self.machine_id != expected_machine_id:
            return False
        return self.expires_at > datetime.now(timezone.utc)


class LicenseError(Exception):
    def __init__(self, message_key: str) -> None:
        super().__init__(message_key)
        self.message_key = message_key


class LicenseManager:
    def __init__(self, path: Path) -> None:
        self.path = path
        self.path.parent.mkdir(parents=True, exist_ok=True)
        self.machine_id = machine_fingerprint()
        self._info: Optional[LicenseInfo] = None
        self._load()

    def _load(self) -> Optional[LicenseInfo]:
        if not self.path.exists():
            self._info = None
            return None
        try:
            with self.path.open("r", encoding="utf-8") as fp:
                data = json.load(fp)
            info = LicenseInfo.from_dict(data)
        except Exception as exc:  # pragma: no cover - file corruption
            logger.warning("license.load_failed: %s", exc)
            self._info = None
            return None
        if not self._verify_signature(info):
            logger.warning("license.signature_mismatch")
            self._info = None
            return None
        self._info = info
        return info

    def get_info(self) -> Optional[LicenseInfo]:
        if self._info is None:
            return self._load()
        return self._info

    def activate(self, license_key: str) -> LicenseInfo:
        info = self._decode_key(license_key)
        if info.machine_id != self.machine_id:
            raise LicenseError("license.error_machine")
        if not info.is_active():
            raise LicenseError("license.error_expired")
        info.key = license_key
        info.activated_at = datetime.now(timezone.utc)
        self._info = info
        self._save(info)
        return info

    def _save(self, info: LicenseInfo) -> None:
        with self.path.open("w", encoding="utf-8") as fp:
            json.dump(info.to_dict(), fp, indent=2, ensure_ascii=False)

    def _verify_signature(self, info: LicenseInfo) -> bool:
        expected = _sign_payload(info.machine_id, info.expires_at.isoformat(), info.plan)
        return hmac.compare_digest(expected, info.signature)

    def _decode_key(self, license_key: str) -> LicenseInfo:
        try:
            decoded = base64.urlsafe_b64decode(license_key.encode("utf-8"))
            data = json.loads(decoded)
        except Exception as exc:  # pragma: no cover - invalid license
            raise LicenseError("license.error_invalid") from exc
        required = {"machine_id", "expires_at", "plan", "signature"}
        if not required.issubset(set(data.keys())):
            raise LicenseError("license.error_invalid")
        info = LicenseInfo.from_dict(data)
        if not self._verify_signature(info):
            raise LicenseError("license.error_signature")
        return info

    def format_datetime(self, dt: datetime, timezone_name: str) -> str:
        tz = _resolve_timezone(timezone_name)
        return dt.astimezone(tz).strftime("%d/%m/%Y %H:%M:%S")


PLAN_CHOICES = {
    "1m": ("1 Ay", LicenseDuration(months=1)),
    "3m": ("3 Ay", LicenseDuration(months=3)),
    "6m": ("6 Ay", LicenseDuration(months=6)),
}


def encode_license(
    machine_id: str,
    plan_code: Optional[str] = None,
    *,
    duration: Optional[LicenseDuration] = None,
    label: Optional[str] = None,
) -> str:
    if plan_code:
        if plan_code not in PLAN_CHOICES:
            raise ValueError("license plan is not supported")
        default_label, plan_duration = PLAN_CHOICES[plan_code]
        duration_obj = plan_duration
        plan_label = label or default_label
    elif duration is not None:
        duration_obj = duration
        plan_label = label or duration_obj.label()
    else:
        raise ValueError("either plan_code or duration must be provided")

    normalized = duration_obj.normalized()
    if normalized.is_zero():
        raise ValueError("license duration must be greater than zero")

    expires_at = normalized.apply(datetime.now(timezone.utc))
    expires_iso = expires_at.isoformat()
    signature = _sign_payload(machine_id, expires_iso, plan_label)
    payload = {
        "machine_id": machine_id,
        "expires_at": expires_iso,
        "plan": plan_label,
        "signature": signature,
        "duration": normalized.to_dict(),
    }
    return base64.urlsafe_b64encode(json.dumps(payload).encode("utf-8")).decode("utf-8")


__all__ = [
    "LicenseDuration",
    "LicenseInfo",
    "LicenseManager",
    "LicenseError",
    "machine_fingerprint",
    "PLAN_CHOICES",
    "encode_license",
]
