from __future__ import annotations

import asyncio
import logging
from dataclasses import dataclass
from datetime import datetime, timedelta, timezone
from typing import Callable, Dict, Iterable, List, Optional

from telethon import TelegramClient
from telethon.errors import FloodWaitError, UserPrivacyRestrictedError
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
        elif not self._total:
            self._total = max(self._processed, 1)
        self._processed += 1
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

            stored = StoredUser(
                user_id=user.id,
                username=user.username,
                phone=user.phone,
                access_hash=user.access_hash,
                first_name=user.first_name,
                last_name=user.last_name,
                last_seen=None,
                status=type(user.status).__name__ if user.status else None,
                source=str(entity),
                is_bot=bool(getattr(user, "bot", False)),
                last_seen_utc=UserStorage.to_iso(last_seen),
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
            try:
                await self.client(
                    functions.channels.InviteToChannelRequest(
                        channel=entity,
                        users=[types.InputUser(user_id=user.user_id, access_hash=user.access_hash or 0)],
                    )
                )
                progress_callback(self.build_progress(total, user, status="status.running"))
                self.storage.remove_user(user.user_id)
            except UserPrivacyRestrictedError:
                progress_callback(self.build_progress(total, user, status="status.error"))
            except FloodWaitError as exc:
                await self._handle_flood_wait(exc.seconds, status_cb)
                await self._throttle(self.settings.rate_limit.join_interval)
                continue
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
            if message.sender_id in seen_users:
                continue
            try:
                sender = await self.client.get_entity(message.sender_id)
            except FloodWaitError as exc:
                await self._handle_flood_wait(exc.seconds, status_cb)
                await self._throttle(self.settings.rate_limit.scan_interval)
                continue
            if getattr(sender, "bot", False):
                continue
            seen_users.add(message.sender_id)
            stored = StoredUser(
                user_id=sender.id,
                username=sender.username,
                phone=sender.phone,
                access_hash=getattr(sender, "access_hash", None),
                first_name=sender.first_name,
                last_name=sender.last_name,
                last_seen=None,
                status=type(sender.status).__name__ if getattr(sender, "status", None) else None,
                source=str(entity),
                is_bot=False,
                last_seen_utc=UserStorage.to_iso(message.date.replace(tzinfo=timezone.utc) if message.date else None),
            )
            stored = self.storage.prepare_user(stored)
            if persist:
                self.storage.add_users([stored])
            processed += 1
            progress_callback(self.build_progress(total or processed, stored, status="status.running"))
            if limit and processed >= limit:
                break
            await self._throttle(self.settings.rate_limit.scan_interval)
        logger.info("log.active_finished")

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
    def __init__(self, settings: AppSettings, storage: UserStorage) -> None:
        self.settings = settings
        self.storage = storage
        self._tasks: Dict[str, SessionTask] = {}

    def create_task(self, session_name: str, client: TelegramClient) -> SessionTask:
        task = SessionTask(session_name, client, self.settings, self.storage)
        self._tasks[session_name] = task
        return task

    def complete_task(self, session_name: str) -> None:
        self._tasks.pop(session_name, None)

    def cancel_task(self, session_name: str) -> None:
        task = self._tasks.get(session_name)
        if task:
            task.cancel()
            self._tasks.pop(session_name, None)

    def cancel_all(self) -> None:
        for task in self._tasks.values():
            task.cancel()
        self._tasks.clear()
