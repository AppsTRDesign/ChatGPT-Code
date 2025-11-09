"""Utility classes that coordinate Telethon client sessions."""
from __future__ import annotations

import asyncio
import json
from dataclasses import dataclass
from datetime import datetime, timedelta
from pathlib import Path
from typing import Dict, Iterable, List, Optional, Sequence, Tuple

from telethon import TelegramClient, errors, functions
from telethon.errors import FloodWaitError
from telethon.tl.types import (
    Channel,
    ChannelForbidden,
    Chat,
    ChatForbidden,
    InputPeerUser,
    Message,
    User,
    UserStatusEmpty,
    UserStatusLastMonth,
    UserStatusLastWeek,
    UserStatusOffline,
    UserStatusOnline,
    UserStatusRecently,
)

SESSION_DIR = Path("session")
USERS_DIR = Path("users")
GROUPS_DIR = Path("groups")
CONFIG_FILE = Path("config.json")

DEFAULT_RATE_LIMITS = {
    "delay_between_actions": 2.0,
    "delay_between_sessions": 5.0,
    "delay_between_messages": 4.0,
    "delay_between_group_messages": 6.0,
}


def ensure_directories() -> None:
    SESSION_DIR.mkdir(exist_ok=True)
    USERS_DIR.mkdir(exist_ok=True)
    GROUPS_DIR.mkdir(exist_ok=True)


@dataclass
class SessionInfo:
    phone: str
    path: Path


@dataclass
class RateLimits:
    delay_between_actions: float = DEFAULT_RATE_LIMITS["delay_between_actions"]
    delay_between_sessions: float = DEFAULT_RATE_LIMITS["delay_between_sessions"]
    delay_between_messages: float = DEFAULT_RATE_LIMITS["delay_between_messages"]
    delay_between_group_messages: float = DEFAULT_RATE_LIMITS[
        "delay_between_group_messages"
    ]

    @classmethod
    def load(cls) -> "RateLimits":
        if CONFIG_FILE.exists():
            try:
                data = json.loads(CONFIG_FILE.read_text(encoding="utf-8"))
                payload = data.get("rate_limits", data)
                return cls(
                    delay_between_actions=float(
                        payload.get(
                            "delay_between_actions",
                            DEFAULT_RATE_LIMITS["delay_between_actions"],
                        )
                    ),
                    delay_between_sessions=float(
                        payload.get(
                            "delay_between_sessions",
                            DEFAULT_RATE_LIMITS["delay_between_sessions"],
                        )
                    ),
                    delay_between_messages=float(
                        payload.get(
                            "delay_between_messages",
                            DEFAULT_RATE_LIMITS["delay_between_messages"],
                        )
                    ),
                    delay_between_group_messages=float(
                        payload.get(
                            "delay_between_group_messages",
                            DEFAULT_RATE_LIMITS["delay_between_group_messages"],
                        )
                    ),
                )
            except (ValueError, OSError):
                pass
        return cls()

    def save(self) -> None:
        data: Dict[str, object] = {}
        if CONFIG_FILE.exists():
            try:
                data = json.loads(CONFIG_FILE.read_text(encoding="utf-8"))
            except (ValueError, OSError):
                data = {}
        data["rate_limits"] = {
            "delay_between_actions": self.delay_between_actions,
            "delay_between_sessions": self.delay_between_sessions,
            "delay_between_messages": self.delay_between_messages,
            "delay_between_group_messages": self.delay_between_group_messages,
        }
        CONFIG_FILE.write_text(json.dumps(data, indent=2), encoding="utf-8")


@dataclass
class FloodInfo:
    seconds: int
    message: str


