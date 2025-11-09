"""Qt GUI for the Telegram automation suite."""
from __future__ import annotations

import asyncio
import json
import re
from datetime import timedelta
from pathlib import Path
from typing import Dict, List, Optional

from PySide6.QtCore import Qt
from PySide6.QtGui import QAction, QGuiApplication, QKeySequence
from PySide6.QtWidgets import (
    QAbstractItemView,
    QCheckBox,
    QComboBox,
    QFileDialog,
    QFormLayout,
    QGroupBox,
    QHBoxLayout,
    QHeaderView,
    QInputDialog,
    QLabel,
    QLineEdit,
    QListWidget,
    QListWidgetItem,
    QMainWindow,
    QMessageBox,
    QPushButton,
    QProgressBar,
    QScrollArea,
    QSpinBox,
    QTabWidget,
    QTableWidget,
    QTableWidgetItem,
    QTextEdit,
    QVBoxLayout,
    QWidget,
)

from telethon import errors

from .telethon_manager import RateLimits, SessionInfo, TelethonManager
from .translations import Translator


class SessionProgressWidget(QGroupBox):
    """Progress display for a single session."""

    def __init__(self, title: str) -> None:
        super().__init__(title)
        self.layout = QVBoxLayout(self)
        self.status_label = QLabel()
        self.progress = QProgressBar()
        self.log = QTextEdit()
        self.log.setReadOnly(True)
        self.layout.addWidget(self.status_label)
        self.layout.addWidget(self.progress)
        self.layout.addWidget(self.log)
        self.setStatus("ready")

    def setStatus(self, status: str, detail: str = "", total: int = 0, processed: int = 0) -> None:
        self.status_label.setText(f"{status} | {processed}/{total if total else '∞'}")
        if total:
            self.progress.setMaximum(total)
            self.progress.setValue(processed)
        else:
            self.progress.setMaximum(0)
        if detail:
            self.log.append(detail)

    def setFlood(self, message: str) -> None:
        self.log.append(message)


class SessionsTab(QWidget):
    def __init__(
        self,
        manager: TelethonManager,
        translator: Translator,
        loop: asyncio.AbstractEventLoop,
        on_sessions_updated=None,
    ) -> None:
        super().__init__()
        self.manager = manager
        self.translator = translator
        self.loop = loop
        self.client_cache: Dict[str, object] = {}
        self.on_sessions_updated = on_sessions_updated
        self._build_ui()

    def _build_ui(self) -> None:
        layout = QVBoxLayout(self)
        form = QFormLayout()
        self.phone_label = QLabel(self.translator.tr("login_phone"))
        self.phone_input = QLineEdit()
        form.addRow(self.phone_label, self.phone_input)
        self.send_code_btn = QPushButton(self.translator.tr("login_send_code"))
        self.send_code_btn.clicked.connect(self._on_send_code)
        form.addRow(self.send_code_btn)
        layout.addLayout(form)

        self.session_label = QLabel(self.translator.tr("sessions_active"))
        layout.addWidget(self.session_label)
        self.session_list = QListWidget()
        layout.addWidget(self.session_list)
        refresh_layout = QHBoxLayout()
        self.refresh_btn = QPushButton(self.translator.tr("refresh"))
        self.refresh_btn.clicked.connect(self.refresh_sessions)
        refresh_layout.addWidget(self.refresh_btn)
        self.otp_label = QLabel(self.translator.tr("otp_sessions"))
        refresh_layout.addWidget(self.otp_label)
        refresh_layout.addStretch(1)
        layout.addLayout(refresh_layout)

        self.log = QTextEdit()
        self.log.setReadOnly(True)
        layout.addWidget(self.log)
        self.refresh_sessions(notify=False)

    def refresh_sessions(self, notify: bool = True) -> None:
        self.session_list.clear()
        for session in self.manager.list_sessions():
            self.session_list.addItem(session.phone)
        if notify and self.on_sessions_updated:
            self.on_sessions_updated()

    def _on_send_code(self) -> None:
        self.loop.create_task(self.handle_send_code())

    async def handle_send_code(self) -> None:
        phone = self.phone_input.text().strip()
        if not phone:
            return
        try:
            client = await self.manager.send_login_code(phone)
        except Exception as exc:
            QMessageBox.critical(
                self,
                self.translator.tr("app_title"),
                self.translator.localize_error(str(exc)),
            )
            return
        code, ok = QInputDialog.getText(
            self,
            self.translator.tr("app_title"),
            self.translator.tr("login_code_prompt", phone=phone),
        )
        if not ok or not code:
            await client.disconnect()
            return
        password = None
        try:
            await self.manager.complete_sign_in(client, phone, code)
        except errors.SessionPasswordNeededError:
            password, ok_pass = QInputDialog.getText(
                self,
                self.translator.tr("app_title"),
                self.translator.tr("login_password_prompt", phone=phone),
            )
            if not ok_pass:
                await client.disconnect()
                return
            await self.manager.complete_sign_in(client, phone, code, password=password)
        except Exception as exc:
            localized = self.translator.localize_error(str(exc))
            QMessageBox.critical(
                self,
                self.translator.tr("app_title"),
                self.translator.tr("login_failure", phone=phone, error=localized),
            )
            return
        self.log.append(self.translator.tr("login_success", phone=phone))
        self.refresh_sessions()

    def retranslate_ui(self) -> None:
        self.phone_label.setText(self.translator.tr("login_phone"))
        self.session_label.setText(self.translator.tr("sessions_active"))
        self.refresh_btn.setText(self.translator.tr("refresh"))
        self.send_code_btn.setText(self.translator.tr("login_send_code"))
        self.otp_label.setText(self.translator.tr("otp_sessions"))


class BanTab(QWidget):
    def __init__(self, manager: TelethonManager, translator: Translator, loop: asyncio.AbstractEventLoop) -> None:
        super().__init__()
        self.manager = manager
        self.translator = translator
        self.loop = loop
        self._build_ui()

    def _build_ui(self) -> None:
        layout = QVBoxLayout(self)
        self.button = QPushButton(self.translator.tr("ban_check"))
        self.button.clicked.connect(self._on_ban_check)
        layout.addWidget(self.button)
        self.log = QTextEdit()
        self.log.setReadOnly(True)
        layout.addWidget(self.log)

    async def handle_ban_check(self) -> None:
        self.log.clear()
        sessions = self.manager.list_sessions()
        for session in sessions:
            valid = await self.manager.validate_session(session)
            if valid:
                self.log.append(self.translator.tr("ban_ok", phone=session.phone))
            else:
                self.log.append(self.translator.tr("ban_removed", phone=session.phone))
        QMessageBox.information(self, self.translator.tr("app_title"), self.translator.tr("ban_results"))

    def _on_ban_check(self) -> None:
        self.loop.create_task(self.handle_ban_check())

    def retranslate_ui(self) -> None:
        self.button.setText(self.translator.tr("ban_check"))


