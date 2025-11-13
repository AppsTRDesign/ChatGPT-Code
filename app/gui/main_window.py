from __future__ import annotations

import asyncio
from datetime import timedelta
from functools import partial
from pathlib import Path
from typing import Dict, List, Optional, Tuple

from PySide6.QtCore import Qt
from PySide6.QtGui import QCloseEvent
from PySide6.QtWidgets import (
    QApplication,
    QCheckBox,
    QComboBox,
    QDialog,
    QDialogButtonBox,
    QFileDialog,
    QFormLayout,
    QGridLayout,
    QGroupBox,
    QHBoxLayout,
    QHeaderView,
    QLabel,
    QLineEdit,
    QListWidget,
    QListWidgetItem,
    QMainWindow,
    QMessageBox,
    QPushButton,
    QSpinBox,
    QTabWidget,
    QTableWidget,
    QTableWidgetItem,
    QTextEdit,
    QVBoxLayout,
    QWidget,
)

from telethon import functions, types

from app.core.license import LicenseError, LicenseManager
from app.core.logger import configure_logging
from app.core.session_manager import PendingLogin, SessionManager
from app.core.settings import POPULAR_TIMEZONES, SettingsRepository
from app.core.tasks import ProgressUpdate, TaskOrchestrator
from app.data.user_storage import StoredUser, UserStorage
from app.gui.widgets.session_progress import SessionProgressWidget
from app.gui.workers import SessionWorkerThread, TaskRequest
from app.i18n.strings import translator


