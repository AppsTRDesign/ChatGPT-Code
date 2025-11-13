from __future__ import annotations

import asyncio
import logging
import sqlite3
from dataclasses import dataclass
from pathlib import Path
from typing import Dict, List, Optional

from telethon import TelegramClient
from telethon.errors import FloodWaitError, PasswordHashInvalidError, SessionPasswordNeededError, UserDeactivatedBanError
from telethon.sessions import StringSession

from app.core.settings import AppSettings

logger = logging.getLogger(__name__)


@dataclass
class PendingLogin:
    phone: str
    phone_code_hash: str


@dataclass
class SessionInfo:
    name: str
    path: Path


class SessionManager:
    def __init__(self, settings: AppSettings) -> None:
        self.settings = settings
        self.settings.ensure_directories()
        self._pending: Dict[str, PendingLogin] = {}
        self._lock = asyncio.Lock()

    def list_sessions(self) -> List[SessionInfo]:
        sessions = []
        for path in self.settings.session_directory.glob("*.session"):
            sessions.append(SessionInfo(name=path.stem, path=path))
        for path in self.settings.session_directory.glob("*.session-journal"):
            # ignore sqlite journal files
            continue
        return sessions

    async def _create_client(self, session_name: str) -> TelegramClient:
        session_path = self.settings.session_directory / f"{session_name}.session"
        attempts = 0
        while True:
            client = TelegramClient(
                str(session_path),
                self.settings.api_id,
                self.settings.api_hash,
                lang_code=self.settings.language,
            )
            try:
                await client.connect()
                return client
            except sqlite3.OperationalError as exc:
                await client.disconnect()
                message = str(exc).lower()
                attempts += 1
                if "database is locked" in message and attempts < 5:
                    await asyncio.sleep(0.5)
                    continue
                raise

    async def start_login(self, session_name: str, phone: str) -> PendingLogin:
        async with self._lock:
            client = await self._create_client(session_name)
            try:
                if await client.is_user_authorized():
                    raise ValueError("log.login_success")
                sent = await client.send_code_request(phone)
                pending = PendingLogin(phone=phone, phone_code_hash=sent.phone_code_hash)
                self._pending[session_name] = pending
                return pending
            finally:
                await client.disconnect()

    async def confirm_code(self, session_name: str, code: str, password: Optional[str] = None) -> None:
        async with self._lock:
            pending = self._pending.get(session_name)
            if not pending:
                raise ValueError("No pending login")
            client = await self._create_client(session_name)
            try:
                try:
                    await client.sign_in(phone=pending.phone, code=code, phone_code_hash=pending.phone_code_hash)
                except SessionPasswordNeededError:
                    if not password:
                        raise
                    await client.sign_in(password=password)
            except PasswordHashInvalidError as exc:
                raise ValueError("log.login_failure") from exc
            except Exception as exc:  # pragma: no cover - network errors
                raise
            else:
                logger.info("log.login_success")
            finally:
                self._pending.pop(session_name, None)
                await client.disconnect()

    async def close_pending(self, session_name: str) -> None:
        async with self._lock:
            self._pending.pop(session_name, None)

    async def remove_session(self, session_name: str) -> None:
        session_path = self.settings.session_directory / f"{session_name}.session"
        if session_path.exists():
            session_path.unlink()
            logger.info("log.session_removed")

    async def check_ban(self, session_name: str) -> bool:
        client = await self._create_client(session_name)
        try:
            try:
                me = await client.get_me()
                return bool(me)
            except UserDeactivatedBanError:
                logger.warning("log.ban_detected")
                await self.remove_session(session_name)
                return False
        finally:
            await client.disconnect()

    async def run_with_client(self, session_name: str, coroutine):
        client = await self._create_client(session_name)
        try:
            return await coroutine(client)
        finally:
            await client.disconnect()

    async def gather_clients(self, session_names: List[str]) -> List[TelegramClient]:
        clients: List[TelegramClient] = []
        for session_name in session_names:
            client = await self._create_client(session_name)
            clients.append(client)
        return clients

    async def release_clients(self, clients: List[TelegramClient]) -> None:
        for client in clients:
            await client.disconnect()


async def handle_flood_wait(async_fn, *args, **kwargs):
    while True:
        try:
            return await async_fn(*args, **kwargs)
        except FloodWaitError as exc:  # pragma: no cover - requires telegram interaction
            wait_time = int(exc.seconds) + 1
            logger.warning("log.flood_wait")
            await asyncio.sleep(wait_time)
