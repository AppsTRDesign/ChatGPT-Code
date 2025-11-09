"""Utility classes that coordinate Telethon client sessions."""
from __future__ import annotations

import asyncio
import json
from dataclasses import dataclass
from datetime import datetime, timedelta
from pathlib import Path
from typing import Dict, Iterable, List, Optional

from telethon import TelegramClient, errors, functions
from telethon.errors import FloodWaitError
from telethon.tl.types import (
    InputPeerUser,
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
CONFIG_FILE = Path("config.json")

DEFAULT_RATE_LIMITS = {
    "delay_between_actions": 2.0,
    "delay_between_sessions": 5.0,
}


def ensure_directories() -> None:
    SESSION_DIR.mkdir(exist_ok=True)
    USERS_DIR.mkdir(exist_ok=True)


@dataclass
class SessionInfo:
    phone: str
    path: Path


@dataclass
class RateLimits:
    delay_between_actions: float = DEFAULT_RATE_LIMITS["delay_between_actions"]
    delay_between_sessions: float = DEFAULT_RATE_LIMITS["delay_between_sessions"]

    @classmethod
    def load(cls) -> "RateLimits":
        if CONFIG_FILE.exists():
            try:
                data = json.loads(CONFIG_FILE.read_text(encoding="utf-8"))
                payload = data.get("rate_limits", data)
                return cls(
                    delay_between_actions=float(
                        payload.get("delay_between_actions", DEFAULT_RATE_LIMITS["delay_between_actions"])
                    ),
                    delay_between_sessions=float(
                        payload.get("delay_between_sessions", DEFAULT_RATE_LIMITS["delay_between_sessions"])
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
                users_iterable: Iterable[User]
                if expected is not None:
                    offset = offsets.get(session.phone, 0)
                    participants = await client.get_participants(
                        target,
                        limit=expected,
                        offset=offset,
                    )
                    users_iterable = getattr(participants, "users", participants)
                else:
                    participants = await client.get_participants(target)
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
        return {
            "id": user.id,
            "access_hash": getattr(user, "access_hash", None),
            "first_name": user.first_name,
            "last_name": user.last_name,
            "username": user.username,
            "phone": user.phone,
            "bot": user.bot,
            "is_verified": user.verified,
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
