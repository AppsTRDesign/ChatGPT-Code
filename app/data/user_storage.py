from __future__ import annotations

import json
from dataclasses import asdict, dataclass
from datetime import datetime, timedelta, timezone, tzinfo
from pathlib import Path
from typing import Dict, Iterable, List, Optional
from zoneinfo import ZoneInfo, ZoneInfoNotFoundError


@dataclass
class StoredUser:
    user_id: int
    username: Optional[str]
    phone: Optional[str]
    access_hash: Optional[int]
    first_name: Optional[str]
    last_name: Optional[str]
    last_seen: Optional[str]
    status: Optional[str]
    source: Optional[str]
    is_bot: bool = False
    last_seen_utc: Optional[str] = None
    last_message: Optional[str] = None
    dm_status: Optional[str] = None

    def to_dict(self) -> Dict[str, object]:
        return asdict(self)


class UserStorage:
    def __init__(self, path: Path, timezone_name: str = "UTC") -> None:
        self.path = path
        self.path.parent.mkdir(parents=True, exist_ok=True)
        self._users: Dict[int, StoredUser] = {}
        self.timezone_name = timezone_name
        self.load()

    def load(self) -> None:
        if not self.path.exists():
            self._users = {}
            return
        with self.path.open("r", encoding="utf-8") as fp:
            data = json.load(fp)
        self._users = {}
        for item in data:
            payload = {
                "user_id": item.get("user_id"),
                "username": item.get("username"),
                "phone": item.get("phone"),
                "access_hash": item.get("access_hash"),
                "first_name": item.get("first_name"),
                "last_name": item.get("last_name"),
                "last_seen": item.get("last_seen"),
                "status": item.get("status"),
                "source": item.get("source"),
                "is_bot": bool(item.get("is_bot", False)),
                "last_seen_utc": item.get("last_seen_utc"),
                "last_message": item.get("last_message"),
                "dm_status": item.get("dm_status"),
            }
            user_id = payload["user_id"]
            if user_id is None:
                continue
            stored = StoredUser(**payload)
            self._users[int(user_id)] = self.prepare_user(stored)

    def save(self) -> None:
        with self.path.open("w", encoding="utf-8") as fp:
            json.dump([user.to_dict() for user in self._users.values()], fp, indent=2, ensure_ascii=False)

    def add_users(self, users: Iterable[StoredUser]) -> None:
        for user in users:
            prepared = self.prepare_user(user)
            self._users[prepared.user_id] = prepared
        self.save()

    def update_user(self, user: StoredUser) -> None:
        prepared = self.prepare_user(user)
        self._users[prepared.user_id] = prepared
        self.save()

    def has_user(self, user_id: int) -> bool:
        return user_id in self._users

    def remove_user(self, user_id: int) -> None:
        if user_id in self._users:
            del self._users[user_id]
            self.save()

    def remove_users(self, user_ids: Iterable[int]) -> None:
        changed = False
        for user_id in user_ids:
            key = int(user_id)
            if key in self._users:
                del self._users[key]
                changed = True
        if changed:
            self.save()

    def get_user(self, user_id: int) -> Optional[StoredUser]:
        return self._users.get(int(user_id))

    def get_users_by_ids(self, user_ids: Iterable[int]) -> List[StoredUser]:
        results: List[StoredUser] = []
        for user_id in user_ids:
            stored = self.get_user(int(user_id))
            if stored:
                results.append(stored)
        return results

    def get_users(self) -> List[StoredUser]:
        return sorted(self._users.values(), key=lambda user: user.user_id)

    def import_from_file(self, file_path: Path) -> None:
        if not file_path.exists():
            return
        with file_path.open("r", encoding="utf-8") as fp:
            data = json.load(fp)
        imported: List[StoredUser] = []
        for item in data:
            if not isinstance(item, dict) or "user_id" not in item:
                continue
            payload = {
                "user_id": item.get("user_id"),
                "username": item.get("username"),
                "phone": item.get("phone"),
                "access_hash": item.get("access_hash"),
                "first_name": item.get("first_name"),
                "last_name": item.get("last_name"),
                "last_seen": item.get("last_seen"),
                "status": item.get("status"),
                "source": item.get("source"),
                "is_bot": bool(item.get("is_bot", False)),
                "last_seen_utc": item.get("last_seen_utc"),
                "last_message": item.get("last_message"),
                "dm_status": item.get("dm_status"),
            }
            imported.append(StoredUser(**payload))
        self.add_users(imported)

    def export_to_file(self, file_path: Path) -> None:
        file_path.parent.mkdir(parents=True, exist_ok=True)
        with file_path.open("w", encoding="utf-8") as fp:
            json.dump([user.to_dict() for user in self._users.values()], fp, indent=2, ensure_ascii=False)

    def clear(self) -> None:
        self._users = {}
        self.save()

    @staticmethod
    def to_iso(dt: Optional[datetime]) -> Optional[str]:
        if dt is None:
            return None
        aware = dt if dt.tzinfo else dt.replace(tzinfo=timezone.utc)
        return aware.astimezone(timezone.utc).isoformat()

    def prepare_user(self, user: StoredUser) -> StoredUser:
        if user.last_seen_utc is None and user.last_seen:
            parsed = self._parse_datetime(user.last_seen)
            if parsed:
                user.last_seen_utc = parsed.astimezone(timezone.utc).isoformat()
        if user.last_seen_utc:
            formatted = self._format_last_seen(user.last_seen_utc)
            if formatted:
                user.last_seen = formatted
        user.status = self._normalize_status(user.status)
        user.dm_status = self._normalize_dm_status(user.dm_status)
        return user

    def set_timezone(self, timezone_name: str) -> None:
        if timezone_name == self.timezone_name:
            return
        self.timezone_name = timezone_name
        for user in self._users.values():
            if user.last_seen_utc:
                formatted = self._format_last_seen(user.last_seen_utc)
                if formatted:
                    user.last_seen = formatted
        self.save()

    def _format_last_seen(self, iso_value: str) -> Optional[str]:
        parsed = self._parse_datetime(iso_value)
        if not parsed:
            return None
        tz = self._current_timezone()
        return parsed.astimezone(tz).strftime("%d/%m/%Y %H:%M")

    def _parse_datetime(self, value: str) -> Optional[datetime]:
        try:
            parsed = datetime.fromisoformat(value)
        except ValueError:
            try:
                parsed = datetime.strptime(value, "%d/%m/%Y %H:%M")
                parsed = parsed.replace(tzinfo=self._current_timezone())
            except ValueError:
                return None
        if parsed.tzinfo is None:
            parsed = parsed.replace(tzinfo=timezone.utc)
        return parsed

    def _current_timezone(self) -> tzinfo:
        fallback = _FALLBACK_TIMEZONES.get(self.timezone_name)
        if fallback:
            return fallback
        try:
            return ZoneInfo(self.timezone_name)
        except (ZoneInfoNotFoundError, ModuleNotFoundError):
            return _FALLBACK_TIMEZONES.get("UTC", timezone.utc)
        except Exception:
            return timezone.utc

    def _normalize_status(self, status: Optional[str]) -> Optional[str]:
        if not status:
            return None
        key = status.strip()
        if key.startswith("status."):
            return key
        mapped = _STATUS_NORMALIZATION.get(key)
        if mapped:
            return mapped
        mapped = _STATUS_NORMALIZATION.get(key.lower())
        if mapped:
            return mapped
        return "status.unknown_label"

    def _normalize_dm_status(self, value: Optional[str]) -> Optional[str]:
        if not value:
            return None
        key = value.strip()
        if not key:
            return None
        if key.startswith("status.dm_"):
            return key
        mapped = _DM_STATUS_MAP.get(key.lower())
        if mapped:
            return mapped
        return None


