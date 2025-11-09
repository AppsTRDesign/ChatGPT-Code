"""Qt GUI for the Telegram automation suite."""
from __future__ import annotations

import asyncio
import json
import re
from datetime import timedelta
from pathlib import Path
from typing import Dict, List, Optional

from PySide6.QtCore import Qt
from PySide6.QtWidgets import (
    QComboBox,
    QFileDialog,
    QFormLayout,
    QGroupBox,
    QHBoxLayout,
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
        self.send_code_btn.clicked.connect(lambda: asyncio.ensure_future(self.handle_send_code()))
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

    async def handle_send_code(self) -> None:
        phone = self.phone_input.text().strip()
        if not phone:
            return
        try:
            client = await self.manager.send_login_code(phone)
        except Exception as exc:
            QMessageBox.critical(self, self.translator.tr("app_title"), str(exc))
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
            QMessageBox.critical(
                self,
                self.translator.tr("app_title"),
                self.translator.tr("login_failure", phone=phone, error=str(exc)),
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
        self.button.clicked.connect(lambda: asyncio.ensure_future(self.handle_ban_check()))
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

    def retranslate_ui(self) -> None:
        self.button.setText(self.translator.tr("ban_check"))


class ScanTab(QWidget):
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
        self.start_btn.clicked.connect(lambda: asyncio.ensure_future(self.start_scan()))
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
        sessions = []
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

    async def start_scan(self) -> None:
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
        limit_text = self.limit_input.text().strip()
        limit = None
        if limit_text:
            if not limit_text.isdigit() or int(limit_text) <= 0:
                QMessageBox.warning(self, self.translator.tr("app_title"), self.translator.tr("invalid_limit"))
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
            self.log.append(str(exc))
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
                widget.setStatus(status_map.get(status, status), detail, total, processed)
                if flood:
                    widget.setFlood(self.translator.tr("flood_wait", seconds=flood.seconds))

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
                filename = self._save_members(target, result)
                self.log.append(self.translator.tr("members_saved", count=len(result), path=str(filename)))
            self.info_label.setText(self.translator.tr("status_completed"))

        self.current_task = asyncio.ensure_future(run_scan())
        await self.current_task

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
        self.start_btn.clicked.connect(lambda: asyncio.ensure_future(self.start_add()))
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
                widget.setStatus(status_map.get(status, status), detail, total, processed)
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

        self.current_task = asyncio.ensure_future(run_add())
        await self.current_task

    def retranslate_ui(self) -> None:
        self.start_btn.setText(self.translator.tr("start"))
        self.stop_btn.setText(self.translator.tr("stop"))
        self.info_label.setText(self.translator.tr("status_ready"))
        self.file_btn.setText(self.translator.tr("choose_file"))
        self.target_label.setText(self.translator.tr("target_group"))
        self.users_label.setText(self.translator.tr("users_file"))
        self.session_label.setText(self.translator.tr("select_sessions"))


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

        self.rate_box = QGroupBox(self.translator.tr("rate_limits"))
        rate_layout = QFormLayout(self.rate_box)
        self.delay_actions = QSpinBox()
        self.delay_actions.setRange(1, 120)
        self.delay_sessions = QSpinBox()
        self.delay_sessions.setRange(1, 120)
        self.delay_actions_label = QLabel(self.translator.tr("delay_between_actions"))
        self.delay_sessions_label = QLabel(self.translator.tr("delay_between_sessions"))
        rate_layout.addRow(self.delay_actions_label, self.delay_actions)
        rate_layout.addRow(self.delay_sessions_label, self.delay_sessions)
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

    def save_settings(self) -> None:
        self.manager.rate_limits.delay_between_actions = float(self.delay_actions.value())
        self.manager.rate_limits.delay_between_sessions = float(self.delay_sessions.value())
        self.manager.rate_limits.save()
        QMessageBox.information(self, "Info", self.translator.tr("save_settings"))

    def retranslate_ui(self) -> None:
        self.rate_box.setTitle(self.translator.tr("rate_limits"))
        self.save_btn.setText(self.translator.tr("save_settings"))
        self.language_label.setText(self.translator.tr("language_label"))
        self.delay_actions_label.setText(self.translator.tr("delay_between_actions"))
        self.delay_sessions_label.setText(self.translator.tr("delay_between_sessions"))


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
        self.scan_tab = ScanTab(manager, translator, loop)
        self.add_tab = AddTab(manager, translator, loop)
        self.settings_tab = SettingsTab(manager, translator)
        self.tabs.addTab(self.sessions_tab, self.translator.tr("tab_sessions"))
        self.tabs.addTab(self.ban_tab, self.translator.tr("tab_ban"))
        self.tabs.addTab(self.scan_tab, self.translator.tr("tab_scan"))
        self.tabs.addTab(self.add_tab, self.translator.tr("tab_add"))
        self.tabs.addTab(self.settings_tab, self.translator.tr("tab_settings"))
        self.setCentralWidget(self.tabs)
        self.settings_tab.language_combo.currentIndexChanged.connect(self.change_language)
        self.tabs.currentChanged.connect(self.handle_tab_change)
        self._on_sessions_updated()

    def change_language(self) -> None:
        lang = self.settings_tab.language_combo.currentData()
        if lang:
            self.translator.set_language(lang)
            self.retranslate_ui()

    def handle_tab_change(self, index: int) -> None:
        if index == 2:
            self.scan_tab.refresh_sessions()
        elif index == 3:
            self.add_tab.refresh_sessions()

    def retranslate_ui(self) -> None:
        self.setWindowTitle(self.translator.tr("app_title"))
        self.tabs.setTabText(0, self.translator.tr("tab_sessions"))
        self.tabs.setTabText(1, self.translator.tr("tab_ban"))
        self.tabs.setTabText(2, self.translator.tr("tab_scan"))
        self.tabs.setTabText(3, self.translator.tr("tab_add"))
        self.tabs.setTabText(4, self.translator.tr("tab_settings"))
        self.sessions_tab.retranslate_ui()
        self.ban_tab.retranslate_ui()
        self.scan_tab.retranslate_ui()
        self.add_tab.retranslate_ui()
        self.settings_tab.retranslate_ui()

    def _on_sessions_updated(self) -> None:
        self.scan_tab.refresh_sessions()
        self.add_tab.refresh_sessions()


__all__ = ["MainWindow"]
