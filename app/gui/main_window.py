from __future__ import annotations

import asyncio
import csv
import json
from datetime import timedelta
from functools import partial
from pathlib import Path
from typing import Dict, List, Optional, Tuple, Union

from PySide6.QtCore import Qt
from PySide6.QtGui import QColor, QCloseEvent, QPainter, QPixmap, QPolygon
from PySide6.QtCore import QPoint
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
    QInputDialog,
    QLabel,
    QLineEdit,
    QListWidget,
    QListWidgetItem,
    QMainWindow,
    QMessageBox,
    QPushButton,
    QScrollArea,
    QSpinBox,
    QTabWidget,
    QTableWidget,
    QTableWidgetItem,
    QTextEdit,
    QVBoxLayout,
    QWidget,
)

from telethon import functions, types
from telethon.errors import FloodWaitError

from app.core.license import LicenseError, LicenseManager
from app.core.logger import configure_logging
from app.core.session_manager import PendingLogin, SessionManager
from app.core.settings import POPULAR_TIMEZONES, SettingsRepository
from app.core.tasks import ProgressUpdate, TaskOrchestrator, build_dm_profile
from app.data.group_storage import GroupStorage, StoredGroup
from app.data.template_storage import MessageTemplate, TemplateStorage
from app.data.user_storage import StoredUser, UserStorage
from app.gui.widgets.session_progress import SessionProgressWidget
from app.gui.workers import SessionWorkerThread, TaskRequest
from app.i18n.strings import translator

PLACEHOLDER_BUTTONS: List[Tuple[str, str]] = [
    ("button.placeholder.username", "{user_name}"),
    ("button.placeholder.first_name", "{first_name}"),
    ("button.placeholder.last_name", "{last_name}"),
]

EMOJI_CHOICES: List[str] = [
    "😀",
    "😁",
    "😂",
    "🤣",
    "😊",
    "😍",
    "😘",
    "😎",
    "🤩",
    "🤗",
    "😇",
    "🤖",
    "🤝",
    "🙏",
    "🔥",
    "⭐",
    "⚡",
    "💎",
    "💬",
    "💡",
    "✅",
    "❗",
    "❕",
    "🎯",
    "🏆",
    "📣",
    "📈",
    "💰",
    "🧠",
    "🚀",
    "🌟",
    "🎁",
    "🆕",
    "📱",
    "🔒",
    "💼",
    "🕒",
    "🎉",
    "📌",
]

GROUP_TABLE_HEADERS: List[str] = [
    "table.column.select",
    "label.group_name",
    "label.group_username",
    "label.group_link",
    "label.group_type",
    "label.members",
    "label.group_online",
    "label.group_visibility",
    "label.group_messaging",
    "label.group_hidden_members",
    "label.source",
]