_FALLBACK_TIMEZONES: Dict[str, tzinfo] = {
    "UTC": timezone.utc,
    "Europe/Istanbul": timezone(timedelta(hours=3)),
    "Europe/London": timezone.utc,
    "Europe/Paris": timezone(timedelta(hours=1)),
    "Europe/Berlin": timezone(timedelta(hours=1)),
    "Europe/Moscow": timezone(timedelta(hours=3)),
    "Europe/Madrid": timezone(timedelta(hours=1)),
    "Europe/Rome": timezone(timedelta(hours=1)),
    "Europe/Amsterdam": timezone(timedelta(hours=1)),
    "Europe/Athens": timezone(timedelta(hours=2)),
    "Europe/Zurich": timezone(timedelta(hours=1)),
    "Europe/Vienna": timezone(timedelta(hours=1)),
    "Europe/Warsaw": timezone(timedelta(hours=1)),
    "Europe/Prague": timezone(timedelta(hours=1)),
    "Europe/Stockholm": timezone(timedelta(hours=1)),
    "Asia/Dubai": timezone(timedelta(hours=4)),
    "Asia/Tehran": timezone(timedelta(hours=3, minutes=30)),
    "Asia/Tokyo": timezone(timedelta(hours=9)),
    "Asia/Shanghai": timezone(timedelta(hours=8)),
    "Asia/Singapore": timezone(timedelta(hours=8)),
    "Asia/Hong_Kong": timezone(timedelta(hours=8)),
    "Asia/Kuala_Lumpur": timezone(timedelta(hours=8)),
    "Asia/Seoul": timezone(timedelta(hours=9)),
    "Asia/Jakarta": timezone(timedelta(hours=7)),
    "Asia/Karachi": timezone(timedelta(hours=5)),
    "Asia/Calcutta": timezone(timedelta(hours=5, minutes=30)),
    "Asia/Bangkok": timezone(timedelta(hours=7)),
    "America/New_York": timezone(-timedelta(hours=5)),
    "America/Chicago": timezone(-timedelta(hours=6)),
    "America/Los_Angeles": timezone(-timedelta(hours=8)),
    "America/Sao_Paulo": timezone(-timedelta(hours=3)),
    "America/Mexico_City": timezone(-timedelta(hours=6)),
    "America/Toronto": timezone(-timedelta(hours=5)),
    "Australia/Sydney": timezone(timedelta(hours=10)),
    "Australia/Melbourne": timezone(timedelta(hours=10)),
}


