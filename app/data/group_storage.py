from __future__ import annotations

import json
from dataclasses import asdict, dataclass
from pathlib import Path
from typing import Dict, Iterable, List, Optional


@dataclass
class StoredGroup:
    group_id: Optional[int]
    access_hash: Optional[int]
    title: str
    username: Optional[str]
    link: Optional[str]
    members: Optional[int]
    online: Optional[int]
    is_public: bool
    is_megagroup: bool
    is_broadcast: bool
    messages_restricted: bool
    source: Optional[str] = None

    def to_dict(self) -> Dict[str, object]:
        return asdict(self)


class GroupStorage:
    def __init__(self, path: Path) -> None:
        self.path = path
        self.path.parent.mkdir(parents=True, exist_ok=True)
        self._groups: Dict[str, StoredGroup] = {}
        self.load()

    def _make_key(self, group: StoredGroup) -> str:
        if group.group_id:
            return str(group.group_id)
        if group.link:
            return group.link.lower()
        if group.username:
            return group.username.lower()
        return group.title.lower()

    def load(self) -> None:
        if not self.path.exists():
            self._groups = {}
            return
        with self.path.open("r", encoding="utf-8") as fp:
            data = json.load(fp)
        parsed: Dict[str, StoredGroup] = {}
        for item in data:
            if not isinstance(item, dict):
                continue
            payload = {
                "group_id": item.get("group_id") or item.get("id"),
                "access_hash": item.get("access_hash"),
                "title": item.get("title") or item.get("name") or "",
                "username": item.get("username"),
                "link": item.get("link"),
                "members": item.get("members") or item.get("participants_count"),
                "online": item.get("online") or item.get("online_count"),
                "is_public": bool(item.get("is_public", False)),
                "is_megagroup": bool(item.get("is_megagroup", False)),
                "is_broadcast": bool(item.get("is_broadcast", False)),
                "messages_restricted": bool(item.get("messages_restricted", False)),
                "source": item.get("source"),
            }
            title = payload["title"].strip()
            if not title:
                continue
            group = StoredGroup(**payload)
            parsed[self._make_key(group)] = group
        self._groups = parsed

    def save(self) -> None:
        with self.path.open("w", encoding="utf-8") as fp:
            json.dump([group.to_dict() for group in self._groups.values()], fp, indent=2, ensure_ascii=False)

    def add_groups(self, groups: Iterable[StoredGroup]) -> None:
        for group in groups:
            key = self._make_key(group)
            self._groups[key] = group
        self.save()

    def update_group(self, group: StoredGroup) -> None:
        key = self._make_key(group)
        self._groups[key] = group
        self.save()

    def remove_group(self, group: StoredGroup) -> None:
        key = self._make_key(group)
        if key in self._groups:
            del self._groups[key]
            self.save()

    def clear(self) -> None:
        self._groups = {}
        self.save()

    def get_groups(self) -> List[StoredGroup]:
        groups = list(self._groups.values())
        groups.sort(key=lambda grp: grp.title.lower())
        return groups

    def import_from_file(self, file_path: Path) -> None:
        if not file_path.exists():
            return
        with file_path.open("r", encoding="utf-8") as fp:
            data = json.load(fp)
        imported: List[StoredGroup] = []
        for item in data:
            if not isinstance(item, dict):
                continue
            payload = {
                "group_id": item.get("group_id") or item.get("id"),
                "access_hash": item.get("access_hash"),
                "title": item.get("title") or item.get("name") or "",
                "username": item.get("username"),
                "link": item.get("link"),
                "members": item.get("members") or item.get("participants_count"),
                "online": item.get("online") or item.get("online_count"),
                "is_public": bool(item.get("is_public", False)),
                "is_megagroup": bool(item.get("is_megagroup", False)),
                "is_broadcast": bool(item.get("is_broadcast", False)),
                "messages_restricted": bool(item.get("messages_restricted", False)),
                "source": item.get("source"),
            }
            title = payload["title"].strip()
            if not title:
                continue
            imported.append(StoredGroup(**payload))
        if imported:
            self.add_groups(imported)

    def export_to_file(self, file_path: Path) -> None:
        file_path.parent.mkdir(parents=True, exist_ok=True)
        with file_path.open("w", encoding="utf-8") as fp:
            json.dump([group.to_dict() for group in self.get_groups()], fp, indent=2, ensure_ascii=False)