class MainWindow(QMainWindow):
    def __init__(self, settings_repo: SettingsRepository, parent: Optional[QWidget] = None) -> None:
        super().__init__(parent)
        self.settings_repo = settings_repo
        self.settings = self.settings_repo.load()
        translator.set_language(self.settings.language)
        self.settings.ensure_directories()
        log_file = self.settings.log_directory / "application.log"
        configure_logging(log_file)

        license_path = Path("config") / "license.json"
        self.license_manager = LicenseManager(license_path)
        self.license_info = self.license_manager.get_info()

        self.session_manager = SessionManager(self.settings)
        self.user_storages = {
            "scanned": UserStorage(
                self.settings.user_directory / "scanned_users.json",
                timezone_name=self.settings.timezone,
            ),
            "active": UserStorage(
                self.settings.user_directory / "active_users.json",
                timezone_name=self.settings.timezone,
            ),
            "scanned_added": UserStorage(
                self.settings.user_directory / "scanned_added_users.json",
                timezone_name=self.settings.timezone,
            ),
            "active_added": UserStorage(
                self.settings.user_directory / "active_added_users.json",
                timezone_name=self.settings.timezone,
            ),
        }
        self.user_table_meta: List[Tuple[str, bool, str]] = [
            ("scanned", False, "tab.users_scanned"),
            ("active", True, "tab.users_active"),
            ("scanned_added", False, "tab.users_scanned_added"),
            ("active_added", True, "tab.users_active_added"),
        ]
        self.orchestrator = TaskOrchestrator(self.settings)

        self.pending_login: Optional[PendingLogin] = None
        self.worker_threads: Dict[tuple[str, str], SessionWorkerThread] = {}
        self.progress_widgets: Dict[str, Dict[str, SessionProgressWidget]] = {
            "scan": {},
            "add": {},
            "active": {},
        }
        self._pending_completion_notifications: set[str] = set()
        self._cancelling = False

        self.setWindowTitle(translator.translate("app.title"))
        self.resize(1280, 860)

        self.tab_widget = QTabWidget()
        self.setCentralWidget(self.tab_widget)

        self._build_sessions_tab()
        self._build_ban_tab()
        self._build_scan_tab()
        self._build_add_tab()
        self._build_active_tab()
        self._build_rate_tab()
        self._build_settings_tab()
        self._build_user_tab()

        self.refresh_sessions()
        self.populate_user_tables()
        self._update_task_controls()

    # region builders
    def _build_sessions_tab(self) -> None:
        tab = QWidget()
        layout = QGridLayout()

        credentials_group = QGroupBox(translator.translate("tab.sessions"))
        credentials_layout = QFormLayout()
        self.api_id_input = QLineEdit(str(self.settings.api_id or ""))
        self.api_hash_input = QLineEdit(self.settings.api_hash or "")
        self.phone_input = QLineEdit()
        self.session_name_input = QLineEdit()
        credentials_layout.addRow(translator.translate("label.api_id"), self.api_id_input)
        credentials_layout.addRow(translator.translate("label.api_hash"), self.api_hash_input)
        credentials_layout.addRow(translator.translate("label.phone"), self.phone_input)
        credentials_layout.addRow(translator.translate("label.session_name"), self.session_name_input)
        credentials_group.setLayout(credentials_layout)

        login_button = QPushButton(translator.translate("button.login"))
        login_button.clicked.connect(self.handle_login)
        confirm_button = QPushButton(translator.translate("button.confirm_code"))
        confirm_button.clicked.connect(self.handle_confirm_code)
        self.code_input = QLineEdit()
        self.password_input = QLineEdit()
        self.password_input.setEchoMode(QLineEdit.Password)

        layout.addWidget(credentials_group, 0, 0, 1, 2)
        layout.addWidget(QLabel(translator.translate("label.code")), 1, 0)
        layout.addWidget(self.code_input, 1, 1)
        layout.addWidget(QLabel(translator.translate("label.password")), 2, 0)
        layout.addWidget(self.password_input, 2, 1)
        layout.addWidget(login_button, 3, 0)
        layout.addWidget(confirm_button, 3, 1)

        self.session_list = QListWidget()
        self.session_list.setSelectionMode(QListWidget.MultiSelection)
        refresh_button = QPushButton(translator.translate("button.refresh_sessions"))
        refresh_button.clicked.connect(self.refresh_sessions)

        layout.addWidget(self.session_list, 0, 2, 4, 1)
        layout.addWidget(refresh_button, 4, 2)

        tab.setLayout(layout)
        self.tab_widget.addTab(tab, translator.translate("tab.sessions"))

    def _build_ban_tab(self) -> None:
        tab = QWidget()
        layout = QVBoxLayout()
        self.ban_result = QTextEdit()
        self.ban_result.setReadOnly(True)
        check_button = QPushButton(translator.translate("button.check_ban"))
        check_button.clicked.connect(self.handle_ban_check)
        layout.addWidget(check_button)
        layout.addWidget(self.ban_result)
        tab.setLayout(layout)
        self.tab_widget.addTab(tab, translator.translate("tab.ban_check"))

    def _build_scan_tab(self) -> None:
        tab = QWidget()
        layout = QGridLayout()

        self.scan_session_list = QListWidget()
        self.scan_session_list.setSelectionMode(QListWidget.MultiSelection)
        layout.addWidget(self.scan_session_list, 0, 0, 4, 1)

        form_layout = QFormLayout()
        self.scan_target_input = QLineEdit()
        self.scan_limit_input = QLineEdit()
        self.scan_interval_combo = QComboBox()
        self.scan_interval_combo.addItems(
            [
                translator.translate("label.interval_minutes"),
                translator.translate("label.interval_hours"),
                translator.translate("label.interval_days"),
            ]
        )
        self.scan_interval_value = QSpinBox()
        self.scan_interval_value.setRange(1, 10000)
        self.scan_save_checkbox = QCheckBox(translator.translate("checkbox.save_results"))
        self.scan_save_checkbox.setChecked(True)
        self.scan_include_no_username_checkbox = QCheckBox(
            translator.translate("checkbox.include_no_username")
        )
        form_layout.addRow(translator.translate("label.target_group"), self.scan_target_input)
        form_layout.addRow(translator.translate("label.limit"), self.scan_limit_input)
        form_layout.addRow(translator.translate("label.interval"), self.scan_interval_combo)
        form_layout.addRow(translator.translate("label.interval"), self.scan_interval_value)
        form_layout.addRow(self.scan_save_checkbox)
        form_layout.addRow(self.scan_include_no_username_checkbox)
        layout.addLayout(form_layout, 0, 1, 1, 2)

        self.scan_start_button = QPushButton(translator.translate("button.start"))
        self.scan_start_button.clicked.connect(partial(self.start_task, task_type="scan"))
        self.scan_cancel_button = QPushButton(translator.translate("button.cancel"))
        self.scan_cancel_button.clicked.connect(partial(self.cancel_tasks, task_type="scan"))
        control_layout = QHBoxLayout()
        control_layout.addWidget(self.scan_start_button)
        control_layout.addWidget(self.scan_cancel_button)
        layout.addLayout(control_layout, 1, 1, 1, 2)

        self.scan_progress_container = QVBoxLayout()
        progress_group = QGroupBox(translator.translate("label.progress"))
        progress_group.setLayout(self.scan_progress_container)
        layout.addWidget(progress_group, 2, 1, 2, 2)

        tab.setLayout(layout)
        self.tab_widget.addTab(tab, translator.translate("tab.scan"))

    def _build_add_tab(self) -> None:
        tab = QWidget()
        layout = QGridLayout()

        self.add_session_list = QListWidget()
        self.add_session_list.setSelectionMode(QListWidget.MultiSelection)
        layout.addWidget(self.add_session_list, 0, 0, 4, 1)

        form_layout = QFormLayout()
        self.add_target_input = QLineEdit()
        self.add_target_type_combo = QComboBox()
        self.add_target_type_combo.addItem(
            translator.translate("option.target_channel"),
            userData="channel",
        )
        self.add_target_type_combo.addItem(
            translator.translate("option.target_group"),
            userData="group",
        )
        self.add_source_combo = QComboBox()
        self.add_source_combo.addItems(
            [
                translator.translate("option.add_source_scanned"),
                translator.translate("option.add_source_active"),
            ]
        )
        form_layout.addRow(translator.translate("label.target_group"), self.add_target_input)
        form_layout.addRow(translator.translate("label.target_type"), self.add_target_type_combo)
        form_layout.addRow(translator.translate("label.add_source"), self.add_source_combo)
        layout.addLayout(form_layout, 0, 1, 1, 2)

        self.add_start_button = QPushButton(translator.translate("button.start"))
        self.add_start_button.clicked.connect(partial(self.start_task, task_type="add"))
        self.add_cancel_button = QPushButton(translator.translate("button.cancel"))
        self.add_cancel_button.clicked.connect(partial(self.cancel_tasks, task_type="add"))
        add_control_layout = QHBoxLayout()
        add_control_layout.addWidget(self.add_start_button)
        add_control_layout.addWidget(self.add_cancel_button)
        layout.addLayout(add_control_layout, 1, 1, 1, 2)

        self.add_progress_container = QVBoxLayout()
        progress_group = QGroupBox(translator.translate("label.progress"))
        progress_group.setLayout(self.add_progress_container)
        layout.addWidget(progress_group, 2, 1, 2, 2)

        tab.setLayout(layout)
        self.tab_widget.addTab(tab, translator.translate("tab.add_members"))

    def _build_active_tab(self) -> None:
        tab = QWidget()
        layout = QGridLayout()

        self.active_session_list = QListWidget()
        self.active_session_list.setSelectionMode(QListWidget.MultiSelection)
        layout.addWidget(self.active_session_list, 0, 0, 4, 1)

        form_layout = QFormLayout()
        self.active_target_input = QLineEdit()
        self.active_limit_input = QLineEdit()
        self.active_interval_combo = QComboBox()
        self.active_interval_combo.addItems(
            [
                translator.translate("label.interval_minutes"),
                translator.translate("label.interval_hours"),
                translator.translate("label.interval_days"),
            ]
        )
        self.active_interval_value = QSpinBox()
        self.active_interval_value.setRange(1, 10000)
        self.active_save_checkbox = QCheckBox(translator.translate("checkbox.save_results"))
        self.active_save_checkbox.setChecked(True)
        self.active_include_no_username_checkbox = QCheckBox(
            translator.translate("checkbox.include_no_username")
        )
        form_layout.addRow(translator.translate("label.target_group"), self.active_target_input)
        form_layout.addRow(translator.translate("label.limit"), self.active_limit_input)
        form_layout.addRow(translator.translate("label.interval"), self.active_interval_combo)
        form_layout.addRow(translator.translate("label.interval"), self.active_interval_value)
        form_layout.addRow(self.active_save_checkbox)
        form_layout.addRow(self.active_include_no_username_checkbox)
        layout.addLayout(form_layout, 0, 1, 1, 2)

        self.active_start_button = QPushButton(translator.translate("button.start"))
        self.active_start_button.clicked.connect(partial(self.start_task, task_type="active"))
        self.active_cancel_button = QPushButton(translator.translate("button.cancel"))
        self.active_cancel_button.clicked.connect(partial(self.cancel_tasks, task_type="active"))
        active_control_layout = QHBoxLayout()
        active_control_layout.addWidget(self.active_start_button)
        active_control_layout.addWidget(self.active_cancel_button)
        layout.addLayout(active_control_layout, 1, 1, 1, 2)

        self.active_progress_container = QVBoxLayout()
        progress_group = QGroupBox(translator.translate("label.progress"))
        progress_group.setLayout(self.active_progress_container)
        layout.addWidget(progress_group, 2, 1, 2, 2)

        tab.setLayout(layout)
        self.tab_widget.addTab(tab, translator.translate("tab.active_senders"))

    def _build_rate_tab(self) -> None:
        tab = QWidget()
        form_layout = QFormLayout()
        self.join_interval_input = QSpinBox()
        self.join_interval_input.setRange(1, 3600)
        self.join_interval_input.setValue(int(self.settings.rate_limit.join_interval))
        self.message_interval_input = QSpinBox()
        self.message_interval_input.setRange(1, 3600)
        self.message_interval_input.setValue(int(self.settings.rate_limit.message_interval))
        self.scan_interval_input = QSpinBox()
        self.scan_interval_input.setRange(1, 3600)
        self.scan_interval_input.setValue(int(self.settings.rate_limit.scan_interval))
        self.max_active_messages_input = QSpinBox()
        self.max_active_messages_input.setRange(100, 50000)
        self.max_active_messages_input.setValue(int(self.settings.max_active_messages))
        self.max_active_messages_label = QLabel(translator.translate("label.max_active_messages"))
        self.flood_checkbox = QCheckBox(translator.translate("label.flood_wait"))
        self.flood_checkbox.setChecked(self.settings.rate_limit.flood_wait_handling)

        form_layout.addRow(translator.translate("label.rate_join"), self.join_interval_input)
        form_layout.addRow(translator.translate("label.rate_message"), self.message_interval_input)
        form_layout.addRow(translator.translate("label.rate_scan"), self.scan_interval_input)
        form_layout.addRow(self.max_active_messages_label, self.max_active_messages_input)
        form_layout.addRow(self.flood_checkbox)

        save_button = QPushButton(translator.translate("button.save"))
        save_button.clicked.connect(self.save_rate_limits)

        layout = QVBoxLayout()
        layout.addLayout(form_layout)
        layout.addWidget(save_button)
        tab.setLayout(layout)
        self.tab_widget.addTab(tab, translator.translate("tab.rate_limit"))

    def _build_settings_tab(self) -> None:
        tab = QWidget()
        layout = QFormLayout()
        self.language_combo = QComboBox()
        self.language_combo.addItems(["tr", "en"])
        self.language_combo.setCurrentText(self.settings.language)
        self.language_combo.currentTextChanged.connect(self.change_language)

        self.timezone_combo = QComboBox()
        self.timezone_combo.addItems(POPULAR_TIMEZONES)
        index = self.timezone_combo.findText(self.settings.timezone)
        if index >= 0:
            self.timezone_combo.setCurrentIndex(index)
        self.timezone_combo.currentTextChanged.connect(self.change_timezone)

        layout.addRow(translator.translate("label.language"), self.language_combo)
        layout.addRow(translator.translate("label.timezone"), self.timezone_combo)

        self.license_group = QGroupBox(translator.translate("group.license"))
        license_layout = QFormLayout()
        self.license_status_caption = QLabel(translator.translate("label.license_status"))
        self.license_status_value = QLabel("-")
        self.license_plan_caption = QLabel(translator.translate("label.license_plan"))
        self.license_plan_value = QLabel("-")
        self.license_expiry_caption = QLabel(translator.translate("label.license_expires"))
        self.license_expiry_value = QLabel("-")
        self.license_remaining_caption = QLabel(translator.translate("label.license_remaining"))
        self.license_remaining_value = QLabel("-")
        self.license_machine_caption = QLabel(translator.translate("label.license_machine"))
        self.license_machine_value = QLineEdit(self.license_manager.machine_id)
        self.license_machine_value.setReadOnly(True)
        self.license_machine_value.setCursorPosition(0)
        self.license_machine_copy = QPushButton(translator.translate("button.copy_machine_id"))
        self.license_machine_copy.clicked.connect(self.copy_machine_id)
        machine_row = QWidget()
        machine_layout = QHBoxLayout(machine_row)
        machine_layout.setContentsMargins(0, 0, 0, 0)
        machine_layout.setSpacing(6)
        machine_layout.addWidget(self.license_machine_value)
        machine_layout.addWidget(self.license_machine_copy)

        license_layout.addRow(self.license_status_caption, self.license_status_value)
        license_layout.addRow(self.license_plan_caption, self.license_plan_value)
        license_layout.addRow(self.license_expiry_caption, self.license_expiry_value)
        license_layout.addRow(self.license_remaining_caption, self.license_remaining_value)
        license_layout.addRow(self.license_machine_caption, machine_row)

        self.license_key_caption = QLabel(translator.translate("label.license_key"))
        license_input_layout = QHBoxLayout()
        self.license_input = QLineEdit()
        self.license_activate_button = QPushButton(translator.translate("button.activate_license"))
        self.license_activate_button.clicked.connect(self.handle_license_activation)
        license_input_layout.addWidget(self.license_input)
        license_input_layout.addWidget(self.license_activate_button)
        license_layout.addRow(self.license_key_caption, license_input_layout)

        self.license_group.setLayout(license_layout)
        layout.addRow(self.license_group)

        tab.setLayout(layout)
        self.tab_widget.addTab(tab, translator.translate("tab.settings"))
        self._refresh_license_labels()

    def _build_user_tab(self) -> None:
        tab = QWidget()
        layout = QVBoxLayout()
        button_layout = QHBoxLayout()
        self.export_users_button = QPushButton(translator.translate("button.export_users"))
        self.export_users_button.clicked.connect(self.export_users)
        self.import_users_button = QPushButton(translator.translate("button.import_users"))
        self.import_users_button.clicked.connect(self.import_users)
        self.add_user_button = QPushButton(translator.translate("button.add_user"))
        self.add_user_button.clicked.connect(self.add_user_manual)
        self.clear_users_button = QPushButton(translator.translate("button.clear_users"))
        self.clear_users_button.clicked.connect(self.clear_users)
        button_layout.addWidget(self.export_users_button)
        button_layout.addWidget(self.import_users_button)
        button_layout.addWidget(self.add_user_button)
        button_layout.addWidget(self.clear_users_button)

        self.user_tab_widget = QTabWidget()
        self.user_tables: Dict[str, QTableWidget] = {}

        for key, has_message, title_key in self.user_table_meta:
            column_count = 8 if has_message else 7
            table = QTableWidget(0, column_count)
            table.setSelectionBehavior(QTableWidget.SelectRows)
            table.setEditTriggers(QTableWidget.NoEditTriggers)
            table.setSortingEnabled(True)
            header = table.horizontalHeader()
            if header:
                header.setSectionsClickable(True)
                header.setSectionResizeMode(QHeaderView.Stretch)
            self.user_tables[key] = table
            self.user_tab_widget.addTab(table, translator.translate(title_key))

        layout.addLayout(button_layout)
        layout.addWidget(self.user_tab_widget)
        tab.setLayout(layout)
        self.tab_widget.addTab(tab, translator.translate("tab.users_root"))
        self._update_user_table_headers()

    # endregion

    def refresh_sessions(self) -> None:
        self.session_list.clear()
        self.scan_session_list.clear()
        self.add_session_list.clear()
        self.active_session_list.clear()
        for info in self.session_manager.list_sessions():
            for widget in [self.session_list, self.scan_session_list, self.add_session_list, self.active_session_list]:
                item = QListWidgetItem(info.name)
                item.setFlags(item.flags() | Qt.ItemIsUserCheckable | Qt.ItemIsSelectable | Qt.ItemIsEnabled)
                item.setCheckState(Qt.Unchecked)
                widget.addItem(item)

    def get_selected_sessions(self, widget: QListWidget) -> List[str]:
        selected: List[str] = []
        for index in range(widget.count()):
            item = widget.item(index)
            if item.checkState() == Qt.Checked:
                selected.append(item.text())
        return selected

    def handle_login(self) -> None:
        api_id = self.api_id_input.text().strip()
        api_hash = self.api_hash_input.text().strip()
        phone = self.phone_input.text().strip()
        session_name = self.session_name_input.text().strip() or phone
        if not (api_id and api_hash and phone):
            QMessageBox.warning(self, self.windowTitle(), "Eksik bilgiler")
            return
        self.settings.api_id = int(api_id)
        self.settings.api_hash = api_hash
        self.settings_repo.save(self.settings)

        async def run_login() -> None:
            try:
                pending = await self.session_manager.start_login(session_name, phone)
            except Exception as exc:  # pragma: no cover - network dependent
                QMessageBox.critical(self, self.windowTitle(), str(exc))
            else:
                self.pending_login = pending
                QMessageBox.information(self, self.windowTitle(), translator.translate("dialog.enter_code"))

        asyncio.run(run_login())

    def handle_confirm_code(self) -> None:
        session_name = self.session_name_input.text().strip() or self.phone_input.text().strip()
        code = self.code_input.text().strip()
        password = self.password_input.text().strip() or None
        if not self.pending_login:
            QMessageBox.warning(self, self.windowTitle(), "OTP beklenmiyor")
            return

        async def run_confirm() -> None:
            try:
                await self.session_manager.confirm_code(session_name, code, password)
            except Exception as exc:  # pragma: no cover
                QMessageBox.critical(self, self.windowTitle(), str(exc))
            else:
                QMessageBox.information(self, self.windowTitle(), translator.translate("dialog.success"))
                self.refresh_sessions()

        asyncio.run(run_confirm())
        self.pending_login = None

    def handle_ban_check(self) -> None:
        sessions = self.get_selected_sessions(self.session_list)
        if not sessions:
            QMessageBox.warning(self, self.windowTitle(), "Oturum seçin")
            return

        results: List[str] = []

        async def run_check(session_name: str) -> None:
            ok = await self.session_manager.check_ban(session_name)
            status = "OK" if ok else translator.translate("log.ban_detected")
            results.append(f"{session_name}: {status}")

        async def runner() -> None:
            await asyncio.gather(*(run_check(name) for name in sessions))

        asyncio.run(runner())
        self.ban_result.setPlainText("\n".join(results))
        self.refresh_sessions()

    def start_task(self, task_type: str) -> None:
        if self._cancelling:
            QMessageBox.warning(
                self,
                self.windowTitle(),
                translator.translate("dialog.cancellation_pending"),
            )
            return
        if not self._license_is_active():
            QMessageBox.warning(
                self,
                self.windowTitle(),
                translator.translate("dialog.license_required"),
            )
            return
        if self.worker_threads:
            QMessageBox.warning(
                self,
                self.windowTitle(),
                translator.translate("dialog.task_in_progress"),
            )
            return

        include_no_username = True
        target_type: Optional[str] = None
        if task_type == "scan":
            sessions = self.get_selected_sessions(self.scan_session_list)
            target = self.scan_target_input.text().strip()
            limit = self._parse_int(self.scan_limit_input.text())
            interval = self._build_interval(self.scan_interval_combo.currentIndex(), self.scan_interval_value.value())
            container = self.scan_progress_container
            persist = self.scan_save_checkbox.isChecked()
            storage_key = "scanned"
            include_no_username = self.scan_include_no_username_checkbox.isChecked()
        elif task_type == "add":
            sessions = self.get_selected_sessions(self.add_session_list)
            target = self.add_target_input.text().strip()
            limit = None
            interval = None
            container = self.add_progress_container
            persist = True
            storage_key = self._current_add_storage_key()
            target_type = self.add_target_type_combo.currentData()
        else:
            sessions = self.get_selected_sessions(self.active_session_list)
            target = self.active_target_input.text().strip()
            limit = self._parse_int(self.active_limit_input.text())
            interval = self._build_interval(self.active_interval_combo.currentIndex(), self.active_interval_value.value())
            container = self.active_progress_container
            persist = self.active_save_checkbox.isChecked()
            storage_key = "active"
            include_no_username = self.active_include_no_username_checkbox.isChecked()

        if not sessions or not target:
            QMessageBox.warning(self, self.windowTitle(), "Oturum ve hedef girilmeli")
            return

        storage = self.user_storages.get(storage_key)
        if storage is None:
            QMessageBox.critical(self, self.windowTitle(), translator.translate("dialog.storage_missing"))
            return
        result_storage: Optional[UserStorage] = None
        if task_type == "add":
            result_key = self._add_result_storage_key(storage_key)
            result_storage = self.user_storages.get(result_key)

        progress_map = self.progress_widgets[task_type]
        for session_name in list(progress_map.keys()):
            if session_name not in sessions:
                widget = progress_map.pop(session_name)
                self._remove_progress_widget(container, widget)

        session_count = len(sessions)
        per_session_limits: List[Optional[int]] = [None] * session_count
        offsets: List[int] = [0] * session_count
        if task_type == "add":
            users = storage.get_users()
            if not users:
                QMessageBox.warning(self, self.windowTitle(), translator.translate("dialog.no_users_available"))
                return
            user_chunks = self._split_users_for_sessions(users, session_count)
            per_session_limits = [len(chunk) for chunk in user_chunks]
        else:
            user_chunks = [None] * session_count
            if task_type == "scan":
                per_session_limits, offsets = self._scan_distribution(limit, target, sessions)
            else:
                per_session_limits, offsets = self._active_distribution(limit, session_count)

        requests_started = 0
        for idx, session_name in enumerate(sessions):
            widget = progress_map.get(session_name)
            if not widget:
                widget = SessionProgressWidget(session_name, task_type)
                progress_map[session_name] = widget
                container.addWidget(widget)
            else:
                widget.reset()

            per_session_limit = per_session_limits[idx] if idx < len(per_session_limits) else None
            offset_value = offsets[idx] if idx < len(offsets) else 0

            if task_type == "add":
                session_users = user_chunks[idx] or []
                total_target = len(session_users)
                if total_target == 0:
                    widget.update_state(0, 0, status_key="status.completed")
                    continue
            else:
                session_users = None
                if per_session_limit == 0:
                    widget.update_state(0, 0, status_key="status.completed")
                    continue
                total_target = per_session_limit or 0

            widget.update_state(0, total_target or 0, status_key="status.running")

            request_limit: Optional[int] = None
            if task_type != "add" and isinstance(per_session_limit, int) and per_session_limit > 0:
                request_limit = per_session_limit

            request = TaskRequest(
                task_type=task_type,
                session_name=session_name,
                entity=target,
                limit=request_limit,
                interval=interval,
                users=session_users,
                persist_results=persist,
                storage=storage,
                include_no_username=include_no_username,
                offset=offset_value if request_limit else 0,
                result_storage=result_storage,
                target_type=target_type,
            )
            thread = SessionWorkerThread(self.session_manager, self.orchestrator, request)
            thread.setParent(self)
            thread.progress.connect(partial(self.on_progress, widget))
            thread.finished.connect(partial(self.on_finished, widget))
            thread.status.connect(partial(self.on_status_update, widget))
            thread.error.connect(partial(self.on_error, widget))
            key = (task_type, session_name)
            self.worker_threads[key] = thread
            thread.start()
            requests_started += 1

        if not requests_started:
            QMessageBox.information(self, self.windowTitle(), translator.translate("dialog.no_remaining_work"))
        else:
            self._pending_completion_notifications.add(task_type)
        self._update_task_controls()

    def cancel_tasks(self, task_type: str) -> None:
        if self._cancelling:
            QMessageBox.information(
                self,
                self.windowTitle(),
                translator.translate("dialog.cancellation_pending"),
            )
            return
        self._cancel_running_tasks(task_type=task_type, notify=True)

    def _remove_progress_widget(self, container: QVBoxLayout, widget: SessionProgressWidget) -> None:
        index = container.indexOf(widget)
        if index >= 0:
            item = container.takeAt(index)
            if item and item.widget():
                item.widget().deleteLater()

    def _notify_completion_if_ready(self) -> None:
        if not self.worker_threads and self._pending_completion_notifications:
            self._pending_completion_notifications.clear()
            QMessageBox.information(
                self,
                self.windowTitle(),
                translator.translate("dialog.task_complete"),
            )

    def on_progress(self, widget: SessionProgressWidget, update: ProgressUpdate) -> None:
        processed = update.processed
        total = update.total or processed
        status_key = update.status or widget.state.status
        widget.update_state(processed, total, status_key)
        if update.user:
            widget.append_user(self._format_user(update.user))
            self.populate_user_tables()
        else:
            if update.status and not update.status.startswith("status."):
                widget.append_user(update.status)
            if widget.task_type == "add":
                self.populate_user_tables()

    def on_finished(self, widget: SessionProgressWidget, session_name: str, task_type: str) -> None:
        key = (task_type, session_name)
        processed_total = widget.state.processed
        final_total = processed_total or widget.state.total
        widget.update_state(final_total, final_total, status_key="status.completed")
        thread = self.worker_threads.pop(key, None)
        if thread:
            thread.wait(1000)
        self._notify_completion_if_ready()
        self._update_task_controls()

    def on_status_update(self, widget: SessionProgressWidget, session_name: str, task_type: str, status_key: str) -> None:
        total = widget.state.total or widget.state.processed
        widget.update_state(widget.state.processed, total, status_key=status_key)

    def on_error(self, widget: SessionProgressWidget, session_name: str, task_type: str, message: str) -> None:
        total = widget.state.total or widget.state.processed
        widget.update_state(widget.state.processed, total, status_key="status.error")
        widget.append_user(message)
        key = (task_type, session_name)
        thread = self.worker_threads.pop(key, None)
        if thread:
            thread.wait(1000)
        self._notify_completion_if_ready()
        self._update_task_controls()

    def closeEvent(self, event: QCloseEvent) -> None:  # noqa: D401
        self._cancel_running_tasks(notify=False)
        super().closeEvent(event)

    def _cancel_running_tasks(self, task_type: Optional[str] = None, notify: bool = False) -> None:
        if self._cancelling:
            if notify:
                QMessageBox.information(
                    self,
                    self.windowTitle(),
                    translator.translate("dialog.cancellation_pending"),
                )
            return
        if not self.worker_threads:
            if notify:
                self._reset_progress_widgets(task_type)
            return
        keys = [key for key in list(self.worker_threads.keys()) if task_type is None or key[0] == task_type]
        if not keys:
            if notify:
                self._reset_progress_widgets(task_type)
            return
        threads = []
        for key in keys:
            thread = self.worker_threads.pop(key, None)
            if thread:
                threads.append(thread)
        if not threads:
            if notify:
                self._reset_progress_widgets(task_type)
            return
        self._cancelling = True
        self._set_widgets_busy(task_type, True)
        self._update_task_controls()
        for thread in threads:
            thread.stop()
        if task_type is None:
            self.orchestrator.cancel_all()
        for thread in threads:
            thread.wait(5000)
        self._cancelling = False
        self._set_widgets_busy(task_type, False)
        if task_type:
            self._pending_completion_notifications.discard(task_type)
        else:
            self._pending_completion_notifications.clear()
        self._reset_progress_widgets(task_type)
        if notify and threads:
            QMessageBox.information(
                self,
                self.windowTitle(),
                translator.translate("dialog.task_cancelled"),
            )
        self._update_task_controls()

    def _reset_progress_widgets(self, task_type: Optional[str]) -> None:
        if task_type is None:
            targets = self.progress_widgets.values()
        else:
            targets = [self.progress_widgets.get(task_type, {})]
        for widgets in targets:
            if not widgets:
                continue
            for widget in widgets.values():
                widget.set_busy(False)
                widget.reset()
        self._update_task_controls()

    def _set_widgets_busy(self, task_type: Optional[str], busy: bool) -> None:
        if task_type is None:
            target_maps = self.progress_widgets.values()
        else:
            target_maps = [self.progress_widgets.get(task_type, {})]
        for widgets in target_maps:
            if not widgets:
                continue
            for widget in widgets.values():
                widget.set_busy(busy)
                if busy:
                    widget.update_state(widget.state.processed, widget.state.total, status_key="status.cancelling")

    def _update_task_controls(self) -> None:
        enabled = not self.worker_threads and not self._cancelling
        for button in [self.scan_start_button, self.add_start_button, self.active_start_button]:
            button.setEnabled(enabled)

    def _build_interval(self, index: int, value: int) -> Optional[timedelta]:
        if value <= 0:
            return None
        if index == 0:
            return timedelta(minutes=value)
        if index == 1:
            return timedelta(hours=value)
        return timedelta(days=value)

    @staticmethod
    def _parse_int(value: str) -> Optional[int]:
        value = value.strip()
        if not value:
            return None
        try:
            return int(value)
        except ValueError:
            return None

    @staticmethod
    def _format_user(user: StoredUser) -> str:
        parts = [str(user.user_id)]
        if user.username:
            parts.append(user.username)
        name = " ".join(part for part in [user.first_name or "", user.last_name or ""] if part)
        if name:
            parts.append(name)
        return " | ".join(parts)

    def populate_user_tables(self) -> None:
        for key, has_message, _ in self.user_table_meta:
            storage = self.user_storages.get(key)
            table = self.user_tables.get(key)
            if storage is None or table is None:
                continue
            users = storage.get_users()
            table.setSortingEnabled(False)
            table.clearContents()
            table.setRowCount(len(users))
            for row, user in enumerate(users):
                table.setItem(row, 0, QTableWidgetItem(str(user.user_id)))
                table.setItem(row, 1, QTableWidgetItem(user.username or ""))
                table.setItem(row, 2, QTableWidgetItem(user.first_name or ""))
                table.setItem(row, 3, QTableWidgetItem(user.last_name or ""))
                table.setItem(row, 4, QTableWidgetItem(user.phone or ""))
                table.setItem(row, 5, QTableWidgetItem(user.last_seen or ""))
                status_value = ""
                if user.status:
                    status_value = translator.translate(user.status)
                table.setItem(row, 6, QTableWidgetItem(status_value))
                if has_message:
                    message_text = user.last_message or ""
                    table.setItem(row, 7, QTableWidgetItem(message_text))
            table.setSortingEnabled(True)

    def _current_user_key(self) -> str:
        if not hasattr(self, "user_tab_widget"):
            return "scanned"
        index = self.user_tab_widget.currentIndex()
        keys = list(self.user_tables.keys())
        if 0 <= index < len(keys):
            return keys[index]
        return "scanned"

    def _current_user_storage(self) -> UserStorage:
        key = self._current_user_key()
        return self.user_storages[key]

    def _current_add_storage_key(self) -> str:
        return "scanned" if self.add_source_combo.currentIndex() == 0 else "active"

    @staticmethod
    def _add_result_storage_key(source_key: str) -> str:
        return "scanned_added" if source_key == "scanned" else "active_added"

    def _split_users_for_sessions(self, users: List[StoredUser], count: int) -> List[List[StoredUser]]:
        if count <= 0:
            return []
        total = len(users)
        base = total // count
        remainder = total % count
        chunks: List[List[StoredUser]] = []
        start = 0
        for idx in range(count):
            size = base + (1 if idx < remainder else 0)
            end = start + size
            chunks.append(users[start:end])
            start = end
        return chunks

    def _scan_distribution(
        self, limit: Optional[int], target: str, sessions: List[str]
    ) -> Tuple[List[Optional[int]], List[int]]:
        session_count = len(sessions)
        if session_count <= 0:
            return [], []
        total_target = limit if limit and limit > 0 else None
        if total_target is None:
            estimated = self._estimate_member_total(target, sessions)
            if estimated:
                total_target = estimated
        if total_target:
            shard_limits, offsets = self._calculate_shards(total_target, session_count)
            return [value if value > 0 else 0 for value in shard_limits], offsets
        limits: List[Optional[int]] = [None] + [0] * (session_count - 1)
        return limits[:session_count], [0] * session_count

    def _active_distribution(
        self, limit: Optional[int], session_count: int
    ) -> Tuple[List[Optional[int]], List[int]]:
        if session_count <= 0:
            return [], []
        total_target = limit if limit and limit > 0 else self.settings.max_active_messages
        if total_target <= 0:
            total_target = self.settings.max_active_messages
        shard_limits, offsets = self._calculate_shards(total_target, session_count)
        return [value if value > 0 else 0 for value in shard_limits], offsets

    @staticmethod
    def _calculate_shards(total: int, count: int) -> Tuple[List[int], List[int]]:
        if count <= 0:
            return [], []
        if total <= 0:
            return [0] * count, [0] * count
        base = total // count
        remainder = total % count
        limits: List[int] = []
        offsets: List[int] = []
        start = 0
        for idx in range(count):
            size = base + (1 if idx < remainder else 0)
            limits.append(size)
            offsets.append(start)
            start += size
        return limits, offsets

    def _estimate_member_total(self, target: str, sessions: List[str]) -> Optional[int]:
        if not sessions:
            return None
        session_name = sessions[0]

        async def fetch(client):
            try:
                entity = await client.get_entity(target)
            except Exception:
                return None
            count = getattr(entity, "participants_count", None)
            if isinstance(count, int) and count > 0:
                return count
            try:
                input_entity = await client.get_input_entity(entity)
            except Exception:
                input_entity = None
            channel = None
            if isinstance(input_entity, types.InputChannel):
                channel = input_entity
            elif isinstance(input_entity, types.InputPeerChannel):
                channel = types.InputChannel(input_entity.channel_id, input_entity.access_hash)
            if channel is not None:
                try:
                    full = await client(functions.channels.GetFullChannelRequest(channel))
                    full_count = getattr(full.full_chat, "participants_count", None)
                    if isinstance(full_count, int) and full_count > 0:
                        return full_count
                except Exception:
                    pass
            if isinstance(entity, types.Chat):
                try:
                    full_chat = await client(functions.messages.GetFullChatRequest(entity.id))
                    chat_count = getattr(full_chat.full_chat, "participants_count", None)
                    if isinstance(chat_count, int) and chat_count > 0:
                        return chat_count
                except Exception:
                    pass
            return None

        try:
            return asyncio.run(self.session_manager.run_with_client(session_name, fetch))
        except Exception:
            return None

    def export_users(self) -> None:
        storage_key = self._current_user_key()
        default_names = {
            "scanned": "exported_scanned_users.json",
            "active": "exported_active_users.json",
            "scanned_added": "exported_added_scanned_users.json",
            "active_added": "exported_added_active_users.json",
        }
        default_name = default_names.get(storage_key, "exported_users.json")
        default_path = self.settings.user_directory / default_name
        path, _ = QFileDialog.getSaveFileName(
            self,
            translator.translate("dialog.export_title"),
            str(default_path),
            "JSON (*.json)",
        )
        if not path:
            return
        try:
            storage = self._current_user_storage()
            storage.export_to_file(Path(path))
        except Exception as exc:  # pragma: no cover - file system errors
            QMessageBox.critical(self, self.windowTitle(), f"{translator.translate('dialog.export_failure')}: {exc}")
        else:
            QMessageBox.information(self, self.windowTitle(), translator.translate("dialog.export_success"))

    def import_users(self) -> None:
        path, _ = QFileDialog.getOpenFileName(
            self,
            translator.translate("dialog.import_title"),
            str(self.settings.user_directory),
            "JSON (*.json)",
        )
        if not path:
            return
        try:
            storage = self._current_user_storage()
            storage.import_from_file(Path(path))
        except Exception as exc:  # pragma: no cover - file system errors
            QMessageBox.critical(self, self.windowTitle(), f"{translator.translate('dialog.import_failure')}: {exc}")
        else:
            self.populate_user_tables()
            QMessageBox.information(self, self.windowTitle(), translator.translate("dialog.import_success"))

    def add_user_manual(self) -> None:
        include_message = self._current_user_key() in {"active", "active_added"}
        dialog = ManualUserDialog(self, include_message=include_message)
        if dialog.exec() != QDialog.Accepted:
            return
        user = dialog.build_user()
        if not user:
            QMessageBox.warning(self, self.windowTitle(), translator.translate("dialog.add_user_invalid"))
            return
        storage = self._current_user_storage()
        storage.add_users([user])
        self.populate_user_tables()
        QMessageBox.information(self, self.windowTitle(), translator.translate("dialog.add_user_success"))

    def clear_users(self) -> None:
        confirm = QMessageBox.question(
            self,
            self.windowTitle(),
            translator.translate("dialog.clear_users_confirm"),
        )
        if confirm != QMessageBox.Yes:
            return
        storage = self._current_user_storage()
        storage.clear()
        self.populate_user_tables()
        QMessageBox.information(
            self,
            self.windowTitle(),
            translator.translate("dialog.clear_users_success"),
        )

    def save_rate_limits(self) -> None:
        self.settings.rate_limit.join_interval = float(self.join_interval_input.value())
        self.settings.rate_limit.message_interval = float(self.message_interval_input.value())
        self.settings.rate_limit.scan_interval = float(self.scan_interval_input.value())
        self.settings.rate_limit.flood_wait_handling = self.flood_checkbox.isChecked()
        self.settings.max_active_messages = int(self.max_active_messages_input.value())
        self.settings_repo.save(self.settings)
        QMessageBox.information(self, self.windowTitle(), translator.translate("dialog.success"))

    def change_language(self, language: str) -> None:
        translator.set_language(language)
        self.settings.language = language
        self.settings_repo.save(self.settings)
        self.retranslate_ui()

    def change_timezone(self, timezone: str) -> None:
        self.settings.timezone = timezone
        for storage in self.user_storages.values():
            storage.set_timezone(timezone)
        self.settings_repo.save(self.settings)
        self.populate_user_tables()
        self._refresh_license_labels()

    def handle_license_activation(self) -> None:
        key = self.license_input.text().strip()
        if not key:
            QMessageBox.warning(self, self.windowTitle(), translator.translate("dialog.license_key_missing"))
            return
        try:
            self.license_info = self.license_manager.activate(key)
        except LicenseError as exc:
            QMessageBox.critical(self, self.windowTitle(), translator.translate(exc.message_key))
            return
        self.license_input.clear()
        self._refresh_license_labels()
        QMessageBox.information(self, self.windowTitle(), translator.translate("dialog.license_activation_success"))

    def _license_is_active(self) -> bool:
        return bool(self.license_info and self.license_info.is_active(self.license_manager.machine_id))

    def _refresh_license_labels(self) -> None:
        if not hasattr(self, "license_status_value"):
            return
        self.license_machine_value.setText(self.license_manager.machine_id)
        self.license_machine_value.setCursorPosition(0)
        info = self.license_info
        if info:
            active = info.is_active(self.license_manager.machine_id)
            status_key = "license.status_active" if active else "license.status_expired"
            expiry_text = self.license_manager.format_datetime(info.expires_at, self.settings.timezone)
            remaining_delta = info.remaining()
            if remaining_delta.total_seconds() <= 0:
                remaining_text = translator.translate("license.remaining_none")
            else:
                days = remaining_delta.days
                hours, remainder = divmod(remaining_delta.seconds, 3600)
                minutes = remainder // 60
                remaining_text = translator.translate("license.remaining_format").format(
                    days=days,
                    hours=hours,
                    minutes=minutes,
                )
            plan_text = info.plan or translator.translate("license.plan_unknown")
        else:
            status_key = "license.status_missing"
            expiry_text = translator.translate("license.expiry_unknown")
            remaining_text = translator.translate("license.remaining_unknown")
            plan_text = translator.translate("license.plan_unknown")
        self.license_status_value.setText(translator.translate(status_key))
        self.license_expiry_value.setText(expiry_text)
        self.license_remaining_value.setText(remaining_text)
        self.license_plan_value.setText(plan_text)

    def copy_machine_id(self) -> None:
        QApplication.clipboard().setText(self.license_machine_value.text())
        status = self.statusBar()
        if status:
            status.showMessage(translator.translate("dialog.machine_id_copied"), 3000)

    def retranslate_ui(self) -> None:
        self.setWindowTitle(translator.translate("app.title"))
        tab_keys = [
            "tab.sessions",
            "tab.ban_check",
            "tab.scan",
            "tab.add_members",
            "tab.active_senders",
            "tab.rate_limit",
            "tab.settings",
            "tab.users_root",
        ]
        for index, key in enumerate(tab_keys):
            self.tab_widget.setTabText(index, translator.translate(key))
        # Sessions tab controls
        self._update_sessions_tab_labels()
        self._update_user_table_headers()
        self.scan_save_checkbox.setText(translator.translate("checkbox.save_results"))
        self.active_save_checkbox.setText(translator.translate("checkbox.save_results"))
        self.scan_include_no_username_checkbox.setText(
            translator.translate("checkbox.include_no_username")
        )
        self.active_include_no_username_checkbox.setText(
            translator.translate("checkbox.include_no_username")
        )
        self.scan_start_button.setText(translator.translate("button.start"))
        self.scan_cancel_button.setText(translator.translate("button.cancel"))
        self.add_start_button.setText(translator.translate("button.start"))
        self.add_cancel_button.setText(translator.translate("button.cancel"))
        self.active_start_button.setText(translator.translate("button.start"))
        self.active_cancel_button.setText(translator.translate("button.cancel"))
        if hasattr(self, "max_active_messages_label"):
            self.max_active_messages_label.setText(translator.translate("label.max_active_messages"))
        if hasattr(self, "flood_checkbox"):
            self.flood_checkbox.setText(translator.translate("label.flood_wait"))
        self.export_users_button.setText(translator.translate("button.export_users"))
        self.import_users_button.setText(translator.translate("button.import_users"))
        self.add_user_button.setText(translator.translate("button.add_user"))
        self.clear_users_button.setText(translator.translate("button.clear_users"))
        self.add_source_combo.setItemText(0, translator.translate("option.add_source_scanned"))
        self.add_source_combo.setItemText(1, translator.translate("option.add_source_active"))
        if hasattr(self, "user_tab_widget"):
            for index, (_, __, title_key) in enumerate(self.user_table_meta):
                self.user_tab_widget.setTabText(index, translator.translate(title_key))
        if hasattr(self, "license_group"):
            self.license_group.setTitle(translator.translate("group.license"))
            self.license_status_caption.setText(translator.translate("label.license_status"))
            self.license_plan_caption.setText(translator.translate("label.license_plan"))
            self.license_expiry_caption.setText(translator.translate("label.license_expires"))
            self.license_remaining_caption.setText(translator.translate("label.license_remaining"))
            self.license_machine_caption.setText(translator.translate("label.license_machine"))
            self.license_machine_copy.setText(translator.translate("button.copy_machine_id"))
            self.license_key_caption.setText(translator.translate("label.license_key"))
            self.license_activate_button.setText(translator.translate("button.activate_license"))
            self._refresh_license_labels()
        for container in [self.scan_progress_container, self.add_progress_container, self.active_progress_container]:
            for i in range(container.count()):
                widget = container.itemAt(i).widget()
                if isinstance(widget, SessionProgressWidget):
                    widget.retranslate()
        self.populate_user_tables()

    def _update_sessions_tab_labels(self) -> None:
        sessions_tab = self.tab_widget.widget(0)
        if not sessions_tab:
            return
        layout = sessions_tab.layout()
        if not isinstance(layout, QGridLayout):
            return
        group = layout.itemAtPosition(0, 0).widget()
        if isinstance(group, QGroupBox):
            group.setTitle(translator.translate("tab.sessions"))
            form = group.layout()
            if isinstance(form, QFormLayout):
                form.itemAt(0, QFormLayout.LabelRole).widget().setText(translator.translate("label.api_id"))
                form.itemAt(2, QFormLayout.LabelRole).widget().setText(translator.translate("label.api_hash"))
                form.itemAt(4, QFormLayout.LabelRole).widget().setText(translator.translate("label.phone"))
                form.itemAt(6, QFormLayout.LabelRole).widget().setText(translator.translate("label.session_name"))
        layout.itemAtPosition(1, 0).widget().setText(translator.translate("label.code"))
        layout.itemAtPosition(2, 0).widget().setText(translator.translate("label.password"))
        layout.itemAtPosition(3, 0).widget().setText(translator.translate("button.login"))
        layout.itemAtPosition(3, 1).widget().setText(translator.translate("button.confirm_code"))
        layout.itemAtPosition(4, 2).widget().setText(translator.translate("button.refresh_sessions"))

    def _update_user_table_headers(self) -> None:
        base_headers = [
            "ID",
            translator.translate("table.column.username"),
            translator.translate("table.column.first_name"),
            translator.translate("table.column.last_name"),
            translator.translate("table.column.phone"),
            translator.translate("table.column.last_seen"),
            translator.translate("table.column.status"),
        ]
        message_header = translator.translate("table.column.message")
        for key, has_message, _ in self.user_table_meta:
            table = self.user_tables.get(key)
            if not table:
                continue
            headers = base_headers + ([message_header] if has_message else [])
            for index, title in enumerate(headers):
                table.setHorizontalHeaderItem(index, QTableWidgetItem(title))