_STATUS_NORMALIZATION: Dict[str, str] = {
    "UserStatusOnline": "status.online_label",
    "UserStatusRecently": "status.recently_label",
    "UserStatusOffline": "status.offline_label",
    "UserStatusLastWeek": "status.last_week_label",
    "UserStatusLastMonth": "status.last_month_label",
    "UserStatusLongAgo": "status.long_ago_label",
    "UserStatusEmpty": "status.unknown_label",
    "online": "status.online_label",
    "recently": "status.recently_label",
    "offline": "status.offline_label",
    "last_week": "status.last_week_label",
    "last_month": "status.last_month_label",
    "long_ago": "status.long_ago_label",
    "unknown": "status.unknown_label",
}

_STATUS_NORMALIZATION.update({key.lower(): value for key, value in list(_STATUS_NORMALIZATION.items())})

_DM_STATUS_MAP: Dict[str, str] = {
    "acik": "status.dm_open",
    "açık": "status.dm_open",
    "open": "status.dm_open",
    "available": "status.dm_open",
    "dm açık": "status.dm_open",
    "kapali": "status.dm_closed",
    "kapalı": "status.dm_closed",
    "closed": "status.dm_closed",
    "restricted": "status.dm_closed",
    "dm kapalı": "status.dm_closed",
}