class TelethonManager:
    """Creates and manages Telethon client sessions."""

    def __init__(self, api_id: int, api_hash: str, loop: asyncio.AbstractEventLoop) -> None:
        ensure_directories()
        self.api_id = api_id
        self.api_hash = api_hash
        self.loop = loop
        self.rate_limits = RateLimits.load()

    def list_sessions(self) -> List[SessionInfo]:
        sessions: List[SessionInfo] = []
        for path in SESSION_DIR.glob("*.session"):
            sessions.append(SessionInfo(phone=path.stem, path=path))
        return sorted(sessions, key=lambda s: s.phone)

    def _client(self, session: SessionInfo) -> TelegramClient:
        return TelegramClient(session.path, self.api_id, self.api_hash, loop=self.loop)

    async def send_login_code(self, phone: str) -> TelegramClient:
        session = SessionInfo(phone=phone, path=SESSION_DIR / f"{phone}.session")
        client = self._client(session)
        await client.connect()
        await client.send_code_request(phone)
        return client

    async def complete_sign_in(
        self,
        client: TelegramClient,
        phone: str,
        code: str,
        password: Optional[str] = None,
    ) -> None:
        try:
            if password:
                await client.sign_in(phone=phone, code=code)
                await client.sign_in(password=password)
            else:
                await client.sign_in(phone=phone, code=code)
        finally:
            await client.disconnect()

    async def validate_session(self, session: SessionInfo) -> bool:
        try:
            async with self._client(session) as client:
                me = await client.get_me()
                return bool(me)
        except (errors.UserDeactivatedBanError, errors.PhoneNumberBannedError):
            try:
                session.path.unlink(missing_ok=True)
            except OSError:
                pass
            return False
        except Exception:
            return False

    async def fetch_group_info(self, session: SessionInfo, target: str) -> tuple[str, int]:
        async with self._client(session) as client:
            entity = await client.get_entity(target)
            title = getattr(entity, "title", getattr(entity, "username", str(target)))
            participants = await client.get_participants(target, limit=0)
            count = getattr(participants, "total", len(participants))
            return title, int(count)

    async def scan_members(
        self,
        sessions: Iterable[SessionInfo],
        target: str,
        timeframe: Optional[timedelta],
        limit: Optional[int],
        update_cb,
        stop_event: asyncio.Event,
        total_members: Optional[int] = None,
    ) -> List[Dict[str, object]]:
        results: List[Dict[str, object]] = []
        sessions_list = list(sessions)
        portions = self._portion_counts_sessions(
            sessions_list,
            limit if limit else total_members,
        )
        offsets: Dict[str, int] = {}
        running_offset = 0
        for session in sessions_list:
            expected = portions.get(session.phone)
            offsets[session.phone] = running_offset
            if expected:
                running_offset += expected

        async def process(session: SessionInfo, expected: Optional[int]) -> None:
            processed = 0
            total = expected or 0
            client = self._client(session)
            update_cb(session, processed, total, "running", "")
            try:
                await client.connect()
                try:
                    entity, joined = await self._prepare_target(client, target)
                    if joined:
                        update_cb(session, processed, total, "running", "__joined__")
                except Exception as join_exc:
                    update_cb(
                        session,
                        processed,
                        total,
                        "error",
                        f"__join_error__:{join_exc}",
                    )
                    return
                users_iterable: Iterable[User]
                if expected is not None:
                    offset = offsets.get(session.phone, 0)
                    participants = await client.get_participants(
                        entity,
                        limit=expected,
                        offset=offset,
                    )
                    users_iterable = getattr(participants, "users", participants)
                else:
                    participants = await client.get_participants(entity)
                    users_iterable = getattr(participants, "users", participants)
                for user in users_iterable:
                    if stop_event.is_set():
                        update_cb(session, processed, total, "stopped", "")
                        break
                    if timeframe and not self._within_timeframe(user, timeframe):
                        continue
                    results.append(self._serialize_user(user))
                    processed += 1
                    update_cb(session, processed, total, "running", f"{user.id}")
                    await asyncio.sleep(self.rate_limits.delay_between_actions)
                    if expected and processed >= expected:
                        break
                if not stop_event.is_set():
                    update_cb(session, processed, total, "completed", "")
            except FloodWaitError as exc:
                update_cb(
                    session,
                    processed,
                    total,
                    "running",
                    "",
                    FloodInfo(seconds=exc.seconds, message=str(exc)),
                )
                await asyncio.sleep(exc.seconds)
            except Exception as exc:
                update_cb(session, processed, total, "error", str(exc))
            finally:
                await client.disconnect()

        await asyncio.gather(
            *[
                process(session, portions.get(session.phone))
                for session in sessions_list
            ]
        )
        return results

    async def add_members(
        self,
        sessions: Iterable[SessionInfo],
        target: str,
        users: List[Dict[str, object]],
        update_cb,
        stop_event: asyncio.Event,
    ) -> List[int]:
        sessions_list = list(sessions)
        assignments = self._split_users(sessions_list, users)
        portions = self._portion_counts_sessions(sessions_list, len(users))
        processed_ids: List[int] = []
        lock = asyncio.Lock()

        async def process(session: SessionInfo, expected: Optional[int]) -> None:
            processed = 0
            user_list = assignments.get(session.phone, [])
            total = expected or len(user_list)
            client = self._client(session)
            update_cb(session, processed, total, "running", "")
            try:
                await client.connect()
                entity = await client.get_entity(target)
                index = 0
                while index < len(user_list):
                    if stop_event.is_set():
                        update_cb(session, processed, total, "stopped", "")
                        break
                    user = user_list[index]
                    try:
                        if user.get("access_hash"):
                            input_user = InputPeerUser(int(user["id"]), int(user["access_hash"]))
                        else:
                            input_user = await client.get_input_entity(int(user["id"]))
                        await client(
                            functions.channels.InviteToChannelRequest(entity, [input_user])
                        )
                        processed += 1
                        update_cb(session, processed, total, "running", str(user["id"]))
                        async with lock:
                            processed_ids.append(int(user["id"]))
                        index += 1
                    except FloodWaitError as exc:
                        update_cb(
                            session,
                            processed,
                            total,
                            "running",
                            "",
                            FloodInfo(seconds=exc.seconds, message=str(exc)),
                        )
                        await asyncio.sleep(exc.seconds)
                    except errors.UserAlreadyParticipantError:
                        update_cb(session, processed, total, "running", "already")
                        async with lock:
                            processed_ids.append(int(user["id"]))
                        index += 1
                    except Exception as exc:
                        update_cb(session, processed, total, "error", str(exc))
                        index += 1
                    await asyncio.sleep(self.rate_limits.delay_between_actions)
                if not stop_event.is_set():
                    update_cb(session, processed, total, "completed", "")
            finally:
                await client.disconnect()

        await asyncio.gather(
            *[
                process(session, portions.get(session.phone))
                for session in sessions_list
            ]
        )
        return processed_ids

    async def search_groups(
        self,
        sessions: Sequence[SessionInfo],
        keywords: Sequence[str],
        limit: Optional[int],
        visibility: str,
        update_cb,
        stop_event: asyncio.Event,
    ) -> List[Dict[str, object]]:
        sessions_list = list(sessions)
        if not sessions_list:
            return []
        keywords = [kw.strip() for kw in keywords if kw.strip()]
        if not keywords:
            return []
        limit = limit if limit and limit > 0 else None
        portions = self._portion_counts_sessions(sessions_list, limit)
        results: List[Dict[str, object]] = []
        seen: set[Tuple[int, str]] = set()
        lock = asyncio.Lock()

        async def process(session: SessionInfo, expected: Optional[int]) -> None:
            processed = 0
            total = expected or (limit or 0)
            client = self._client(session)
            update_cb(session, processed, total, "running", "")
            try:
                await client.connect()
                for keyword in keywords:
                    if stop_event.is_set():
                        update_cb(session, processed, total, "stopped", "")
                        break
                    try:
                        async for dialog in client.iter_dialogs(
                            search=keyword,
                            limit=expected or limit or 300,
                        ):
                            if stop_event.is_set():
                                break
                            chat = dialog.entity
                            if not isinstance(chat, (Channel, Chat)):
                                continue
                            if isinstance(chat, (ChannelForbidden, ChatForbidden)):
                                continue
                            is_public = bool(getattr(chat, "username", None))
                            if visibility == "public" and not is_public:
                                continue
                            if visibility == "private" and is_public:
                                continue
                            identifier = (int(chat.id), chat.__class__.__name__)
                            async with lock:
                                if identifier in seen:
                                    continue
                                if limit and len(results) >= limit:
                                    break
                                seen.add(identifier)
                            info = await self._enrich_chat(client, chat)
                            async with lock:
                                results.append(info)
                            processed += 1
                            update_cb(
                                session,
                                processed,
                                total,
                                "running",
                                info,
                            )
                            await asyncio.sleep(self.rate_limits.delay_between_actions)
                            if expected and processed >= expected:
                                break
                    except FloodWaitError as exc:
                        update_cb(
                            session,
                            processed,
                            total,
                            "running",
                            "",
                            FloodInfo(seconds=exc.seconds, message=str(exc)),
                        )
                        await asyncio.sleep(exc.seconds)
                        continue
                    if expected and processed >= expected:
                        break
                if not stop_event.is_set():
                    update_cb(session, processed, total, "completed", "")
            except Exception as exc:
                update_cb(session, processed, total, "error", str(exc))
            finally:
                await client.disconnect()

        await asyncio.gather(
            *[
                process(session, portions.get(session.phone))
                for session in sessions_list
            ]
        )
        return results

    async def search_users(
        self,
        sessions: Sequence[SessionInfo],
        keywords: Sequence[str],
        limit: Optional[int],
        visibility: str,
        update_cb,
        stop_event: asyncio.Event,
    ) -> List[Dict[str, object]]:
        sessions_list = list(sessions)
        if not sessions_list:
            return []
        keywords = [kw.strip() for kw in keywords if kw.strip()]
        if not keywords:
            return []
        limit = limit if limit and limit > 0 else None
        portions = self._portion_counts_sessions(sessions_list, limit)
        results: List[Dict[str, object]] = []
        seen_ids: set[int] = set()
        lock = asyncio.Lock()

        async def process(session: SessionInfo, expected: Optional[int]) -> None:
            processed = 0
            total = expected or (limit or 0)
            client = self._client(session)
            update_cb(session, processed, total, "running", "")
            try:
                await client.connect()
                for keyword in keywords:
                    if stop_event.is_set():
                        update_cb(session, processed, total, "stopped", "")
                        break
                    try:
                        response = await client(
                            functions.contacts.SearchRequest(
                                q=keyword,
                                limit=expected or limit or 100,
                            )
                        )
                    except FloodWaitError as exc:
                        update_cb(
                            session,
                            processed,
                            total,
                            "running",
                            "",
                            FloodInfo(seconds=exc.seconds, message=str(exc)),
                        )
                        await asyncio.sleep(exc.seconds)
                        continue
                    for user in response.users:
                        if stop_event.is_set():
                            break
                        if not isinstance(user, User):
                            continue
                        if user.bot:
                            continue
                        is_public = bool(getattr(user, "username", None))
                        if visibility == "public" and not is_public:
                            continue
                        if visibility == "private" and is_public:
                            continue
                        async with lock:
                            if user.id in seen_ids:
                                continue
                            if limit and len(results) >= limit:
                                break
                            seen_ids.add(user.id)
                        info = self._serialize_user(user)
                        async with lock:
                            results.append(info)
                        processed += 1
                        update_cb(
                            session,
                            processed,
                            total,
                            "running",
                            info,
                        )
                        await asyncio.sleep(self.rate_limits.delay_between_actions)
                        if expected and processed >= expected:
                            break
                    if expected and processed >= expected:
                        break
                if not stop_event.is_set():
                    update_cb(session, processed, total, "completed", "")
            except Exception as exc:
                update_cb(session, processed, total, "error", str(exc))
            finally:
                await client.disconnect()

        await asyncio.gather(
            *[
                process(session, portions.get(session.phone))
                for session in sessions_list
            ]
        )
        return results

    async def scan_active_senders(
        self,
        sessions: Iterable[SessionInfo],
        target: str,
        timeframe: Optional[timedelta],
        limit: Optional[int],
        update_cb,
        stop_event: asyncio.Event,
    ) -> List[Dict[str, object]]:
        sessions_list = list(sessions)
        if not sessions_list:
            return []
        limit = limit if limit and limit > 0 else None
        portions = self._portion_counts_sessions(sessions_list, limit)
        cutoff = datetime.utcnow() - timeframe if timeframe else None
        results: List[Dict[str, object]] = []
        seen_ids: set[int] = set()
        lock = asyncio.Lock()

        async def process(session: SessionInfo, expected: Optional[int]) -> None:
            processed = 0
            total = expected or (limit or 0)
            client = self._client(session)
            update_cb(session, processed, total, "running", "")
            try:
                await client.connect()
                try:
                    entity, joined = await self._prepare_target(client, target)
                    if joined:
                        update_cb(session, processed, total, "running", "__joined__")
                except Exception as join_exc:
                    update_cb(
                        session,
                        processed,
                        total,
                        "error",
                        f"__join_error__:{join_exc}",
                    )
                    return
                async for message in client.iter_messages(entity):
                    if stop_event.is_set():
                        update_cb(session, processed, total, "stopped", "")
                        break
                    if not isinstance(message, Message):
                        continue
                    if cutoff and message.date and message.date < cutoff:
                        break
                    if not message.sender_id:
                        continue
                    sender = message.sender
                    if sender is None:
                        try:
                            sender = await client.get_entity(message.sender_id)
                        except Exception:
                            continue
                    if not isinstance(sender, User):
                        continue
                    async with lock:
                        if sender.id in seen_ids:
                            continue
                        seen_ids.add(sender.id)
                        serialized = self._serialize_user(sender)
                        serialized["last_message_date"] = (
                            message.date.isoformat() if message.date else None
                        )
                        results.append(serialized)
                    processed += 1
                    update_cb(session, processed, total, "running", str(sender.id))
                    await asyncio.sleep(self.rate_limits.delay_between_actions)
                    if expected and processed >= expected:
                        break
                if not stop_event.is_set():
                    update_cb(session, processed, total, "completed", "")
            except FloodWaitError as exc:
                update_cb(
                    session,
                    processed,
                    total,
                    "running",
                    "",
                    FloodInfo(seconds=exc.seconds, message=str(exc)),
                )
                await asyncio.sleep(exc.seconds)
            except Exception as exc:
                update_cb(session, processed, total, "error", str(exc))
            finally:
                await client.disconnect()

        await asyncio.gather(
            *[
                process(session, portions.get(session.phone))
                for session in sessions_list
            ]
        )
        return results

    async def send_direct_messages(
        self,
        sessions: Iterable[SessionInfo],
        users: List[Dict[str, object]],
        message: str,
        media_path: Optional[Path],
        link_preview: bool,
        update_cb,
        stop_event: asyncio.Event,
    ) -> None:
        sessions_list = list(sessions)
        assignments = self._split_users(sessions_list, users)
        portions = self._portion_counts_sessions(sessions_list, len(users))

        async def process(session: SessionInfo, expected: Optional[int]) -> None:
            processed = 0
            user_list = assignments.get(session.phone, [])
            total = expected or len(user_list)
            client = self._client(session)
            update_cb(session, processed, total, "running", "")
            try:
                await client.connect()
                for user in user_list:
                    if stop_event.is_set():
                        update_cb(session, processed, total, "stopped", "")
                        break
                    try:
                        if user.get("access_hash"):
                            peer = InputPeerUser(
                                int(user["id"]), int(user["access_hash"])
                            )
                        else:
                            peer = await client.get_input_entity(int(user["id"]))
                    except Exception as exc:
                        update_cb(session, processed, total, "error", str(exc))
                        continue
                    try:
                        if media_path:
                            await client.send_file(
                                peer,
                                file=str(media_path),
                                caption=message,
                            )
                        else:
                            await client.send_message(
                                peer,
                                message,
                                link_preview=link_preview,
                            )
                        processed += 1
                        update_cb(
                            session,
                            processed,
                            total,
                            "running",
                            str(user.get("id")),
                        )
                    except FloodWaitError as exc:
                        update_cb(
                            session,
                            processed,
                            total,
                            "running",
                            "",
                            FloodInfo(seconds=exc.seconds, message=str(exc)),
                        )
                        await asyncio.sleep(exc.seconds)
                        continue
                    except errors.PeerFloodError as exc:
                        update_cb(session, processed, total, "error", str(exc))
                        break
                    except Exception as exc:
                        update_cb(session, processed, total, "error", str(exc))
                    await asyncio.sleep(self.rate_limits.delay_between_messages)
                if not stop_event.is_set():
                    update_cb(session, processed, total, "completed", "")
            finally:
                await client.disconnect()

        await asyncio.gather(
            *[
                process(session, portions.get(session.phone))
                for session in sessions_list
            ]
        )

    async def send_group_messages(
        self,
        sessions: Iterable[SessionInfo],
        groups: List[Dict[str, object]],
        message: str,
        media_path: Optional[Path],
        link_preview: bool,
        update_cb,
        stop_event: asyncio.Event,
    ) -> None:
        sessions_list = list(sessions)
        assignments = self._split_users(sessions_list, groups)
        portions = self._portion_counts_sessions(sessions_list, len(groups))

        async def process(session: SessionInfo, expected: Optional[int]) -> None:
            processed = 0
            group_list = assignments.get(session.phone, [])
            total = expected or len(group_list)
            client = self._client(session)
            update_cb(session, processed, total, "running", "")
            try:
                await client.connect()
                joined_entities: List[Tuple[Dict[str, object], object]] = []
                join_progress = 0
                update_cb(session, join_progress, total, "joining", "")
                for group in group_list:
                    if stop_event.is_set():
                        update_cb(session, join_progress, total, "stopped", "")
                        break
                    identifier = group.get("username") or group.get("id")
                    if not identifier:
                        continue
                    display_name = group.get("title") or str(identifier)
                    try:
                        entity, joined = await self._prepare_target(client, identifier)
                        joined_entities.append((group, entity))
                        join_progress += 1
                        status_key = "__join_new__" if joined else "__join_existing__"
                        update_cb(
                            session,
                            join_progress,
                            total,
                            "joining",
                            f"{status_key}:{display_name}",
                        )
                    except FloodWaitError as exc:
                        update_cb(
                            session,
                            join_progress,
                            total,
                            "joining",
                            "",
                            FloodInfo(seconds=exc.seconds, message=str(exc)),
                        )
                        await asyncio.sleep(exc.seconds)
                    except Exception as exc:
                        update_cb(
                            session,
                            join_progress,
                            total,
                            "error",
                            f"{display_name}:{exc}",
                        )
                    await asyncio.sleep(self.rate_limits.delay_between_actions)
                    if expected and join_progress >= expected:
                        break
                if stop_event.is_set():
                    return
                send_total = len(joined_entities)
                processed = 0
                update_cb(session, processed, send_total, "running", "__sending__")
                for group, entity in joined_entities:
                    if stop_event.is_set():
                        update_cb(session, processed, send_total, "stopped", "")
                        break
                    display_name = (
                        group.get("title")
                        or group.get("username")
                        or str(group.get("id"))
                    )
                    try:
                        if media_path:
                            await client.send_file(
                                entity,
                                file=str(media_path),
                                caption=message,
                            )
                        else:
                            await client.send_message(
                                entity,
                                message,
                                link_preview=link_preview,
                            )
                        processed += 1
                        update_cb(
                            session,
                            processed,
                            send_total,
                            "running",
                            str(group.get("title", identifier)),
                        )
                    except FloodWaitError as exc:
                        update_cb(
                            session,
                            processed,
                            send_total,
                            "running",
                            "",
                            FloodInfo(seconds=exc.seconds, message=str(exc)),
                        )
                        await asyncio.sleep(exc.seconds)
                        continue
                    except Exception as exc:
                        update_cb(
                            session,
                            processed,
                            send_total,
                            "error",
                            f"{display_name}:{exc}",
                        )
                        await asyncio.sleep(self.rate_limits.delay_between_group_messages)
                if not stop_event.is_set():
                    update_cb(session, processed, send_total, "completed", "")
            finally:
                await client.disconnect()

        await asyncio.gather(
            *[
                process(session, portions.get(session.phone))
                for session in sessions_list
            ]
        )

    @staticmethod
    def _within_timeframe(user: User, timeframe: timedelta) -> bool:
        status = user.status
        if status is None or isinstance(status, (UserStatusEmpty,)):
            return False
        now = datetime.utcnow()
        if isinstance(status, UserStatusRecently):
            return timeframe >= timedelta(days=2)
        if isinstance(status, UserStatusLastWeek):
            return timeframe >= timedelta(days=7)
        if isinstance(status, UserStatusLastMonth):
            return timeframe >= timedelta(days=30)
        if isinstance(status, UserStatusOnline):
            return True
        if isinstance(status, UserStatusOffline):
            last_online = datetime.utcfromtimestamp(status.was_online)
            return now - last_online <= timeframe
        return False

    @staticmethod
    def _serialize_user(user: User) -> Dict[str, object]:
        status = getattr(user, "status", None)
        status_name = status.__class__.__name__ if status else None
        return {
            "id": user.id,
            "access_hash": getattr(user, "access_hash", None),
            "first_name": user.first_name,
            "last_name": user.last_name,
            "username": user.username,
            "phone": user.phone,
            "bot": user.bot,
            "is_verified": user.verified,
            "is_scam": getattr(user, "scam", False),
            "is_fake": getattr(user, "fake", False),
            "is_restricted": getattr(user, "restricted", False),
            "mutual_contact": getattr(user, "mutual_contact", False),
            "language_code": getattr(user, "lang_code", None),
            "status": status_name,
        }

    @staticmethod
    def _portion_counts_sessions(
        sessions: List[SessionInfo], total: Optional[int]
    ) -> Dict[str, Optional[int]]:
        if not sessions:
            return {}
        if not total or total <= 0:
            return {session.phone: None for session in sessions}
        base = total // len(sessions)
        remainder = total % len(sessions)
        portions: Dict[str, Optional[int]] = {}
        for index, session in enumerate(sessions):
            portion = base + (1 if index < remainder else 0)
            portions[session.phone] = portion
        return portions

    @staticmethod
    def _split_users(
        sessions: List[SessionInfo], users: List[Dict[str, object]]
    ) -> Dict[str, List[Dict[str, object]]]:
        if not sessions:
            return {}
        assignments: Dict[str, List[Dict[str, object]]] = {session.phone: [] for session in sessions}
        index = 0
        for user in users:
            session = sessions[index % len(sessions)]
            assignments[session.phone].append(user)
            index += 1
        return assignments

    async def _prepare_target(
        self, client: TelegramClient, target: object
    ) -> Tuple[object, bool]:
        entity = await client.get_entity(target)
        joined = False
        if isinstance(entity, Channel):
            try:
                await client(functions.channels.JoinChannelRequest(entity))
                joined = True
                entity = await client.get_entity(target)
            except errors.UserAlreadyParticipantError:
                joined = False
            except errors.InviteHashExpiredError:
                raise
            except errors.InviteHashInvalidError:
                raise
            except errors.ChannelPrivateError:
                raise
            except errors.ChatAdminRequiredError:
                pass
        elif isinstance(entity, ChannelForbidden):
            raise errors.ChannelPrivateError(target)
        elif isinstance(entity, Chat):
            if getattr(entity, "left", False):
                raise RuntimeError("Session must be invited to the target chat")
        elif isinstance(entity, ChatForbidden):
            raise RuntimeError("Session cannot access the target chat")
        return entity, joined

    async def _enrich_chat(
        self, client: TelegramClient, chat: object
    ) -> Dict[str, object]:
        info: Dict[str, object] = {
            "id": getattr(chat, "id", None),
            "access_hash": getattr(chat, "access_hash", None),
            "title": getattr(chat, "title", ""),
            "username": getattr(chat, "username", None),
            "is_public": bool(getattr(chat, "username", None)),
            "megagroup": getattr(chat, "megagroup", False),
            "participants": getattr(chat, "participants_count", None),
            "country": None,
            "link": None,
        }
        if info["username"]:
            info["link"] = f"https://t.me/{info['username']}"
        try:
            if isinstance(chat, Channel):
                full = await client(functions.channels.GetFullChannelRequest(chat))
                info["participants"] = getattr(full.full_chat, "participants_count", info["participants"])
                about = getattr(full.full_chat, "about", None)
                if about:
                    info["about"] = about
                location = getattr(full.full_chat, "location", None)
                address = getattr(location, "address", None) if location else None
                if isinstance(address, str):
                    info["country"] = address
                elif address is not None:
                    info["country"] = getattr(address, "country", None)
            elif isinstance(chat, Chat):
                full = await client(functions.messages.GetFullChatRequest(chat.id))
                info["participants"] = getattr(full.full_chat, "participants_count", info["participants"])
                about = getattr(full.full_chat, "about", None)
                if about:
                    info["about"] = about
        except Exception:
            pass
        return info