class GroupSearchTab(QWidget):
    def __init__(
        self,
        manager: TelethonManager,
        translator: Translator,
        loop: asyncio.AbstractEventLoop,
    ) -> None:
        super().__init__()
        self.manager = manager
        self.translator = translator
        self.loop = loop
        self.stop_event = asyncio.Event()
        self.current_task: Optional[asyncio.Task] = None
        self.progress_widgets: Dict[str, SessionProgressWidget] = {}
        self.results: List[Dict[str, object]] = []
        self.result_keys: set[tuple[int, Optional[str]]] = set()
        self.page_size = 20
        self.current_page = 1
        self._build_ui()

    def _build_ui(self) -> None:
        layout = QVBoxLayout(self)
        form = QFormLayout()
        self.keyword_input = QLineEdit()
        self.keyword_input.setPlaceholderText(self.translator.tr("group_keywords_placeholder"))
        form.addRow(QLabel(self.translator.tr("group_keywords")), self.keyword_input)
        self.limit_input = QLineEdit()
        form.addRow(QLabel(self.translator.tr("group_limit")), self.limit_input)
        self.visibility_combo = QComboBox()
        self.visibility_combo.addItems(
            [
                self.translator.tr("group_visibility_all"),
                self.translator.tr("group_visibility_public"),
                self.translator.tr("group_visibility_private"),
            ]
        )
        form.addRow(QLabel(self.translator.tr("group_visibility")), self.visibility_combo)
        layout.addLayout(form)

        self.session_label = QLabel(self.translator.tr("select_sessions"))
        layout.addWidget(self.session_label)
        self.session_list = QListWidget()
        layout.addWidget(self.session_list)

        button_layout = QHBoxLayout()
        self.start_btn = QPushButton(self.translator.tr("start"))
        self.stop_btn = QPushButton(self.translator.tr("stop"))
        self.start_btn.clicked.connect(self._on_start_clicked)
        self.stop_btn.clicked.connect(self.stop_search)
        button_layout.addWidget(self.start_btn)
        button_layout.addWidget(self.stop_btn)
        layout.addLayout(button_layout)

        self.info_label = QLabel(self.translator.tr("status_ready"))
        layout.addWidget(self.info_label)

        self.scroll = QScrollArea()
        self.scroll.setWidgetResizable(True)
        self.progress_container = QWidget()
        self.progress_layout = QVBoxLayout(self.progress_container)
        self.scroll.setWidget(self.progress_container)
        layout.addWidget(self.scroll)

        controls_layout = QHBoxLayout()
        self.sort_combo = QComboBox()
        self.sort_combo.addItems(
            [
                self.translator.tr("group_sort_name"),
                self.translator.tr("group_sort_members"),
            ]
        )
        self.sort_combo.currentIndexChanged.connect(self._apply_sort)
        self.save_btn = QPushButton(self.translator.tr("group_save"))
        self.save_btn.clicked.connect(self.save_results)
        controls_layout.addWidget(QLabel(self.translator.tr("group_sort_label")))
        controls_layout.addWidget(self.sort_combo)
        controls_layout.addStretch(1)
        controls_layout.addWidget(self.save_btn)
        layout.addLayout(controls_layout)

        pagination = QHBoxLayout()
        self.prev_btn = QPushButton(self.translator.tr("previous_page"))
        self.next_btn = QPushButton(self.translator.tr("next_page"))
        self.prev_btn.clicked.connect(lambda: self._change_page(-1))
        self.next_btn.clicked.connect(lambda: self._change_page(1))
        self.page_label = QLabel(self.translator.tr("group_page", current=1, total=1))
        pagination.addWidget(self.prev_btn)
        pagination.addWidget(self.next_btn)
        pagination.addWidget(self.page_label)
        pagination.addStretch(1)
        layout.addLayout(pagination)

        self.table = QTableWidget(0, 5)
        self.table.setHorizontalHeaderLabels(
            [
                self.translator.tr("group_column_title"),
                self.translator.tr("group_column_username"),
                self.translator.tr("group_column_members"),
                self.translator.tr("group_column_public"),
                self.translator.tr("group_column_country"),
            ]
        )
        self.table.horizontalHeader().setSectionResizeMode(QHeaderView.Stretch)
        self.table.setEditTriggers(QTableWidget.NoEditTriggers)
        self.table.setSelectionBehavior(QAbstractItemView.SelectRows)
        self.table.setSelectionMode(QAbstractItemView.ExtendedSelection)
        self.table.setContextMenuPolicy(Qt.ActionsContextMenu)
        self.copy_action = QAction(self.translator.tr("copy_selected"), self.table)
        self.copy_action.setShortcut(QKeySequence.Copy)
        self.copy_action.triggered.connect(self.copy_selected_rows)
        self.table.addAction(self.copy_action)
        self.table.setSortingEnabled(False)
        self.table.horizontalHeader().sectionClicked.connect(self._on_header_clicked)
        layout.addWidget(self.table)

        self.log = QTextEdit()
        self.log.setReadOnly(True)
        layout.addWidget(self.log)

        self.refresh_sessions()

    def refresh_sessions(self) -> None:
        self.session_list.clear()
        for session in self.manager.list_sessions():
            item = QListWidgetItem(session.phone)
            item.setCheckState(Qt.Unchecked)
            self.session_list.addItem(item)

    def selected_sessions(self) -> List[SessionInfo]:
        sessions: List[SessionInfo] = []
        for index in range(self.session_list.count()):
            item = self.session_list.item(index)
            if item.checkState() == Qt.Checked:
                sessions.append(
                    SessionInfo(phone=item.text(), path=Path(f"session/{item.text()}.session"))
                )
        return sessions

    def stop_search(self) -> None:
        self.stop_event.set()
        self.info_label.setText(self.translator.tr("status_stopped"))

    def _on_start_clicked(self) -> None:
        if not self.current_task or self.current_task.done():
            self.loop.create_task(self.start_search())

    async def start_search(self) -> None:
        if self.current_task and not self.current_task.done():
            return
        sessions = self.selected_sessions()
        if not sessions:
            QMessageBox.warning(
                self,
                self.translator.tr("app_title"),
                self.translator.tr("no_sessions_selected"),
            )
            return
        keywords = [kw.strip() for kw in self.keyword_input.text().split(",") if kw.strip()]
        if not keywords:
            QMessageBox.warning(
                self,
                self.translator.tr("app_title"),
                self.translator.tr("group_keywords_warning"),
            )
            return
        limit_text = self.limit_input.text().strip()
        limit = None
        if limit_text:
            if not limit_text.isdigit() or int(limit_text) <= 0:
                QMessageBox.warning(
                    self,
                    self.translator.tr("app_title"),
                    self.translator.tr("invalid_limit"),
                )
                return
            limit = int(limit_text)
        visibility_index = self.visibility_combo.currentIndex()
        visibility = "all"
        if visibility_index == 1:
            visibility = "public"
        elif visibility_index == 2:
            visibility = "private"
        self.stop_event = asyncio.Event()
        self.progress_widgets.clear()
        while self.progress_layout.count():
            item = self.progress_layout.takeAt(0)
            widget = item.widget()
            if widget:
                widget.deleteLater()
        for session in sessions:
            widget = SessionProgressWidget(
                self.translator.tr("session_progress", phone=session.phone)
            )
            self.progress_layout.addWidget(widget)
            self.progress_widgets[session.phone] = widget

        self.info_label.setText(self.translator.tr("status_running"))
        self.log.clear()
        self.table.setRowCount(0)
        self.results = []
        self.result_keys.clear()
        self.current_page = 1
        self._update_page_label()

        def update_cb(session: SessionInfo, processed: int, total: int, status: str, detail, flood=None):
            widget = self.progress_widgets.get(session.phone)
            if widget:
                status_map = {
                    "running": self.translator.tr("status_running"),
                    "completed": self.translator.tr("status_completed"),
                    "stopped": self.translator.tr("status_stopped"),
                    "error": self.translator.tr("status_error"),
                }
                display_detail = self._detail_text(detail)
                widget.setStatus(status_map.get(status, status), display_detail, total or 0, processed)
                if flood:
                    widget.setFlood(self.translator.tr("flood_wait", seconds=flood.seconds))
            if isinstance(detail, dict):
                self._add_result(detail)

        async def run_search() -> None:
            try:
                data = await self.manager.search_groups(
                    sessions=sessions,
                    keywords=keywords,
                    limit=limit,
                    visibility=visibility,
                    update_cb=update_cb,
                    stop_event=self.stop_event,
                )
                for item in data:
                    self._add_result(item)
            finally:
                self.info_label.setText(self.translator.tr("status_completed"))

        self.current_task = self.loop.create_task(run_search())

    def _detail_text(self, detail) -> str:
        if isinstance(detail, dict):
            return detail.get("title", "")
        if isinstance(detail, str):
            return self.translator.localize_error(detail)
        return ""

    def _add_result(self, data: Dict[str, object]) -> None:
        key = (int(data.get("id", 0)), data.get("username"))
        if key in self.result_keys:
            return
        self.result_keys.add(key)
        self.results.append(data)
        self._apply_sort()

    def _apply_sort(self) -> None:
        if not self.results:
            self.table.setRowCount(0)
            self._update_page_label()
            return
        index = self.sort_combo.currentIndex()
        if index == 1:
            sorted_results = sorted(
                self.results,
                key=lambda item: int(item.get("participants") or 0),
                reverse=True,
            )
        else:
            sorted_results = sorted(
                self.results,
                key=lambda item: str(item.get("title") or "").lower(),
            )
        self.results = sorted_results
        self._display_page(self.current_page)

    def _display_page(self, page: int) -> None:
        total_pages = max(1, (len(self.results) + self.page_size - 1) // self.page_size)
        page = max(1, min(page, total_pages))
        self.current_page = page
        start = (page - 1) * self.page_size
        end = start + self.page_size
        subset = self.results[start:end]
        self.table.setRowCount(len(subset))
        for row, item in enumerate(subset):
            self.table.setItem(row, 0, QTableWidgetItem(str(item.get("title", ""))))
            self.table.setItem(row, 1, QTableWidgetItem(str(item.get("username", ""))))
            self.table.setItem(row, 2, QTableWidgetItem(str(item.get("participants", ""))))
            self.table.setItem(
                row,
                3,
                QTableWidgetItem(
                    self.translator.tr("yes") if item.get("is_public") else self.translator.tr("no")
                ),
            )
            self.table.setItem(row, 4, QTableWidgetItem(str(item.get("country", ""))))
        self._update_page_label(total_pages)

    def _update_page_label(self, total_pages: Optional[int] = None) -> None:
        if total_pages is None:
            total_pages = max(1, (len(self.results) + self.page_size - 1) // self.page_size)
        self.page_label.setText(
            self.translator.tr("group_page", current=self.current_page, total=total_pages)
        )
        self.prev_btn.setEnabled(self.current_page > 1)
        self.next_btn.setEnabled(self.current_page < total_pages)

    def _change_page(self, delta: int) -> None:
        self._display_page(self.current_page + delta)

    def copy_selected_rows(self) -> None:
        selection = self.table.selectionModel()
        if not selection:
            return
        rows = []
        for index in selection.selectedRows():
            values = []
            for column in range(self.table.columnCount()):
                item = self.table.item(index.row(), column)
                values.append(item.text() if item else "")
            rows.append("\t".join(values))
        if rows:
            QGuiApplication.clipboard().setText("\n".join(rows))

    def _on_header_clicked(self, logical_index: int) -> None:
        if logical_index == 0:
            self.sort_combo.setCurrentIndex(0)
        elif logical_index == 2:
            self.sort_combo.setCurrentIndex(1)

    def save_results(self) -> None:
        if not self.results:
            QMessageBox.information(
                self,
                self.translator.tr("app_title"),
                self.translator.tr("group_no_results"),
            )
            return
        default_name = self.keyword_input.text().strip() or "groups"
        name, ok = QInputDialog.getText(
            self,
            self.translator.tr("group_save"),
            self.translator.tr("group_filename_prompt", default=default_name),
            text=default_name,
        )
        if not ok or not name.strip():
            return
        safe_name = re.sub(r"[^a-zA-Z0-9_-]", "_", name.strip())
        path = Path("groups") / f"{safe_name}.json"
        path.write_text(json.dumps(self.results, indent=2), encoding="utf-8")
        QMessageBox.information(
            self,
            self.translator.tr("app_title"),
            self.translator.tr("group_saved", path=str(path)),
        )

    def retranslate_ui(self) -> None:
        self.session_label.setText(self.translator.tr("select_sessions"))
        self.start_btn.setText(self.translator.tr("start"))
        self.stop_btn.setText(self.translator.tr("stop"))
        self.info_label.setText(self.translator.tr("status_ready"))
        self.sort_combo.setItemText(0, self.translator.tr("group_sort_name"))
        self.sort_combo.setItemText(1, self.translator.tr("group_sort_members"))
        self.save_btn.setText(self.translator.tr("group_save"))
        self.prev_btn.setText(self.translator.tr("previous_page"))
        self.next_btn.setText(self.translator.tr("next_page"))
        self.page_label.setText(self.translator.tr("group_page", current=1, total=1))
        self.table.setHorizontalHeaderLabels(
            [
                self.translator.tr("group_column_title"),
                self.translator.tr("group_column_username"),
                self.translator.tr("group_column_members"),
                self.translator.tr("group_column_public"),
                self.translator.tr("group_column_country"),
            ]
        )
        self.keyword_input.setPlaceholderText(self.translator.tr("group_keywords_placeholder"))
        if hasattr(self, "copy_action"):
            self.copy_action.setText(self.translator.tr("copy_selected"))
        self._apply_sort()

class MemberSearchTab(QWidget):
    def __init__(
        self,
        manager: TelethonManager,
        translator: Translator,
        loop: asyncio.AbstractEventLoop,
    ) -> None:
        super().__init__()
        self.manager = manager
        self.translator = translator
        self.loop = loop
        self.stop_event = asyncio.Event()
        self.current_task: Optional[asyncio.Task] = None
        self.progress_widgets: Dict[str, SessionProgressWidget] = {}
        self.results: List[Dict[str, object]] = []
        self.result_ids: set[int] = set()
        self.page_size = 20
        self.current_page = 1
        self._build_ui()

    def _build_ui(self) -> None:
        layout = QVBoxLayout(self)
        form = QFormLayout()
        self.keyword_input = QLineEdit()
        self.keyword_input.setPlaceholderText(self.translator.tr("member_keywords_placeholder"))
        form.addRow(QLabel(self.translator.tr("member_keywords")), self.keyword_input)
        self.limit_input = QLineEdit()
        form.addRow(QLabel(self.translator.tr("member_limit")), self.limit_input)
        self.visibility_combo = QComboBox()
        self.visibility_combo.addItems(
            [
                self.translator.tr("member_visibility_all"),
                self.translator.tr("member_visibility_public"),
                self.translator.tr("member_visibility_private"),
            ]
        )
        form.addRow(QLabel(self.translator.tr("member_visibility")), self.visibility_combo)
        layout.addLayout(form)

        self.session_label = QLabel(self.translator.tr("select_sessions"))
        layout.addWidget(self.session_label)
        self.session_list = QListWidget()
        layout.addWidget(self.session_list)

        button_layout = QHBoxLayout()
        self.start_btn = QPushButton(self.translator.tr("start"))
        self.stop_btn = QPushButton(self.translator.tr("stop"))
        self.start_btn.clicked.connect(self._on_start_clicked)
        self.stop_btn.clicked.connect(self.stop_search)
        button_layout.addWidget(self.start_btn)
        button_layout.addWidget(self.stop_btn)
        layout.addLayout(button_layout)

        self.info_label = QLabel(self.translator.tr("status_ready"))
        layout.addWidget(self.info_label)

        self.scroll = QScrollArea()
        self.scroll.setWidgetResizable(True)
        self.progress_container = QWidget()
        self.progress_layout = QVBoxLayout(self.progress_container)
        self.scroll.setWidget(self.progress_container)
        layout.addWidget(self.scroll)

        controls_layout = QHBoxLayout()
        self.sort_combo = QComboBox()
        self.sort_combo.addItems(
            [
                self.translator.tr("member_sort_name"),
                self.translator.tr("member_sort_username"),
                self.translator.tr("member_sort_status"),
            ]
        )
        self.sort_combo.currentIndexChanged.connect(self._apply_sort)
        self.save_btn = QPushButton(self.translator.tr("member_save"))
        self.save_btn.clicked.connect(self.save_results)
        controls_layout.addWidget(QLabel(self.translator.tr("member_sort_label")))
        controls_layout.addWidget(self.sort_combo)
        controls_layout.addStretch(1)
        controls_layout.addWidget(self.save_btn)
        layout.addLayout(controls_layout)

        pagination = QHBoxLayout()
        self.prev_btn = QPushButton(self.translator.tr("previous_page"))
        self.next_btn = QPushButton(self.translator.tr("next_page"))
        self.prev_btn.clicked.connect(lambda: self._change_page(-1))
        self.next_btn.clicked.connect(lambda: self._change_page(1))
        self.page_label = QLabel(self.translator.tr("member_page", current=1, total=1))
        pagination.addWidget(self.prev_btn)
        pagination.addWidget(self.next_btn)
        pagination.addWidget(self.page_label)
        pagination.addStretch(1)
        layout.addLayout(pagination)

        self.table = QTableWidget(0, 8)
        self.table.setHorizontalHeaderLabels(
            [
                self.translator.tr("member_column_first_name"),
                self.translator.tr("member_column_last_name"),
                self.translator.tr("member_column_username"),
                self.translator.tr("member_column_phone"),
                self.translator.tr("member_column_status"),
                self.translator.tr("member_column_language"),
                self.translator.tr("member_column_verified"),
                self.translator.tr("member_column_flags"),
            ]
        )
        self.table.horizontalHeader().setSectionResizeMode(QHeaderView.Stretch)
        self.table.setEditTriggers(QTableWidget.NoEditTriggers)
        self.table.setSelectionBehavior(QAbstractItemView.SelectRows)
        self.table.setSelectionMode(QAbstractItemView.ExtendedSelection)
        self.table.setContextMenuPolicy(Qt.ActionsContextMenu)
        self.copy_action = QAction(self.translator.tr("copy_selected"), self.table)
        self.copy_action.setShortcut(QKeySequence.Copy)
        self.copy_action.triggered.connect(self.copy_selected_rows)
        self.table.addAction(self.copy_action)
        self.table.horizontalHeader().sectionClicked.connect(self._on_header_clicked)
        layout.addWidget(self.table)

        self.log = QTextEdit()
        self.log.setReadOnly(True)
        layout.addWidget(self.log)

        self.refresh_sessions()

    def refresh_sessions(self) -> None:
        self.session_list.clear()
        for session in self.manager.list_sessions():
            item = QListWidgetItem(session.phone)
            item.setCheckState(Qt.Unchecked)
            self.session_list.addItem(item)

    def selected_sessions(self) -> List[SessionInfo]:
        sessions: List[SessionInfo] = []
        for index in range(self.session_list.count()):
            item = self.session_list.item(index)
            if item.checkState() == Qt.Checked:
                sessions.append(
                    SessionInfo(phone=item.text(), path=Path(f"session/{item.text()}.session"))
                )
        return sessions

    def stop_search(self) -> None:
        self.stop_event.set()
        self.info_label.setText(self.translator.tr("status_stopped"))

    def _on_start_clicked(self) -> None:
        if not self.current_task or self.current_task.done():
            self.loop.create_task(self.start_search())

    async def start_search(self) -> None:
        if self.current_task and not self.current_task.done():
            return
        sessions = self.selected_sessions()
        if not sessions:
            QMessageBox.warning(
                self,
                self.translator.tr("app_title"),
                self.translator.tr("no_sessions_selected"),
            )
            return
        keywords = [kw.strip() for kw in self.keyword_input.text().split(",") if kw.strip()]
        if not keywords:
            QMessageBox.warning(
                self,
                self.translator.tr("app_title"),
                self.translator.tr("member_keywords_warning"),
            )
            return
        limit_text = self.limit_input.text().strip()
        limit = None
        if limit_text:
            if not limit_text.isdigit() or int(limit_text) <= 0:
                QMessageBox.warning(
                    self,
                    self.translator.tr("app_title"),
                    self.translator.tr("invalid_limit"),
                )
                return
            limit = int(limit_text)
        visibility_index = self.visibility_combo.currentIndex()
        visibility = "all"
        if visibility_index == 1:
            visibility = "public"
        elif visibility_index == 2:
            visibility = "private"

        self.stop_event = asyncio.Event()
        self.progress_widgets.clear()
        while self.progress_layout.count():
            item = self.progress_layout.takeAt(0)
            widget = item.widget()
            if widget:
                widget.deleteLater()
        for session in sessions:
            widget = SessionProgressWidget(
                self.translator.tr("session_progress", phone=session.phone)
            )
            self.progress_layout.addWidget(widget)
            self.progress_widgets[session.phone] = widget

        self.info_label.setText(self.translator.tr("status_running"))
        self.log.clear()
        self.table.setRowCount(0)
        self.results = []
        self.result_ids.clear()
        self.current_page = 1
        self._update_page_label()

        def update_cb(session: SessionInfo, processed: int, total: int, status: str, detail, flood=None):
            widget = self.progress_widgets.get(session.phone)
            if widget:
                status_map = {
                    "running": self.translator.tr("status_running"),
                    "completed": self.translator.tr("status_completed"),
                    "stopped": self.translator.tr("status_stopped"),
                    "error": self.translator.tr("status_error"),
                }
                display_text = ""
                if isinstance(detail, dict):
                    display_text = detail.get("username") or detail.get("first_name") or str(
                        detail.get("id", "")
                    )
                else:
                    display_text = str(detail or "")
                widget.setStatus(
                    status_map.get(status, status), display_text, total or 0, processed
                )
                if flood:
                    widget.setFlood(self.translator.tr("flood_wait", seconds=flood.seconds))
            if isinstance(detail, dict):
                self._add_result(detail)

        async def run_search() -> None:
            try:
                data = await self.manager.search_users(
                    sessions=sessions,
                    keywords=keywords,
                    limit=limit,
                    visibility=visibility,
                    update_cb=update_cb,
                    stop_event=self.stop_event,
                )
                for item in data:
                    self._add_result(item)
            finally:
                self.info_label.setText(self.translator.tr("status_completed"))

        self.current_task = self.loop.create_task(run_search())

    def _add_result(self, data: Dict[str, object]) -> None:
        identifier = int(data.get("id", 0))
        if identifier in self.result_ids:
            return
        self.result_ids.add(identifier)
        self.results.append(data)
        self._apply_sort()

    def _apply_sort(self) -> None:
        if not self.results:
            self.table.setRowCount(0)
            self._update_page_label()
            return
        index = self.sort_combo.currentIndex()
        if index == 1:
            sorted_results = sorted(
                self.results,
                key=lambda item: str(item.get("username") or item.get("id") or "").lower(),
            )
        elif index == 2:
            sorted_results = sorted(
                self.results,
                key=lambda item: str(item.get("status") or ""),
            )
        else:
            sorted_results = sorted(
                self.results,
                key=lambda item: (
                    str(item.get("first_name") or "").lower(),
                    str(item.get("last_name") or "").lower(),
                ),
            )
        self.results = sorted_results
        self._display_page(self.current_page)

    def _display_page(self, page: int) -> None:
        total_pages = max(1, (len(self.results) + self.page_size - 1) // self.page_size)
        page = max(1, min(page, total_pages))
        self.current_page = page
        start = (page - 1) * self.page_size
        end = start + self.page_size
        subset = self.results[start:end]
        self.table.setRowCount(len(subset))
        for row, item in enumerate(subset):
            self.table.setItem(row, 0, QTableWidgetItem(str(item.get("first_name", ""))))
            self.table.setItem(row, 1, QTableWidgetItem(str(item.get("last_name", ""))))
            username = item.get("username") or item.get("id") or ""
            self.table.setItem(row, 2, QTableWidgetItem(str(username)))
            self.table.setItem(row, 3, QTableWidgetItem(str(item.get("phone", ""))))
            self.table.setItem(row, 4, QTableWidgetItem(self._status_text(item.get("status"))))
            self.table.setItem(row, 5, QTableWidgetItem(str(item.get("language_code", ""))))
            verified_text = self.translator.tr("yes") if item.get("is_verified") else self.translator.tr("no")
            self.table.setItem(row, 6, QTableWidgetItem(verified_text))
            flags = []
            if item.get("mutual_contact"):
                flags.append(self.translator.tr("member_flag_mutual"))
            if item.get("is_scam"):
                flags.append(self.translator.tr("member_flag_scam"))
            if item.get("is_fake"):
                flags.append(self.translator.tr("member_flag_fake"))
            if item.get("is_restricted"):
                flags.append(self.translator.tr("member_flag_restricted"))
            self.table.setItem(row, 7, QTableWidgetItem(", ".join(flags)))
        self._update_page_label(total_pages)

    def _status_text(self, status_value: Optional[str]) -> str:
        mapping = {
            "UserStatusOnline": self.translator.tr("user_status_online"),
            "UserStatusRecently": self.translator.tr("user_status_recently"),
            "UserStatusLastWeek": self.translator.tr("user_status_last_week"),
            "UserStatusLastMonth": self.translator.tr("user_status_last_month"),
            "UserStatusOffline": self.translator.tr("user_status_offline"),
            "UserStatusEmpty": self.translator.tr("user_status_empty"),
        }
        if not status_value:
            return self.translator.tr("status_unknown")
        return mapping.get(status_value, status_value)

    def _update_page_label(self, total_pages: Optional[int] = None) -> None:
        if total_pages is None:
            total_pages = max(1, (len(self.results) + self.page_size - 1) // self.page_size)
        self.page_label.setText(
            self.translator.tr("member_page", current=self.current_page, total=total_pages)
        )
        self.prev_btn.setEnabled(self.current_page > 1)
        self.next_btn.setEnabled(self.current_page < total_pages)

    def _change_page(self, delta: int) -> None:
        self._display_page(self.current_page + delta)

    def copy_selected_rows(self) -> None:
        selection = self.table.selectionModel()
        if not selection:
            return
        rows = []
        for index in selection.selectedRows():
            values = []
            for column in range(self.table.columnCount()):
                item = self.table.item(index.row(), column)
                values.append(item.text() if item else "")
            rows.append("\t".join(values))
        if rows:
            QGuiApplication.clipboard().setText("\n".join(rows))

    def _on_header_clicked(self, logical_index: int) -> None:
        if logical_index == 0:
            self.sort_combo.setCurrentIndex(0)
        elif logical_index == 2:
            self.sort_combo.setCurrentIndex(1)
        elif logical_index == 4:
            self.sort_combo.setCurrentIndex(2)

    def save_results(self) -> None:
        if not self.results:
            QMessageBox.information(
                self,
                self.translator.tr("app_title"),
                self.translator.tr("member_no_results"),
            )
            return
        default_name = self.keyword_input.text().strip() or "users"
        name, ok = QInputDialog.getText(
            self,
            self.translator.tr("member_save"),
            self.translator.tr("member_filename_prompt", default=default_name),
            text=default_name,
        )
        if not ok or not name.strip():
            return
        safe_name = re.sub(r"[^a-zA-Z0-9_-]", "_", name.strip())
        path = Path("users") / f"{safe_name}.json"
        path.write_text(json.dumps(self.results, indent=2), encoding="utf-8")
        QMessageBox.information(
            self,
            self.translator.tr("app_title"),
            self.translator.tr("member_saved", path=str(path)),
        )

    def retranslate_ui(self) -> None:
        self.session_label.setText(self.translator.tr("select_sessions"))
        self.start_btn.setText(self.translator.tr("start"))
        self.stop_btn.setText(self.translator.tr("stop"))
        self.info_label.setText(self.translator.tr("status_ready"))
        self.sort_combo.setItemText(0, self.translator.tr("member_sort_name"))
        self.sort_combo.setItemText(1, self.translator.tr("member_sort_username"))
        self.sort_combo.setItemText(2, self.translator.tr("member_sort_status"))
        self.save_btn.setText(self.translator.tr("member_save"))
        self.prev_btn.setText(self.translator.tr("previous_page"))
        self.next_btn.setText(self.translator.tr("next_page"))
        self.page_label.setText(self.translator.tr("member_page", current=1, total=1))
        self.table.setHorizontalHeaderLabels(
            [
                self.translator.tr("member_column_first_name"),
                self.translator.tr("member_column_last_name"),
                self.translator.tr("member_column_username"),
                self.translator.tr("member_column_phone"),
                self.translator.tr("member_column_status"),
                self.translator.tr("member_column_language"),
                self.translator.tr("member_column_verified"),
                self.translator.tr("member_column_flags"),
            ]
        )
        self.keyword_input.setPlaceholderText(self.translator.tr("member_keywords_placeholder"))
        self.copy_action.setText(self.translator.tr("copy_selected"))
        self._apply_sort()

class ScanTab(QWidget):
    def __init__(self, manager: TelethonManager, translator: Translator, loop: asyncio.AbstractEventLoop) -> None:
        super().__init__()
        self.manager = manager
        self.translator = translator
        self.loop = loop
        self.stop_event = asyncio.Event()
        self.current_task: Optional[asyncio.Task] = None
        self.progress_widgets: Dict[str, SessionProgressWidget] = {}
        self.results: List[Dict[str, object]] = []
        self.result_ids: set[int] = set()
        self.page_size = 20
        self.current_page = 1
        self._build_ui()

    def _build_ui(self) -> None:
        layout = QVBoxLayout(self)
        form = QFormLayout()
        self.target_label = QLabel(self.translator.tr("target_group"))
        self.target_input = QLineEdit()
        form.addRow(self.target_label, self.target_input)
        timeframe_layout = QHBoxLayout()
        self.time_value = QSpinBox()
        self.time_value.setRange(0, 10000)
        self.time_value.setValue(0)
        self.time_unit = QComboBox()
        self.time_unit.addItems(
            [
                self.translator.tr("minutes"),
                self.translator.tr("hours"),
                self.translator.tr("days"),
            ]
        )
        timeframe_layout.addWidget(self.time_value)
        timeframe_layout.addWidget(self.time_unit)
        self.timeframe_label = QLabel(self.translator.tr("timeframe"))
        form.addRow(self.timeframe_label, timeframe_layout)
        self.limit_label = QLabel(self.translator.tr("member_limit"))
        self.limit_input = QLineEdit()
        form.addRow(self.limit_label, self.limit_input)
        layout.addLayout(form)

        self.session_label = QLabel(self.translator.tr("select_sessions"))
        layout.addWidget(self.session_label)
        self.session_list = QListWidget()
        self.session_list.setSelectionMode(QListWidget.MultiSelection)
        layout.addWidget(self.session_list)

        button_layout = QHBoxLayout()
        self.start_btn = QPushButton(self.translator.tr("start"))
        self.stop_btn = QPushButton(self.translator.tr("stop"))
        self.start_btn.clicked.connect(self._on_start_clicked)
        self.stop_btn.clicked.connect(self.stop_scan)
        button_layout.addWidget(self.start_btn)
        button_layout.addWidget(self.stop_btn)
        layout.addLayout(button_layout)

        self.group_label = QLabel("")
        layout.addWidget(self.group_label)
        self.info_label = QLabel(self.translator.tr("status_ready"))
        layout.addWidget(self.info_label)

        self.scroll = QScrollArea()
        self.scroll.setWidgetResizable(True)
        self.progress_container = QWidget()
        self.progress_layout = QVBoxLayout(self.progress_container)
        self.scroll.setWidget(self.progress_container)
        layout.addWidget(self.scroll)

        controls_layout = QHBoxLayout()
        controls_layout.addWidget(QLabel(self.translator.tr("member_sort_label")))
        self.sort_combo = QComboBox()
        self.sort_combo.addItems(
            [
                self.translator.tr("member_sort_name"),
                self.translator.tr("member_sort_username"),
                self.translator.tr("member_sort_status"),
            ]
        )
        self.sort_combo.currentIndexChanged.connect(lambda: self._apply_sort(preserve_page=True))
        controls_layout.addWidget(self.sort_combo)
        controls_layout.addStretch(1)
        self.save_btn = QPushButton(self.translator.tr("member_save"))
        self.save_btn.clicked.connect(self.save_results)
        controls_layout.addWidget(self.save_btn)
        layout.addLayout(controls_layout)

        pagination = QHBoxLayout()
        self.prev_btn = QPushButton(self.translator.tr("previous_page"))
        self.next_btn = QPushButton(self.translator.tr("next_page"))
        self.prev_btn.clicked.connect(lambda: self._change_page(-1))
        self.next_btn.clicked.connect(lambda: self._change_page(1))
        self.page_label = QLabel(self.translator.tr("member_page", current=1, total=1))
        pagination.addWidget(self.prev_btn)
        pagination.addWidget(self.next_btn)
        pagination.addWidget(self.page_label)
        pagination.addStretch(1)
        layout.addLayout(pagination)

        self.table = QTableWidget(0, 6)
        self.table.setHorizontalHeaderLabels(
            [
                self.translator.tr("member_column_first_name"),
                self.translator.tr("member_column_last_name"),
                self.translator.tr("member_column_username"),
                self.translator.tr("member_column_phone"),
                self.translator.tr("member_column_status"),
                self.translator.tr("member_column_last_active"),
            ]
        )
        self.table.horizontalHeader().setSectionResizeMode(QHeaderView.Stretch)
        self.table.setEditTriggers(QTableWidget.NoEditTriggers)
        self.table.setSelectionBehavior(QAbstractItemView.SelectRows)
        self.table.setSelectionMode(QAbstractItemView.ExtendedSelection)
        self.table.setContextMenuPolicy(Qt.ActionsContextMenu)
        self.copy_action = QAction(self.translator.tr("copy_selected"), self.table)
        self.copy_action.setShortcut(QKeySequence.Copy)
        self.copy_action.triggered.connect(self.copy_selected_rows)
        self.table.addAction(self.copy_action)
        layout.addWidget(self.table)

        self.log = QTextEdit()
        self.log.setReadOnly(True)
        layout.addWidget(self.log)
        self.refresh_sessions()

    def refresh_sessions(self) -> None:
        self.session_list.clear()
        for session in self.manager.list_sessions():
            item = QListWidgetItem(session.phone)
            item.setCheckState(Qt.Unchecked)
            self.session_list.addItem(item)

    def selected_sessions(self) -> List[SessionInfo]:
        sessions: List[SessionInfo] = []
        for index in range(self.session_list.count()):
            item = self.session_list.item(index)
            if item.checkState() == Qt.Checked:
                sessions.append(SessionInfo(phone=item.text(), path=Path(f"session/{item.text()}.session")))
        return sessions

    def _timeframe(self) -> Optional[timedelta]:
        value = int(self.time_value.value())
        if value <= 0:
            return None
        unit = self.time_unit.currentIndex()
        if unit == 0:
            return timedelta(minutes=value)
        if unit == 1:
            return timedelta(hours=value)
        return timedelta(days=value)

    def stop_scan(self) -> None:
        self.stop_event.set()
        self.info_label.setText(self.translator.tr("status_stopped"))

    def _on_start_clicked(self) -> None:
        if not self.current_task or self.current_task.done():
            self.loop.create_task(self.start_scan())

    async def start_scan(self) -> None:
        if self.current_task and not self.current_task.done():
            return
        sessions = self.selected_sessions()
        if not sessions:
            QMessageBox.warning(
                self,
                self.translator.tr("app_title"),
                self.translator.tr("no_sessions_selected"),
            )
            return
        target = self.target_input.text().strip()
        if not target:
            QMessageBox.warning(
                self,
                self.translator.tr("app_title"),
                self.translator.tr("target_group"),
            )
            return
        limit_text = self.limit_input.text().strip()
        limit = None
        if limit_text:
            if not limit_text.isdigit() or int(limit_text) <= 0:
                QMessageBox.warning(
                    self,
                    self.translator.tr("app_title"),
                    self.translator.tr("invalid_limit"),
                )
                return
            limit = int(limit_text)
        timeframe = self._timeframe()
        total_members = None
        try:
            title, count = await self.manager.fetch_group_info(sessions[0], target)
            total_members = count
            group_text = self.translator.tr("group_info", title=title, count=count)
            self.group_label.setText(group_text)
            self.log.append(group_text)
        except Exception as exc:
            self.log.append(self.translator.localize_error(str(exc)))

        self.stop_event = asyncio.Event()
        self.progress_widgets.clear()
        while self.progress_layout.count():
            item = self.progress_layout.takeAt(0)
            widget = item.widget()
            if widget:
                widget.deleteLater()
        for session in sessions:
            widget = SessionProgressWidget(self.translator.tr("session_progress", phone=session.phone))
            self.progress_layout.addWidget(widget)
            self.progress_widgets[session.phone] = widget
        self.info_label.setText(self.translator.tr("status_running"))
        self.log.clear()
        self.results.clear()
        self.result_ids.clear()
        self.table.setRowCount(0)
        self.current_page = 1
        self._update_page_label()

        def update_cb(session: SessionInfo, processed: int, total: int, status: str, detail, flood=None):
            widget = self.progress_widgets.get(session.phone)
            if widget:
                status_map = {
                    "running": self.translator.tr("status_running"),
                    "completed": self.translator.tr("status_completed"),
                    "stopped": self.translator.tr("status_stopped"),
                    "error": self.translator.tr("status_error"),
                    "joining": self.translator.tr("status_joining"),
                }
                widget.setStatus(
                    status_map.get(status, status),
                    self._format_detail(detail, status),
                    total or 0,
                    processed,
                )
                if flood:
                    widget.setFlood(self.translator.tr("flood_wait", seconds=flood.seconds))
            if isinstance(detail, dict):
                self._register_result(detail)

        async def run_scan() -> None:
            result = await self.manager.scan_members(
                sessions=sessions,
                target=target,
                timeframe=timeframe,
                limit=limit,
                update_cb=update_cb,
                stop_event=self.stop_event,
                total_members=total_members,
            )
            if result:
                self.results = list(result)
                self.result_ids = {
                    int(item.get("id"))
                    for item in result
                    if isinstance(item, dict) and item.get("id") is not None
                }
                self._apply_sort()
                filename = self._save_members(target, result)
                self.log.append(
                    self.translator.tr("members_saved", count=len(result), path=str(filename))
                )
            self.info_label.setText(self.translator.tr("status_completed"))

        self.current_task = self.loop.create_task(run_scan())

    def _register_result(self, detail: Dict[str, object]) -> None:
        identifier = detail.get("id")
        try:
            user_id = int(identifier) if identifier is not None else None
        except (TypeError, ValueError):
            user_id = None
        if user_id is not None and user_id in self.result_ids:
            return
        if user_id is not None:
            self.result_ids.add(user_id)
        self.results.append(detail)
        self._apply_sort(preserve_page=True)

    def _format_detail(self, detail, status: str) -> str:
        if detail == "__joined__":
            return self.translator.tr("session_joined")
        if isinstance(detail, str) and detail.startswith("__join_error__:"):
            message = detail.split(":", 1)[1]
            return self.translator.tr(
                "session_join_error", error=self.translator.localize_error(message)
            )
        if isinstance(detail, str) and detail.startswith("__join_new__:"):
            name = detail.split(":", 1)[1]
            return self.translator.tr("join_new", name=name)
        if isinstance(detail, str) and detail.startswith("__join_existing__:"):
            name = detail.split(":", 1)[1]
            return self.translator.tr("join_existing", name=name)
        if detail == "__sending__":
            return self.translator.tr("sending_phase")
        if isinstance(detail, dict):
            username = detail.get("username") or detail.get("id")
            first = detail.get("first_name") or ""
            last = detail.get("last_name") or ""
            name = " ".join(part for part in [first, last] if part)
            status_text = self._status_text(detail.get("status"))
            pieces = [piece for piece in [name, str(username)] if piece]
            if status_text:
                pieces.append(f"({status_text})")
            return " - ".join(pieces) if pieces else status_text
        if status == "error":
            if not detail:
                return self.translator.tr("status_error")
            return self.translator.localize_error(str(detail))
        if isinstance(detail, str):
            return self.translator.localize_error(detail)
        return str(detail)

    def _status_text(self, status_value: Optional[str]) -> str:
        mapping = {
            "UserStatusOnline": self.translator.tr("user_status_online"),
            "UserStatusRecently": self.translator.tr("user_status_recently"),
            "UserStatusLastWeek": self.translator.tr("user_status_last_week"),
            "UserStatusLastMonth": self.translator.tr("user_status_last_month"),
            "UserStatusOffline": self.translator.tr("user_status_offline"),
            "UserStatusEmpty": self.translator.tr("user_status_empty"),
        }
        if not status_value:
            return self.translator.tr("status_unknown")
        return mapping.get(status_value, status_value)

    def _apply_sort(self, preserve_page: bool = False) -> None:
        if not self.results:
            self.table.setRowCount(0)
            self._update_page_label()
            return
        index = self.sort_combo.currentIndex()
        if index == 1:
            key_func = lambda item: str(item.get("username") or item.get("id") or "").lower()
        elif index == 2:
            key_func = lambda item: str(item.get("status") or "")
        else:
            key_func = lambda item: (
                str(item.get("first_name") or "").lower(),
                str(item.get("last_name") or "").lower(),
            )
        self.results = sorted(self.results, key=key_func)
        page = self.current_page if preserve_page else 1
        self._display_page(page)

    def _display_page(self, page: int) -> None:
        total_pages = max(1, (len(self.results) + self.page_size - 1) // self.page_size)
        page = max(1, min(page, total_pages))
        self.current_page = page
        start = (page - 1) * self.page_size
        end = start + self.page_size
        subset = self.results[start:end]
        self.table.setRowCount(len(subset))
        for row, item in enumerate(subset):
            self.table.setItem(row, 0, QTableWidgetItem(str(item.get("first_name", ""))))
            self.table.setItem(row, 1, QTableWidgetItem(str(item.get("last_name", ""))))
            username = item.get("username") or item.get("id") or ""
            self.table.setItem(row, 2, QTableWidgetItem(str(username)))
            self.table.setItem(row, 3, QTableWidgetItem(str(item.get("phone", ""))))
            self.table.setItem(row, 4, QTableWidgetItem(self._status_text(item.get("status"))))
            self.table.setItem(row, 5, QTableWidgetItem(self._format_last_activity(item)))
        self._update_page_label(total_pages)

    def _format_last_activity(self, item: Dict[str, object]) -> str:
        last_message = item.get("last_message_date")
        if isinstance(last_message, str):
            return last_message
        return ""

    def _update_page_label(self, total_pages: Optional[int] = None) -> None:
        if total_pages is None:
            total_pages = max(1, (len(self.results) + self.page_size - 1) // self.page_size)
        self.page_label.setText(
            self.translator.tr("member_page", current=self.current_page, total=total_pages)
        )
        self.prev_btn.setEnabled(self.current_page > 1)
        self.next_btn.setEnabled(self.current_page < total_pages)

    def _change_page(self, delta: int) -> None:
        self._display_page(self.current_page + delta)

    def copy_selected_rows(self) -> None:
        selection = self.table.selectionModel()
        if not selection:
            return
        rows = []
        for index in selection.selectedRows():
            values = []
            for column in range(self.table.columnCount()):
                item = self.table.item(index.row(), column)
                values.append(item.text() if item else "")
            rows.append("\t".join(values))
        if rows:
            QGuiApplication.clipboard().setText("\n".join(rows))

    def save_results(self) -> None:
        if not self.results:
            QMessageBox.information(
                self,
                self.translator.tr("app_title"),
                self.translator.tr("member_no_results"),
            )
            return
        default_name = self.target_input.text().strip() or "users"
        name, ok = QInputDialog.getText(
            self,
            self.translator.tr("member_save"),
            self.translator.tr("member_filename_prompt", default=default_name),
            text=default_name,
        )
        if not ok or not name.strip():
            return
        safe_name = re.sub(r"[^a-zA-Z0-9_-]", "_", name.strip())
        path = Path("users") / f"{safe_name}.json"
        path.write_text(json.dumps(self.results, indent=2), encoding="utf-8")
        QMessageBox.information(
            self,
            self.translator.tr("app_title"),
            self.translator.tr("member_saved", path=str(path)),
        )

    def _save_members(self, target: str, data: List[Dict[str, object]]) -> Path:
        safe_name = re.sub(r"[^a-zA-Z0-9_-]", "_", target)
        path = Path("users") / f"{safe_name}.json"
        path.write_text(json.dumps(data, indent=2), encoding="utf-8")
        return path

    def retranslate_ui(self) -> None:
        self.start_btn.setText(self.translator.tr("start"))
        self.stop_btn.setText(self.translator.tr("stop"))
        self.info_label.setText(self.translator.tr("status_ready"))
        self.time_unit.setItemText(0, self.translator.tr("minutes"))
        self.time_unit.setItemText(1, self.translator.tr("hours"))
        self.time_unit.setItemText(2, self.translator.tr("days"))
        self.group_label.setText("")
        self.target_label.setText(self.translator.tr("target_group"))
        self.timeframe_label.setText(self.translator.tr("timeframe"))
        self.limit_label.setText(self.translator.tr("member_limit"))
        self.session_label.setText(self.translator.tr("select_sessions"))
        self.sort_combo.setItemText(0, self.translator.tr("member_sort_name"))
        self.sort_combo.setItemText(1, self.translator.tr("member_sort_username"))
        self.sort_combo.setItemText(2, self.translator.tr("member_sort_status"))
        self.save_btn.setText(self.translator.tr("member_save"))
        self.prev_btn.setText(self.translator.tr("previous_page"))
        self.next_btn.setText(self.translator.tr("next_page"))
        total_pages = max(1, (len(self.results) + self.page_size - 1) // self.page_size)
        self.page_label.setText(
            self.translator.tr("member_page", current=self.current_page, total=total_pages)
        )
        self.table.setHorizontalHeaderLabels(
            [
                self.translator.tr("member_column_first_name"),
                self.translator.tr("member_column_last_name"),
                self.translator.tr("member_column_username"),
                self.translator.tr("member_column_phone"),
                self.translator.tr("member_column_status"),
                self.translator.tr("member_column_last_active"),
            ]
        )
        self.copy_action.setText(self.translator.tr("copy_selected"))


class MessageActivityTab(QWidget):
    def __init__(self, manager: TelethonManager, translator: Translator, loop: asyncio.AbstractEventLoop) -> None:
        super().__init__()
        self.manager = manager
        self.translator = translator
        self.loop = loop
        self.stop_event = asyncio.Event()
        self.current_task: Optional[asyncio.Task] = None
        self.progress_widgets: Dict[str, SessionProgressWidget] = {}
        self.results: List[Dict[str, object]] = []
        self.result_ids: set[int] = set()
        self.page_size = 20
        self.current_page = 1
        self._build_ui()

    def _build_ui(self) -> None:
        layout = QVBoxLayout(self)
        form = QFormLayout()
        self.target_label = QLabel(self.translator.tr("target_group"))
        self.target_input = QLineEdit()
        form.addRow(self.target_label, self.target_input)
        timeframe_layout = QHBoxLayout()
        self.time_value = QSpinBox()
        self.time_value.setRange(0, 10000)
        self.time_value.setValue(0)
        self.time_unit = QComboBox()
        self.time_unit.addItems(
            [
                self.translator.tr("minutes"),
                self.translator.tr("hours"),
                self.translator.tr("days"),
            ]
        )
        timeframe_layout.addWidget(self.time_value)
        timeframe_layout.addWidget(self.time_unit)
        self.timeframe_label = QLabel(self.translator.tr("timeframe"))
        form.addRow(self.timeframe_label, timeframe_layout)
        self.limit_label = QLabel(self.translator.tr("member_limit"))
        self.limit_input = QLineEdit()
        form.addRow(self.limit_label, self.limit_input)
        layout.addLayout(form)

        self.session_label = QLabel(self.translator.tr("select_sessions"))
        layout.addWidget(self.session_label)
        self.session_list = QListWidget()
        self.session_list.setSelectionMode(QListWidget.MultiSelection)
        layout.addWidget(self.session_list)

        button_layout = QHBoxLayout()
        self.start_btn = QPushButton(self.translator.tr("start"))
        self.stop_btn = QPushButton(self.translator.tr("stop"))
        self.start_btn.clicked.connect(self._on_start_clicked)
        self.stop_btn.clicked.connect(self.stop_scan)
        button_layout.addWidget(self.start_btn)
        button_layout.addWidget(self.stop_btn)
        layout.addLayout(button_layout)

        self.group_label = QLabel("")
        layout.addWidget(self.group_label)
        self.info_label = QLabel(self.translator.tr("status_ready"))
        layout.addWidget(self.info_label)

        self.scroll = QScrollArea()
        self.scroll.setWidgetResizable(True)
        self.progress_container = QWidget()
        self.progress_layout = QVBoxLayout(self.progress_container)
        self.scroll.setWidget(self.progress_container)
        layout.addWidget(self.scroll)

        controls_layout = QHBoxLayout()
        controls_layout.addWidget(QLabel(self.translator.tr("member_sort_label")))
        self.sort_combo = QComboBox()
        self.sort_combo.addItems(
            [
                self.translator.tr("member_sort_name"),
                self.translator.tr("member_sort_username"),
                self.translator.tr("member_sort_status"),
            ]
        )
        self.sort_combo.currentIndexChanged.connect(lambda: self._apply_sort(preserve_page=True))
        controls_layout.addWidget(self.sort_combo)
        controls_layout.addStretch(1)
        self.save_btn = QPushButton(self.translator.tr("member_save"))
        self.save_btn.clicked.connect(self.save_results)
        controls_layout.addWidget(self.save_btn)
        layout.addLayout(controls_layout)

        pagination = QHBoxLayout()
        self.prev_btn = QPushButton(self.translator.tr("previous_page"))
        self.next_btn = QPushButton(self.translator.tr("next_page"))
        self.prev_btn.clicked.connect(lambda: self._change_page(-1))
        self.next_btn.clicked.connect(lambda: self._change_page(1))
        self.page_label = QLabel(self.translator.tr("member_page", current=1, total=1))
        pagination.addWidget(self.prev_btn)
        pagination.addWidget(self.next_btn)
        pagination.addWidget(self.page_label)
        pagination.addStretch(1)
        layout.addLayout(pagination)

        self.table = QTableWidget(0, 6)
        self.table.setHorizontalHeaderLabels(
            [
                self.translator.tr("member_column_first_name"),
                self.translator.tr("member_column_last_name"),
                self.translator.tr("member_column_username"),
                self.translator.tr("member_column_phone"),
                self.translator.tr("member_column_status"),
                self.translator.tr("member_column_last_active"),
            ]
        )
        self.table.horizontalHeader().setSectionResizeMode(QHeaderView.Stretch)
        self.table.setEditTriggers(QTableWidget.NoEditTriggers)
        self.table.setSelectionBehavior(QAbstractItemView.SelectRows)
        self.table.setSelectionMode(QAbstractItemView.ExtendedSelection)
        self.table.setContextMenuPolicy(Qt.ActionsContextMenu)
        self.copy_action = QAction(self.translator.tr("copy_selected"), self.table)
        self.copy_action.setShortcut(QKeySequence.Copy)
        self.copy_action.triggered.connect(self.copy_selected_rows)
        self.table.addAction(self.copy_action)
        layout.addWidget(self.table)

        self.log = QTextEdit()
        self.log.setReadOnly(True)
        layout.addWidget(self.log)
        self.refresh_sessions()

    def refresh_sessions(self) -> None:
        self.session_list.clear()
        for session in self.manager.list_sessions():
            item = QListWidgetItem(session.phone)
            item.setCheckState(Qt.Unchecked)
            self.session_list.addItem(item)

    def selected_sessions(self) -> List[SessionInfo]:
        sessions: List[SessionInfo] = []
        for index in range(self.session_list.count()):
            item = self.session_list.item(index)
            if item.checkState() == Qt.Checked:
                sessions.append(SessionInfo(phone=item.text(), path=Path(f"session/{item.text()}.session")))
        return sessions

    def _timeframe(self) -> Optional[timedelta]:
        value = int(self.time_value.value())
        if value <= 0:
            return None
        unit = self.time_unit.currentIndex()
        if unit == 0:
            return timedelta(minutes=value)
        if unit == 1:
            return timedelta(hours=value)
        return timedelta(days=value)

    def stop_scan(self) -> None:
        self.stop_event.set()
        self.info_label.setText(self.translator.tr("status_stopped"))

    def _on_start_clicked(self) -> None:
        if not self.current_task or self.current_task.done():
            self.loop.create_task(self.start_scan())

    async def start_scan(self) -> None:
        if self.current_task and not self.current_task.done():
            return
        sessions = self.selected_sessions()
        if not sessions:
            QMessageBox.warning(
                self,
                self.translator.tr("app_title"),
                self.translator.tr("no_sessions_selected"),
            )
            return
        target = self.target_input.text().strip()
        if not target:
            QMessageBox.warning(
                self,
                self.translator.tr("app_title"),
                self.translator.tr("target_group"),
            )
            return
        limit_text = self.limit_input.text().strip()
        limit = None
        if limit_text:
            if not limit_text.isdigit() or int(limit_text) <= 0:
                QMessageBox.warning(
                    self,
                    self.translator.tr("app_title"),
                    self.translator.tr("invalid_limit"),
                )
                return
            limit = int(limit_text)
        timeframe = self._timeframe()
        try:
            title, count = await self.manager.fetch_group_info(sessions[0], target)
            group_text = self.translator.tr("group_info", title=title, count=count)
            self.group_label.setText(group_text)
            self.log.append(group_text)
        except Exception as exc:
            self.log.append(self.translator.localize_error(str(exc)))

        self.stop_event = asyncio.Event()
        self.progress_widgets.clear()
        while self.progress_layout.count():
            item = self.progress_layout.takeAt(0)
            widget = item.widget()
            if widget:
                widget.deleteLater()
        for session in sessions:
            widget = SessionProgressWidget(self.translator.tr("session_progress", phone=session.phone))
            self.progress_layout.addWidget(widget)
            self.progress_widgets[session.phone] = widget
        self.info_label.setText(self.translator.tr("status_running"))
        self.log.clear()
        self.results.clear()
        self.result_ids.clear()
        self.table.setRowCount(0)
        self.current_page = 1
        self._update_page_label()

        def update_cb(session: SessionInfo, processed: int, total: int, status: str, detail, flood=None):
            widget = self.progress_widgets.get(session.phone)
            if widget:
                status_map = {
                    "running": self.translator.tr("status_running"),
                    "completed": self.translator.tr("status_completed"),
                    "stopped": self.translator.tr("status_stopped"),
                    "error": self.translator.tr("status_error"),
                    "joining": self.translator.tr("status_joining"),
                }
                widget.setStatus(
                    status_map.get(status, status),
                    self._format_detail(detail, status),
                    total or 0,
                    processed,
                )
                if flood:
                    widget.setFlood(self.translator.tr("flood_wait", seconds=flood.seconds))
            if isinstance(detail, dict):
                self._register_result(detail)

        async def run_scan() -> None:
            result = await self.manager.scan_active_senders(
                sessions=sessions,
                target=target,
                timeframe=timeframe,
                limit=limit,
                update_cb=update_cb,
                stop_event=self.stop_event,
            )
            if result:
                self.results = list(result)
                self.result_ids = {
                    int(item.get("id"))
                    for item in result
                    if isinstance(item, dict) and item.get("id") is not None
                }
                self._apply_sort()
                filename = self._save_members(target, result)
                self.log.append(
                    self.translator.tr("message_members_saved", count=len(result), path=str(filename))
                )
            self.info_label.setText(self.translator.tr("status_completed"))

        self.current_task = self.loop.create_task(run_scan())

    def _register_result(self, detail: Dict[str, object]) -> None:
        identifier = detail.get("id")
        try:
            user_id = int(identifier) if identifier is not None else None
        except (TypeError, ValueError):
            user_id = None
        if user_id is not None and user_id in self.result_ids:
            return
        if user_id is not None:
            self.result_ids.add(user_id)
        self.results.append(detail)
        self._apply_sort(preserve_page=True)

    def _format_detail(self, detail, status: str) -> str:
        if detail == "__joined__":
            return self.translator.tr("session_joined")
        if isinstance(detail, str) and detail.startswith("__join_error__:"):
            message = detail.split(":", 1)[1]
            return self.translator.tr(
                "session_join_error", error=self.translator.localize_error(message)
            )
        if isinstance(detail, str) and detail.startswith("__join_new__:"):
            name = detail.split(":", 1)[1]
            return self.translator.tr("join_new", name=name)
        if isinstance(detail, str) and detail.startswith("__join_existing__:"):
            name = detail.split(":", 1)[1]
            return self.translator.tr("join_existing", name=name)
        if detail == "__sending__":
            return self.translator.tr("sending_phase")
        if isinstance(detail, dict):
            username = detail.get("username") or detail.get("id")
            first = detail.get("first_name") or ""
            last = detail.get("last_name") or ""
            name = " ".join(part for part in [first, last] if part)
            status_text = self._status_text(detail.get("status"))
            parts = [piece for piece in [name, str(username)] if piece]
            if status_text:
                parts.append(f"({status_text})")
            last_active = self._format_last_activity(detail)
            if last_active:
                parts.append(last_active)
            return " - ".join(parts) if parts else status_text
        if status == "error":
            if not detail:
                return self.translator.tr("status_error")
            return self.translator.localize_error(str(detail))
        if isinstance(detail, str):
            return self.translator.localize_error(detail)
        return str(detail)

    def _status_text(self, status_value: Optional[str]) -> str:
        mapping = {
            "UserStatusOnline": self.translator.tr("user_status_online"),
            "UserStatusRecently": self.translator.tr("user_status_recently"),
            "UserStatusLastWeek": self.translator.tr("user_status_last_week"),
            "UserStatusLastMonth": self.translator.tr("user_status_last_month"),
            "UserStatusOffline": self.translator.tr("user_status_offline"),
            "UserStatusEmpty": self.translator.tr("user_status_empty"),
        }
        if not status_value:
            return self.translator.tr("status_unknown")
        return mapping.get(status_value, status_value)

    def _apply_sort(self, preserve_page: bool = False) -> None:
        if not self.results:
            self.table.setRowCount(0)
            self._update_page_label()
            return
        index = self.sort_combo.currentIndex()
        if index == 1:
            key_func = lambda item: str(item.get("username") or item.get("id") or "").lower()
        elif index == 2:
            key_func = lambda item: str(item.get("status") or "")
        else:
            key_func = lambda item: (
                str(item.get("first_name") or "").lower(),
                str(item.get("last_name") or "").lower(),
            )
        self.results = sorted(self.results, key=key_func)
        page = self.current_page if preserve_page else 1
        self._display_page(page)

    def _display_page(self, page: int) -> None:
        total_pages = max(1, (len(self.results) + self.page_size - 1) // self.page_size)
        page = max(1, min(page, total_pages))
        self.current_page = page
        start = (page - 1) * self.page_size
        end = start + self.page_size
        subset = self.results[start:end]
        self.table.setRowCount(len(subset))
        for row, item in enumerate(subset):
            self.table.setItem(row, 0, QTableWidgetItem(str(item.get("first_name", ""))))
            self.table.setItem(row, 1, QTableWidgetItem(str(item.get("last_name", ""))))
            username = item.get("username") or item.get("id") or ""
            self.table.setItem(row, 2, QTableWidgetItem(str(username)))
            self.table.setItem(row, 3, QTableWidgetItem(str(item.get("phone", ""))))
            self.table.setItem(row, 4, QTableWidgetItem(self._status_text(item.get("status"))))
            self.table.setItem(row, 5, QTableWidgetItem(self._format_last_activity(item)))
        self._update_page_label(total_pages)

    def _format_last_activity(self, item: Dict[str, object]) -> str:
        last_message = item.get("last_message_date")
        if isinstance(last_message, str):
            return last_message
        return ""

    def _update_page_label(self, total_pages: Optional[int] = None) -> None:
        if total_pages is None:
            total_pages = max(1, (len(self.results) + self.page_size - 1) // self.page_size)
        self.page_label.setText(
            self.translator.tr("member_page", current=self.current_page, total=total_pages)
        )
        self.prev_btn.setEnabled(self.current_page > 1)
        self.next_btn.setEnabled(self.current_page < total_pages)

    def _change_page(self, delta: int) -> None:
        self._display_page(self.current_page + delta)

    def copy_selected_rows(self) -> None:
        selection = self.table.selectionModel()
        if not selection:
            return
        rows = []
        for index in selection.selectedRows():
            values = []
            for column in range(self.table.columnCount()):
                item = self.table.item(index.row(), column)
                values.append(item.text() if item else "")
            rows.append("\t".join(values))
        if rows:
            QGuiApplication.clipboard().setText("\n".join(rows))

    def save_results(self) -> None:
        if not self.results:
            QMessageBox.information(
                self,
                self.translator.tr("app_title"),
                self.translator.tr("member_no_results"),
            )
            return
        default_name = f"message_{self.target_input.text().strip() or 'users'}"
        name, ok = QInputDialog.getText(
            self,
            self.translator.tr("member_save"),
            self.translator.tr("member_filename_prompt", default=default_name),
            text=default_name,
        )
        if not ok or not name.strip():
            return
        safe_name = re.sub(r"[^a-zA-Z0-9_-]", "_", name.strip())
        path = Path("users") / f"{safe_name}.json"
        path.write_text(json.dumps(self.results, indent=2), encoding="utf-8")
        QMessageBox.information(
            self,
            self.translator.tr("app_title"),
            self.translator.tr("member_saved", path=str(path)),
        )

    def _save_members(self, target: str, data: List[Dict[str, object]]) -> Path:
        safe_name = re.sub(r"[^a-zA-Z0-9_-]", "_", f"message_{target}")
        path = Path("users") / f"{safe_name}.json"
        path.write_text(json.dumps(data, indent=2), encoding="utf-8")
        return path

    def retranslate_ui(self) -> None:
        self.start_btn.setText(self.translator.tr("start"))
        self.stop_btn.setText(self.translator.tr("stop"))
        self.info_label.setText(self.translator.tr("status_ready"))
        self.time_unit.setItemText(0, self.translator.tr("minutes"))
        self.time_unit.setItemText(1, self.translator.tr("hours"))
        self.time_unit.setItemText(2, self.translator.tr("days"))
        self.group_label.setText("")
        self.target_label.setText(self.translator.tr("target_group"))
        self.timeframe_label.setText(self.translator.tr("timeframe"))
        self.limit_label.setText(self.translator.tr("member_limit"))
        self.session_label.setText(self.translator.tr("select_sessions"))
        self.sort_combo.setItemText(0, self.translator.tr("member_sort_name"))
        self.sort_combo.setItemText(1, self.translator.tr("member_sort_username"))
        self.sort_combo.setItemText(2, self.translator.tr("member_sort_status"))
        self.save_btn.setText(self.translator.tr("member_save"))
        self.prev_btn.setText(self.translator.tr("previous_page"))
        self.next_btn.setText(self.translator.tr("next_page"))
        total_pages = max(1, (len(self.results) + self.page_size - 1) // self.page_size)
        self.page_label.setText(
            self.translator.tr("member_page", current=self.current_page, total=total_pages)
        )
        self.table.setHorizontalHeaderLabels(
            [
                self.translator.tr("member_column_first_name"),
                self.translator.tr("member_column_last_name"),
                self.translator.tr("member_column_username"),
                self.translator.tr("member_column_phone"),
                self.translator.tr("member_column_status"),
                self.translator.tr("member_column_last_active"),
            ]
        )
        self.copy_action.setText(self.translator.tr("copy_selected"))

class AddTab(QWidget):
    def __init__(self, manager: TelethonManager, translator: Translator, loop: asyncio.AbstractEventLoop) -> None:
        super().__init__()
        self.manager = manager
        self.translator = translator
        self.loop = loop
        self.stop_event = asyncio.Event()
        self.current_task: Optional[asyncio.Task] = None
        self.progress_widgets: Dict[str, SessionProgressWidget] = {}
        self.users: List[Dict[str, object]] = []
        self._build_ui()

    def _build_ui(self) -> None:
        layout = QVBoxLayout(self)
        form = QFormLayout()
        self.target_label = QLabel(self.translator.tr("target_group"))
        self.target_input = QLineEdit()
        form.addRow(self.target_label, self.target_input)
        file_layout = QHBoxLayout()
        self.file_input = QLineEdit()
        self.file_btn = QPushButton(self.translator.tr("choose_file"))
        self.file_btn.clicked.connect(self.choose_file)
        file_layout.addWidget(self.file_input)
        file_layout.addWidget(self.file_btn)
        self.users_label = QLabel(self.translator.tr("users_file"))
        form.addRow(self.users_label, file_layout)
        layout.addLayout(form)

        self.session_label = QLabel(self.translator.tr("select_sessions"))
        layout.addWidget(self.session_label)
        self.session_list = QListWidget()
        self.session_list.setSelectionMode(QListWidget.MultiSelection)
        layout.addWidget(self.session_list)

        button_layout = QHBoxLayout()
        self.start_btn = QPushButton(self.translator.tr("start"))
        self.stop_btn = QPushButton(self.translator.tr("stop"))
        self.start_btn.clicked.connect(self._on_start_clicked)
        self.stop_btn.clicked.connect(self.stop_add)
        button_layout.addWidget(self.start_btn)
        button_layout.addWidget(self.stop_btn)
        layout.addLayout(button_layout)

        self.info_label = QLabel(self.translator.tr("status_ready"))
        layout.addWidget(self.info_label)

        self.scroll = QScrollArea()
        self.scroll.setWidgetResizable(True)
        self.progress_container = QWidget()
        self.progress_layout = QVBoxLayout(self.progress_container)
        self.scroll.setWidget(self.progress_container)
        layout.addWidget(self.scroll)

        self.log = QTextEdit()
        self.log.setReadOnly(True)
        layout.addWidget(self.log)
        self.refresh_sessions()

    def refresh_sessions(self) -> None:
        self.session_list.clear()
        for session in self.manager.list_sessions():
            item = QListWidgetItem(session.phone)
            item.setCheckState(Qt.Unchecked)
            self.session_list.addItem(item)

    def choose_file(self) -> None:
        path, _ = QFileDialog.getOpenFileName(self, "Users", str(Path("users").absolute()), "JSON (*.json)")
        if path:
            self.file_input.setText(path)

    def selected_sessions(self) -> List[SessionInfo]:
        sessions = []
        for index in range(self.session_list.count()):
            item = self.session_list.item(index)
            if item.checkState() == Qt.Checked:
                sessions.append(SessionInfo(phone=item.text(), path=Path(f"session/{item.text()}.session")))
        return sessions

    def stop_add(self) -> None:
        self.stop_event.set()
        self.info_label.setText(self.translator.tr("status_stopped"))

    def _on_start_clicked(self) -> None:
        if not self.current_task or self.current_task.done():
            self.loop.create_task(self.start_add())

    async def start_add(self) -> None:
        if self.current_task and not self.current_task.done():
            return
        sessions = self.selected_sessions()
        if not sessions:
            QMessageBox.warning(self, self.translator.tr("app_title"), self.translator.tr("no_sessions_selected"))
            return
        target = self.target_input.text().strip()
        if not target:
            QMessageBox.warning(self, self.translator.tr("app_title"), self.translator.tr("target_group"))
            return
        file_path = Path(self.file_input.text())
        if not file_path.exists():
            QMessageBox.warning(self, self.translator.tr("app_title"), self.translator.tr("file_not_found"))
            return
        self.users = json.loads(file_path.read_text(encoding="utf-8"))
        self.stop_event = asyncio.Event()
        self.progress_widgets.clear()
        while self.progress_layout.count():
            item = self.progress_layout.takeAt(0)
            widget = item.widget()
            if widget:
                widget.deleteLater()
        for session in sessions:
            widget = SessionProgressWidget(self.translator.tr("session_progress", phone=session.phone))
            self.progress_layout.addWidget(widget)
            self.progress_widgets[session.phone] = widget
        self.info_label.setText(self.translator.tr("status_running"))
        self.log.clear()

        def update_cb(session: SessionInfo, processed: int, total: int, status: str, detail: str, flood=None):
            widget = self.progress_widgets.get(session.phone)
            if widget:
                status_map = {
                    "running": self.translator.tr("status_running"),
                    "completed": self.translator.tr("status_completed"),
                    "stopped": self.translator.tr("status_stopped"),
                    "error": self.translator.tr("status_error"),
                }
                widget.setStatus(
                    status_map.get(status, status),
                    self._format_detail(detail),
                    total,
                    processed,
                )
                if flood:
                    widget.setFlood(self.translator.tr("flood_wait", seconds=flood.seconds))

        async def run_add() -> None:
            processed_ids = await self.manager.add_members(
                sessions=sessions,
                target=target,
                users=self.users,
                update_cb=update_cb,
                stop_event=self.stop_event,
            )
            self.info_label.setText(self.translator.tr("status_completed"))
            self.log.append(self.translator.tr("adding_done"))
            remaining = [u for u in self.users if int(u.get("id", 0)) not in processed_ids]
            file_path.write_text(json.dumps(remaining, indent=2), encoding="utf-8")
            self.users = remaining

        self.current_task = self.loop.create_task(run_add())

    def _format_detail(self, detail: str) -> str:
        if detail == "already":
            return self.translator.tr("member_already")
        if isinstance(detail, str):
            return self.translator.localize_error(detail)
        return detail

    def retranslate_ui(self) -> None:
        self.start_btn.setText(self.translator.tr("start"))
        self.stop_btn.setText(self.translator.tr("stop"))
        self.info_label.setText(self.translator.tr("status_ready"))
        self.file_btn.setText(self.translator.tr("choose_file"))
        self.target_label.setText(self.translator.tr("target_group"))
        self.users_label.setText(self.translator.tr("users_file"))
        self.session_label.setText(self.translator.tr("select_sessions"))


class DirectMessageTab(QWidget):
    def __init__(self, manager: TelethonManager, translator: Translator, loop: asyncio.AbstractEventLoop) -> None:
        super().__init__()
        self.manager = manager
        self.translator = translator
        self.loop = loop
        self.stop_event = asyncio.Event()
        self.current_task: Optional[asyncio.Task] = None
        self.progress_widgets: Dict[str, SessionProgressWidget] = {}
        self._build_ui()

    def _build_ui(self) -> None:
        layout = QVBoxLayout(self)
        form = QFormLayout()
        users_layout = QHBoxLayout()
        self.users_input = QLineEdit()
        self.users_btn = QPushButton(self.translator.tr("choose_file"))
        self.users_btn.clicked.connect(self.choose_users_file)
        users_layout.addWidget(self.users_input)
        users_layout.addWidget(self.users_btn)
        form.addRow(QLabel(self.translator.tr("users_file")), users_layout)

        self.message_edit = QTextEdit()
        form.addRow(QLabel(self.translator.tr("message_body")), self.message_edit)

        media_layout = QHBoxLayout()
        self.media_input = QLineEdit()
        self.media_btn = QPushButton(self.translator.tr("choose_media"))
        self.media_btn.clicked.connect(self.choose_media_file)
        media_layout.addWidget(self.media_input)
        media_layout.addWidget(self.media_btn)
        form.addRow(QLabel(self.translator.tr("media_file")), media_layout)

        self.link_preview = QCheckBox(self.translator.tr("link_preview"))
        form.addRow(self.link_preview)
        layout.addLayout(form)

        self.session_label = QLabel(self.translator.tr("select_sessions"))
        layout.addWidget(self.session_label)
        self.session_list = QListWidget()
        layout.addWidget(self.session_list)

        button_layout = QHBoxLayout()
        self.start_btn = QPushButton(self.translator.tr("start"))
        self.stop_btn = QPushButton(self.translator.tr("stop"))
        self.start_btn.clicked.connect(self._on_start_clicked)
        self.stop_btn.clicked.connect(self.stop_send)
        button_layout.addWidget(self.start_btn)
        button_layout.addWidget(self.stop_btn)
        layout.addLayout(button_layout)

        self.info_label = QLabel(self.translator.tr("status_ready"))
        layout.addWidget(self.info_label)

        self.scroll = QScrollArea()
        self.scroll.setWidgetResizable(True)
        self.progress_container = QWidget()
        self.progress_layout = QVBoxLayout(self.progress_container)
        self.scroll.setWidget(self.progress_container)
        layout.addWidget(self.scroll)

        self.log = QTextEdit()
        self.log.setReadOnly(True)
        layout.addWidget(self.log)
        self.refresh_sessions()

    def refresh_sessions(self) -> None:
        self.session_list.clear()
        for session in self.manager.list_sessions():
            item = QListWidgetItem(session.phone)
            item.setCheckState(Qt.Unchecked)
            self.session_list.addItem(item)

    def selected_sessions(self) -> List[SessionInfo]:
        sessions: List[SessionInfo] = []
        for index in range(self.session_list.count()):
            item = self.session_list.item(index)
            if item.checkState() == Qt.Checked:
                sessions.append(SessionInfo(phone=item.text(), path=Path(f"session/{item.text()}.session")))
        return sessions

    def choose_users_file(self) -> None:
        path, _ = QFileDialog.getOpenFileName(self, "Users", str(Path("users").absolute()), "JSON (*.json)")
        if path:
            self.users_input.setText(path)

    def choose_media_file(self) -> None:
        path, _ = QFileDialog.getOpenFileName(self, "Media", str(Path.cwd()), "All Files (*.*)")
        if path:
            self.media_input.setText(path)

    def stop_send(self) -> None:
        self.stop_event.set()
        self.info_label.setText(self.translator.tr("status_stopped"))

    def _on_start_clicked(self) -> None:
        if not self.current_task or self.current_task.done():
            self.loop.create_task(self.start_send())

    async def start_send(self) -> None:
        if self.current_task and not self.current_task.done():
            return
        sessions = self.selected_sessions()
        if not sessions:
            QMessageBox.warning(self, self.translator.tr("app_title"), self.translator.tr("no_sessions_selected"))
            return
        message = self.message_edit.toPlainText().strip()
        media_path = Path(self.media_input.text()) if self.media_input.text().strip() else None
        if not message and not media_path:
            QMessageBox.warning(
                self,
                self.translator.tr("app_title"),
                self.translator.tr("message_required"),
            )
            return
        if media_path and not media_path.exists():
            QMessageBox.warning(
                self,
                self.translator.tr("app_title"),
                self.translator.tr("file_not_found"),
            )
            return
        users_path = Path(self.users_input.text())
        if not users_path.exists():
            QMessageBox.warning(
                self,
                self.translator.tr("app_title"),
                self.translator.tr("file_not_found"),
            )
            return
        try:
            users = json.loads(users_path.read_text(encoding="utf-8"))
        except ValueError as exc:
            QMessageBox.warning(
                self,
                self.translator.tr("app_title"),
                self.translator.localize_error(str(exc)),
            )
            return

        self.stop_event = asyncio.Event()
        self.progress_widgets.clear()
        while self.progress_layout.count():
            item = self.progress_layout.takeAt(0)
            widget = item.widget()
            if widget:
                widget.deleteLater()
        for session in sessions:
            widget = SessionProgressWidget(self.translator.tr("session_progress", phone=session.phone))
            self.progress_layout.addWidget(widget)
            self.progress_widgets[session.phone] = widget
        self.info_label.setText(self.translator.tr("status_running"))
        self.log.clear()

        def update_cb(session: SessionInfo, processed: int, total: int, status: str, detail: str, flood=None):
            widget = self.progress_widgets.get(session.phone)
            if widget:
                status_map = {
                    "running": self.translator.tr("status_running"),
                    "completed": self.translator.tr("status_completed"),
                    "stopped": self.translator.tr("status_stopped"),
                    "error": self.translator.tr("status_error"),
                    "joining": self.translator.tr("status_joining"),
                }
                label = status_map.get(status, status)
                display_detail = detail
                if isinstance(detail, str):
                    if detail.startswith("__join_new__:"):
                        label = self.translator.tr("status_joining")
                        display_detail = self.translator.tr(
                            "join_new", name=detail.split(":", 1)[1]
                        )
                    elif detail.startswith("__join_existing__:"):
                        label = self.translator.tr("status_joining")
                        display_detail = self.translator.tr(
                            "join_existing", name=detail.split(":", 1)[1]
                        )
                    elif detail == "__sending__":
                        label = self.translator.tr("status_sending")
                        display_detail = self.translator.tr("sending_phase")
                    elif status == "error":
                        display_detail = self.translator.localize_error(detail)
                    else:
                        display_detail = self.translator.localize_error(detail)
                widget.setStatus(label, display_detail, total or 0, processed)
                if isinstance(display_detail, str) and display_detail:
                    self.log.append(f"{session.phone}: {display_detail}")
                if flood:
                    widget.setFlood(self.translator.tr("flood_wait", seconds=flood.seconds))

        async def run_send() -> None:
            await self.manager.send_direct_messages(
                sessions=sessions,
                users=users,
                message=message,
                media_path=media_path,
                link_preview=self.link_preview.isChecked(),
                update_cb=update_cb,
                stop_event=self.stop_event,
            )
            self.info_label.setText(self.translator.tr("status_completed"))

        self.current_task = self.loop.create_task(run_send())

    def retranslate_ui(self) -> None:
        self.users_btn.setText(self.translator.tr("choose_file"))
        self.media_btn.setText(self.translator.tr("choose_media"))
        self.link_preview.setText(self.translator.tr("link_preview"))
        self.session_label.setText(self.translator.tr("select_sessions"))
        self.start_btn.setText(self.translator.tr("start"))
        self.stop_btn.setText(self.translator.tr("stop"))
        self.info_label.setText(self.translator.tr("status_ready"))


class GroupBroadcastTab(QWidget):
    def __init__(self, manager: TelethonManager, translator: Translator, loop: asyncio.AbstractEventLoop) -> None:
        super().__init__()
        self.manager = manager
        self.translator = translator
        self.loop = loop
        self.stop_event = asyncio.Event()
        self.current_task: Optional[asyncio.Task] = None
        self.progress_widgets: Dict[str, SessionProgressWidget] = {}
        self._build_ui()

    def _build_ui(self) -> None:
        layout = QVBoxLayout(self)
        form = QFormLayout()
        groups_layout = QHBoxLayout()
        self.groups_input = QLineEdit()
        self.groups_btn = QPushButton(self.translator.tr("choose_file"))
        self.groups_btn.clicked.connect(self.choose_groups_file)
        groups_layout.addWidget(self.groups_input)
        groups_layout.addWidget(self.groups_btn)
        form.addRow(QLabel(self.translator.tr("groups_file")), groups_layout)

        self.message_edit = QTextEdit()
        form.addRow(QLabel(self.translator.tr("message_body")), self.message_edit)

        media_layout = QHBoxLayout()
        self.media_input = QLineEdit()
        self.media_btn = QPushButton(self.translator.tr("choose_media"))
        self.media_btn.clicked.connect(self.choose_media_file)
        media_layout.addWidget(self.media_input)
        media_layout.addWidget(self.media_btn)
        form.addRow(QLabel(self.translator.tr("media_file")), media_layout)

        self.link_preview = QCheckBox(self.translator.tr("link_preview"))
        form.addRow(self.link_preview)
        layout.addLayout(form)

        self.session_label = QLabel(self.translator.tr("select_sessions"))
        layout.addWidget(self.session_label)
        self.session_list = QListWidget()
        layout.addWidget(self.session_list)

        button_layout = QHBoxLayout()
        self.start_btn = QPushButton(self.translator.tr("start"))
        self.stop_btn = QPushButton(self.translator.tr("stop"))
        self.start_btn.clicked.connect(self._on_start_clicked)
        self.stop_btn.clicked.connect(self.stop_send)
        button_layout.addWidget(self.start_btn)
        button_layout.addWidget(self.stop_btn)
        layout.addLayout(button_layout)

        self.info_label = QLabel(self.translator.tr("status_ready"))
        layout.addWidget(self.info_label)

        self.scroll = QScrollArea()
        self.scroll.setWidgetResizable(True)
        self.progress_container = QWidget()
        self.progress_layout = QVBoxLayout(self.progress_container)
        self.scroll.setWidget(self.progress_container)
        layout.addWidget(self.scroll)

        self.log = QTextEdit()
        self.log.setReadOnly(True)
        layout.addWidget(self.log)
        self.refresh_sessions()

    def refresh_sessions(self) -> None:
        self.session_list.clear()
        for session in self.manager.list_sessions():
            item = QListWidgetItem(session.phone)
            item.setCheckState(Qt.Unchecked)
            self.session_list.addItem(item)

    def selected_sessions(self) -> List[SessionInfo]:
        sessions: List[SessionInfo] = []
        for index in range(self.session_list.count()):
            item = self.session_list.item(index)
            if item.checkState() == Qt.Checked:
                sessions.append(SessionInfo(phone=item.text(), path=Path(f"session/{item.text()}.session")))
        return sessions

    def choose_groups_file(self) -> None:
        path, _ = QFileDialog.getOpenFileName(self, "Groups", str(Path("groups").absolute()), "JSON (*.json)")
        if path:
            self.groups_input.setText(path)

    def choose_media_file(self) -> None:
        path, _ = QFileDialog.getOpenFileName(self, "Media", str(Path.cwd()), "All Files (*.*)")
        if path:
            self.media_input.setText(path)

    def stop_send(self) -> None:
        self.stop_event.set()
        self.info_label.setText(self.translator.tr("status_stopped"))

    def _on_start_clicked(self) -> None:
        if not self.current_task or self.current_task.done():
            self.loop.create_task(self.start_send())

    async def start_send(self) -> None:
        if self.current_task and not self.current_task.done():
            return
        sessions = self.selected_sessions()
        if not sessions:
            QMessageBox.warning(self, self.translator.tr("app_title"), self.translator.tr("no_sessions_selected"))
            return
        message = self.message_edit.toPlainText().strip()
        media_path = Path(self.media_input.text()) if self.media_input.text().strip() else None
        if not message and not media_path:
            QMessageBox.warning(
                self,
                self.translator.tr("app_title"),
                self.translator.tr("message_required"),
            )
            return
        if media_path and not media_path.exists():
            QMessageBox.warning(
                self,
                self.translator.tr("app_title"),
                self.translator.tr("file_not_found"),
            )
            return
        groups_path = Path(self.groups_input.text())
        if not groups_path.exists():
            QMessageBox.warning(
                self,
                self.translator.tr("app_title"),
                self.translator.tr("file_not_found"),
            )
            return
        try:
            groups = json.loads(groups_path.read_text(encoding="utf-8"))
        except ValueError as exc:
            QMessageBox.warning(
                self,
                self.translator.tr("app_title"),
                self.translator.localize_error(str(exc)),
            )
            return

        self.stop_event = asyncio.Event()
        self.progress_widgets.clear()
        while self.progress_layout.count():
            item = self.progress_layout.takeAt(0)
            widget = item.widget()
            if widget:
                widget.deleteLater()
        for session in sessions:
            widget = SessionProgressWidget(self.translator.tr("session_progress", phone=session.phone))
            self.progress_layout.addWidget(widget)
            self.progress_widgets[session.phone] = widget
        self.info_label.setText(self.translator.tr("status_running"))
        self.log.clear()

        def update_cb(session: SessionInfo, processed: int, total: int, status: str, detail: str, flood=None):
            widget = self.progress_widgets.get(session.phone)
            if widget:
                status_map = {
                    "running": self.translator.tr("status_running"),
                    "completed": self.translator.tr("status_completed"),
                    "stopped": self.translator.tr("status_stopped"),
                    "error": self.translator.tr("status_error"),
                    "joining": self.translator.tr("status_joining"),
                }
                label = status_map.get(status, status)
                display_detail = detail
                if isinstance(detail, str):
                    if detail.startswith("__join_new__:"):
                        label = self.translator.tr("status_joining")
                        display_detail = self.translator.tr(
                            "join_new", name=detail.split(":", 1)[1]
                        )
                    elif detail.startswith("__join_existing__:"):
                        label = self.translator.tr("status_joining")
                        display_detail = self.translator.tr(
                            "join_existing", name=detail.split(":", 1)[1]
                        )
                    elif detail == "__sending__":
                        label = self.translator.tr("status_sending")
                        display_detail = self.translator.tr("sending_phase")
                widget.setStatus(label, display_detail, total or 0, processed)
                if isinstance(display_detail, str) and display_detail:
                    self.log.append(f"{session.phone}: {display_detail}")
                if flood:
                    widget.setFlood(self.translator.tr("flood_wait", seconds=flood.seconds))

        async def run_send() -> None:
            await self.manager.send_group_messages(
                sessions=sessions,
                groups=groups,
                message=message,
                media_path=media_path,
                link_preview=self.link_preview.isChecked(),
                update_cb=update_cb,
                stop_event=self.stop_event,
            )
            self.info_label.setText(self.translator.tr("status_completed"))

        self.current_task = self.loop.create_task(run_send())

    def retranslate_ui(self) -> None:
        self.groups_btn.setText(self.translator.tr("choose_file"))
        self.media_btn.setText(self.translator.tr("choose_media"))
        self.link_preview.setText(self.translator.tr("link_preview"))
        self.session_label.setText(self.translator.tr("select_sessions"))
        self.start_btn.setText(self.translator.tr("start"))
        self.stop_btn.setText(self.translator.tr("stop"))
        self.info_label.setText(self.translator.tr("status_ready"))

    def _format_detail(self, detail: str) -> str:
        if detail == "already":
            return self.translator.tr("member_already")
        if isinstance(detail, str):
            return self.translator.localize_error(detail)
        return detail


class SettingsTab(QWidget):
    def __init__(self, manager: TelethonManager, translator: Translator) -> None:
        super().__init__()
        self.manager = manager
        self.translator = translator
        self._build_ui()

    def _build_ui(self) -> None:
        layout = QVBoxLayout(self)
        self.language_label = QLabel(self.translator.tr("language_label"))
        self.language_combo = QComboBox()
        for code, label in self.translator.available_languages().items():
            self.language_combo.addItem(label, code)
        current_index = self.language_combo.findData(self.translator.current_language())
        if current_index >= 0:
            self.language_combo.setCurrentIndex(current_index)
        layout.addWidget(self.language_label)
        layout.addWidget(self.language_combo)

        self.timezone_label = QLabel(self.translator.tr("timezone_label"))
        self.timezone_combo = QComboBox()
        tz_options = list(self.manager.available_timezones())
        if self.manager.timezone_name not in tz_options:
            tz_options.insert(0, self.manager.timezone_name)
        for tz in tz_options:
            self.timezone_combo.addItem(tz)
        layout.addWidget(self.timezone_label)
        layout.addWidget(self.timezone_combo)

        self.rate_box = QGroupBox(self.translator.tr("rate_limits"))
        rate_layout = QFormLayout(self.rate_box)
        self.delay_actions = QSpinBox()
        self.delay_actions.setRange(1, 120)
        self.delay_sessions = QSpinBox()
        self.delay_sessions.setRange(1, 120)
        self.delay_messages = QSpinBox()
        self.delay_messages.setRange(1, 600)
        self.delay_group_messages = QSpinBox()
        self.delay_group_messages.setRange(1, 600)
        self.delay_join_requests = QSpinBox()
        self.delay_join_requests.setRange(1, 600)
        self.delay_actions_label = QLabel(self.translator.tr("delay_between_actions"))
        self.delay_sessions_label = QLabel(self.translator.tr("delay_between_sessions"))
        self.delay_messages_label = QLabel(self.translator.tr("delay_between_messages"))
        self.delay_group_messages_label = QLabel(self.translator.tr("delay_between_group_messages"))
        self.delay_join_requests_label = QLabel(
            self.translator.tr("delay_between_join_requests")
        )
        rate_layout.addRow(self.delay_actions_label, self.delay_actions)
        rate_layout.addRow(self.delay_sessions_label, self.delay_sessions)
        rate_layout.addRow(self.delay_messages_label, self.delay_messages)
        rate_layout.addRow(self.delay_group_messages_label, self.delay_group_messages)
        rate_layout.addRow(self.delay_join_requests_label, self.delay_join_requests)
        layout.addWidget(self.rate_box)

        self.save_btn = QPushButton(self.translator.tr("save_settings"))
        layout.addWidget(self.save_btn)
        layout.addStretch(1)
        self.save_btn.clicked.connect(self.save_settings)
        self.load_settings()

    def load_settings(self) -> None:
        limits: RateLimits = self.manager.rate_limits
        self.delay_actions.setValue(int(limits.delay_between_actions))
        self.delay_sessions.setValue(int(limits.delay_between_sessions))
        self.delay_messages.setValue(int(limits.delay_between_messages))
        self.delay_group_messages.setValue(int(limits.delay_between_group_messages))
        self.delay_join_requests.setValue(int(limits.delay_between_join_requests))
        tz_index = self.timezone_combo.findText(self.manager.timezone_name)
        if tz_index >= 0:
            self.timezone_combo.setCurrentIndex(tz_index)

    def save_settings(self) -> None:
        self.manager.rate_limits.delay_between_actions = float(self.delay_actions.value())
        self.manager.rate_limits.delay_between_sessions = float(self.delay_sessions.value())
        self.manager.rate_limits.delay_between_messages = float(self.delay_messages.value())
        self.manager.rate_limits.delay_between_group_messages = float(
            self.delay_group_messages.value()
        )
        self.manager.rate_limits.delay_between_join_requests = float(
            self.delay_join_requests.value()
        )
        self.manager.rate_limits.save()
        selected_tz = self.timezone_combo.currentText()
        if selected_tz:
            self.manager.set_timezone(selected_tz)
        QMessageBox.information(self, self.translator.tr("app_title"), self.translator.tr("settings_saved"))

    def retranslate_ui(self) -> None:
        self.rate_box.setTitle(self.translator.tr("rate_limits"))
        self.save_btn.setText(self.translator.tr("save_settings"))
        self.language_label.setText(self.translator.tr("language_label"))
        self.timezone_label.setText(self.translator.tr("timezone_label"))
        self.delay_actions_label.setText(self.translator.tr("delay_between_actions"))
        self.delay_sessions_label.setText(self.translator.tr("delay_between_sessions"))
        self.delay_messages_label.setText(self.translator.tr("delay_between_messages"))
        self.delay_group_messages_label.setText(
            self.translator.tr("delay_between_group_messages")
        )
        self.delay_join_requests_label.setText(
            self.translator.tr("delay_between_join_requests")
        )


class MainWindow(QMainWindow):
    def __init__(self, manager: TelethonManager, translator: Translator, loop: asyncio.AbstractEventLoop) -> None:
        super().__init__()
        self.manager = manager
        self.translator = translator
        self.loop = loop
        self.setWindowTitle(self.translator.tr("app_title"))
        self.tabs = QTabWidget()
        self.sessions_tab = SessionsTab(manager, translator, loop, self._on_sessions_updated)
        self.ban_tab = BanTab(manager, translator, loop)
        self.group_search_tab = GroupSearchTab(manager, translator, loop)
        self.member_search_tab = MemberSearchTab(manager, translator, loop)
        self.scan_tab = ScanTab(manager, translator, loop)
        self.message_activity_tab = MessageActivityTab(manager, translator, loop)
        self.add_tab = AddTab(manager, translator, loop)
        self.direct_tab = DirectMessageTab(manager, translator, loop)
        self.broadcast_tab = GroupBroadcastTab(manager, translator, loop)
        self.settings_tab = SettingsTab(manager, translator)
        self.tabs.addTab(self.sessions_tab, self.translator.tr("tab_sessions"))
        self.tabs.addTab(self.ban_tab, self.translator.tr("tab_ban"))
        self.tabs.addTab(self.group_search_tab, self.translator.tr("tab_group_search"))
        self.tabs.addTab(self.member_search_tab, self.translator.tr("tab_member_search"))
        self.tabs.addTab(self.scan_tab, self.translator.tr("tab_scan"))
        self.tabs.addTab(self.message_activity_tab, self.translator.tr("tab_active_messages"))
        self.tabs.addTab(self.add_tab, self.translator.tr("tab_add"))
        self.tabs.addTab(self.direct_tab, self.translator.tr("tab_dm"))
        self.tabs.addTab(self.broadcast_tab, self.translator.tr("tab_group_broadcast"))
        self.tabs.addTab(self.settings_tab, self.translator.tr("tab_settings"))
        self.setCentralWidget(self.tabs)
        self.settings_tab.language_combo.currentIndexChanged.connect(self.change_language)
        self.tabs.currentChanged.connect(self.handle_tab_change)
        self._on_sessions_updated()

    def change_language(self) -> None:
        lang = self.settings_tab.language_combo.currentData()
        if lang:
            self.translator.set_language(lang)
            self.manager.save_language_preference(lang)
            self.retranslate_ui()

    def handle_tab_change(self, index: int) -> None:
        if index == 2:
            self.group_search_tab.refresh_sessions()
        elif index == 3:
            self.member_search_tab.refresh_sessions()
        elif index == 4:
            self.scan_tab.refresh_sessions()
        elif index == 5:
            self.message_activity_tab.refresh_sessions()
        elif index == 6:
            self.add_tab.refresh_sessions()
        elif index == 7:
            self.direct_tab.refresh_sessions()
        elif index == 8:
            self.broadcast_tab.refresh_sessions()

    def retranslate_ui(self) -> None:
        self.setWindowTitle(self.translator.tr("app_title"))
        self.tabs.setTabText(0, self.translator.tr("tab_sessions"))
        self.tabs.setTabText(1, self.translator.tr("tab_ban"))
        self.tabs.setTabText(2, self.translator.tr("tab_group_search"))
        self.tabs.setTabText(3, self.translator.tr("tab_member_search"))
        self.tabs.setTabText(4, self.translator.tr("tab_scan"))
        self.tabs.setTabText(5, self.translator.tr("tab_active_messages"))
        self.tabs.setTabText(6, self.translator.tr("tab_add"))
        self.tabs.setTabText(7, self.translator.tr("tab_dm"))
        self.tabs.setTabText(8, self.translator.tr("tab_group_broadcast"))
        self.tabs.setTabText(9, self.translator.tr("tab_settings"))
        self.sessions_tab.retranslate_ui()
        self.ban_tab.retranslate_ui()
        self.group_search_tab.retranslate_ui()
        self.member_search_tab.retranslate_ui()
        self.scan_tab.retranslate_ui()
        self.message_activity_tab.retranslate_ui()
        self.add_tab.retranslate_ui()
        self.direct_tab.retranslate_ui()
        self.broadcast_tab.retranslate_ui()
        self.settings_tab.retranslate_ui()

    def _on_sessions_updated(self) -> None:
        self.group_search_tab.refresh_sessions()
        self.member_search_tab.refresh_sessions()
        self.scan_tab.refresh_sessions()
        self.add_tab.refresh_sessions()
        self.message_activity_tab.refresh_sessions()
        self.direct_tab.refresh_sessions()
        self.broadcast_tab.refresh_sessions()


__all__ = ["MainWindow"]
