import json
import threading
import time
from dataclasses import dataclass
from typing import List, Optional

import requests
from PyQt5 import QtCore, QtGui, QtSvg, QtWidgets


@dataclass
class SurfPlanStep:
    title: str
    detail: str
    seconds: int


class ApiClient:
    def __init__(self, base_url: str):
        self.base_url = base_url.rstrip('/')

    def _headers(self, token: Optional[str]) -> dict:
        headers = {'Content-Type': 'application/json'}
        if token:
            headers['Authorization'] = f'Bearer {token}'
        return headers

    def register(self, email: str, password: str, name: str):
        resp = requests.post(f'{self.base_url}/auth/register', json={
            'email': email,
            'password': password,
            'name': name,
        })
        resp.raise_for_status()
        return resp.json()

    def login(self, email: str, password: str):
        resp = requests.post(f'{self.base_url}/auth/login', json={'email': email, 'password': password})
        resp.raise_for_status()
        return resp.json()

    def profile(self, token: str):
        resp = requests.get(f'{self.base_url}/profile', headers=self._headers(token))
        resp.raise_for_status()
        return resp.json()['user']

    def create_site(self, token: str, payload: dict):
        resp = requests.post(f'{self.base_url}/sites', headers=self._headers(token), json=payload)
        resp.raise_for_status()
        return resp.json()

    def list_sites(self, token: str):
        resp = requests.get(f'{self.base_url}/sites', headers=self._headers(token))
        resp.raise_for_status()
        return resp.json()['items']

    def start_surf(self, token: str):
        resp = requests.post(f'{self.base_url}/surf/start', headers=self._headers(token))
        resp.raise_for_status()
        return resp.json()

    def complete_surf(self, token: str, session_id: int):
        resp = requests.post(f'{self.base_url}/surf/complete', headers=self._headers(token), json={'session_id': session_id})
        resp.raise_for_status()
        return resp.json()

    def dashboard(self, token: str):
        resp = requests.get(f'{self.base_url}/dashboard', headers=self._headers(token))
        resp.raise_for_status()
        return resp.json()


class SurfWorker(QtCore.QObject):
    progress = QtCore.pyqtSignal(int, str)
    finished = QtCore.pyqtSignal(int)
    failed = QtCore.pyqtSignal(str)

    def __init__(self, token: str, client: ApiClient):
        super().__init__()
        self.token = token
        self.client = client
        self._running = True

    def stop(self):
        self._running = False

    def run(self):
        try:
            session = self.client.start_surf(self.token)
        except Exception as exc:
            self.failed.emit(str(exc))
            return

        session_id = session['session_id']
        plan: List[SurfPlanStep] = [SurfPlanStep(**step) for step in session.get('plan', [])]
        elapsed = 0
        for step in plan:
            for second in range(step.seconds):
                if not self._running:
                    self.failed.emit('Surf iptal edildi')
                    return
                elapsed += 1
                self.progress.emit(elapsed, f"{step.title} — {step.detail}")
                time.sleep(1)
        try:
            result = self.client.complete_surf(self.token, session_id)
            earned = result.get('earned', 0)
            self.finished.emit(earned)
        except Exception as exc:
            self.failed.emit(str(exc))


