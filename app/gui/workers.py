from __future__ import annotations

import asyncio
import logging
from dataclasses import dataclass
from datetime import timedelta
from typing import Callable, Dict, Iterable, List, Optional, Union

from PySide6.QtCore import QThread, Signal
from telethon import TelegramClient

from app.core.session_manager import SessionManager
from app.core.tasks import ProgressUpdate, SessionTask, TaskCancelled, TaskOrchestrator
from app.data.group_storage import GroupStorage, StoredGroup
from app.data.user_storage import StoredUser, UserStorage

logger = logging.getLogger(__name__)


@dataclass
class TaskRequest:
    task_type: str
    session_name: str
    entity: str
    limit: Optional[int]
    interval: Optional[timedelta]
    users: Optional[List[StoredUser]] = None
    persist_results: bool = True
    storage: Optional[Union[UserStorage, GroupStorage]] = None
    include_no_username: bool = True
    offset: int = 0
    result_storage: Optional[UserStorage] = None
    message_body: Optional[str] = None
    message_media: Optional[str] = None
    groups: Optional[List[StoredGroup]] = None
    pages: Optional[int] = None
    page_offset: int = 0
    group_filters: Optional[Dict[str, bool]] = None


class SessionWorkerThread(QThread):
    progress = Signal(object)
    status = Signal(str, str, str)
    finished = Signal(str, str)
    error = Signal(str, str, str)

    def __init__(
        self,
        session_manager: SessionManager,
        orchestrator: TaskOrchestrator,
        request: TaskRequest,
    ) -> None:
        super().__init__()
        self.session_manager = session_manager
        self.orchestrator = orchestrator
        self.request = request

    def stop(self) -> None:
        self.orchestrator.cancel_task(self.request.session_name, self.request.task_type)

    def run(self) -> None:  # noqa: D401
        asyncio.run(self._run())

    async def _run(self) -> None:
        client: Optional[TelegramClient] = None
        try:
            client = await self.session_manager._create_client(self.request.session_name)
            if not await client.is_user_authorized():
                raise ValueError("log.session_not_authorized")
        except Exception as exc:  # pragma: no cover - network failure
            logger.exception("Worker start failure")
            if client:
                await self.session_manager._release_client(self.request.session_name, client)
            self.error.emit(self.request.session_name, self.request.task_type, str(exc))
            return
        storage = self.request.storage
        if storage is None:
            raise ValueError("log.storage_not_configured")
        task = self.orchestrator.create_task(
            self.request.session_name,
            self.request.task_type,
            client,
            storage,
            self.request.result_storage,
        )

        def progress_handler(update: ProgressUpdate) -> None:
            self.progress.emit(update)

        def status_handler(status: str) -> None:
            self.status.emit(self.request.session_name, self.request.task_type, status)

        try:
            if self.request.task_type == "scan":
                await task.scan_members(
                    self.request.entity,
                    self.request.limit,
                    self.request.interval,
                    progress_handler,
                    self.request.persist_results,
                    status_handler,
                    include_no_username=self.request.include_no_username,
                    offset=self.request.offset,
                )
            elif self.request.task_type == "group_scan":
                await task.search_groups(
                    self.request.entity,
                    self.request.limit,
                    progress_handler,
                    self.request.persist_results,
                    status_handler,
                    pages=self.request.pages,
                    filters=self.request.group_filters,
                    page_offset=self.request.page_offset,
                )
            elif self.request.task_type == "add":
                await task.add_members(
                    self.request.entity,
                    self.request.users or [],
                    progress_handler,
                    status_handler,
                )
            elif self.request.task_type == "active":
                await task.fetch_active_senders(
                    self.request.entity,
                    self.request.limit,
                    self.request.interval,
                    progress_handler,
                    self.request.persist_results,
                    status_handler,
                    include_no_username=self.request.include_no_username,
                    offset=self.request.offset,
                )
            elif self.request.task_type == "message":
                await task.send_messages(
                    self.request.users or [],
                    self.request.message_body,
                    self.request.message_media,
                    progress_handler,
                    status_handler,
                )
            elif self.request.task_type == "group_message":
                await task.send_group_messages(
                    self.request.groups or [],
                    self.request.message_body,
                    self.request.message_media,
                    progress_handler,
                    status_handler,
                )
            else:
                raise ValueError(f"Unknown task type {self.request.task_type}")
        except TaskCancelled:
            self.status.emit(self.request.session_name, self.request.task_type, "status.stopped")
        except Exception as exc:  # pragma: no cover
            logger.exception("Worker error")
            self.error.emit(self.request.session_name, self.request.task_type, str(exc))
        else:
            self.finished.emit(self.request.session_name, self.request.task_type)
        finally:
            self.orchestrator.complete_task(self.request.session_name, self.request.task_type)
            await self.session_manager._release_client(self.request.session_name, client)
