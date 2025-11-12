from __future__ import annotations

import asyncio
import logging
from dataclasses import dataclass
from datetime import datetime, timedelta, timezone
from typing import Callable, Dict, Iterable, List, Optional, Tuple

from telethon import TelegramClient
from telethon.errors import (
    FloodWaitError,
    PeerFloodError,
    UserIdInvalidError,
    UserPrivacyRestrictedError,
)
from telethon.tl import functions, types

from app.core.settings import AppSettings
from app.data.user_storage import StoredUser, UserStorage
from app.i18n.strings import translator

logger = logging.getLogger(__name__)


@dataclass
class ProgressUpdate:
    session_name: str
    processed: int
    total: int
    user: Optional[StoredUser] = None
    status: Optional[str] = None


class TaskCancelled(Exception):
    pass


class SessionTask:
    def __init__(
        self,
        session_name: str,
        client: TelegramClient,
        settings: AppSettings,
        storage: UserStorage,
    ) -> None:
        self.session_name = session_name
        self.client = client
        self.settings = settings
        self.storage = storage
        self._cancel_event = asyncio.Event()
        self._processed = 0
        self._total = 0

    def cancel(self) -> None:
        self._cancel_event.set()

    async def _check_cancelled(self) -> None:
        if self._cancel_event.is_set():
            raise TaskCancelled()

    def build_progress(
        self, total: int, user: Optional[StoredUser] = None, status: Optional[str] = None
    ) -> ProgressUpdate:
        if total:
            self._total = total
        self._processed += 1
        if not self._total or self._processed > self._total:
            self._total = self._processed
        return ProgressUpdate(
            session_name=self.session_name,
            processed=self._processed,
            total=self._total,
            user=user,
            status=status,
        )

    async def _throttle(self, interval: float) -> None:
        await asyncio.sleep(max(interval, 0.1))

    async def scan_members(
        self,
        entity: str,
        limit: Optional[int],
        active_within: Optional[timedelta],
        progress_callback: Callable[[ProgressUpdate], None],
        persist: bool,
        status_callback: Optional[Callable[[str], None]] = None,
        include_no_username: bool = True,
    ) -> None:
        await self._check_cancelled()
        status_cb = status_callback or (lambda _: None)
        status_cb("status.running")
        total = limit or 0
        processed = 0
        iterator = self.client.iter_participants(entity, limit=limit)
        seen_ids: set[int] = set()
        while True:
            await self._check_cancelled()
            try:
                participant = await iterator.__anext__()
            except StopAsyncIteration:
                break
            except FloodWaitError as exc:
                await self._handle_flood_wait(exc.seconds, status_cb)
                await self._throttle(self.settings.rate_limit.scan_interval)
                continue
            if participant.id in seen_ids:
                continue
            try:
                user = await self.client.get_entity(participant.id)
            except FloodWaitError as exc:
                await self._handle_flood_wait(exc.seconds, status_cb)
                await self._throttle(self.settings.rate_limit.scan_interval)
                continue
            if getattr(user, "bot", False):
                continue
            if not include_no_username and not getattr(user, "username", None):
                continue
            seen_ids.add(participant.id)
            last_seen = None
            if isinstance(user.status, types.UserStatusOffline):
                was_online = user.status.was_online
                if isinstance(was_online, datetime):
                    last_seen = was_online.astimezone(timezone.utc)
                else:
                    last_seen = datetime.fromtimestamp(was_online, tz=timezone.utc)
            elif isinstance(user.status, (types.UserStatusOnline, types.UserStatusRecently)):
                last_seen = datetime.now(tz=timezone.utc)
            elif isinstance(user.status, types.UserStatusLastMonth):
                last_seen = datetime.now(tz=timezone.utc) - timedelta(days=30)
            elif isinstance(user.status, types.UserStatusLastWeek):
                last_seen = datetime.now(tz=timezone.utc) - timedelta(days=7)

            if active_within:
                if not last_seen:
                    continue
                now_utc = datetime.now(tz=timezone.utc)
                if last_seen < now_utc - active_within:
                    continue

            access_hash = getattr(user, "access_hash", None)
            if access_hash is None:
                try:
                    input_entity = await self.client.get_input_entity(types.PeerUser(user.id))
                except FloodWaitError as exc:
                    await self._handle_flood_wait(exc.seconds, status_cb)
                    await self._throttle(self.settings.rate_limit.scan_interval)
                    continue
                except (TypeError, ValueError):
                    try:
                        input_entity = await self.client.get_input_entity(user.id)
                    except (TypeError, ValueError):
                        input_entity = None
                if isinstance(input_entity, (types.InputPeerUser, types.InputUser)):
                    access_hash = input_entity.access_hash

            stored = StoredUser(
                user_id=user.id,
                username=user.username,
                phone=user.phone,
                access_hash=access_hash,
                first_name=user.first_name,
                last_name=user.last_name,
                last_seen=None,
                status=type(user.status).__name__ if user.status else None,
                source=str(entity),
                is_bot=bool(getattr(user, "bot", False)),
                last_seen_utc=UserStorage.to_iso(last_seen),
                last_message=None,
            )
            stored = self.storage.prepare_user(stored)
            if persist:
                self.storage.add_users([stored])
            processed += 1
            progress_callback(self.build_progress(total or processed, stored, status="status.running"))
            if limit and processed >= limit:
                break
            await self._throttle(self.settings.rate_limit.scan_interval)
        logger.info("log.scan_finished")

    async def add_members(
        self,
        entity: str,
        users: Iterable[StoredUser],
        progress_callback: Callable[[ProgressUpdate], None],
        status_callback: Optional[Callable[[str], None]] = None,
    ) -> None:
        users_list = list(users)
        total = len(users_list)
        status_cb = status_callback or (lambda _: None)
        status_cb("status.running")
        for user in users_list:
            await self._check_cancelled()
            if user.is_bot:
                progress_callback(self.build_progress(total or 1, user, status="status.skipped_bot"))
                self.storage.remove_user(user.user_id)
                continue
            input_user = await self._resolve_input_user(user)
            if input_user is None:
                logger.warning("log.member_input_missing")
                progress_callback(self.build_progress(total or 1, user, status="status.error"))
                continue
            try:
                await self.client(
                    functions.channels.InviteToChannelRequest(
                        channel=entity,
                        users=[input_user],
                    )
                )
                progress_callback(self.build_progress(total, user, status="status.running"))
                self.storage.remove_user(user.user_id)
            except UserPrivacyRestrictedError:
                progress_callback(self.build_progress(total, user, status="status.error"))
            except UserIdInvalidError:
                logger.warning("log.member_input_invalid")
                progress_callback(self.build_progress(total, user, status="status.error"))
            except FloodWaitError as exc:
                await self._handle_flood_wait(exc.seconds, status_cb)
                await self._throttle(self.settings.rate_limit.join_interval)
                continue
            except PeerFloodError:
                status_cb("status.peer_flood")
                total_value = total or self._total or max(self._processed, 1)
                progress_callback(
                    ProgressUpdate(
                        session_name=self.session_name,
                        processed=self._processed,
                        total=total_value,
                        user=user,
                        status="status.peer_flood",
                    )
                )
                logger.warning("log.peer_flood")
                break
            await self._throttle(self.settings.rate_limit.join_interval)
        logger.info("log.add_finished")

    async def fetch_active_senders(
        self,
        entity: str,
        limit: Optional[int],
        active_within: Optional[timedelta],
        progress_callback: Callable[[ProgressUpdate], None],
        persist: bool,
        status_callback: Optional[Callable[[str], None]] = None,
        include_no_username: bool = True,
    ) -> None:
        cutoff = datetime.now(tz=timezone.utc) - active_within if active_within else None
        messages = self.client.iter_messages(entity, limit=limit)
        processed = 0
        total = limit or 0
        seen_users: set[int] = set()
        status_cb = status_callback or (lambda _: None)
        status_cb("status.running")
        while True:
            await self._check_cancelled()
            try:
                message = await messages.__anext__()
            except StopAsyncIteration:
                break
            except FloodWaitError as exc:
                await self._handle_flood_wait(exc.seconds, status_cb)
                await self._throttle(self.settings.rate_limit.scan_interval)
                continue
            if cutoff and message.date:
                message_dt = message.date.replace(tzinfo=timezone.utc)
                if message_dt < cutoff:
                    break
            if not message.sender_id:
                continue
            if isinstance(message.sender_id, int):
                sender_key = message.sender_id
            elif isinstance(message.sender_id, (types.PeerUser,)):
                sender_key = getattr(message.sender_id, "user_id", None)
            elif isinstance(message.sender_id, (types.PeerChannel, types.PeerChat)):
                sender_key = getattr(message.sender_id, "channel_id", None) or getattr(message.sender_id, "chat_id", None)
            else:
                sender_key = None
            if sender_key and sender_key in seen_users:
                continue
            try:
                sender = await message.get_sender()
            except FloodWaitError as exc:
                await self._handle_flood_wait(exc.seconds, status_cb)
                await self._throttle(self.settings.rate_limit.scan_interval)
                continue
            except (TypeError, ValueError):
                continue
            if sender is None or not isinstance(sender, types.User):
                continue
            if getattr(sender, "bot", False):
                continue
            if sender.id in seen_users:
                continue
            if not include_no_username and not getattr(sender, "username", None):
                continue
            seen_users.add(sender.id)
            access_hash = getattr(sender, "access_hash", None)
            if access_hash is None:
                try:
                    input_peer = await self.client.get_input_entity(types.PeerUser(sender.id))
                except FloodWaitError as exc:
                    await self._handle_flood_wait(exc.seconds, status_cb)
                    await self._throttle(self.settings.rate_limit.scan_interval)
                    continue
                except (TypeError, ValueError):
                    try:
                        input_peer = await self.client.get_input_entity(sender.id)
                    except (TypeError, ValueError):
                        input_peer = None
                if isinstance(input_peer, (types.InputPeerUser, types.InputUser)):
                    access_hash = input_peer.access_hash
                else:
                    logger.warning("log.active_sender_missing_entity")
                    continue
            stored = StoredUser(
                user_id=sender.id,
                username=sender.username,
                phone=getattr(sender, "phone", None),
                access_hash=access_hash,
                first_name=sender.first_name,
                last_name=sender.last_name,
                last_seen=None,
                status=type(sender.status).__name__ if getattr(sender, "status", None) else None,
                source=str(entity),
                is_bot=False,
                last_seen_utc=UserStorage.to_iso(message.date.replace(tzinfo=timezone.utc) if message.date else None),
                last_message=(message.message or "") if getattr(message, "message", None) else None,
            )
            stored = self.storage.prepare_user(stored)
            if persist:
                self.storage.add_users([stored])
            elif stored.access_hash and hasattr(self.storage, "has_user") and self.storage.has_user(stored.user_id):
                self.storage.update_user(stored)
            processed += 1
            progress_callback(self.build_progress(total or processed, stored, status="status.running"))
            if limit and processed >= limit:
                break
            await self._throttle(self.settings.rate_limit.scan_interval)
        logger.info("log.active_finished")

    async def _resolve_input_user(self, user: StoredUser) -> Optional[types.InputUser]:
        try:
            entity = await self.client.get_input_entity(types.PeerUser(user.user_id))
        except (TypeError, ValueError):
            try:
                entity = await self.client.get_input_entity(user.user_id)
            except (TypeError, ValueError):
                return None
        if isinstance(entity, types.InputPeerUser):
            resolved = types.InputUser(user_id=entity.user_id, access_hash=entity.access_hash)
        elif isinstance(entity, types.InputUser):
            resolved = entity
        else:
            return None
        user.access_hash = resolved.access_hash
        if hasattr(self.storage, "has_user") and self.storage.has_user(user.user_id):
            self.storage.update_user(user)
        return resolved

    async def _handle_flood_wait(
        self, seconds: int, status_callback: Callable[[str], None]
    ) -> None:
        logger.warning("log.flood_wait")
        for remaining in range(int(seconds), 0, -1):
            await self._check_cancelled()
            message = f"{translator.translate('status.waiting_flood')} ({remaining}s)"
            status_callback(message)
            await asyncio.sleep(1)
        status_callback("status.running")


class TaskOrchestrator:
    def __init__(self, settings: AppSettings) -> None:
        self.settings = settings
        self._tasks: Dict[Tuple[str, str], SessionTask] = {}

    def create_task(
        self,
        session_name: str,
        task_type: str,
        client: TelegramClient,
        storage: UserStorage,
    ) -> SessionTask:
        key = (session_name, task_type)
        task = SessionTask(session_name, client, self.settings, storage)
        self._tasks[key] = task
        return task

    def complete_task(self, session_name: str, task_type: str) -> None:
        self._tasks.pop((session_name, task_type), None)

    def cancel_task(self, session_name: str, task_type: str) -> None:
        key = (session_name, task_type)
        task = self._tasks.get(key)
        if task:
            task.cancel()
            self._tasks.pop(key, None)

    def cancel_all(self) -> None:
        for task in self._tasks.values():
            task.cancel()
        self._tasks.clear()
