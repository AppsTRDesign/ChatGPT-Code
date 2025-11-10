from __future__ import annotations

import asyncio
import logging
from dataclasses import dataclass
from datetime import timedelta
from typing import Callable, Iterable, List, Optional

from PySide6.QtCore import QObject, QThread, Signal

from app.core.session_manager import SessionManager
from app.core.tasks import ProgressUpdate, SessionTask, TaskCancelled, TaskOrchestrator
from app.data.user_storage import StoredUser

logger = logging.getLogger(__name__)


@dataclass
class TaskRequest:
    task_type: str
    session_name: str
    entity: str
    limit: Optional[int]
    interval: Optional[timedelta]
    users: Optional[List[StoredUser]] = None


class SessionWorkerThread(QThread):
    progress = Signal(object)
    status = Signal(str, str)
    finished = Signal(str)
    error = Signal(str, str)

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
        self._cancel_event = asyncio.Event()

    def stop(self) -> None:
        self._cancel_event.set()

    def run(self) -> None:  # noqa: D401
        asyncio.run(self._run())

    async def _run(self) -> None:
        try:
            client = await self.session_manager._create_client(self.request.session_name)
            if not await client.is_user_authorized():
                raise ValueError("log.session_not_authorized")
        except Exception as exc:  # pragma: no cover - network failure
            self.error.emit(self.request.session_name, str(exc))
            return
        task = SessionTask(self.request.session_name, client, self.orchestrator.settings, self.orchestrator.storage)

        def progress_handler(update: ProgressUpdate) -> None:
            self.progress.emit(update)

        try:
            if self.request.task_type == "scan":
                await task.scan_members(
                    self.request.entity,
                    self.request.limit,
                    self.request.interval,
                    progress_handler,
                )
            elif self.request.task_type == "add":
                await task.add_members(
                    self.request.entity,
                    self.request.users or [],
                    progress_handler,
                )
            elif self.request.task_type == "active":
                await task.fetch_active_senders(
                    self.request.entity,
                    self.request.limit,
                    self.request.interval,
                    progress_handler,
                )
            else:
                raise ValueError(f"Unknown task type {self.request.task_type}")
        except TaskCancelled:
            self.status.emit(self.request.session_name, "status.stopped")
        except Exception as exc:  # pragma: no cover
            logger.exception("Worker error")
            self.error.emit(self.request.session_name, str(exc))
        else:
            self.finished.emit(self.request.session_name)
        finally:
            await client.disconnect()
