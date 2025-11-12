from __future__ import annotations

from dataclasses import dataclass
from typing import Optional

from PySide6.QtCore import Qt
from PySide6.QtWidgets import QLabel, QPlainTextEdit, QProgressBar, QVBoxLayout, QWidget

from app.i18n.strings import translator


@dataclass
class ProgressState:
    processed: int = 0
    total: int = 0
    status: str = "status.idle"


class SessionProgressWidget(QWidget):
    def __init__(self, session_name: str, task_type: str, parent: Optional[QWidget] = None) -> None:
        super().__init__(parent)
        self.session_name = session_name
        self.task_type = task_type
        self.state = ProgressState()

        self.title_label = QLabel(session_name)
        self.progress_bar = QProgressBar()
        self.progress_bar.setMinimum(0)
        self.progress_bar.setMaximum(1)
        self.progress_bar.setValue(0)
        self.status_label = QLabel(translator.translate(self.state.status))
        self.log_view = QPlainTextEdit()
        self.log_view.setReadOnly(True)
        self.log_view.setMaximumBlockCount(500)

        layout = QVBoxLayout()
        layout.addWidget(self.title_label)
        layout.addWidget(self.progress_bar)
        layout.addWidget(self.status_label)
        layout.addWidget(self.log_view)
        layout.setContentsMargins(8, 8, 8, 8)
        self.setLayout(layout)

    def reset(self) -> None:
        self.state = ProgressState()
        self.progress_bar.setMaximum(1)
        self.progress_bar.setValue(0)
        self.status_label.setText(self._translate_status(self.state.status))
        self.log_view.clear()

    def update_state(self, processed: int, total: int, status_key: Optional[str] = None) -> None:
        self.state.processed = processed
        self.state.total = total
        if total > 0:
            self.progress_bar.setMaximum(total)
        self.progress_bar.setValue(processed)
        if status_key:
            self.state.status = status_key
        self.status_label.setText(
            f"{self._translate_status(self.state.status)} - {processed}/{total if total else processed}"
        )

    def append_user(self, description: str) -> None:
        self.log_view.appendPlainText(description)

    def retranslate(self) -> None:
        self.status_label.setText(
            f"{self._translate_status(self.state.status)} - {self.state.processed}/{self.state.total if self.state.total else self.state.processed}"
        )
        # Title remains session name

    @staticmethod
    def _translate_status(status: str) -> str:
        if status.startswith("status."):
            return translator.translate(status)
        return status
