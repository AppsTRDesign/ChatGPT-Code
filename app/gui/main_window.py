from __future__ import annotations

import asyncio
from datetime import timedelta
from functools import partial
from pathlib import Path
from typing import Dict, List, Optional

from PySide6.QtCore import Qt
from PySide6.QtWidgets import (
    QApplication,
    QCheckBox,
    QComboBox,
    QFormLayout,
    QGridLayout,
    QGroupBox,
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
        self.session_manager = SessionManager(self.settings)
        self.user_storage = UserStorage(self.settings.user_directory / "users.json")
        self.orchestrator = TaskOrchestrator(self.settings, self.user_storage)
        configure_logging()

        self.pending_login: Optional[PendingLogin] = None
        self.worker_threads: Dict[str, SessionWorkerThread] = {}

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
        self.populate_user_table()

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
        form_layout.addRow(translator.translate("label.target_group"), self.scan_target_input)
        form_layout.addRow(translator.translate("label.limit"), self.scan_limit_input)
        form_layout.addRow(translator.translate("label.interval"), self.scan_interval_combo)
        form_layout.addRow(translator.translate("label.interval"), self.scan_interval_value)
        layout.addLayout(form_layout, 0, 1, 1, 2)

        self.scan_start_button = QPushButton(translator.translate("button.start"))
        self.scan_stop_button = QPushButton(translator.translate("button.stop"))
        self.scan_start_button.clicked.connect(partial(self.start_task, task_type="scan"))
        self.scan_stop_button.clicked.connect(self.stop_all_tasks)
        layout.addWidget(self.scan_start_button, 1, 1)
        layout.addWidget(self.scan_stop_button, 1, 2)

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
        form_layout.addRow(translator.translate("label.target_group"), self.add_target_input)
        layout.addLayout(form_layout, 0, 1, 1, 2)

        self.add_start_button = QPushButton(translator.translate("button.start"))
        self.add_stop_button = QPushButton(translator.translate("button.stop"))
        self.add_start_button.clicked.connect(partial(self.start_task, task_type="add"))
        self.add_stop_button.clicked.connect(self.stop_all_tasks)
        layout.addWidget(self.add_start_button, 1, 1)
        layout.addWidget(self.add_stop_button, 1, 2)

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
        form_layout.addRow(translator.translate("label.target_group"), self.active_target_input)
        form_layout.addRow(translator.translate("label.limit"), self.active_limit_input)
        form_layout.addRow(translator.translate("label.interval"), self.active_interval_combo)
        form_layout.addRow(translator.translate("label.interval"), self.active_interval_value)
        layout.addLayout(form_layout, 0, 1, 1, 2)

        self.active_start_button = QPushButton(translator.translate("button.start"))
        self.active_stop_button = QPushButton(translator.translate("button.stop"))
        self.active_start_button.clicked.connect(partial(self.start_task, task_type="active"))
        self.active_stop_button.clicked.connect(self.stop_all_tasks)
        layout.addWidget(self.active_start_button, 1, 1)
        layout.addWidget(self.active_stop_button, 1, 2)

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
        self.flood_checkbox = QCheckBox(translator.translate("label.flood_wait"))
        self.flood_checkbox.setChecked(self.settings.rate_limit.flood_wait_handling)

        form_layout.addRow(translator.translate("label.rate_join"), self.join_interval_input)
        form_layout.addRow(translator.translate("label.rate_message"), self.message_interval_input)
        form_layout.addRow(translator.translate("label.rate_scan"), self.scan_interval_input)
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
        tab.setLayout(layout)
        self.tab_widget.addTab(tab, translator.translate("tab.settings"))

    def _build_user_tab(self) -> None:
        tab = QWidget()
        layout = QVBoxLayout()
        self.user_table = QTableWidget(0, 5)
        headers = [
            "ID",
            translator.translate("table.column.username"),
            translator.translate("table.column.phone"),
            translator.translate("table.column.last_seen"),
            translator.translate("table.column.status"),
        ]
        self.user_table.setHorizontalHeaderLabels(headers)
        self.user_table.setSelectionBehavior(QTableWidget.SelectRows)
        self.user_table.setEditTriggers(QTableWidget.NoEditTriggers)
        layout.addWidget(QLabel(translator.translate("label.user_table")))
        layout.addWidget(self.user_table)
        tab.setLayout(layout)
        self.tab_widget.addTab(tab, translator.translate("label.user_table"))

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
        if task_type == "scan":
            sessions = self.get_selected_sessions(self.scan_session_list)
            target = self.scan_target_input.text().strip()
            limit = self._parse_int(self.scan_limit_input.text())
            interval = self._build_interval(self.scan_interval_combo.currentIndex(), self.scan_interval_value.value())
            container = self.scan_progress_container
        elif task_type == "add":
            sessions = self.get_selected_sessions(self.add_session_list)
            target = self.add_target_input.text().strip()
            limit = None
            interval = None
            container = self.add_progress_container
        else:
            sessions = self.get_selected_sessions(self.active_session_list)
            target = self.active_target_input.text().strip()
            limit = self._parse_int(self.active_limit_input.text())
            interval = self._build_interval(self.active_interval_combo.currentIndex(), self.active_interval_value.value())
            container = self.active_progress_container

        if not sessions or not target:
            QMessageBox.warning(self, self.windowTitle(), "Oturum ve hedef girilmeli")
            return

        self.stop_all_tasks()
        self._clear_progress(container)

        limit_per_session = None
        if limit:
            limit_per_session = max(limit // len(sessions), 1)

        users = None
        if task_type == "add":
            users = self.user_storage.get_users()
            if not users:
                QMessageBox.warning(self, self.windowTitle(), "Kayıtlı kullanıcı yok")
                return
            chunk_size = max(len(users) // len(sessions), 1)
        else:
            chunk_size = 0

        for idx, session_name in enumerate(sessions):
            widget = SessionProgressWidget(session_name)
            container.addWidget(widget)
            if task_type == "add" and users is not None:
                start_index = idx * chunk_size
                end_index = (idx + 1) * chunk_size if idx < len(sessions) - 1 else len(users)
                subset = users[start_index:end_index]
            else:
                subset = None

            request = TaskRequest(
                task_type=task_type,
                session_name=session_name,
                entity=target,
                limit=limit_per_session if limit_per_session else limit,
                interval=interval,
                users=subset,
            )
            thread = SessionWorkerThread(self.session_manager, self.orchestrator, request)
            thread.progress.connect(partial(self.on_progress, widget))
            thread.finished.connect(partial(self.on_finished, widget))
            thread.status.connect(partial(self.on_status_update, widget))
            thread.error.connect(partial(self.on_error, widget))
            self.worker_threads[session_name] = thread
            widget.update_state(0, request.limit or 0, status_key="status.running")
            thread.start()

    def _clear_progress(self, container: QVBoxLayout) -> None:
        while container.count():
            item = container.takeAt(0)
            widget = item.widget()
            if widget:
                widget.deleteLater()

    def on_progress(self, widget: SessionProgressWidget, update: ProgressUpdate) -> None:
        widget.update_state(update.processed, update.total, update.status)
        if update.user:
            widget.append_user(self._format_user(update.user))
            self.populate_user_table()

    def on_finished(self, widget: SessionProgressWidget, session_name: str) -> None:
        total = widget.state.total or widget.state.processed
        widget.update_state(widget.state.processed, total, status_key="label.completed")

    def on_status_update(self, widget: SessionProgressWidget, session_name: str, status_key: str) -> None:
        total = widget.state.total or widget.state.processed
        widget.update_state(widget.state.processed, total, status_key=status_key)

    def on_error(self, widget: SessionProgressWidget, session_name: str, message: str) -> None:
        total = widget.state.total or widget.state.processed
        widget.update_state(widget.state.processed, total, status_key="status.error")
        widget.append_user(message)

    def stop_all_tasks(self) -> None:
        for thread in self.worker_threads.values():
            thread.stop()
        self.worker_threads.clear()
        self.orchestrator.cancel_all()

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
        return f"{user.user_id} | {user.username or ''} | {user.first_name or ''} {user.last_name or ''}".strip()

    def populate_user_table(self) -> None:
        users = self.user_storage.get_users()
        self.user_table.setRowCount(len(users))
        for row, user in enumerate(users):
            self.user_table.setItem(row, 0, QTableWidgetItem(str(user.user_id)))
            self.user_table.setItem(row, 1, QTableWidgetItem(user.username or ""))
            self.user_table.setItem(row, 2, QTableWidgetItem(user.phone or ""))
            self.user_table.setItem(row, 3, QTableWidgetItem(user.last_seen or ""))
            self.user_table.setItem(row, 4, QTableWidgetItem(user.status or ""))

    def save_rate_limits(self) -> None:
        self.settings.rate_limit.join_interval = float(self.join_interval_input.value())
        self.settings.rate_limit.message_interval = float(self.message_interval_input.value())
        self.settings.rate_limit.scan_interval = float(self.scan_interval_input.value())
        self.settings.rate_limit.flood_wait_handling = self.flood_checkbox.isChecked()
        self.settings_repo.save(self.settings)
        QMessageBox.information(self, self.windowTitle(), translator.translate("dialog.success"))

    def change_language(self, language: str) -> None:
        translator.set_language(language)
        self.settings.language = language
        self.settings_repo.save(self.settings)
        self.retranslate_ui()

    def change_timezone(self, timezone: str) -> None:
        self.settings.timezone = timezone
        self.settings_repo.save(self.settings)

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
            "label.user_table",
        ]
        for index, key in enumerate(tab_keys):
            self.tab_widget.setTabText(index, translator.translate(key))
        # Sessions tab controls
        self._update_sessions_tab_labels()
        self._update_user_table_headers()
        for container in [self.scan_progress_container, self.add_progress_container, self.active_progress_container]:
            for i in range(container.count()):
                widget = container.itemAt(i).widget()
                if isinstance(widget, SessionProgressWidget):
                    widget.retranslate()
        self.populate_user_table()

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
        headers = [
            "ID",
            translator.translate("table.column.username"),
            translator.translate("table.column.phone"),
            translator.translate("table.column.last_seen"),
            translator.translate("table.column.status"),
        ]
        for index, title in enumerate(headers):
            self.user_table.setHorizontalHeaderItem(index, QTableWidgetItem(title))


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