class NumericTableWidgetItem(QTableWidgetItem):
    def __init__(self, value: Optional[int]):
        display = "-" if value is None else f"{value}"
        super().__init__(display)
        self.numeric_value = value if value is not None else 0

    def __lt__(self, other: object) -> bool:  # pragma: no cover - Qt runtime comparison
        if isinstance(other, NumericTableWidgetItem):
            return self.numeric_value < other.numeric_value
        return super().__lt__(other)


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
            "searched": UserStorage(
                self.settings.user_directory / "searched_users.json",
                timezone_name=self.settings.timezone,
            ),
        }
        self.template_storage = TemplateStorage(Path("config") / "message_templates.json")
        self.group_template_storage = TemplateStorage(Path("config") / "group_templates.json")
        self.group_storage = GroupStorage(self.settings.user_directory / "groups.json")
        self.user_table_meta: List[Tuple[str, bool, str]] = [
            ("scanned", False, "tab.users_scanned"),
            ("searched", False, "tab.users_searched"),
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
            "user_search": {},
            "message": {},
            "group_scan": {},
            "group_message": {},
            "group_join": {},
        }
        self._pending_completion_notifications: set[str] = set()
        self._cancelling = False

        self.setWindowTitle(translator.translate("app.title"))
        self.resize(1280, 860)

        central = QWidget()
        central_layout = QVBoxLayout(central)
        central_layout.setContentsMargins(0, 0, 0, 0)
        central_layout.setSpacing(0)
        self.header_widget = self._build_header_bar()
        central_layout.addWidget(self.header_widget)
        self.tab_widget = QTabWidget()
        central_layout.addWidget(self.tab_widget)
        self.setCentralWidget(central)

        self._build_sessions_tab()
        self._build_ban_tab()
        self._build_scan_tab()
        self._build_active_tab()
        self._build_user_search_tab()
        self._build_add_tab()
        self._build_user_tab()
        self._build_template_tab()
        self._build_message_tab()
        self._build_group_discovery_tab()
        self._build_group_template_tab()
        self._build_group_message_tab()
        self._build_rate_tab()
        self._build_settings_tab()

        self.refresh_sessions()
        self.populate_user_tables()
        self._update_task_controls()

    # region builders
    def _build_header_bar(self) -> QWidget:
        widget = QWidget()
        widget.setObjectName("appHeader")
        widget.setStyleSheet(
            "#appHeader {background-color: #0d8dc0;}"
            "#headerTitle {color: white; font-size: 20px; font-weight: 600;}"
            "#headerSubtitle {color: white; font-size: 12px;}"
        )
        layout = QHBoxLayout(widget)
        layout.setContentsMargins(24, 16, 24, 16)
        layout.setSpacing(16)
        logo = QLabel()
        logo.setPixmap(self._create_logo_pixmap())
        layout.addWidget(logo, 0, Qt.AlignVCenter)
        text_layout = QVBoxLayout()
        self.header_title_label = QLabel()
        self.header_title_label.setObjectName("headerTitle")
        self.header_subtitle_label = QLabel()
        self.header_subtitle_label.setObjectName("headerSubtitle")
        text_layout.addWidget(self.header_title_label)
        text_layout.addWidget(self.header_subtitle_label)
        layout.addLayout(text_layout)
        layout.addStretch()
        self._update_header_text()
        return widget

    def _create_logo_pixmap(self, size: int = 64) -> QPixmap:
        pixmap = QPixmap(size, size)
        pixmap.fill(Qt.transparent)
        painter = QPainter(pixmap)
        painter.setRenderHint(QPainter.Antialiasing)
        painter.setBrush(QColor("#29A8E0"))
        painter.setPen(Qt.NoPen)
        painter.drawEllipse(0, 0, size, size)
        painter.setBrush(QColor("#FFFFFF"))
        triangle = QPolygon(
            [
                QPoint(int(size * 0.25), int(size * 0.40)),
                QPoint(int(size * 0.75), int(size * 0.55)),
                QPoint(int(size * 0.25), int(size * 0.70)),
            ]
        )
        painter.drawPolygon(triangle)
        painter.end()
        return pixmap

    def _update_header_text(self) -> None:
        if hasattr(self, "header_title_label"):
            self.header_title_label.setText(translator.translate("header.title"))
        if hasattr(self, "header_subtitle_label"):
            self.header_subtitle_label.setText(translator.translate("header.subtitle"))

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
        self.session_refresh_button = refresh_button
        self.session_select_all_button = QPushButton(translator.translate("button.select_all"))
        self.session_select_all_button.clicked.connect(self.select_all_sessions)
        self.session_clear_selection_button = QPushButton(
            translator.translate("button.clear_selection")
        )
        self.session_clear_selection_button.clicked.connect(self.clear_session_selection)
        self.session_remove_button = QPushButton(translator.translate("button.remove_sessions"))
        self.session_remove_button.clicked.connect(self.remove_selected_sessions)

        layout.addWidget(self.session_list, 0, 2, 4, 1)
        session_button_row = QHBoxLayout()
        session_button_row.addWidget(self.session_refresh_button)
        session_button_row.addWidget(self.session_select_all_button)
        session_button_row.addWidget(self.session_clear_selection_button)
        session_button_row.addWidget(self.session_remove_button)
        session_button_row.addStretch()
        layout.addLayout(session_button_row, 4, 2)

        tab.setLayout(layout)
        self.tab_widget.addTab(tab, translator.translate("tab.sessions"))

    def _build_ban_tab(self) -> None:
        tab = QWidget()
        layout = QGridLayout()

        self.ban_session_list = QListWidget()
        self.ban_session_list.setSelectionMode(QListWidget.MultiSelection)
        layout.addWidget(self.ban_session_list, 0, 0, 4, 1)

        ban_button_row = QHBoxLayout()
        self.ban_select_all_button = QPushButton(translator.translate("button.select_all"))
        self.ban_select_all_button.clicked.connect(self.select_all_ban_sessions)
        self.ban_clear_selection_button = QPushButton(
            translator.translate("button.clear_selection")
        )
        self.ban_clear_selection_button.clicked.connect(self.clear_ban_selection)
        ban_button_row.addWidget(self.ban_select_all_button)
        ban_button_row.addWidget(self.ban_clear_selection_button)
        ban_button_row.addStretch()
        layout.addLayout(ban_button_row, 4, 0, 1, 1)

        check_button = QPushButton(translator.translate("button.check_ban"))
        check_button.clicked.connect(self.handle_ban_check)
        layout.addWidget(check_button, 0, 1)

        self.ban_result = QTextEdit()
        self.ban_result.setReadOnly(True)
        layout.addWidget(self.ban_result, 1, 1, 4, 1)

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
        self.scan_interval_value.setRange(1, 2_147_483_647)
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
        self.add_limit_input = QLineEdit()
        self.add_source_combo = QComboBox()
        self.add_source_combo.addItems(
            [
                translator.translate("option.add_source_scanned"),
                translator.translate("option.add_source_active"),
            ]
        )
        form_layout.addRow(translator.translate("label.target_group"), self.add_target_input)
        form_layout.addRow(translator.translate("label.limit"), self.add_limit_input)
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
        self.active_interval_value.setRange(1, 2_147_483_647)
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

    def _build_user_search_tab(self) -> None:
        tab = QWidget()
        layout = QGridLayout()

        self.user_search_session_list = QListWidget()
        self.user_search_session_list.setSelectionMode(QListWidget.MultiSelection)
        layout.addWidget(self.user_search_session_list, 0, 0, 4, 1)

        form_layout = QFormLayout()
        self.user_search_keyword_input = QLineEdit()
        self.user_search_limit_input = QSpinBox()
        self.user_search_limit_input.setRange(1, 1000)
        self.user_search_limit_input.setValue(100)
        self.user_search_save_checkbox = QCheckBox(
            translator.translate("checkbox.save_search_results")
        )
        self.user_search_save_checkbox.setChecked(True)
        self.user_search_include_no_username_checkbox = QCheckBox(
            translator.translate("checkbox.include_no_username")
        )
        form_layout.addRow(
            translator.translate("label.user_keywords"), self.user_search_keyword_input
        )
        form_layout.addRow(translator.translate("label.limit"), self.user_search_limit_input)
        form_layout.addRow(self.user_search_save_checkbox)
        form_layout.addRow(self.user_search_include_no_username_checkbox)
        layout.addLayout(form_layout, 0, 1, 1, 2)

        self.user_search_start_button = QPushButton(translator.translate("button.start"))
        self.user_search_start_button.clicked.connect(
            partial(self.start_task, task_type="user_search")
        )
        self.user_search_cancel_button = QPushButton(translator.translate("button.cancel"))
        self.user_search_cancel_button.clicked.connect(
            partial(self.cancel_tasks, task_type="user_search")
        )
        control_layout = QHBoxLayout()
        control_layout.addWidget(self.user_search_start_button)
        control_layout.addWidget(self.user_search_cancel_button)
        layout.addLayout(control_layout, 1, 1, 1, 2)

        self.user_search_progress_container = QVBoxLayout()
        progress_group = QGroupBox(translator.translate("label.progress"))
        progress_group.setLayout(self.user_search_progress_container)
        layout.addWidget(progress_group, 2, 1, 2, 2)

        tab.setLayout(layout)
        self.tab_widget.addTab(tab, translator.translate("tab.user_search"))

    def _build_group_discovery_tab(self) -> None:
        tab = QWidget()
        layout = QGridLayout()

        self.group_scan_session_list = QListWidget()
        self.group_scan_session_list.setSelectionMode(QListWidget.MultiSelection)
        layout.addWidget(self.group_scan_session_list, 0, 0, 3, 1)

        form_layout = QFormLayout()
        self.group_search_keyword_input = QLineEdit()
        self.group_search_limit_input = QSpinBox()
        self.group_search_limit_input.setRange(1, 100)
        self.group_search_limit_input.setValue(25)
        self.group_search_save_checkbox = QCheckBox(translator.translate("checkbox.save_groups"))
        self.group_search_save_checkbox.setChecked(True)
        form_layout.addRow(translator.translate("label.group_keyword"), self.group_search_keyword_input)
        form_layout.addRow(
            translator.translate("label.group_results_per_keyword"), self.group_search_limit_input
        )
        filter_row = QWidget()
        filter_layout = QHBoxLayout(filter_row)
        filter_layout.setContentsMargins(0, 0, 0, 0)
        filter_layout.setSpacing(6)
        self.group_filter_channel_checkbox = QCheckBox(
            translator.translate("checkbox.group_filter_channel")
        )
        self.group_filter_channel_checkbox.setChecked(True)
        self.group_filter_supergroup_checkbox = QCheckBox(
            translator.translate("checkbox.group_filter_supergroup")
        )
        self.group_filter_supergroup_checkbox.setChecked(True)
        self.group_filter_group_checkbox = QCheckBox(
            translator.translate("checkbox.group_filter_group")
        )
        self.group_filter_group_checkbox.setChecked(True)
        self.group_filter_admin_checkbox = QCheckBox(
            translator.translate("checkbox.group_filter_admin")
        )
        filter_layout.addWidget(self.group_filter_channel_checkbox)
        filter_layout.addWidget(self.group_filter_supergroup_checkbox)
        filter_layout.addWidget(self.group_filter_group_checkbox)
        filter_layout.addWidget(self.group_filter_admin_checkbox)
        filter_layout.addStretch()
        form_layout.addRow(translator.translate("label.group_filters"), filter_row)
        form_layout.addRow(self.group_search_save_checkbox)
        layout.addLayout(form_layout, 0, 1, 1, 2)

        control_layout = QHBoxLayout()
        self.group_search_start_button = QPushButton(translator.translate("button.start"))
        self.group_search_start_button.clicked.connect(partial(self.start_task, task_type="group_scan"))
        self.group_search_cancel_button = QPushButton(translator.translate("button.cancel"))
        self.group_search_cancel_button.clicked.connect(partial(self.cancel_tasks, task_type="group_scan"))
        self.group_join_button = QPushButton(translator.translate("button.join_groups"))
        self.group_join_button.clicked.connect(partial(self.start_task, task_type="group_join"))
        self.group_join_cancel_button = QPushButton(translator.translate("button.cancel_group_join"))
        self.group_join_cancel_button.clicked.connect(partial(self.cancel_tasks, task_type="group_join"))
        control_layout.addWidget(self.group_search_start_button)
        control_layout.addWidget(self.group_search_cancel_button)
        control_layout.addWidget(self.group_join_button)
        control_layout.addWidget(self.group_join_cancel_button)
        layout.addLayout(control_layout, 1, 1, 1, 2)

        self.group_scan_progress_container = QVBoxLayout()
        progress_group = QGroupBox(translator.translate("label.progress"))
        progress_group.setLayout(self.group_scan_progress_container)
        layout.addWidget(progress_group, 2, 1, 1, 2)

        table_group = QGroupBox(translator.translate("group.saved_groups"))
        table_layout = QVBoxLayout()
        self.group_table = QTableWidget(0, len(GROUP_TABLE_HEADERS))
        self.group_table.setSelectionBehavior(QTableWidget.SelectRows)
        self.group_table.setEditTriggers(QTableWidget.NoEditTriggers)
        self.group_table.horizontalHeader().setSectionResizeMode(QHeaderView.Stretch)
        self.group_table.verticalHeader().setVisible(False)
        self.group_table.setSortingEnabled(True)
        table_layout.addWidget(self.group_table)

        button_row = QHBoxLayout()
        self.group_import_button = QPushButton(translator.translate("button.import_groups"))
        self.group_import_button.clicked.connect(self.import_groups)
        self.group_export_button = QPushButton(translator.translate("button.export_groups"))
        self.group_export_button.clicked.connect(self.export_groups)
        self.group_add_button = QPushButton(translator.translate("button.add_group"))
        self.group_add_button.clicked.connect(self.add_group_manual)
        self.group_clear_button = QPushButton(translator.translate("button.clear_groups"))
        self.group_clear_button.clicked.connect(self.clear_groups)
        self.group_select_all_button = QPushButton(translator.translate("button.select_all"))
        self.group_select_all_button.clicked.connect(self.select_all_groups)
        self.group_clear_selection_button = QPushButton(
            translator.translate("button.clear_selection")
        )
        self.group_clear_selection_button.clicked.connect(self.clear_group_selection)
        self.group_export_selected_button = QPushButton(translator.translate("button.export_selected"))
        self.group_export_selected_button.clicked.connect(self.export_selected_groups)
        self.group_delete_selected_button = QPushButton(translator.translate("button.delete_selected"))
        self.group_delete_selected_button.clicked.connect(self.delete_selected_groups)
        button_row.addWidget(self.group_import_button)
        button_row.addWidget(self.group_export_button)
        button_row.addWidget(self.group_add_button)
        button_row.addWidget(self.group_clear_button)
        button_row.addWidget(self.group_select_all_button)
        button_row.addWidget(self.group_clear_selection_button)
        button_row.addWidget(self.group_export_selected_button)
        button_row.addWidget(self.group_delete_selected_button)
        button_row.addStretch()
        table_layout.addLayout(button_row)
        table_group.setLayout(table_layout)
        layout.addWidget(table_group, 3, 0, 1, 3)

        tab.setLayout(layout)
        self.tab_widget.addTab(tab, translator.translate("tab.group_discovery"))
        self._refresh_group_table()

    def _build_message_tab(self) -> None:
        tab = QWidget()
        layout = QGridLayout()

        self.message_session_list = QListWidget()
        self.message_session_list.setSelectionMode(QListWidget.MultiSelection)
        self.message_session_list.currentItemChanged.connect(self._handle_message_session_change)
        self.message_session_list.itemChanged.connect(lambda _: self._refresh_message_template_summary())
        layout.addWidget(self.message_session_list, 0, 0, 6, 1)

        form_layout = QFormLayout()
        self.message_source_combo = QComboBox()
        for key, _, title_key in self.user_table_meta:
            self.message_source_combo.addItem(translator.translate(title_key), key)
        form_layout.addRow(translator.translate("label.message_source"), self.message_source_combo)

        self.message_limit_input = QLineEdit()
        form_layout.addRow(translator.translate("label.message_limit"), self.message_limit_input)

        self.message_body_input = QTextEdit()
        form_layout.addRow(translator.translate("label.message_body"), self.message_body_input)
        placeholder_bar = self._build_placeholder_toolbar(self.message_body_input)
        form_layout.addRow(translator.translate("label.placeholders"), placeholder_bar)

        media_row = QHBoxLayout()
        self.message_media_input = QLineEdit()
        self.message_media_button = QPushButton(translator.translate("button.choose_file"))
        self.message_media_button.clicked.connect(partial(self._browse_media_file, self.message_media_input))
        media_row.addWidget(self.message_media_input)
        media_row.addWidget(self.message_media_button)
        form_layout.addRow(translator.translate("label.message_media"), media_row)

        layout.addLayout(form_layout, 0, 1, 3, 1)

        assignment_group = QGroupBox(translator.translate("group.assigned_templates"))
        assignment_layout = QVBoxLayout()
        self.message_template_summary = QListWidget()
        assignment_layout.addWidget(self.message_template_summary)
        assignment_group.setLayout(assignment_layout)
        layout.addWidget(assignment_group, 0, 2, 3, 1)

        self.message_start_button = QPushButton(translator.translate("button.start"))
        self.message_start_button.clicked.connect(partial(self.start_task, task_type="message"))
        message_control_layout = QHBoxLayout()
        message_control_layout.addWidget(self.message_start_button)
        layout.addLayout(message_control_layout, 3, 1, 1, 2)

        self.message_progress_container = QVBoxLayout()
        progress_group = QGroupBox(translator.translate("label.progress"))
        progress_group.setLayout(self.message_progress_container)
        layout.addWidget(progress_group, 4, 1, 2, 2)

        tab.setLayout(layout)
        self.tab_widget.addTab(tab, translator.translate("tab.direct_messages"))
        self._refresh_message_template_summary()

    def _build_group_message_tab(self) -> None:
        tab = QWidget()
        layout = QGridLayout()

        self.group_message_session_list = QListWidget()
        self.group_message_session_list.setSelectionMode(QListWidget.MultiSelection)
        layout.addWidget(self.group_message_session_list, 0, 0, 4, 1)

        form_layout = QFormLayout()
        self.group_message_limit_input = QLineEdit()
        form_layout.addRow(translator.translate("label.limit"), self.group_message_limit_input)

        self.group_message_list = QListWidget()
        self.group_message_list.setSelectionMode(QListWidget.MultiSelection)
        form_layout.addRow(translator.translate("label.saved_groups"), self.group_message_list)

        self.group_message_manual_input = QTextEdit()
        form_layout.addRow(translator.translate("label.manual_groups"), self.group_message_manual_input)

        self.group_message_body_input = QTextEdit()
        form_layout.addRow(translator.translate("label.message_body"), self.group_message_body_input)
        group_placeholder_bar = self._build_placeholder_toolbar(
            self.group_message_body_input, placeholders=[]
        )
        form_layout.addRow(translator.translate("label.placeholders"), group_placeholder_bar)

        media_row = QHBoxLayout()
        self.group_message_media_input = QLineEdit()
        self.group_message_media_button = QPushButton(translator.translate("button.choose_file"))
        self.group_message_media_button.clicked.connect(partial(self._browse_media_file, self.group_message_media_input))
        media_row.addWidget(self.group_message_media_input)
        media_row.addWidget(self.group_message_media_button)
        form_layout.addRow(translator.translate("label.message_media"), media_row)

        layout.addLayout(form_layout, 0, 1, 3, 1)

        assignment_group = QGroupBox(translator.translate("group.assigned_templates"))
        assignment_layout = QVBoxLayout()
        self.group_message_template_summary = QListWidget()
        assignment_layout.addWidget(self.group_message_template_summary)
        assignment_group.setLayout(assignment_layout)
        layout.addWidget(assignment_group, 0, 2, 3, 1)

        self.group_message_start_button = QPushButton(translator.translate("button.start"))
        self.group_message_start_button.clicked.connect(partial(self.start_task, task_type="group_message"))
        self.group_message_cancel_button = QPushButton(translator.translate("button.cancel"))
        self.group_message_cancel_button.clicked.connect(partial(self.cancel_tasks, task_type="group_message"))
        control_layout = QHBoxLayout()
        control_layout.addWidget(self.group_message_start_button)
        control_layout.addWidget(self.group_message_cancel_button)
        layout.addLayout(control_layout, 3, 1, 1, 2)

        self.group_message_progress_container = QVBoxLayout()
        progress_group = QGroupBox(translator.translate("label.progress"))
        progress_group.setLayout(self.group_message_progress_container)
        layout.addWidget(progress_group, 4, 1, 2, 2)

        tab.setLayout(layout)
        self.tab_widget.addTab(tab, translator.translate("tab.group_messages"))
        self._refresh_group_message_list()
        self._refresh_group_message_template_summary()

    def _build_template_tab(self) -> None:
        tab = QWidget()
        layout = QGridLayout()

        self.template_session_list = QListWidget()
        self.template_session_list.setSelectionMode(QListWidget.SingleSelection)
        self.template_session_list.currentItemChanged.connect(self._handle_template_session_change)
        layout.addWidget(self.template_session_list, 0, 0, 3, 1)

        self.template_list_widget = QListWidget()
        self.template_list_widget.setSelectionMode(QListWidget.SingleSelection)
        self.template_list_widget.currentItemChanged.connect(self._handle_template_selection_change)
        layout.addWidget(self.template_list_widget, 0, 1, 3, 1)

        form_layout = QFormLayout()
        self.template_name_input = QLineEdit()
        form_layout.addRow(translator.translate("label.template_name"), self.template_name_input)

        self.template_body_input = QTextEdit()
        form_layout.addRow(translator.translate("label.template_body"), self.template_body_input)
        template_placeholder_bar = self._build_placeholder_toolbar(self.template_body_input)
        form_layout.addRow(translator.translate("label.placeholders"), template_placeholder_bar)

        template_media_row = QHBoxLayout()
        self.template_media_input = QLineEdit()
        self.template_media_button = QPushButton(translator.translate("button.choose_file"))
        self.template_media_button.clicked.connect(partial(self._browse_media_file, self.template_media_input))
        template_media_row.addWidget(self.template_media_input)
        template_media_row.addWidget(self.template_media_button)
        form_layout.addRow(translator.translate("label.template_media"), template_media_row)

        template_button_layout = QHBoxLayout()
        self.template_save_button = QPushButton(translator.translate("button.save_template"))
        self.template_save_button.clicked.connect(self._save_message_template)
        self.template_delete_button = QPushButton(translator.translate("button.delete_template"))
        self.template_delete_button.clicked.connect(self._delete_message_template)
        self.template_assign_button = QPushButton(translator.translate("button.assign_template"))
        self.template_assign_button.clicked.connect(self._assign_template_to_session)
        template_button_layout.addWidget(self.template_save_button)
        template_button_layout.addWidget(self.template_delete_button)
        template_button_layout.addWidget(self.template_assign_button)
        form_layout.addRow(template_button_layout)

        layout.addLayout(form_layout, 0, 2, 3, 1)

        tab.setLayout(layout)
        self.tab_widget.addTab(tab, translator.translate("tab.templates"))
        self._refresh_template_combo(self._current_template_session())

    def _build_group_template_tab(self) -> None:
        tab = QWidget()
        layout = QGridLayout()

        self.group_template_session_list = QListWidget()
        self.group_template_session_list.setSelectionMode(QListWidget.SingleSelection)
        self.group_template_session_list.currentItemChanged.connect(self._handle_group_template_session_change)
        layout.addWidget(self.group_template_session_list, 0, 0, 3, 1)

        self.group_template_list_widget = QListWidget()
        self.group_template_list_widget.setSelectionMode(QListWidget.SingleSelection)
        self.group_template_list_widget.currentItemChanged.connect(self._handle_group_template_selection_change)
        layout.addWidget(self.group_template_list_widget, 0, 1, 3, 1)

        form_layout = QFormLayout()
        self.group_template_name_input = QLineEdit()
        form_layout.addRow(translator.translate("label.template_name"), self.group_template_name_input)
        self.group_template_body_input = QTextEdit()
        form_layout.addRow(translator.translate("label.template_body"), self.group_template_body_input)
        group_template_toolbar = self._build_placeholder_toolbar(
            self.group_template_body_input, placeholders=[]
        )
        form_layout.addRow(translator.translate("label.placeholders"), group_template_toolbar)
        media_row = QHBoxLayout()
        self.group_template_media_input = QLineEdit()
        self.group_template_media_button = QPushButton(translator.translate("button.choose_file"))
        self.group_template_media_button.clicked.connect(partial(self._browse_media_file, self.group_template_media_input))
        media_row.addWidget(self.group_template_media_input)
        media_row.addWidget(self.group_template_media_button)
        form_layout.addRow(translator.translate("label.template_media"), media_row)
        button_row = QHBoxLayout()
        self.group_template_save_button = QPushButton(translator.translate("button.save_template"))
        self.group_template_save_button.clicked.connect(self._save_group_template)
        self.group_template_delete_button = QPushButton(translator.translate("button.delete_template"))
        self.group_template_delete_button.clicked.connect(self._delete_group_template)
        self.group_template_assign_button = QPushButton(translator.translate("button.assign_template"))
        self.group_template_assign_button.clicked.connect(self._assign_group_template)
        button_row.addWidget(self.group_template_save_button)
        button_row.addWidget(self.group_template_delete_button)
        button_row.addWidget(self.group_template_assign_button)
        form_layout.addRow(button_row)

        layout.addLayout(form_layout, 0, 2, 3, 1)

        tab.setLayout(layout)
        self.tab_widget.addTab(tab, translator.translate("tab.group_templates"))
        self._refresh_group_template_combo(self._current_group_template_session())

    def _build_rate_tab(self) -> None:
        tab = QWidget()
        form_layout = QFormLayout()
        self.join_interval_input = QSpinBox()
        self.join_interval_input.setRange(1, 3600)
        self.join_interval_input.setValue(int(self.settings.rate_limit.join_interval))
        self.group_join_interval_input = QSpinBox()
        self.group_join_interval_input.setRange(1, 3600)
        self.group_join_interval_input.setValue(int(self.settings.rate_limit.group_join_interval))
        self.message_interval_input = QSpinBox()
        self.message_interval_input.setRange(1, 3600)
        self.message_interval_input.setValue(int(self.settings.rate_limit.message_interval))
        self.scan_interval_input = QSpinBox()
        self.scan_interval_input.setRange(1, 3600)
        self.scan_interval_input.setValue(int(self.settings.rate_limit.scan_interval))
        self.max_active_messages_input = QSpinBox()
        self.max_active_messages_input.setRange(1, 2_147_483_647)
        self.max_active_messages_input.setValue(int(self.settings.max_active_messages))
        self.max_active_messages_label = QLabel(translator.translate("label.max_active_messages"))
        self.flood_checkbox = QCheckBox(translator.translate("label.flood_wait"))
        self.flood_checkbox.setChecked(self.settings.rate_limit.flood_wait_handling)

        form_layout.addRow(translator.translate("label.rate_join"), self.join_interval_input)
        form_layout.addRow(translator.translate("label.rate_group_join"), self.group_join_interval_input)
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
        self.select_all_users_button = QPushButton(translator.translate("button.select_all"))
        self.select_all_users_button.clicked.connect(self.select_all_users)
        self.clear_selection_users_button = QPushButton(
            translator.translate("button.clear_selection")
        )
        self.clear_selection_users_button.clicked.connect(self.clear_user_selection)
        self.export_selected_users_button = QPushButton(translator.translate("button.export_selected"))
        self.export_selected_users_button.clicked.connect(self.export_selected_users)
        self.delete_selected_users_button = QPushButton(translator.translate("button.delete_selected"))
        self.delete_selected_users_button.clicked.connect(self.delete_selected_users)
        self.refresh_dm_button = QPushButton(translator.translate("button.refresh_dm"))
        self.refresh_dm_button.clicked.connect(self.refresh_dm_statuses)
        button_layout.addWidget(self.export_users_button)
        button_layout.addWidget(self.import_users_button)
        button_layout.addWidget(self.add_user_button)
        button_layout.addWidget(self.clear_users_button)
        button_layout.addWidget(self.select_all_users_button)
        button_layout.addWidget(self.clear_selection_users_button)
        button_layout.addWidget(self.export_selected_users_button)
        button_layout.addWidget(self.delete_selected_users_button)
        button_layout.addWidget(self.refresh_dm_button)

        self.user_tab_widget = QTabWidget()
        self.user_tables: Dict[str, QTableWidget] = {}

        base_columns = 12
        for key, has_message, title_key in self.user_table_meta:
            column_count = 1 + base_columns + (1 if has_message else 0)
            table = QTableWidget(0, column_count)
            table.setSelectionBehavior(QTableWidget.SelectRows)
            table.setEditTriggers(QTableWidget.NoEditTriggers)
            table.setSortingEnabled(True)
            header = table.horizontalHeader()
            if header:
                header.setSectionsClickable(True)
                header.setSectionResizeMode(QHeaderView.Stretch)
            table.verticalHeader().setVisible(False)
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
        if hasattr(self, "user_search_session_list"):
            self.user_search_session_list.clear()
        message_widget = getattr(self, "message_session_list", None)
        template_widget = getattr(self, "template_session_list", None)
        group_scan_widget = getattr(self, "group_scan_session_list", None)
        group_message_widget = getattr(self, "group_message_session_list", None)
        group_template_widget = getattr(self, "group_template_session_list", None)
        ban_widget = getattr(self, "ban_session_list", None)
        if group_scan_widget:
            group_scan_widget.clear()
        if group_message_widget:
            group_message_widget.clear()
        if group_template_widget:
            group_template_widget.clear()
        if message_widget:
            message_widget.blockSignals(True)
            message_widget.clear()
        if template_widget:
            template_widget.blockSignals(True)
            template_widget.clear()
        if ban_widget:
            ban_widget.clear()
        if group_template_widget:
            group_template_widget.blockSignals(True)
        session_widgets = [
            self.session_list,
            self.scan_session_list,
            self.add_session_list,
            self.active_session_list,
            getattr(self, "user_search_session_list", None),
        ]
        if ban_widget:
            session_widgets.append(ban_widget)
        for info in self.session_manager.list_sessions():
            widgets = list(session_widgets)
            if group_scan_widget:
                widgets.append(group_scan_widget)
            if group_message_widget:
                widgets.append(group_message_widget)
            if message_widget:
                widgets.append(message_widget)
            for widget in widgets:
                item = QListWidgetItem(info.name)
                item.setFlags(item.flags() | Qt.ItemIsUserCheckable | Qt.ItemIsSelectable | Qt.ItemIsEnabled)
                item.setCheckState(Qt.Unchecked)
                widget.addItem(item)
            if template_widget:
                template_item = QListWidgetItem(info.name)
                template_item.setFlags(Qt.ItemIsSelectable | Qt.ItemIsEnabled)
                template_widget.addItem(template_item)
            if group_template_widget:
                group_template_item = QListWidgetItem(info.name)
                group_template_item.setFlags(Qt.ItemIsSelectable | Qt.ItemIsEnabled)
                group_template_widget.addItem(group_template_item)
        if message_widget:
            message_widget.blockSignals(False)
        if template_widget:
            template_widget.blockSignals(False)
            if template_widget.count() > 0 and template_widget.currentRow() == -1:
                template_widget.setCurrentRow(0)
        if group_template_widget:
            group_template_widget.blockSignals(False)
            if group_template_widget.count() > 0 and group_template_widget.currentRow() == -1:
                group_template_widget.setCurrentRow(0)
        self._refresh_message_template_summary()
        self._refresh_group_message_template_summary()
        self._refresh_template_combo(self._current_template_session())
        self._refresh_group_template_combo(self._current_group_template_session())

    def get_selected_sessions(self, widget: QListWidget) -> List[str]:
        selected: List[str] = []
        for index in range(widget.count()):
            item = widget.item(index)
            if item.checkState() == Qt.Checked:
                selected.append(item.text())
        return selected

    def _set_session_checks(self, widget: Optional[QListWidget], state: Qt.CheckState) -> None:
        if not widget:
            return
        for index in range(widget.count()):
            item = widget.item(index)
            if item:
                item.setCheckState(state)

    def clear_session_selection(self) -> None:
        self._set_session_checks(self.session_list, Qt.Unchecked)

    def select_all_sessions(self) -> None:
        self._set_session_checks(self.session_list, Qt.Checked)

    def select_all_ban_sessions(self) -> None:
        self._set_session_checks(getattr(self, "ban_session_list", None), Qt.Checked)

    def clear_ban_selection(self) -> None:
        self._set_session_checks(getattr(self, "ban_session_list", None), Qt.Unchecked)

    def remove_selected_sessions(self) -> None:
        sessions = self.get_selected_sessions(self.session_list)
        if not sessions:
            QMessageBox.warning(
                self,
                self.windowTitle(),
                translator.translate("dialog.no_sessions_selected"),
            )
            return

        confirm = QMessageBox.question(
            self,
            self.windowTitle(),
            translator.translate("dialog.delete_selected_sessions_confirm"),
            QMessageBox.Yes | QMessageBox.No,
            QMessageBox.No,
        )
        if confirm != QMessageBox.Yes:
            return

        async def runner() -> None:
            for name in sessions:
                await self.session_manager.remove_session(name)

        try:
            asyncio.run(runner())
        except Exception as exc:  # pragma: no cover - filesystem/network errors
            QMessageBox.critical(self, self.windowTitle(), str(exc))
            return

        QMessageBox.information(self, self.windowTitle(), translator.translate("dialog.success"))
        self.refresh_sessions()

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
        target_widget = getattr(self, "ban_session_list", self.session_list)
        sessions = self.get_selected_sessions(target_widget)
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
        storage: Optional[Union[UserStorage, GroupStorage]] = None
        group_chunks: List[List[StoredGroup]] = []
        keyword_pool: List[str] = []
        pending_group_join: List[StoredGroup] = []
        message_payloads: Dict[str, Tuple[str, Optional[str]]] = {}
        group_filters: Optional[Dict[str, bool]] = None
        session_targets: List[str] = []
        if task_type == "scan":
            sessions = self.get_selected_sessions(self.scan_session_list)
            target = self.scan_target_input.text().strip()
            limit = self._parse_int(self.scan_limit_input.text())
            interval = self._build_interval(self.scan_interval_combo.currentIndex(), self.scan_interval_value.value())
            container = self.scan_progress_container
            persist = self.scan_save_checkbox.isChecked()
            storage = self.user_storages["searched"]
            include_no_username = self.scan_include_no_username_checkbox.isChecked()
        elif task_type == "group_scan":
            sessions = self.get_selected_sessions(self.group_scan_session_list)
            target = self.group_search_keyword_input.text().strip()
            limit = self.group_search_limit_input.value()
            interval = None
            container = self.group_scan_progress_container
            persist = self.group_search_save_checkbox.isChecked()
            storage = self.group_storage
            group_filters = self._collect_group_filters()
            keyword_pool = self._parse_keywords(target)
            if not keyword_pool:
                QMessageBox.warning(
                    self,
                    self.windowTitle(),
                    translator.translate("dialog.group_keyword_required"),
                )
                return
            type_selected = any(
                [
                    group_filters.get("channel", False),
                    group_filters.get("supergroup", False),
                    group_filters.get("group", False),
                ]
            )
            if not type_selected:
                QMessageBox.warning(
                    self,
                    self.windowTitle(),
                    translator.translate("dialog.group_filter_required"),
                )
                return
        elif task_type == "group_join":
            sessions = self.get_selected_sessions(self.group_scan_session_list)
            target = ""
            limit = None
            interval = None
            container = self.group_scan_progress_container
            persist = False
            storage = self.group_storage
            pending_group_join = self._selected_groups()
            if not pending_group_join:
                QMessageBox.warning(
                    self,
                    self.windowTitle(),
                    translator.translate("dialog.no_groups_selected"),
                )
                return
        elif task_type == "add":
            sessions = self.get_selected_sessions(self.add_session_list)
            target = self.add_target_input.text().strip()
            limit = self._parse_int(self.add_limit_input.text())
            interval = None
            container = self.add_progress_container
            persist = True
            storage = self.user_storages[self._current_add_storage_key()]
        elif task_type == "message":
            sessions = self.get_selected_sessions(self.message_session_list)
            target = ""
            limit = self._parse_int(self.message_limit_input.text())
            interval = None
            container = self.message_progress_container
            persist = False
            storage = self.user_storages[self._current_message_storage_key()]
        elif task_type == "group_message":
            sessions = self.get_selected_sessions(self.group_message_session_list)
            target = ""
            limit = self._parse_int(self.group_message_limit_input.text())
            interval = None
            container = self.group_message_progress_container
            persist = False
            storage = self.group_storage
        elif task_type == "user_search":
            sessions = self.get_selected_sessions(self.user_search_session_list)
            target = self.user_search_keyword_input.text().strip()
            limit = self.user_search_limit_input.value()
            interval = None
            container = self.user_search_progress_container
            persist = self.user_search_save_checkbox.isChecked()
            storage = self.user_storages["searched"]
            include_no_username = self.user_search_include_no_username_checkbox.isChecked()
            keyword_pool = self._parse_keywords(target)
            if not keyword_pool:
                QMessageBox.warning(
                    self,
                    self.windowTitle(),
                    translator.translate("dialog.user_keyword_required"),
                )
                return
        else:
            sessions = self.get_selected_sessions(self.active_session_list)
            target = self.active_target_input.text().strip()
            limit = self._parse_int(self.active_limit_input.text())
            interval = self._build_interval(self.active_interval_combo.currentIndex(), self.active_interval_value.value())
            container = self.active_progress_container
            persist = self.active_save_checkbox.isChecked()
            storage = self.user_storages["active"]
            include_no_username = self.active_include_no_username_checkbox.isChecked()

        requires_target = task_type in {"scan", "group_scan", "add", "active", "user_search"}
        if requires_target and (not sessions or not target):
            QMessageBox.warning(self, self.windowTitle(), translator.translate("dialog.sessions_required"))
            return
        if task_type in {"message", "group_message", "group_join"} and not sessions:
            QMessageBox.warning(self, self.windowTitle(), translator.translate("dialog.sessions_required"))
            return
        if task_type == "message" and not (self.message_body_input.toPlainText().strip() or self.message_media_input.text().strip()):
            has_template = any(self.template_storage.get_assignment(session) for session in sessions)
            if not has_template:
                QMessageBox.warning(self, self.windowTitle(), translator.translate("dialog.message_missing_body"))
                return
        if task_type == "group_message" and not (
            self.group_message_body_input.toPlainText().strip() or self.group_message_media_input.text().strip()
        ):
            has_template = any(self.group_template_storage.get_assignment(session) for session in sessions)
            if not has_template:
                QMessageBox.warning(self, self.windowTitle(), translator.translate("dialog.message_missing_body"))
                return

        if storage is None:
            QMessageBox.critical(self, self.windowTitle(), translator.translate("dialog.storage_missing"))
            return
        result_storage: Optional[UserStorage] = None
        if task_type == "add":
            result_key = self._add_result_storage_key("scanned" if storage is self.user_storages["scanned"] else "active")
            result_storage = self.user_storages.get(result_key)

        progress_map = self.progress_widgets[task_type]
        for session_name in list(progress_map.keys()):
            if session_name not in sessions:
                if (task_type, session_name) in self.worker_threads:
                    continue
                widget = progress_map.pop(session_name)
                self._remove_progress_widget(container, widget)

        session_count = len(sessions)
        per_session_limits: List[Optional[int]] = [None] * session_count
        offsets: List[int] = [0] * session_count
        if task_type == "add":
            users = storage.get_users()  # type: ignore[attr-defined]
            if not users:
                QMessageBox.warning(self, self.windowTitle(), translator.translate("dialog.no_users_available"))
                return
            selected_users = users
            if limit and limit > 0:
                selected_users = users[:limit]
            user_chunks = self._split_users_for_sessions(selected_users, session_count)
            per_session_limits = [len(chunk) for chunk in user_chunks]
        elif task_type == "message":
            users = storage.get_users()  # type: ignore[attr-defined]
            if not users:
                QMessageBox.warning(self, self.windowTitle(), translator.translate("dialog.no_users_available"))
                return
            selected_users = users
            if limit and limit > 0:
                selected_users = users[:limit]
            user_chunks = self._split_users_for_sessions(selected_users, session_count)
            per_session_limits = [len(chunk) for chunk in user_chunks]
            for session in sessions:
                body, media = self._resolve_message_payload(
                    session,
                    self.message_body_input.toPlainText().strip(),
                    self.message_media_input.text().strip(),
                )
                if not (body or media):
                    QMessageBox.warning(
                        self,
                        self.windowTitle(),
                        translator.translate("dialog.message_missing_body"),
                    )
                    return
                message_payloads[session] = (body, media)
        elif task_type == "group_message":
            groups = self._collect_group_targets(limit)
            if not groups:
                QMessageBox.warning(self, self.windowTitle(), translator.translate("dialog.no_groups_available"))
                return
            group_chunks = self._split_groups_for_sessions(groups, session_count)
            per_session_limits = [len(chunk) for chunk in group_chunks]
            manual_body = self.group_message_body_input.toPlainText().strip()
            manual_media = self.group_message_media_input.text().strip()
            for session in sessions:
                body, media = self._resolve_message_payload(
                    session,
                    manual_body,
                    manual_media,
                    storage=self.group_template_storage,
                )
                if not (body or media):
                    QMessageBox.warning(
                        self,
                        self.windowTitle(),
                        translator.translate("dialog.message_missing_body"),
                    )
                    return
                message_payloads[session] = (body, media)
        elif task_type == "group_join":
            group_chunks = self._split_groups_for_sessions(pending_group_join, session_count)
            per_session_limits = [len(chunk) for chunk in group_chunks]
        else:
            user_chunks = [None] * session_count
            if task_type == "scan":
                per_session_limits, offsets = self._scan_distribution(limit, target, sessions)
            elif task_type == "group_scan":
                pass
            elif task_type == "user_search":
                pass
            else:
                per_session_limits, offsets = self._active_distribution(limit, session_count)

        keyword_chunks: List[List[str]] = []
        session_targets = [target] * session_count
        if task_type == "group_scan":
            keyword_chunks = self._split_keywords_for_sessions(keyword_pool, session_count)
            if not any(keyword_chunks) and keyword_pool:
                keyword_chunks = [keyword_pool]
            session_targets = [",".join(chunk) if chunk else "" for chunk in keyword_chunks]
            per_session_limits = [
                (limit or 1) * len(chunk) if chunk else 0 for chunk in keyword_chunks
            ]
        elif task_type == "user_search":
            keyword_chunks = self._split_keywords_for_sessions(keyword_pool, session_count)
            if not any(keyword_chunks) and keyword_pool:
                keyword_chunks = [keyword_pool]
            session_targets = [",".join(chunk) if chunk else "" for chunk in keyword_chunks]
            per_session_limits = [
                (limit or 1) * len(chunk) if chunk else 0 for chunk in keyword_chunks
            ]

        requests_started = 0
        for idx, session_name in enumerate(sessions):
            widget = progress_map.get(session_name)
            if not widget:
                widget = SessionProgressWidget(session_name, task_type)
                progress_map[session_name] = widget
                container.addWidget(widget)
            else:
                widget.reset()

            session_target = session_targets[idx] if idx < len(session_targets) else target
            per_session_limit = per_session_limits[idx] if idx < len(per_session_limits) else None
            offset_value = offsets[idx] if idx < len(offsets) else 0

            session_groups = group_chunks[idx] if idx < len(group_chunks) else None
            if task_type in {"add", "message"}:
                session_users = user_chunks[idx] or []
                total_target = len(session_users)
                if total_target == 0:
                    widget.update_state(0, 0, status_key="status.completed")
                    continue
            elif task_type == "group_message":
                session_users = None
                group_list = session_groups or []
                total_target = len(group_list)
                if total_target == 0:
                    widget.update_state(0, 0, status_key="status.completed")
                    continue
            elif task_type == "group_join":
                session_users = None
                group_list = session_groups or []
                total_target = len(group_list)
                if total_target == 0:
                    widget.update_state(0, 0, status_key="status.completed")
                    continue
            else:
                session_users = None
                if per_session_limit == 0:
                    widget.update_state(0, 0, status_key="status.completed")
                    continue
                total_target = per_session_limit or 0

            if task_type in {"group_scan", "user_search"} and not session_target.strip():
                widget.update_state(0, 0, status_key="status.completed")
                continue

            widget.update_state(0, total_target or 0, status_key="status.running")

            request_limit: Optional[int] = None
            if task_type in {"group_scan", "user_search"}:
                request_limit = max(limit or 1, 1)
            elif task_type not in {"add", "message"} and isinstance(per_session_limit, int) and per_session_limit > 0:
                request_limit = per_session_limit

            payload = message_payloads.get(session_name)
            request = TaskRequest(
                task_type=task_type,
                session_name=session_name,
                entity=session_target,
                limit=request_limit,
                interval=interval,
                users=session_users,
                persist_results=persist,
                storage=storage,
                include_no_username=include_no_username,
                offset=offset_value if request_limit else 0,
                result_storage=result_storage,
                message_body=payload[0] if payload else None,
                message_media=payload[1] if payload else None,
                groups=session_groups if task_type in {"group_message", "group_join"} else None,
                group_filters=group_filters if task_type == "group_scan" else None,
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
        elif update.group:
            widget.append_user(self._format_group(update.group))
            if widget.task_type in {"group_scan", "group_message"}:
                self._refresh_group_table()
                self._refresh_group_message_list()
        else:
            if update.status and not update.status.startswith("status."):
                widget.append_user(update.status)
            if widget.task_type == "add":
                self.populate_user_tables()

    def on_finished(self, widget: SessionProgressWidget, session_name: str, task_type: str) -> None:
        key = (task_type, session_name)
        processed_total = widget.state.processed
        total_value = widget.state.total or processed_total
        widget.update_state(processed_total, total_value, status_key="status.completed")
        thread = self.worker_threads.pop(key, None)
        if thread:
            thread.wait(1000)
        if task_type in {"group_scan", "group_message"}:
            self._refresh_group_table()
            self._refresh_group_message_list()
        if task_type in {"scan", "active", "user_search"}:
            self.populate_user_tables()
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
        buttons = [
            self.scan_start_button,
            self.add_start_button,
            self.active_start_button,
            self.user_search_start_button,
        ]
        for attr in [
            "group_search_start_button",
            "group_join_button",
            "message_start_button",
            "group_message_start_button",
        ]:
            if hasattr(self, attr):
                buttons.append(getattr(self, attr))
        for button in buttons:
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

    @staticmethod
    def _format_group(group: StoredGroup) -> str:
        parts = [group.title]
        if group.members:
            parts.append(str(group.members))
        if group.online:
            parts.append(translator.translate("label.group_online_short").format(count=group.online))
        if group.link:
            parts.append(group.link)
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
                checkbox = QTableWidgetItem()
                checkbox.setFlags(Qt.ItemIsUserCheckable | Qt.ItemIsEnabled)
                checkbox.setCheckState(Qt.Unchecked)
                checkbox.setData(Qt.UserRole, user.user_id)
                table.setItem(row, 0, checkbox)
                table.setItem(row, 1, QTableWidgetItem(str(user.user_id)))
                table.setItem(row, 2, QTableWidgetItem(user.username or ""))
                table.setItem(row, 3, QTableWidgetItem(user.first_name or ""))
                table.setItem(row, 4, QTableWidgetItem(user.last_name or ""))
                table.setItem(row, 5, QTableWidgetItem(user.phone or ""))
                table.setItem(row, 6, QTableWidgetItem(user.last_seen or ""))
                status_value = translator.translate(user.status) if user.status else ""
                table.setItem(row, 7, QTableWidgetItem(status_value))
                dm_value = translator.translate(user.dm_status) if user.dm_status else ""
                table.setItem(row, 8, QTableWidgetItem(dm_value))
                score_item = NumericTableWidgetItem(user.dm_score)
                table.setItem(row, 9, score_item)
                label_text = translator.translate(user.dm_score_label) if user.dm_score_label else ""
                table.setItem(row, 10, QTableWidgetItem(label_text))
                table.setItem(row, 11, QTableWidgetItem(user.source or ""))
                bot_key = "table.value.yes" if getattr(user, "is_bot", False) else "table.value.no"
                table.setItem(row, 12, QTableWidgetItem(translator.translate(bot_key)))
                if has_message:
                    message_text = user.last_message or ""
                    table.setItem(row, 13, QTableWidgetItem(message_text))
            table.setSortingEnabled(True)

    def _refresh_group_table(self) -> None:
        if not hasattr(self, "group_table"):
            return
        groups = self.group_storage.get_groups()
        self.group_table.setSortingEnabled(False)
        self.group_table.setRowCount(len(groups))
        for col, header_key in enumerate(GROUP_TABLE_HEADERS):
            item = QTableWidgetItem(translator.translate(header_key))
            self.group_table.setHorizontalHeaderItem(col, item)
        for row, group in enumerate(groups):
            checkbox = QTableWidgetItem()
            checkbox.setFlags(Qt.ItemIsUserCheckable | Qt.ItemIsEnabled)
            checkbox.setCheckState(Qt.Unchecked)
            checkbox.setData(Qt.UserRole, group)
            self.group_table.setItem(row, 0, checkbox)
            self.group_table.setItem(row, 1, QTableWidgetItem(group.title))
            username_display = f"@{group.username}" if group.username else ""
            self.group_table.setItem(row, 2, QTableWidgetItem(username_display))
            self.group_table.setItem(row, 3, QTableWidgetItem(group.link or ""))
            self.group_table.setItem(row, 4, QTableWidgetItem(self._group_type_text(group)))
            members_item = NumericTableWidgetItem(group.members)
            self.group_table.setItem(row, 5, members_item)
            online_item = NumericTableWidgetItem(group.online)
            self.group_table.setItem(row, 6, online_item)
            self.group_table.setItem(row, 7, QTableWidgetItem(self._group_visibility_text(group)))
            self.group_table.setItem(row, 8, QTableWidgetItem(self._group_messaging_text(group)))
            hidden_key = "table.value.yes" if getattr(group, "members_hidden", False) else "table.value.no"
            self.group_table.setItem(row, 9, QTableWidgetItem(translator.translate(hidden_key)))
            self.group_table.setItem(row, 10, QTableWidgetItem(group.source or ""))
        self.group_table.setSortingEnabled(True)

    def _group_visibility_text(self, group: StoredGroup) -> str:
        key = "label.group_public" if group.is_public else "label.group_private"
        return translator.translate(key)

    def _group_type_text(self, group: StoredGroup) -> str:
        if group.is_broadcast and not group.is_megagroup:
            key = "label.group_type_channel"
        elif group.is_megagroup:
            key = "label.group_type_supergroup"
        else:
            key = "label.group_type_group"
        return translator.translate(key)

    def _group_messaging_text(self, group: StoredGroup) -> str:
        key = "label.group_open_chat" if not group.messages_restricted else "label.group_closed_chat"
        return translator.translate(key)

    def _refresh_group_message_list(self) -> None:
        if not hasattr(self, "group_message_list"):
            return
        self.group_message_list.clear()
        for group in self.group_storage.get_groups():
            label = group.title
            if group.members:
                label = f"{group.title} ({group.members})"
            item = QListWidgetItem(label)
            item.setData(Qt.UserRole, group)
            self.group_message_list.addItem(item)

    def import_groups(self) -> None:
        path, _ = QFileDialog.getOpenFileName(
            self,
            translator.translate("dialog.group_import_title"),
            str(self.settings.user_directory),
            "JSON (*.json)",
        )
        if not path:
            return
        try:
            self.group_storage.import_from_file(Path(path))
        except Exception as exc:
            QMessageBox.critical(self, self.windowTitle(), f"{translator.translate('dialog.group_import_failure')}: {exc}")
            return
        self._refresh_group_table()
        self._refresh_group_message_list()
        QMessageBox.information(self, self.windowTitle(), translator.translate("dialog.group_import_success"))

    def export_groups(self) -> None:
        path, _ = QFileDialog.getSaveFileName(
            self,
            translator.translate("dialog.group_export_title"),
            str(self.settings.user_directory / "exported_groups.json"),
            "JSON (*.json)",
        )
        if not path:
            return
        try:
            self.group_storage.export_to_file(Path(path))
        except Exception as exc:
            QMessageBox.critical(self, self.windowTitle(), f"{translator.translate('dialog.group_export_failure')}: {exc}")
        else:
            QMessageBox.information(self, self.windowTitle(), translator.translate("dialog.group_export_success"))

    def clear_groups(self) -> None:
        reply = QMessageBox.question(
            self,
            self.windowTitle(),
            translator.translate("dialog.group_clear_confirm"),
        )
        if reply != QMessageBox.Yes:
            return
        self.group_storage.clear()
        self._refresh_group_table()
        self._refresh_group_message_list()
        QMessageBox.information(self, self.windowTitle(), translator.translate("dialog.group_clear_success"))

    def add_group_manual(self) -> None:
        dialog = QDialog(self)
        dialog.setWindowTitle(translator.translate("dialog.group_add_title"))
        form = QFormLayout(dialog)
        name_input = QLineEdit()
        link_input = QLineEdit()
        member_input = QLineEdit()
        visibility_combo = QComboBox()
        visibility_combo.addItems(
            [
                translator.translate("label.group_public"),
                translator.translate("label.group_private"),
            ]
        )
        messaging_combo = QComboBox()
        messaging_combo.addItems(
            [
                translator.translate("label.group_open_chat"),
                translator.translate("label.group_closed_chat"),
            ]
        )
        form.addRow(translator.translate("label.group_name"), name_input)
        form.addRow(translator.translate("label.group_link"), link_input)
        form.addRow(translator.translate("label.members"), member_input)
        form.addRow(translator.translate("label.group_visibility"), visibility_combo)
        form.addRow(translator.translate("label.group_messaging"), messaging_combo)
        buttons = QDialogButtonBox(QDialogButtonBox.Ok | QDialogButtonBox.Cancel)
        form.addRow(buttons)
        buttons.accepted.connect(dialog.accept)
        buttons.rejected.connect(dialog.reject)
        if dialog.exec() != QDialog.Accepted:
            return
        title = name_input.text().strip()
        if not title:
            QMessageBox.warning(self, self.windowTitle(), translator.translate("dialog.group_name_required"))
            return
        members = self._parse_int(member_input.text())
        link = link_input.text().strip() or None
        is_public = visibility_combo.currentIndex() == 0
        restricted = messaging_combo.currentIndex() == 1
        record = StoredGroup(
            group_id=None,
            access_hash=None,
            title=title,
            username=None,
            link=link,
            members=members,
            online=None,
            is_public=is_public,
            is_megagroup=True,
            is_broadcast=False,
            messages_restricted=restricted,
            members_hidden=False,
            source="manual",
        )
        self.group_storage.add_groups([record])
        self._refresh_group_table()
        self._refresh_group_message_list()
        QMessageBox.information(self, self.windowTitle(), translator.translate("dialog.group_add_success"))

    def select_all_groups(self) -> None:
        if not hasattr(self, "group_table"):
            return
        for row in range(self.group_table.rowCount()):
            item = self.group_table.item(row, 0)
            if item:
                item.setCheckState(Qt.Checked)

    def clear_group_selection(self) -> None:
        if not hasattr(self, "group_table"):
            return
        for row in range(self.group_table.rowCount()):
            item = self.group_table.item(row, 0)
            if item:
                item.setCheckState(Qt.Unchecked)

    def _selected_groups(self) -> List[StoredGroup]:
        if not hasattr(self, "group_table"):
            return []
        selected: List[StoredGroup] = []
        for row in range(self.group_table.rowCount()):
            item = self.group_table.item(row, 0)
            if item and item.checkState() == Qt.Checked:
                group = item.data(Qt.UserRole)
                if isinstance(group, StoredGroup):
                    selected.append(group)
        return selected

    def export_selected_groups(self) -> None:
        groups = self._selected_groups()
        if not groups:
            QMessageBox.information(self, self.windowTitle(), translator.translate("dialog.no_groups_selected"))
            return
        path, _ = QFileDialog.getSaveFileName(
            self,
            translator.translate("dialog.group_export_title"),
            str(self.settings.user_directory / "selected_groups.json"),
            "JSON (*.json)",
        )
        if not path:
            return
        file_path = Path(path)
        if file_path.suffix.lower() != ".json":
            file_path = file_path.with_suffix(".json")
        try:
            file_path.parent.mkdir(parents=True, exist_ok=True)
            with file_path.open("w", encoding="utf-8") as fp:
                json.dump([group.to_dict() for group in groups], fp, indent=2, ensure_ascii=False)
        except Exception as exc:
            QMessageBox.critical(self, self.windowTitle(), f"{translator.translate('dialog.group_export_failure')}: {exc}")
        else:
            QMessageBox.information(self, self.windowTitle(), translator.translate("dialog.group_export_success"))

    def delete_selected_groups(self) -> None:
        groups = self._selected_groups()
        if not groups:
            QMessageBox.information(self, self.windowTitle(), translator.translate("dialog.no_groups_selected"))
            return
        confirm = QMessageBox.question(
            self,
            self.windowTitle(),
            translator.translate("dialog.delete_selected_groups_confirm"),
        )
        if confirm != QMessageBox.Yes:
            return
        for group in groups:
            self.group_storage.remove_group(group)
        self._refresh_group_table()
        self._refresh_group_message_list()

    def _selected_group_records(self) -> List[StoredGroup]:
        records: List[StoredGroup] = []
        if not hasattr(self, "group_message_list"):
            return records
        for item in self.group_message_list.selectedItems():
            group = item.data(Qt.UserRole)
            if isinstance(group, StoredGroup):
                records.append(group)
        return records

    def _collect_group_targets(self, limit: Optional[int]) -> List[StoredGroup]:
        targets = list(self._selected_group_records())
        manual_entries = []
        if hasattr(self, "group_message_manual_input"):
            manual_entries = [line.strip() for line in self.group_message_manual_input.toPlainText().splitlines() if line.strip()]
        for entry in manual_entries:
            targets.append(
                StoredGroup(
                    group_id=None,
                    access_hash=None,
                    title=entry,
                    username=None,
                    link=entry,
                    members=None,
                    online=None,
                    is_public=True,
                    is_megagroup=True,
                    is_broadcast=False,
                    messages_restricted=False,
                    members_hidden=False,
                    source="manual",
                )
            )
        if limit and limit > 0:
            return targets[:limit]
        return targets

    def _split_groups_for_sessions(self, groups: List[StoredGroup], count: int) -> List[List[StoredGroup]]:
        if count <= 0:
            return []
        total = len(groups)
        base = total // count
        remainder = total % count
        chunks: List[List[StoredGroup]] = []
        start = 0
        for idx in range(count):
            size = base + (1 if idx < remainder else 0)
            end = start + size
            chunks.append(groups[start:end])
            start = end
        return chunks


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

    def _current_user_table(self) -> Optional[QTableWidget]:
        key = self._current_user_key()
        return self.user_tables.get(key)

    def _selected_user_ids(self) -> List[int]:
        table = self._current_user_table()
        if not table:
            return []
        selected: List[int] = []
        for row in range(table.rowCount()):
            item = table.item(row, 0)
            if item and item.checkState() == Qt.Checked:
                value = item.data(Qt.UserRole)
                if isinstance(value, int):
                    selected.append(value)
        return selected

    def _current_add_storage_key(self) -> str:
        return "scanned" if self.add_source_combo.currentIndex() == 0 else "active"

    @staticmethod
    def _add_result_storage_key(source_key: str) -> str:
        return "scanned_added" if source_key == "scanned" else "active_added"

    def _current_message_storage_key(self) -> str:
        data = self.message_source_combo.currentData()
        if isinstance(data, str):
            return data
        index = self.message_source_combo.currentIndex()
        if 0 <= index < len(self.user_table_meta):
            return self.user_table_meta[index][0]
        return "scanned"

    def _handle_message_session_change(self) -> None:
        self._refresh_message_template_summary()

    def _current_template_session(self) -> Optional[str]:
        if not hasattr(self, "template_session_list"):
            return None
        item = self.template_session_list.currentItem()
        if item:
            return item.text()
        if self.template_session_list.count() > 0:
            return self.template_session_list.item(0).text()
        return None

    def _handle_template_session_change(self) -> None:
        session = self._current_template_session()
        self._refresh_template_combo(session)

    def _handle_template_selection_change(self) -> None:
        name = self._current_template_name()
        if name:
            self._populate_template_fields(name)
        else:
            self._clear_template_fields()

    def _refresh_message_template_summary(self) -> None:
        if not hasattr(self, "message_template_summary"):
            return
        self.message_template_summary.clear()
        if not hasattr(self, "message_session_list"):
            return
        sessions = self.get_selected_sessions(self.message_session_list)
        if not sessions:
            return
        for session in sessions:
            template_name = self.template_storage.get_assignment(session)
            display = template_name or translator.translate("label.template_unassigned")
            self.message_template_summary.addItem(f"{session} → {display}")

    def _refresh_template_combo(self, session: Optional[str]) -> None:
        if not hasattr(self, "template_list_widget"):
            return
        self.template_list_widget.blockSignals(True)
        self.template_list_widget.clear()
        if not session:
            self.template_list_widget.blockSignals(False)
            self._clear_template_fields()
            return
        templates = self.template_storage.list_templates(session)
        assigned = self.template_storage.get_assignment(session)
        if assigned:
            templates.sort(key=lambda tpl: (tpl.name != assigned, tpl.name.lower()))
        else:
            templates.sort(key=lambda tpl: tpl.name.lower())
        for template in templates:
            label = template.name
            if template.name == assigned:
                label = f"{label} ({translator.translate('label.template_default')})"
            item = QListWidgetItem(label)
            item.setData(Qt.UserRole, template.name)
            self.template_list_widget.addItem(item)
        self.template_list_widget.blockSignals(False)
        if self.template_list_widget.count() > 0:
            if assigned:
                self._select_template_in_list(assigned)
            else:
                self.template_list_widget.setCurrentRow(0)
        else:
            self._clear_template_fields()

    def _populate_template_fields(self, template_name: str) -> None:
        session = self._current_template_session()
        if not session:
            return
        template = self.template_storage.get_template(session, template_name)
        if not template:
            self._clear_template_fields()
            return
        self.template_name_input.setText(template.name)
        self.template_body_input.setPlainText(template.body)
        self.template_media_input.setText(template.media or "")

    def _clear_template_fields(self) -> None:
        self.template_name_input.clear()
        self.template_body_input.clear()
        self.template_media_input.clear()

    def _save_message_template(self) -> None:
        session = self._current_template_session()
        if not session:
            QMessageBox.warning(self, self.windowTitle(), translator.translate("dialog.sessions_required"))
            return
        name = self.template_name_input.text().strip()
        body = self.template_body_input.toPlainText().strip()
        media = self.template_media_input.text().strip()
        if not name:
            QMessageBox.warning(self, self.windowTitle(), translator.translate("dialog.template_name_required"))
            return
        if not body and not media:
            QMessageBox.warning(self, self.windowTitle(), translator.translate("dialog.message_missing_body"))
            return
        template = MessageTemplate(name=name, body=body, media=media or None)
        self.template_storage.upsert_template(session, template)
        self.template_storage.save()
        self._refresh_template_combo(session)
        self._select_template_in_list(template.name)
        self._refresh_message_template_summary()
        QMessageBox.information(self, self.windowTitle(), translator.translate("dialog.template_saved"))

    def _delete_message_template(self) -> None:
        session = self._current_template_session()
        if not session:
            QMessageBox.warning(self, self.windowTitle(), translator.translate("dialog.sessions_required"))
            return
        template_name = self._current_template_name()
        if not template_name:
            QMessageBox.warning(self, self.windowTitle(), translator.translate("dialog.template_name_required"))
            return
        self.template_storage.delete_template(session, template_name)
        self.template_storage.save()
        self._refresh_template_combo(session)
        self._refresh_message_template_summary()
        QMessageBox.information(self, self.windowTitle(), translator.translate("dialog.template_deleted"))

    def _assign_template_to_session(self) -> None:
        session = self._current_template_session()
        if not session:
            QMessageBox.warning(self, self.windowTitle(), translator.translate("dialog.sessions_required"))
            return
        template_name = self._current_template_name()
        if not template_name:
            QMessageBox.warning(self, self.windowTitle(), translator.translate("dialog.template_name_required"))
            return
        self.template_storage.set_assignment(session, template_name)
        self.template_storage.save()
        QMessageBox.information(self, self.windowTitle(), translator.translate("dialog.template_assigned"))
        self._refresh_template_combo(session)
        self._refresh_message_template_summary()

    def _refresh_group_message_template_summary(self) -> None:
        if not hasattr(self, "group_message_template_summary"):
            return
        self.group_message_template_summary.clear()
        sessions = self.get_selected_sessions(getattr(self, "group_message_session_list", QListWidget()))
        if not sessions:
            return
        for session in sessions:
            template_name = self.group_template_storage.get_assignment(session)
            display = template_name or translator.translate("label.template_unassigned")
            self.group_message_template_summary.addItem(f"{session} → {display}")

    def _handle_group_template_session_change(self, current: Optional[QListWidgetItem]) -> None:
        session = current.text() if current else None
        self._refresh_group_template_combo(session)

    def _handle_group_template_selection_change(self, current: Optional[QListWidgetItem]) -> None:
        if not current:
            self._clear_group_template_fields()
            return
        session = self._current_group_template_session()
        if not session:
            return
        template = self.group_template_storage.get_template(session, current.data(Qt.UserRole))
        if not template:
            self._clear_group_template_fields()
            return
        self.group_template_name_input.setText(template.name)
        self.group_template_body_input.setPlainText(template.body)
        self.group_template_media_input.setText(template.media or "")

    def _clear_group_template_fields(self) -> None:
        self.group_template_name_input.clear()
        self.group_template_body_input.clear()
        self.group_template_media_input.clear()

    def _save_group_template(self) -> None:
        session = self._current_group_template_session()
        if not session:
            QMessageBox.warning(self, self.windowTitle(), translator.translate("dialog.sessions_required"))
            return
        name = self.group_template_name_input.text().strip()
        body = self.group_template_body_input.toPlainText().strip()
        media = self.group_template_media_input.text().strip()
        if not name:
            QMessageBox.warning(self, self.windowTitle(), translator.translate("dialog.template_name_required"))
            return
        if not body and not media:
            QMessageBox.warning(self, self.windowTitle(), translator.translate("dialog.message_missing_body"))
            return
        template = MessageTemplate(name=name, body=body, media=media or None)
        self.group_template_storage.upsert_template(session, template)
        self.group_template_storage.save()
        self._refresh_group_template_combo(session)
        self._select_group_template_in_list(template.name)
        self._refresh_group_message_template_summary()
        QMessageBox.information(self, self.windowTitle(), translator.translate("dialog.template_saved"))

    def _delete_group_template(self) -> None:
        session = self._current_group_template_session()
        if not session:
            QMessageBox.warning(self, self.windowTitle(), translator.translate("dialog.sessions_required"))
            return
        name = self._current_group_template_name()
        if not name:
            QMessageBox.warning(self, self.windowTitle(), translator.translate("dialog.template_name_required"))
            return
        self.group_template_storage.delete_template(session, name)
        self.group_template_storage.save()
        self._refresh_group_template_combo(session)
        self._refresh_group_message_template_summary()
        QMessageBox.information(self, self.windowTitle(), translator.translate("dialog.template_deleted"))

    def _assign_group_template(self) -> None:
        session = self._current_group_template_session()
        if not session:
            QMessageBox.warning(self, self.windowTitle(), translator.translate("dialog.sessions_required"))
            return
        name = self._current_group_template_name()
        if not name:
            QMessageBox.warning(self, self.windowTitle(), translator.translate("dialog.template_name_required"))
            return
        self.group_template_storage.set_assignment(session, name)
        self.group_template_storage.save()
        QMessageBox.information(self, self.windowTitle(), translator.translate("dialog.template_assigned"))
        self._refresh_group_template_combo(session)
        self._refresh_group_message_template_summary()

    def _current_group_template_session(self) -> Optional[str]:
        item = self.group_template_session_list.currentItem()
        return item.text() if item else None

    def _current_group_template_name(self) -> Optional[str]:
        item = self.group_template_list_widget.currentItem()
        if not item:
            return None
        data = item.data(Qt.UserRole)
        if isinstance(data, str):
            return data
        return item.text().split(" (")[0].strip()

    def _select_group_template_in_list(self, template_name: str) -> None:
        for row in range(self.group_template_list_widget.count()):
            item = self.group_template_list_widget.item(row)
            if item.data(Qt.UserRole) == template_name:
                self.group_template_list_widget.setCurrentRow(row)
                return

    def _refresh_group_template_combo(self, session: Optional[str]) -> None:
        self.group_template_list_widget.clear()
        if not session:
            return
        templates = self.group_template_storage.list_templates(session)
        assignment = self.group_template_storage.get_assignment(session)
        if assignment:
            templates.sort(key=lambda tpl: (tpl.name != assignment, tpl.name.lower()))
        else:
            templates.sort(key=lambda tpl: tpl.name.lower())
        for template in templates:
            label = template.name
            if template.name == assignment:
                label = f"{label} ({translator.translate('label.template_default')})"
            item = QListWidgetItem(label)
            item.setData(Qt.UserRole, template.name)
            self.group_template_list_widget.addItem(item)
        if templates:
            if assignment:
                self._select_group_template_in_list(assignment)
            else:
                self.group_template_list_widget.setCurrentRow(0)
        else:
            self._clear_group_template_fields()

    def _current_template_name(self) -> Optional[str]:
        if not hasattr(self, "template_list_widget"):
            return None
        item = self.template_list_widget.currentItem()
        if item is None:
            return None
        data = item.data(Qt.UserRole)
        if isinstance(data, str) and data:
            return data
        text = item.text().split(" (")[0].strip()
        return text or None

    def _select_template_in_list(self, template_name: str) -> None:
        if not hasattr(self, "template_list_widget"):
            return
        for row in range(self.template_list_widget.count()):
            item = self.template_list_widget.item(row)
            data = item.data(Qt.UserRole)
            if data == template_name:
                self.template_list_widget.setCurrentRow(row)
                return

    def _build_placeholder_toolbar(
        self, target: QTextEdit, placeholders: Optional[List[Tuple[str, str]]] = None
    ) -> QWidget:
        container = QWidget()
        layout = QHBoxLayout(container)
        layout.setContentsMargins(0, 0, 0, 0)
        layout.setSpacing(6)
        tokens = PLACEHOLDER_BUTTONS if placeholders is None else placeholders
        for label_key, token in tokens:
            button = QPushButton(translator.translate(label_key))
            button.setAutoDefault(False)
            button.clicked.connect(lambda _, t=token: self._insert_text_at_cursor(target, t))
            layout.addWidget(button)
        emoji_button = QPushButton(translator.translate("button.emoji_picker"))
        emoji_button.setAutoDefault(False)
        emoji_button.clicked.connect(lambda: self._open_emoji_dialog(target))
        layout.addWidget(emoji_button)
        layout.addStretch()
        return container

    def _insert_text_at_cursor(self, editor: QTextEdit, value: str) -> None:
        cursor = editor.textCursor()
        cursor.insertText(value)
        editor.setTextCursor(cursor)
        editor.setFocus()

    def _open_emoji_dialog(self, target: QTextEdit) -> None:
        dialog = QDialog(self)
        dialog.setWindowTitle(translator.translate("dialog.emoji_select"))
        scroll = QScrollArea(dialog)
        scroll.setWidgetResizable(True)
        container = QWidget()
        grid = QGridLayout(container)
        grid.setContentsMargins(4, 4, 4, 4)
        grid.setSpacing(4)
        for idx, emoji in enumerate(EMOJI_CHOICES):
            button = QPushButton(emoji)
            button.setFixedSize(36, 36)
            button.setAutoDefault(False)
            button.clicked.connect(partial(self._insert_emoji_and_close, dialog, target, emoji))
            grid.addWidget(button, idx // 8, idx % 8)
        scroll.setWidget(container)
        layout = QVBoxLayout(dialog)
        layout.addWidget(scroll)
        dialog.setLayout(layout)
        dialog.resize(360, 280)
        dialog.exec()

    def _insert_emoji_and_close(self, dialog: QDialog, target: QTextEdit, emoji: str) -> None:
        self._insert_text_at_cursor(target, emoji)
        dialog.accept()

    def _browse_media_file(self, target: QLineEdit) -> None:
        path, _ = QFileDialog.getOpenFileName(
            self,
            translator.translate("dialog.media_select"),
            str(Path.home()),
            "All Files (*.*)",
        )
        if path:
            target.setText(path)

    def _resolve_message_payload(
        self,
        session: str,
        manual_body: str,
        manual_media: str,
        storage: Optional[TemplateStorage] = None,
    ) -> Tuple[str, Optional[str]]:
        template_repo = storage or self.template_storage
        template_name = template_repo.get_assignment(session)
        if template_name:
            template = template_repo.get_template(session, template_name)
        else:
            template = None
        if template:
            body = template.body or ""
            media = template.media
        else:
            body = manual_body
            media = manual_media or None
        return body, media

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

    def _split_keywords_for_sessions(self, keywords: List[str], count: int) -> List[List[str]]:
        if count <= 0:
            return []
        total = len(keywords)
        if total == 0:
            return [[] for _ in range(count)]
        base = total // count
        remainder = total % count
        chunks: List[List[str]] = []
        start = 0
        for idx in range(count):
            size = base + (1 if idx < remainder else 0)
            end = start + size
            chunks.append(keywords[start:end])
            start = end
        return chunks

    @staticmethod
    def _parse_keywords(raw: str) -> List[str]:
        tokens = raw.replace("\n", ",").split(",")
        return [token.strip() for token in tokens if token.strip()]

    def _collect_group_filters(self) -> Dict[str, bool]:
        if not hasattr(self, "group_filter_channel_checkbox"):
            return {"channel": True, "supergroup": True, "group": True, "admin": False}
        return {
            "channel": self.group_filter_channel_checkbox.isChecked(),
            "supergroup": self.group_filter_supergroup_checkbox.isChecked(),
            "group": self.group_filter_group_checkbox.isChecked(),
            "admin": self.group_filter_admin_checkbox.isChecked(),
        }

    def _storage_has_message(self, key: str) -> bool:
        for storage_key, has_message, _ in self.user_table_meta:
            if storage_key == key:
                return has_message
        return False

    def _export_users_to_csv(self, users: List[StoredUser], file_path: Path, include_message: bool) -> None:
        headers = [
            ("user_id", translator.translate("table.column.user_id")),
            ("username", translator.translate("table.column.username")),
            ("first_name", translator.translate("table.column.first_name")),
            ("last_name", translator.translate("table.column.last_name")),
            ("phone", translator.translate("table.column.phone")),
            ("last_seen", translator.translate("table.column.last_seen")),
            ("status", translator.translate("table.column.status")),
            ("dm_status", translator.translate("table.column.dm_status")),
            ("dm_score", translator.translate("table.column.dm_score")),
            ("dm_score_label", translator.translate("table.column.dm_score_label")),
            ("source", translator.translate("table.column.source")),
            ("is_bot", translator.translate("table.column.is_bot")),
        ]
        if include_message:
            headers.append(("last_message", translator.translate("table.column.message")))

        file_path.parent.mkdir(parents=True, exist_ok=True)
        with file_path.open("w", encoding="utf-8-sig", newline="") as fp:
            writer = csv.writer(fp)
            writer.writerow([label for _, label in headers])
            for user in users:
                row: List[str] = []
                for field, _ in headers:
                    value = getattr(user, field, "")
                    if value is None:
                        value = ""
                    elif isinstance(value, bool):
                        value = translator.translate("table.value.yes" if value else "table.value.no")
                    elif field == "dm_status" and value:
                        value = translator.translate(value)
                    elif field == "status" and value:
                        value = translator.translate(value)
                    elif field == "dm_score_label" and value:
                        value = translator.translate(value)
                    row.append(str(value))
                writer.writerow(row)

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
            "scanned": "exported_scanned_users",
            "active": "exported_active_users",
            "scanned_added": "exported_added_scanned_users",
            "active_added": "exported_added_active_users",
        }
        default_name = default_names.get(storage_key, "exported_users")
        default_path = self.settings.user_directory / default_name
        path, selected_filter = QFileDialog.getSaveFileName(
            self,
            translator.translate("dialog.export_title"),
            str(default_path),
            "CSV (*.csv);;JSON (*.json)",
        )
        if not path:
            return
        try:
            storage = self._current_user_storage()
            file_path = Path(path)
            suffix = file_path.suffix.lower()
            export_csv = suffix == ".csv" or "CSV" in selected_filter
            if export_csv:
                if suffix != ".csv":
                    file_path = file_path.with_suffix(".csv")
                has_message = self._storage_has_message(storage_key)
                self._export_users_to_csv(storage.get_users(), file_path, has_message)
            else:
                if suffix != ".json":
                    file_path = file_path.with_suffix(".json")
                storage.export_to_file(file_path)
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

    def select_all_users(self) -> None:
        table = self._current_user_table()
        if not table:
            return
        for row in range(table.rowCount()):
            item = table.item(row, 0)
            if item:
                item.setCheckState(Qt.Checked)

    def clear_user_selection(self) -> None:
        table = self._current_user_table()
        if not table:
            return
        for row in range(table.rowCount()):
            item = table.item(row, 0)
            if item:
                item.setCheckState(Qt.Unchecked)

    def export_selected_users(self) -> None:
        selected_ids = self._selected_user_ids()
        if not selected_ids:
            QMessageBox.information(self, self.windowTitle(), translator.translate("dialog.no_users_selected"))
            return
        storage = self._current_user_storage()
        users = storage.get_users_by_ids(selected_ids)
        if not users:
            QMessageBox.information(self, self.windowTitle(), translator.translate("dialog.no_users_selected"))
            return
        path, selected_filter = QFileDialog.getSaveFileName(
            self,
            translator.translate("dialog.export_title"),
            str(self.settings.user_directory / "selected_users.json"),
            "CSV (*.csv);;JSON (*.json)",
        )
        if not path:
            return
        file_path = Path(path)
        suffix = file_path.suffix.lower()
        export_csv = suffix == ".csv" or "CSV" in selected_filter
        has_message = self._storage_has_message(self._current_user_key())
        try:
            if export_csv:
                if suffix != ".csv":
                    file_path = file_path.with_suffix(".csv")
                self._export_users_to_csv(users, file_path, has_message)
            else:
                if suffix != ".json":
                    file_path = file_path.with_suffix(".json")
                file_path.parent.mkdir(parents=True, exist_ok=True)
                with file_path.open("w", encoding="utf-8") as fp:
                    json.dump([user.to_dict() for user in users], fp, indent=2, ensure_ascii=False)
        except Exception as exc:
            QMessageBox.critical(self, self.windowTitle(), f"{translator.translate('dialog.export_failure')}: {exc}")
        else:
            QMessageBox.information(self, self.windowTitle(), translator.translate("dialog.export_success"))

    def refresh_dm_statuses(self) -> None:
        storage = self._current_user_storage()
        if not storage:
            return
        selected_ids = self._selected_user_ids()
        if not selected_ids:
            users = storage.get_users()
            if not users:
                QMessageBox.information(self, self.windowTitle(), translator.translate("dialog.no_users_selected"))
                return
            selected_ids = [user.user_id for user in users]
        sessions = self.session_manager.list_sessions()
        if not sessions:
            QMessageBox.information(self, self.windowTitle(), translator.translate("dialog.no_sessions_selected"))
            return
        session_names = [session.name for session in sessions]
        session_name, ok = QInputDialog.getItem(
            self,
            translator.translate("dialog.select_session"),
            translator.translate("dialog.select_session"),
            session_names,
            0,
            False,
        )
        if not ok or not session_name:
            return
        try:
            asyncio.run(self._refresh_dm_statuses_async(session_name, storage, selected_ids))
        except Exception as exc:  # pragma: no cover - runtime errors
            QMessageBox.critical(
                self,
                self.windowTitle(),
                f"{translator.translate('dialog.dm_refresh_failure')}: {exc}",
            )
            return
        self.populate_user_tables()
        QMessageBox.information(self, self.windowTitle(), translator.translate("dialog.dm_refresh_success"))

    async def _refresh_dm_statuses_async(
        self, session_name: str, storage: UserStorage, user_ids: List[int]
    ) -> None:
        async def runner(client):
            for user_id in user_ids:
                try:
                    entity = await client.get_entity(int(user_id))
                except FloodWaitError as exc:
                    await asyncio.sleep(int(exc.seconds) + 1)
                    continue
                except Exception:
                    continue
                if not isinstance(entity, types.User):
                    continue
                access_hash = getattr(entity, "access_hash", None)
                profile = await build_dm_profile(client, entity, access_hash)
                stored = storage.get_user(int(user_id))
                if not stored:
                    stored = StoredUser(
                        user_id=int(user_id),
                        username=getattr(entity, "username", None),
                        phone=getattr(entity, "phone", None),
                        access_hash=access_hash,
                        first_name=getattr(entity, "first_name", None),
                        last_name=getattr(entity, "last_name", None),
                        last_seen=None,
                        status=type(entity.status).__name__ if getattr(entity, "status", None) else None,
                        source=None,
                        is_bot=bool(getattr(entity, "bot", False)),
                        last_seen_utc=UserStorage.to_iso(
                            entity.status.was_online
                            if isinstance(getattr(entity, "status", None), types.UserStatusOffline)
                            else None
                        ),
                        last_message=None,
                        dm_status=None,
                    )
                stored.dm_status = profile.dm_status
                stored.dm_score = profile.dm_score
                stored.dm_score_label = profile.dm_score_label
                stored.access_hash = access_hash or stored.access_hash
                stored.username = getattr(entity, "username", stored.username)
                stored.phone = getattr(entity, "phone", stored.phone)
                stored.first_name = getattr(entity, "first_name", stored.first_name)
                stored.last_name = getattr(entity, "last_name", stored.last_name)
                stored.is_bot = bool(getattr(entity, "bot", stored.is_bot))
                storage.update_user(stored)

        await self.session_manager.run_with_client(session_name, runner)

    def delete_selected_users(self) -> None:
        selected_ids = self._selected_user_ids()
        if not selected_ids:
            QMessageBox.information(self, self.windowTitle(), translator.translate("dialog.no_users_selected"))
            return
        confirm = QMessageBox.question(
            self,
            self.windowTitle(),
            translator.translate("dialog.delete_selected_users_confirm"),
        )
        if confirm != QMessageBox.Yes:
            return
        storage = self._current_user_storage()
        storage.remove_users(selected_ids)
        self.populate_user_tables()

    def add_user_manual(self) -> None:
        include_message = self._current_user_key() in {
            "active",
            "active_added",
        }
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
        self.settings.rate_limit.group_join_interval = float(self.group_join_interval_input.value())
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
        self._update_header_text()
        tab_keys = [
            "tab.sessions",
            "tab.ban_check",
            "tab.scan",
            "tab.active_senders",
            "tab.user_search",
            "tab.add_members",
            "tab.users_root",
            "tab.templates",
            "tab.direct_messages",
            "tab.group_discovery",
            "tab.group_templates",
            "tab.group_messages",
            "tab.rate_limit",
            "tab.settings",
        ]
        for index, key in enumerate(tab_keys):
            self.tab_widget.setTabText(index, translator.translate(key))
        # Sessions tab controls
        self._update_sessions_tab_labels()
        self._update_user_table_headers()
        self.scan_save_checkbox.setText(translator.translate("checkbox.save_results"))
        self.active_save_checkbox.setText(translator.translate("checkbox.save_results"))
        if hasattr(self, "group_search_save_checkbox"):
            self.group_search_save_checkbox.setText(translator.translate("checkbox.save_groups"))
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
        if hasattr(self, "session_refresh_button"):
            self.session_refresh_button.setText(translator.translate("button.refresh_sessions"))
        if hasattr(self, "session_select_all_button"):
            self.session_select_all_button.setText(translator.translate("button.select_all"))
        if hasattr(self, "session_clear_selection_button"):
            self.session_clear_selection_button.setText(translator.translate("button.clear_selection"))
        if hasattr(self, "session_remove_button"):
            self.session_remove_button.setText(translator.translate("button.remove_sessions"))
        if hasattr(self, "ban_select_all_button"):
            self.ban_select_all_button.setText(translator.translate("button.select_all"))
        if hasattr(self, "ban_clear_selection_button"):
            self.ban_clear_selection_button.setText(translator.translate("button.clear_selection"))
        if hasattr(self, "group_search_start_button"):
            self.group_search_start_button.setText(translator.translate("button.start"))
        if hasattr(self, "group_search_cancel_button"):
            self.group_search_cancel_button.setText(translator.translate("button.cancel"))
        if hasattr(self, "group_filter_channel_checkbox"):
            self.group_filter_channel_checkbox.setText(
                translator.translate("checkbox.group_filter_channel")
            )
        if hasattr(self, "group_filter_supergroup_checkbox"):
            self.group_filter_supergroup_checkbox.setText(
                translator.translate("checkbox.group_filter_supergroup")
            )
        if hasattr(self, "group_filter_group_checkbox"):
            self.group_filter_group_checkbox.setText(
                translator.translate("checkbox.group_filter_group")
            )
        if hasattr(self, "group_filter_admin_checkbox"):
            self.group_filter_admin_checkbox.setText(
                translator.translate("checkbox.group_filter_admin")
            )
        if hasattr(self, "message_start_button"):
            self.message_start_button.setText(translator.translate("button.start"))
        if hasattr(self, "message_media_button"):
            self.message_media_button.setText(translator.translate("button.choose_file"))
        if hasattr(self, "template_media_button"):
            self.template_media_button.setText(translator.translate("button.choose_file"))
        if hasattr(self, "template_save_button"):
            self.template_save_button.setText(translator.translate("button.save_template"))
        if hasattr(self, "template_delete_button"):
            self.template_delete_button.setText(translator.translate("button.delete_template"))
        if hasattr(self, "template_assign_button"):
            self.template_assign_button.setText(translator.translate("button.assign_template"))
        if hasattr(self, "max_active_messages_label"):
            self.max_active_messages_label.setText(translator.translate("label.max_active_messages"))
        if hasattr(self, "flood_checkbox"):
            self.flood_checkbox.setText(translator.translate("label.flood_wait"))
        self.export_users_button.setText(translator.translate("button.export_users"))
        self.import_users_button.setText(translator.translate("button.import_users"))
        self.add_user_button.setText(translator.translate("button.add_user"))
        self.clear_users_button.setText(translator.translate("button.clear_users"))
        self.select_all_users_button.setText(translator.translate("button.select_all"))
        if hasattr(self, "clear_selection_users_button"):
            self.clear_selection_users_button.setText(translator.translate("button.clear_selection"))
        self.export_selected_users_button.setText(translator.translate("button.export_selected"))
        self.delete_selected_users_button.setText(translator.translate("button.delete_selected"))
        if hasattr(self, "refresh_dm_button"):
            self.refresh_dm_button.setText(translator.translate("button.refresh_dm"))
        self.add_source_combo.setItemText(0, translator.translate("option.add_source_scanned"))
        self.add_source_combo.setItemText(1, translator.translate("option.add_source_active"))
        if hasattr(self, "user_tab_widget"):
            for index, (_, __, title_key) in enumerate(self.user_table_meta):
                self.user_tab_widget.setTabText(index, translator.translate(title_key))
        self.group_select_all_button.setText(translator.translate("button.select_all"))
        if hasattr(self, "group_clear_selection_button"):
            self.group_clear_selection_button.setText(translator.translate("button.clear_selection"))
        self.group_export_selected_button.setText(translator.translate("button.export_selected"))
        self.group_delete_selected_button.setText(translator.translate("button.delete_selected"))
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
        if hasattr(self, "session_refresh_button"):
            self.session_refresh_button.setText(translator.translate("button.refresh_sessions"))
        if hasattr(self, "session_select_all_button"):
            self.session_select_all_button.setText(translator.translate("button.select_all"))
        if hasattr(self, "session_clear_selection_button"):
            self.session_clear_selection_button.setText(translator.translate("button.clear_selection"))
        if hasattr(self, "session_remove_button"):
            self.session_remove_button.setText(translator.translate("button.remove_sessions"))

    def _update_user_table_headers(self) -> None:
        base_headers = [
            translator.translate("table.column.select"),
            translator.translate("table.column.user_id"),
            translator.translate("table.column.username"),
            translator.translate("table.column.first_name"),
            translator.translate("table.column.last_name"),
            translator.translate("table.column.phone"),
            translator.translate("table.column.last_seen"),
            translator.translate("table.column.status"),
            translator.translate("table.column.dm_status"),
            translator.translate("table.column.dm_score"),
            translator.translate("table.column.dm_score_label"),
            translator.translate("table.column.source"),
            translator.translate("table.column.is_bot"),
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
        self.dm_status_combo = QComboBox()
        self.dm_status_combo.addItem(translator.translate("option.dm_status_auto"), "")
        self.dm_status_combo.addItem(translator.translate("status.dm_open"), "status.dm_open")
        self.dm_status_combo.addItem(translator.translate("status.dm_closed"), "status.dm_closed")
        self.dm_score_input = QLineEdit()
        self.dm_score_label_combo = QComboBox()
        self.dm_score_label_combo.addItem(translator.translate("option.dm_status_auto"), "")
        self.dm_score_label_combo.addItem(translator.translate("status.dm_score_closed_like"), "status.dm_score_closed_like")
        self.dm_score_label_combo.addItem(translator.translate("status.dm_score_uncertain"), "status.dm_score_uncertain")
        self.dm_score_label_combo.addItem(translator.translate("status.dm_score_medium"), "status.dm_score_medium")
        self.dm_score_label_combo.addItem(translator.translate("status.dm_score_maybe_open"), "status.dm_score_maybe_open")
        self.dm_score_label_combo.addItem(translator.translate("status.dm_score_likely_open"), "status.dm_score_likely_open")

        form_layout.addRow(translator.translate("table.column.user_id"), self.user_id_input)
        form_layout.addRow(translator.translate("table.column.username"), self.username_input)
        form_layout.addRow(translator.translate("table.column.phone"), self.phone_input)
        form_layout.addRow(translator.translate("label.access_hash"), self.access_hash_input)
        form_layout.addRow(translator.translate("label.first_name"), self.first_name_input)
        form_layout.addRow(translator.translate("label.last_name"), self.last_name_input)
        form_layout.addRow(translator.translate("table.column.last_seen"), self.last_seen_input)
        form_layout.addRow(translator.translate("table.column.status"), self.status_input)
        form_layout.addRow(translator.translate("label.dm_status"), self.dm_status_combo)
        form_layout.addRow(translator.translate("label.dm_score"), self.dm_score_input)
        form_layout.addRow(translator.translate("label.dm_score_label"), self.dm_score_label_combo)
        form_layout.addRow(translator.translate("table.column.source"), self.source_input)
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
        dm_status = self.dm_status_combo.currentData() or None
        dm_score_text = self.dm_score_input.text().strip()
        dm_score = int(dm_score_text) if dm_score_text else None
        dm_score_label = self.dm_score_label_combo.currentData() or None
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
            dm_status=dm_status,
            dm_score=dm_score,
            dm_score_label=dm_score_label,
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
