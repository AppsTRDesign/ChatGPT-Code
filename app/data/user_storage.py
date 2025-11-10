from __future__ import annotations

import json
from dataclasses import asdict, dataclass
from datetime import datetime, timezone
from pathlib import Path
from typing import Dict, Iterable, List, Optional


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

    def to_dict(self) -> Dict[str, object]:
        return asdict(self)


class UserStorage:
    def __init__(self, path: Path) -> None:
        self.path = path
        self.path.parent.mkdir(parents=True, exist_ok=True)
        self._users: Dict[int, StoredUser] = {}
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
            }
            user_id = payload["user_id"]
            if user_id is None:
                continue
            self._users[int(user_id)] = StoredUser(**payload)

    def save(self) -> None:
        with self.path.open("w", encoding="utf-8") as fp:
            json.dump([user.to_dict() for user in self._users.values()], fp, indent=2, ensure_ascii=False)

    def add_users(self, users: Iterable[StoredUser]) -> None:
        for user in users:
            self._users[user.user_id] = user
        self.save()

    def remove_user(self, user_id: int) -> None:
        if user_id in self._users:
            del self._users[user_id]
            self.save()

    def get_users(self) -> List[StoredUser]:
        return list(self._users.values())

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
            }
            imported.append(StoredUser(**payload))
        self.add_users(imported)

    def export_to_file(self, file_path: Path) -> None:
        file_path.parent.mkdir(parents=True, exist_ok=True)
        with file_path.open("w", encoding="utf-8") as fp:
            json.dump([user.to_dict() for user in self._users.values()], fp, indent=2, ensure_ascii=False)

    @staticmethod
    def serialize_datetime(dt: Optional[datetime]) -> Optional[str]:
        if dt is None:
            return None
        aware = dt if dt.tzinfo else dt.replace(tzinfo=timezone.utc)
        return aware.astimezone(timezone.utc).strftime("%d/%m/%Y %H:%M")