class SurfApp(QtWidgets.QMainWindow):
    def __init__(self):
        super().__init__()
        self.setWindowTitle('NoaSoft AutoSurf Kontrol Paneli')
        self.resize(1100, 720)
        self.client: Optional[ApiClient] = None
        self.token: Optional[str] = None
        self.worker_thread: Optional[QtCore.QThread] = None
        self.worker: Optional[SurfWorker] = None
        self._build_ui()

    def _build_ui(self):
        container = QtWidgets.QWidget()
        layout = QtWidgets.QVBoxLayout(container)
        layout.setContentsMargins(12, 12, 12, 12)
        layout.setSpacing(10)

        header = self._build_header()
        layout.addWidget(header)

        self.tabs = QtWidgets.QTabWidget()
        self.tabs.addTab(self._build_api_tab(), 'API & Giriş')
        self.tabs.addTab(self._build_sites_tab(), 'Siteler')
        self.tabs.addTab(self._build_surf_tab(), 'Surf + Puan')
        layout.addWidget(self.tabs)

        self.status_label = QtWidgets.QLabel('Hazır')
        self.status_label.setStyleSheet('font-weight: bold; color: #0a2342;')
        layout.addWidget(self.status_label)

        self.setCentralWidget(container)

    def _build_header(self):
        frame = QtWidgets.QFrame()
        frame.setStyleSheet('background:#0f172a; border-radius:10px;')
        layout = QtWidgets.QHBoxLayout(frame)
        layout.setContentsMargins(14, 10, 14, 10)
        logo = QtSvg.QSvgWidget(str(QtCore.QFileInfo('client/assets/noasoft.svg').absoluteFilePath()))
        logo.setFixedSize(220, 64)
        layout.addWidget(logo)

        title = QtWidgets.QVBoxLayout()
        title_lbl = QtWidgets.QLabel('AutoSurf Yönetim ve Simülasyon')
        title_lbl.setStyleSheet('color: white; font-size:20px; font-weight: bold;')
        subtitle_lbl = QtWidgets.QLabel('API + GUI tek panelde • mobil/insansı gezinme senaryoları')
        subtitle_lbl.setStyleSheet('color: #d9e3f0;')
        title.addWidget(title_lbl)
        title.addWidget(subtitle_lbl)
        layout.addLayout(title)
        layout.addStretch()

        self.points_card = QtWidgets.QLabel('Puan: 0')
        self.points_card.setAlignment(QtCore.Qt.AlignCenter)
        self.points_card.setStyleSheet('background:#111827; color:#7cf29c; padding:12px 18px; border-radius:12px; font-size:18px; font-weight: bold;')
        layout.addWidget(self.points_card)
        return frame

    def _build_api_tab(self):
        widget = QtWidgets.QWidget()
        layout = QtWidgets.QFormLayout(widget)
        layout.setLabelAlignment(QtCore.Qt.AlignRight)

        self.base_url_input = QtWidgets.QLineEdit('https://autosurf.noasoft.org')
        self.email_input = QtWidgets.QLineEdit()
        self.password_input = QtWidgets.QLineEdit()
        self.password_input.setEchoMode(QtWidgets.QLineEdit.Password)
        self.name_input = QtWidgets.QLineEdit()

        layout.addRow('API URL', self.base_url_input)
        layout.addRow('Email', self.email_input)
        layout.addRow('Şifre', self.password_input)
        layout.addRow('İsim', self.name_input)

        button_row = QtWidgets.QHBoxLayout()
        register_btn = QtWidgets.QPushButton('Kayıt Ol')
        register_btn.clicked.connect(self.register)
        login_btn = QtWidgets.QPushButton('Giriş Yap')
        login_btn.clicked.connect(self.login)
        dashboard_btn = QtWidgets.QPushButton('Puanları Getir')
        dashboard_btn.clicked.connect(self.refresh_dashboard)
        for btn in (register_btn, login_btn, dashboard_btn):
            btn.setStyleSheet('padding:8px 14px; font-weight:bold;')
            button_row.addWidget(btn)
        layout.addRow(button_row)

        return widget

    def _build_sites_tab(self):
        widget = QtWidgets.QWidget()
        outer = QtWidgets.QVBoxLayout(widget)

        form = QtWidgets.QGridLayout()
        self.site_name = QtWidgets.QLineEdit()
        self.site_url = QtWidgets.QLineEdit()
        self.dwell_spin = QtWidgets.QSpinBox()
        self.dwell_spin.setRange(5, 600)
        self.dwell_spin.setValue(30)

        self.mobile_cb = QtWidgets.QCheckBox('Mobil UA')
        self.realistic_cb = QtWidgets.QCheckBox('Gerçekçi gezinme')
        self.mouse_cb = QtWidgets.QCheckBox('Mouse hareketleri')
        self.click_cb = QtWidgets.QCheckBox('Link tıklama')
        self.scroll_cb = QtWidgets.QCheckBox('Scroll')
        self.form_cb = QtWidgets.QCheckBox('Form doldurma')
        self.media_cb = QtWidgets.QCheckBox('Medya otomasyonu')

        form.addWidget(QtWidgets.QLabel('Site Adı'), 0, 0)
        form.addWidget(self.site_name, 0, 1)
        form.addWidget(QtWidgets.QLabel('URL'), 1, 0)
        form.addWidget(self.site_url, 1, 1)
        form.addWidget(QtWidgets.QLabel('Süre (sn)'), 2, 0)
        form.addWidget(self.dwell_spin, 2, 1)

        flag_layout = QtWidgets.QGridLayout()
        for i, cb in enumerate([
            self.mobile_cb, self.realistic_cb, self.mouse_cb, self.click_cb, self.scroll_cb, self.form_cb, self.media_cb
        ]):
            flag_layout.addWidget(cb, i // 2, i % 2)

        form.addLayout(flag_layout, 3, 0, 1, 2)

        add_btn = QtWidgets.QPushButton('Siteyi Kaydet')
        add_btn.clicked.connect(self.add_site)
        add_btn.setStyleSheet('padding:10px 16px; font-weight:bold; background:#2563eb; color:white; border-radius:8px;')
        form.addWidget(add_btn, 4, 0, 1, 2)

        outer.addLayout(form)

        self.site_table = QtWidgets.QTableWidget(0, 5)
        self.site_table.setHorizontalHeaderLabels(['ID', 'Site Adı', 'URL', 'Süre', 'Ayarlar'])
        self.site_table.horizontalHeader().setStretchLastSection(True)
        self.site_table.setSelectionBehavior(QtWidgets.QAbstractItemView.SelectRows)
        outer.addWidget(self.site_table)

        refresh = QtWidgets.QPushButton('Siteleri Yenile')
        refresh.clicked.connect(self.load_sites)
        outer.addWidget(refresh, alignment=QtCore.Qt.AlignRight)

        return widget

    def _build_surf_tab(self):
        widget = QtWidgets.QWidget()
        layout = QtWidgets.QVBoxLayout(widget)

        self.plan_list = QtWidgets.QListWidget()
        self.plan_list.setStyleSheet('background:#f8fafc;')
        layout.addWidget(self.plan_list)

        self.progress = QtWidgets.QProgressBar()
        self.progress.setRange(0, 100)
        self.progress.setValue(0)
        self.progress.setTextVisible(True)
        layout.addWidget(self.progress)

        btn_row = QtWidgets.QHBoxLayout()
        self.start_btn = QtWidgets.QPushButton('Surf Başlat')
        self.start_btn.clicked.connect(self.start_surf)
        self.cancel_btn = QtWidgets.QPushButton('İptal')
        self.cancel_btn.clicked.connect(self.cancel_surf)
        for btn in (self.start_btn, self.cancel_btn):
            btn.setStyleSheet('padding:10px 16px; font-weight:bold;')
            btn_row.addWidget(btn)
        layout.addLayout(btn_row)

        self.earnings_label = QtWidgets.QLabel('Kazanılan puanlar burada görüntülenecek')
        self.earnings_label.setStyleSheet('font-weight:bold; color:#0f172a;')
        layout.addWidget(self.earnings_label)

        return widget

    def register(self):
        base_url = self.base_url_input.text().strip()
        if not base_url:
            self._toast('API URL boş olamaz')
            return
        self.client = ApiClient(base_url)
        try:
            resp = self.client.register(self.email_input.text(), self.password_input.text(), self.name_input.text())
            self.token = resp['token']
            self._toast('Kayıt başarılı, token alındı')
            self.refresh_dashboard()
        except Exception as exc:
            self._toast(f'Hata: {exc}', error=True)

    def login(self):
        base_url = self.base_url_input.text().strip()
        if not base_url:
            self._toast('API URL boş olamaz')
            return
        self.client = ApiClient(base_url)
        try:
            resp = self.client.login(self.email_input.text(), self.password_input.text())
            self.token = resp['token']
            self._toast('Giriş başarılı')
            self.refresh_dashboard()
        except Exception as exc:
            self._toast(f'Hata: {exc}', error=True)

    def refresh_dashboard(self):
        if not self.client or not self.token:
            self._toast('Önce giriş yapın')
            return
        try:
            dash = self.client.dashboard(self.token)
            self.points_card.setText(f"Puan: {dash['points']}")
            self.status_label.setText(f"Günlük: {dash['daily']} • Haftalık: {dash['weekly']} • Kalan Süre: {dash['remaining_seconds']} sn")
        except Exception as exc:
            self._toast(f'Dashboard hatası: {exc}', error=True)

    def add_site(self):
        if not self.client or not self.token:
            self._toast('Önce giriş yapın')
            return
        payload = {
            'name': self.site_name.text(),
            'url': self.site_url.text(),
            'dwell_seconds': self.dwell_spin.value(),
            'mobile': self.mobile_cb.isChecked(),
            'realistic': self.realistic_cb.isChecked(),
            'mouse_moves': self.mouse_cb.isChecked(),
            'link_clicks': self.click_cb.isChecked(),
            'scroll': self.scroll_cb.isChecked(),
            'form_fill': self.form_cb.isChecked(),
            'media': self.media_cb.isChecked(),
        }
        try:
            self.client.create_site(self.token, payload)
            self._toast('Site eklendi ve puan düşüldü')
            self.load_sites()
            self.refresh_dashboard()
        except Exception as exc:
            self._toast(f'Kayıt hatası: {exc}', error=True)

    def load_sites(self):
        if not self.client or not self.token:
            self._toast('Önce giriş yapın')
            return
        try:
            sites = self.client.list_sites(self.token)
        except Exception as exc:
            self._toast(f'Site liste hatası: {exc}', error=True)
            return
        self.site_table.setRowCount(len(sites))
        for row, site in enumerate(sites):
            self.site_table.setItem(row, 0, QtWidgets.QTableWidgetItem(str(site['id'])))
            self.site_table.setItem(row, 1, QtWidgets.QTableWidgetItem(site['name']))
            self.site_table.setItem(row, 2, QtWidgets.QTableWidgetItem(site['url']))
            self.site_table.setItem(row, 3, QtWidgets.QTableWidgetItem(str(site['dwell_seconds'])))
            settings = []
            for flag in ['mobile', 'realistic', 'mouse_moves', 'link_clicks', 'scroll', 'form_fill', 'media']:
                if site.get(flag):
                    settings.append(flag)
            self.site_table.setItem(row, 4, QtWidgets.QTableWidgetItem(', '.join(settings)))

    def start_surf(self):
        if not self.client or not self.token:
            self._toast('Önce giriş yapın')
            return
        if self.worker_thread and self.worker_thread.isRunning():
            self._toast('Zaten çalışıyor')
            return
        self.plan_list.clear()
        self.progress.setValue(0)
        self.worker_thread = QtCore.QThread()
        self.worker = SurfWorker(self.token, self.client)
        self.worker.moveToThread(self.worker_thread)
        self.worker_thread.started.connect(self.worker.run)
        self.worker.progress.connect(self._on_progress)
        self.worker.finished.connect(self._on_finished)
        self.worker.failed.connect(self._on_failed)
        self.worker.finished.connect(self.worker_thread.quit)
        self.worker.failed.connect(self.worker_thread.quit)
        self.worker_thread.start()

    def cancel_surf(self):
        if self.worker:
            self.worker.stop()
        self._toast('Surf iptal komutu gönderildi')

    def _on_progress(self, elapsed: int, detail: str):
        self.plan_list.addItem(detail)
        total = max(1, self.progress.maximum())
        value = min(100, int((elapsed / total) * 100)) if total == 100 else min(100, elapsed)
        self.progress.setValue(value)

    def _on_finished(self, earned: int):
        self._toast(f'Oturum tamamlandı +{earned} puan')
        self.earnings_label.setText(f'Kazanılan: {earned} puan')
        self.refresh_dashboard()

    def _on_failed(self, message: str):
        self._toast(f'Hata: {message}', error=True)

    def _toast(self, message: str, error: bool = False):
        color = '#ef4444' if error else '#10b981'
        self.status_label.setStyleSheet(f'font-weight:bold; color:{color};')
        self.status_label.setText(message)


def main():
    import sys
    app = QtWidgets.QApplication(sys.argv)
    window = SurfApp()
    window.show()
    sys.exit(app.exec_())


if __name__ == '__main__':
    main()
