from __future__ import annotations

import json
from dataclasses import asdict, dataclass
from datetime import datetime
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
        self._users = {
            int(item["user_id"]): StoredUser(**item)
            for item in data
        }

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

    @staticmethod
    def serialize_datetime(dt: Optional[datetime]) -> Optional[str]:
        if dt is None:
            return None
        return dt.isoformat()