class ManualUserDialog(QDialog):
    def __init__(self, parent: Optional[QWidget] = None, include_message: bool = False) -> None:
        super().__init__(parent)
        self.setWindowTitle(translator.translate("dialog.add_user_title"))
        layout = QVBoxLayout()
        form_layout = QFormLayout()
        self.user_id_input = QLineEdit()
        self.username_input = QLineEdit()
        self.phone_input = QLineEdit()
        self.access_hash_input = QLineEdit()
        self.first_name_input = QLineEdit()
        self.last_name_input = QLineEdit()
        self.last_seen_input = QLineEdit()
        self.status_input = QLineEdit()
        self.source_input = QLineEdit()
        self.is_bot_checkbox = QCheckBox(translator.translate("label.is_bot"))
        self.message_input = QLineEdit()

        form_layout.addRow("ID", self.user_id_input)
        form_layout.addRow(translator.translate("table.column.username"), self.username_input)
        form_layout.addRow(translator.translate("table.column.phone"), self.phone_input)
        form_layout.addRow(translator.translate("label.access_hash"), self.access_hash_input)
        form_layout.addRow(translator.translate("label.first_name"), self.first_name_input)
        form_layout.addRow(translator.translate("label.last_name"), self.last_name_input)
        form_layout.addRow(translator.translate("table.column.last_seen"), self.last_seen_input)
        form_layout.addRow(translator.translate("table.column.status"), self.status_input)
        form_layout.addRow(translator.translate("label.target_group"), self.source_input)
        if include_message:
            form_layout.addRow(translator.translate("label.last_message"), self.message_input)
        form_layout.addRow(self.is_bot_checkbox)

        layout.addLayout(form_layout)
        buttons = QDialogButtonBox(QDialogButtonBox.Ok | QDialogButtonBox.Cancel)
        buttons.accepted.connect(self.accept)
        buttons.rejected.connect(self.reject)
        layout.addWidget(buttons)
        self.setLayout(layout)

    def build_user(self) -> Optional[StoredUser]:
        try:
            user_id = int(self.user_id_input.text())
        except ValueError:
            return None
        access_hash_text = self.access_hash_input.text().strip()
        access_hash = int(access_hash_text) if access_hash_text else None
        last_message = self.message_input.text().strip() or None
        return StoredUser(
            user_id=user_id,
            username=self.username_input.text().strip() or None,
            phone=self.phone_input.text().strip() or None,
            access_hash=access_hash,
            first_name=self.first_name_input.text().strip() or None,
            last_name=self.last_name_input.text().strip() or None,
            last_seen=self.last_seen_input.text().strip() or None,
            status=self.status_input.text().strip() or None,
            source=self.source_input.text().strip() or None,
            is_bot=self.is_bot_checkbox.isChecked(),
            last_message=last_message,
        )


def create_app() -> QApplication:
    app = QApplication([])
    settings_path = Path("config") / "settings.json"
    settings_repo = SettingsRepository(settings_path)
    window = MainWindow(settings_repo)
    window.show()
    return app


def main() -> None:
    app = create_app()
    app.exec()


if __name__ == "__main__":
    main()
