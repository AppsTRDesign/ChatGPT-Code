import random
import time
from dataclasses import dataclass
from typing import Dict, List, Optional, Tuple

import requests
from PyQt6 import QtCore, QtGui, QtWidgets
from PyQt6.QtCharts import QChart, QChartView, QLineSeries, QValueAxis
from PyQt6.QtSvgWidgets import QSvgWidget
from playwright.sync_api import Playwright, sync_playwright


@dataclass
class SurfPlanStep:
    title: str
    detail: str
    seconds: int


class ApiClient:
    def __init__(self, base_url: str):
        self.base_url = base_url.rstrip('/')
        self.session = requests.Session()

    def _headers(self, token: Optional[str]) -> Dict[str, str]:
        headers = {'Content-Type': 'application/json'}
        if token:
            headers['Authorization'] = f'Bearer {token}'
        return headers

    def register(self, email: str, password: str, name: str):
        resp = self.session.post(f'{self.base_url}/auth/register', json={
            'email': email,
            'password': password,
            'name': name,
        })
        resp.raise_for_status()
        return resp.json()

    def login(self, email: str, password: str):
        resp = self.session.post(f'{self.base_url}/auth/login', json={'email': email, 'password': password})
        resp.raise_for_status()
        return resp.json()

    def forgot_password(self, email: str):
        resp = self.session.post(f'{self.base_url}/auth/forgot', json={'email': email})
        resp.raise_for_status()
        return resp.json()

    def profile(self, token: str):
        resp = self.session.get(f'{self.base_url}/profile', headers=self._headers(token))
        resp.raise_for_status()
        return resp.json().get('user')

    def update_profile(self, token: str, payload: dict):
        resp = self.session.patch(f'{self.base_url}/profile', headers=self._headers(token), json=payload)
        resp.raise_for_status()
        return resp.json()

    def list_sites(self, token: str, page: int = 1):
        resp = self.session.get(f'{self.base_url}/sites', params={'page': page}, headers=self._headers(token))
        resp.raise_for_status()
        return resp.json()

    def create_site(self, token: str, payload: dict):
        resp = self.session.post(f'{self.base_url}/sites', headers=self._headers(token), json=payload)
        resp.raise_for_status()
        return resp.json()

    def update_site(self, token: str, site_id: int, payload: dict):
        resp = self.session.patch(f'{self.base_url}/sites/{site_id}', headers=self._headers(token), json=payload)
        resp.raise_for_status()
        return resp.json()

    def delete_site(self, token: str, site_id: int):
        resp = self.session.delete(f'{self.base_url}/sites/{site_id}', headers=self._headers(token))
        resp.raise_for_status()
        return resp.json()

    def start_surf(self, token: str):
        resp = self.session.post(f'{self.base_url}/surf/start', headers=self._headers(token))
        resp.raise_for_status()
        return resp.json()

    def complete_surf(self, token: str, session_id: int, consumed_seconds: int):
        resp = self.session.post(
            f'{self.base_url}/surf/complete',
            headers=self._headers(token),
            json={'session_id': session_id, 'consumed_seconds': consumed_seconds},
        )
        resp.raise_for_status()
        return resp.json()

    def dashboard(self, token: str):
        resp = self.session.get(f'{self.base_url}/dashboard', headers=self._headers(token))
        resp.raise_for_status()
        return resp.json()

    def points_history(self, token: str):
        resp = self.session.get(f'{self.base_url}/dashboard/history', headers=self._headers(token))
        resp.raise_for_status()
        return resp.json().get('history', [])

    def mail_settings(self, token: str):
        resp = self.session.get(f'{self.base_url}/mail/settings', headers=self._headers(token))
        resp.raise_for_status()
        return resp.json()

    def send_mail(self, token: str, payload: dict):
        resp = self.session.post(f'{self.base_url}/mail/send', headers=self._headers(token), json=payload)
        resp.raise_for_status()
        return resp.json()


class SurfWorker(QtCore.QObject):
    progress = QtCore.pyqtSignal(int, int, int, str)
    step_changed = QtCore.pyqtSignal(str)
    finished = QtCore.pyqtSignal(int)
    failed = QtCore.pyqtSignal(str)

    def __init__(self, token: str, client: ApiClient, parent: Optional[QtCore.QObject] = None):
        super().__init__(parent)
        self.token = token
        self.client = client
        self._running = True

    def stop(self):
        self._running = False

    def _build_plan(self, session_payload: dict) -> Tuple[List[SurfPlanStep], Dict]:
        plan_data = session_payload.get('plan') or []
        site = session_payload.get('site', {})
        if plan_data:
            return [SurfPlanStep(**step) for step in plan_data], site

        dwell = int(site.get('dwell_seconds', 30))
        flags = {
            'mobile': site.get('mobile'),
            'realistic': site.get('realistic'),
            'mouse_moves': site.get('mouse_moves'),
            'link_clicks': site.get('link_clicks'),
            'scroll': site.get('scroll'),
            'form_fill': site.get('form_fill'),
            'media': site.get('media'),
        }
        steps: List[SurfPlanStep] = [SurfPlanStep('Sayfa açılıyor', 'URL yükleniyor', min(5, dwell))]
        remaining = max(5, dwell - 5)
        if flags['mouse_moves']:
            steps.append(SurfPlanStep('Mouse hareketleri', 'Rastgele bölgeler üzerinde dolaşma', min(remaining, 8)))
            remaining -= min(remaining, 8)
        if flags['scroll']:
            steps.append(SurfPlanStep('Scroll', 'Aşağı-yukarı kaydırma', min(remaining, 6)))
            remaining -= min(remaining, 6)
        if flags['link_clicks']:
            steps.append(SurfPlanStep('Tıklamalar', 'İç linklere doğal tıklamalar', min(remaining, 8)))
            remaining -= min(remaining, 8)
        if flags['form_fill']:
            steps.append(SurfPlanStep('Form doldurma', 'Input odak ve sahte yazım', min(remaining, 6)))
            remaining -= min(remaining, 6)
        if flags['media']:
            steps.append(SurfPlanStep('Medya kontrolü', 'Video/Audio oynatma, ses ve kalite', min(remaining, 12)))
            remaining -= min(remaining, 12)
        if remaining > 0:
            steps.append(SurfPlanStep('Sayfada kalma', 'Okuma ve bekleme', remaining))
        return steps, site

    def _apply_actions(self, playwright: Playwright, site: dict, plan: List[SurfPlanStep]) -> int:
        consumed = 0
        browser = playwright.chromium.launch(headless=True)
        context_kwargs: Dict[str, object] = {}
        if site.get('mobile'):
            context_kwargs['user_agent'] = (
                'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/605.1.15 '
                '(KHTML, like Gecko) Version/15.0 Mobile/15E148 Safari/604.1'
            )
            context_kwargs['viewport'] = {'width': 390, 'height': 844}
        context = browser.new_context(**context_kwargs)
        page = context.new_page()
        page.goto(site.get('url'), wait_until='domcontentloaded', timeout=20000)

        for step in plan:
            if not self._running:
                break
            if 'mouse' in step.title.lower():
                page.mouse.move(100, 100)
                page.mouse.move(400, 400)
            if 'scroll' in step.title.lower():
                page.mouse.wheel(0, 1500)
                page.wait_for_timeout(800)
                page.mouse.wheel(0, -800)
            if 'tıklamalar' in step.title.lower():
                links = page.query_selector_all('a')
                if links:
                    random.choice(links).click(timeout=5000)
            if 'form' in step.title.lower():
                inputs = page.query_selector_all('input,textarea')
                if inputs:
                    target = random.choice(inputs)
                    target.click()
                    target.type('NoaSoft deneme girdisi', delay=50)
            if 'medya' in step.title.lower():
                video = page.query_selector('video, audio')
                if video:
                    video.hover()
                    page.wait_for_timeout(1500)
                    video.click()
                    page.keyboard.press('Space')
                    page.keyboard.press('KeyF')
            for _ in range(step.seconds):
                if not self._running:
                    break
                consumed += 1
                time.sleep(1)
        context.close()
        browser.close()
        return consumed

    def run(self):
        try:
            session = self.client.start_surf(self.token)
        except Exception as exc:
            self.failed.emit(str(exc))
            return

        plan, site = self._build_plan(session)
        session_id = session.get('session_id') or session.get('id')
        consumed_seconds = 0

        try:
            with sync_playwright() as playwright:
                consumed_seconds = self._apply_actions(playwright, site, plan)
        except Exception as exc:  # noqa: BLE001
            self.failed.emit(f'Surf sırasında hata: {exc}')
            return

        elapsed = 0
        total_seconds = max(1, sum(s.seconds for s in plan))
        for step in plan:
            if not self._running:
                self.failed.emit('Surf iptal edildi')
                return
            self.step_changed.emit(f"{step.title} — {step.detail}")
            for _ in range(step.seconds):
                if not self._running:
                    self.failed.emit('Surf iptal edildi')
                    return
                elapsed += 1
                percent = int((elapsed / total_seconds) * 100)
                self.progress.emit(percent, elapsed, total_seconds, f"{step.title} — {step.detail}")
                time.sleep(1)

        try:
            result = self.client.complete_surf(self.token, int(session_id), consumed_seconds)
            earned = int(result.get('earned', 0))
            self.finished.emit(earned)
        except Exception as exc:
            self.failed.emit(str(exc))


class SurfApp(QtWidgets.QMainWindow):
    def __init__(self):
        super().__init__()
        self.setWindowTitle('NoaSoft AutoSurf Kontrol Paneli')
        self.resize(1280, 820)
        self.client: Optional[ApiClient] = None
        self.token: Optional[str] = None
        self.worker_thread: Optional[QtCore.QThread] = None
        self.worker: Optional[SurfWorker] = None
        self.current_page = 1
        self.total_pages = 1
        self._build_ui()

    def _build_ui(self):
        root = QtWidgets.QWidget()
        root_layout = QtWidgets.QVBoxLayout(root)
        root_layout.setContentsMargins(12, 12, 12, 12)
        root_layout.setSpacing(10)

        header = self._build_header()
        root_layout.addWidget(header)

        self.stack = QtWidgets.QStackedWidget()
        self.stack.addWidget(self._build_auth_panel())
        self.stack.addWidget(self._build_app_panel())
        root_layout.addWidget(self.stack)

        self.status_label = QtWidgets.QLabel('Hazır')
        self.status_label.setStyleSheet('font-weight: bold; color: #0a2342;')
        root_layout.addWidget(self.status_label)

        self.setCentralWidget(root)

    def _build_header(self) -> QtWidgets.QWidget:
        frame = QtWidgets.QFrame()
        frame.setStyleSheet('background:#0f172a; border-radius:12px;')
        layout = QtWidgets.QHBoxLayout(frame)
        layout.setContentsMargins(14, 12, 14, 12)

        logo = QSvgWidget('client/assets/noasoft.svg')
        logo.setFixedSize(220, 64)
        layout.addWidget(logo)

        title_layout = QtWidgets.QVBoxLayout()
        title_lbl = QtWidgets.QLabel('AutoSurf Yönetim ve Simülasyon')
        title_lbl.setStyleSheet('color: white; font-size:20px; font-weight: bold;')
        subtitle_lbl = QtWidgets.QLabel('API + GUI tek panelde • mobil/insansı gezinme senaryoları')
        subtitle_lbl.setStyleSheet('color: #d9e3f0;')
        title_layout.addWidget(title_lbl)
        title_layout.addWidget(subtitle_lbl)
        layout.addLayout(title_layout)

        layout.addStretch()

        self.points_card = QtWidgets.QLabel('Puan: 0')
        self.points_card.setAlignment(QtCore.Qt.AlignmentFlag.AlignCenter)
        self.points_card.setStyleSheet(
            'background:#111827; color:#7cf29c; padding:12px 18px; border-radius:12px; '
            'font-size:18px; font-weight: bold; min-width:180px;'
        )
        layout.addWidget(self.points_card)

        self.logout_btn = QtWidgets.QPushButton('Çıkış Yap')
        self.logout_btn.clicked.connect(self.logout)
        self.logout_btn.setVisible(False)
        self.logout_btn.setStyleSheet('background:#ef4444; color:white; padding:10px 14px; border-radius:10px; font-weight:bold;')
        layout.addWidget(self.logout_btn)
        return frame

    def _build_auth_panel(self) -> QtWidgets.QWidget:
        panel = QtWidgets.QWidget()
        layout = QtWidgets.QVBoxLayout(panel)
        layout.setContentsMargins(10, 10, 10, 10)
        self.base_url_input = QtWidgets.QLineEdit('https://autosurf.noasoft.org')
        self.base_url_input.setPlaceholderText('API URL')
        layout.addWidget(self.base_url_input)

        tabs = QtWidgets.QTabWidget()
        tabs.addTab(self._build_register_tab(), 'Kayıt Ol')
        tabs.addTab(self._build_login_tab(), 'Giriş Yap')
        tabs.addTab(self._build_reset_tab(), 'Şifremi Sıfırla')
        layout.addWidget(tabs)
        return panel

    def _build_register_tab(self) -> QtWidgets.QWidget:
        widget = QtWidgets.QWidget()
        form = QtWidgets.QFormLayout(widget)
        self.reg_email = QtWidgets.QLineEdit()
        self.reg_password = QtWidgets.QLineEdit()
        self.reg_password.setEchoMode(QtWidgets.QLineEdit.EchoMode.Password)
        self.reg_name = QtWidgets.QLineEdit()
        register_btn = QtWidgets.QPushButton('Kayıt Ol')
        register_btn.clicked.connect(self.register)
        form.addRow('Email', self.reg_email)
        form.addRow('Şifre', self.reg_password)
        form.addRow('İsim', self.reg_name)
        form.addRow(register_btn)
        return widget

    def _build_login_tab(self) -> QtWidgets.QWidget:
        widget = QtWidgets.QWidget()
        form = QtWidgets.QFormLayout(widget)
        self.login_email = QtWidgets.QLineEdit()
        self.login_password = QtWidgets.QLineEdit()
        self.login_password.setEchoMode(QtWidgets.QLineEdit.EchoMode.Password)
        login_btn = QtWidgets.QPushButton('Giriş Yap')
        login_btn.clicked.connect(self.login)
        form.addRow('Email', self.login_email)
        form.addRow('Şifre', self.login_password)
        form.addRow(login_btn)
        return widget

    def _build_reset_tab(self) -> QtWidgets.QWidget:
        widget = QtWidgets.QWidget()
        form = QtWidgets.QFormLayout(widget)
        self.reset_email = QtWidgets.QLineEdit()
        reset_btn = QtWidgets.QPushButton('Sıfırlama Maili Gönder')
        reset_btn.clicked.connect(self.reset_password)
        form.addRow('Email', self.reset_email)
        form.addRow(reset_btn)
        return widget

    def _build_app_panel(self) -> QtWidgets.QWidget:
        widget = QtWidgets.QWidget()
        layout = QtWidgets.QVBoxLayout(widget)
        layout.setContentsMargins(0, 0, 0, 0)

        self.tabs = QtWidgets.QTabWidget()
        self.tabs.addTab(self._build_dashboard_tab(), 'Puan & Özet')
        self.tabs.addTab(self._build_sites_tab(), 'Siteler')
        self.tabs.addTab(self._build_surf_tab(), 'Surf + Puan')
        self.tabs.addTab(self._build_mail_tab(), 'İletişim')
        layout.addWidget(self.tabs)
        return widget

    def _build_dashboard_tab(self) -> QtWidgets.QWidget:
        widget = QtWidgets.QWidget()
        layout = QtWidgets.QVBoxLayout(widget)
        layout.setSpacing(10)

        self.warning_bar = QtWidgets.QLabel()
        self.warning_bar.setVisible(False)
        self.warning_bar.setStyleSheet('background:#f43f5e; color:white; padding:8px 12px; border-radius:8px; font-weight:bold;')
        layout.addWidget(self.warning_bar)

        self.summary_label = QtWidgets.QLabel('Günlük: 0 • Haftalık: 0 • Kalan Süre: 0 sn')
        self.summary_label.setStyleSheet('font-weight:bold; color:#0f172a;')
        layout.addWidget(self.summary_label)

        progress_row = QtWidgets.QHBoxLayout()
        self.remaining_bar = QtWidgets.QProgressBar()
        self.remaining_bar.setRange(0, 100)
        self.remaining_bar.setValue(0)
        progress_row.addWidget(self.remaining_bar)

        self.timer_label = QtWidgets.QLabel('Animasyonlu Sayaç: 0 sn')
        self.timer_label.setAlignment(QtCore.Qt.AlignmentFlag.AlignCenter)
        self.timer_label.setStyleSheet('font-size:16px; font-weight:bold;')
        progress_row.addWidget(self.timer_label)
        layout.addLayout(progress_row)

        chart_row = QtWidgets.QHBoxLayout()
        self.daily_chart = self._build_chart('Günlük Kazanç')
        self.weekly_chart = self._build_chart('Haftalık Kazanç')
        chart_row.addWidget(self.daily_chart)
        chart_row.addWidget(self.weekly_chart)
        layout.addLayout(chart_row)

        return widget

    def _build_chart(self, title: str) -> QChartView:
        series = QLineSeries()
        chart = QChart()
        chart.addSeries(series)
        chart.createDefaultAxes()
        chart.setTitle(title)
        chart.legend().hide()
        view = QChartView(chart)
        view.setRenderHint(QtGui.QPainter.RenderHint.Antialiasing)
        return view

    def _build_sites_tab(self) -> QtWidgets.QWidget:
        widget = QtWidgets.QWidget()
        outer = QtWidgets.QVBoxLayout(widget)

        form = QtWidgets.QGridLayout()
        self.site_name = QtWidgets.QLineEdit()
        self.site_url = QtWidgets.QLineEdit()
        self.dwell_spin = QtWidgets.QSpinBox()
        self.dwell_spin.setRange(5, 900)
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
        add_btn.clicked.connect(self.add_or_update_site)
        add_btn.setStyleSheet('padding:10px 16px; font-weight:bold; background:#2563eb; color:white; border-radius:8px;')
        form.addWidget(add_btn, 4, 0, 1, 2)

        outer.addLayout(form)

        self.preview_label = QtWidgets.QLabel('Davranış Önizleme: Henüz seçilmedi')
        self.preview_label.setStyleSheet('font-weight:bold; color:#111827;')
        outer.addWidget(self.preview_label)

        self.site_table = QtWidgets.QTableWidget(0, 6)
        self.site_table.setHorizontalHeaderLabels(['ID', 'Site Adı', 'URL', 'Süre', 'Ayarlar', 'İşlem'])
        self.site_table.horizontalHeader().setStretchLastSection(True)
        self.site_table.setSelectionBehavior(QtWidgets.QAbstractItemView.SelectionBehavior.SelectRows)
        self.site_table.itemSelectionChanged.connect(self._on_site_selected)
        outer.addWidget(self.site_table)

        pager = QtWidgets.QHBoxLayout()
        self.prev_btn = QtWidgets.QPushButton('Önceki')
        self.prev_btn.clicked.connect(self.prev_page)
        self.next_btn = QtWidgets.QPushButton('Sonraki')
        self.next_btn.clicked.connect(self.next_page)
        self.page_label = QtWidgets.QLabel('Sayfa 1/1')
        pager.addWidget(self.prev_btn)
        pager.addWidget(self.next_btn)
        pager.addWidget(self.page_label)
        pager.addStretch()
        outer.addLayout(pager)

        return widget

    def _build_surf_tab(self) -> QtWidgets.QWidget:
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

        self.countdown_label = QtWidgets.QLabel('Kalan süre: 0 sn / 0 sn')
        layout.addWidget(self.countdown_label)

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

    def _build_mail_tab(self) -> QtWidgets.QWidget:
        widget = QtWidgets.QWidget()
        form = QtWidgets.QFormLayout(widget)
        self.mail_subject = QtWidgets.QLineEdit()
        self.mail_message = QtWidgets.QPlainTextEdit()
        self.mail_to = QtWidgets.QLineEdit('info@noasoft.org')
        self.mail_to.setReadOnly(True)
        send_btn = QtWidgets.QPushButton('Gönder')
        send_btn.clicked.connect(self.send_mail)
        form.addRow('Alıcı', self.mail_to)
        form.addRow('Konu', self.mail_subject)
        form.addRow('Mesaj', self.mail_message)
        form.addRow(send_btn)
        return widget

    def register(self):
        self._init_client()
        try:
            resp = self.client.register(self.reg_email.text(), self.reg_password.text(), self.reg_name.text())
            self.token = resp.get('token')
            self._toast('Kayıt başarılı, token alındı')
            self._after_login()
        except Exception as exc:
            self._toast(f'Hata: {exc}', error=True)

    def login(self):
        self._init_client()
        try:
            resp = self.client.login(self.login_email.text(), self.login_password.text())
            self.token = resp.get('token')
            self._toast('Giriş başarılı')
            self._after_login()
        except Exception as exc:
            self._toast(f'Hata: {exc}', error=True)

    def reset_password(self):
        self._init_client()
        try:
            self.client.forgot_password(self.reset_email.text())
            self._toast('Sıfırlama maili gönderildi')
        except Exception as exc:
            self._toast(f'Hata: {exc}', error=True)

    def logout(self):
        self.token = None
        self.logout_btn.setVisible(False)
        self.stack.setCurrentIndex(0)
        self.points_card.setText('Puan: 0')
        self._toast('Çıkış yapıldı')

    def _init_client(self):
        base_url = self.base_url_input.text().strip()
        if not base_url:
            raise ValueError('API URL boş olamaz')
        self.client = ApiClient(base_url)

    def _after_login(self):
        self.logout_btn.setVisible(True)
        self.stack.setCurrentIndex(1)
        self.refresh_dashboard()
        self.load_sites()
        self.load_mail_settings()

    def refresh_dashboard(self):
        if not self.client or not self.token:
            self._toast('Önce giriş yapın')
            return
        try:
            dash = self.client.dashboard(self.token)
        except Exception as exc:
            self._toast(f'Dashboard hatası: {exc}', error=True)
            return
        points = int(dash.get('points', 0))
        self.points_card.setText(f"Puan: {points}")
        daily = dash.get('daily', 0)
        weekly = dash.get('weekly', 0)
        remaining = dash.get('remaining_seconds', 0)
        total = dash.get('total_seconds', max(1, remaining))
        percent = min(100, int((remaining / max(1, total)) * 100))
        self.summary_label.setText(f"Günlük: {daily} • Haftalık: {weekly} • Kalan Süre: {remaining} sn")
        self.remaining_bar.setValue(percent)
        self.timer_label.setText(f'Animasyonlu Sayaç: {remaining} sn')
        self.warning_bar.setVisible(points <= 0)
        if points <= 0:
            self.warning_bar.setText('Puan yetersiz! Lütfen puan ekleyin veya siteleri düzenleyin.')
        self._update_charts()

    def _update_charts(self):
        if not self.client or not self.token:
            return
        try:
            history = self.client.points_history(self.token)
        except Exception:
            history = []
        if not history:
            history = [{'label': 'Gün 1', 'daily': 0, 'weekly': 0}]
        self._fill_chart(self.daily_chart.chart(), [(item['label'], item.get('daily', 0)) for item in history])
        self._fill_chart(self.weekly_chart.chart(), [(item['label'], item.get('weekly', 0)) for item in history])

    def _fill_chart(self, chart: QChart, items: List[Tuple[str, int]]):
        chart.removeAllSeries()
        series = QLineSeries()
        for idx, (_, value) in enumerate(items):
            series.append(idx, value)
        chart.addSeries(series)
        axis_x = QValueAxis()
        axis_x.setRange(0, max(1, len(items) - 1))
        axis_y = QValueAxis()
        axis_y.setRange(0, max(10, max(v for _, v in items)))
        chart.setAxisX(axis_x, series)
        chart.setAxisY(axis_y, series)
        chart.legend().hide()

    def add_or_update_site(self):
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
            self._toast('Site eklendi/güncellendi ve puan düşüldü')
            self.load_sites()
            self.refresh_dashboard()
        except Exception as exc:
            self._toast(f'Kayıt hatası: {exc}', error=True)

    def load_sites(self):
        if not self.client or not self.token:
            self._toast('Önce giriş yapın')
            return
        try:
            data = self.client.list_sites(self.token, page=self.current_page)
        except Exception as exc:
            self._toast(f'Site liste hatası: {exc}', error=True)
            return
        sites = data.get('items', data if isinstance(data, list) else [])
        self.total_pages = max(1, int(data.get('pages', 1))) if isinstance(data, dict) else 1
        self.page_label.setText(f'Sayfa {self.current_page}/{self.total_pages}')
        self.site_table.setRowCount(len(sites))
        for row, site in enumerate(sites):
            self.site_table.setItem(row, 0, QtWidgets.QTableWidgetItem(str(site.get('id'))))
            self.site_table.setItem(row, 1, QtWidgets.QTableWidgetItem(site.get('name', '')))
            self.site_table.setItem(row, 2, QtWidgets.QTableWidgetItem(site.get('url', '')))
            self.site_table.setItem(row, 3, QtWidgets.QTableWidgetItem(str(site.get('dwell_seconds', ''))))
            settings = []
            for flag, label in [
                ('mobile', 'Mobil'), ('realistic', 'Realistik'), ('mouse_moves', 'Mouse'),
                ('link_clicks', 'Link'), ('scroll', 'Scroll'), ('form_fill', 'Form'), ('media', 'Medya')
            ]:
                if site.get(flag):
                    settings.append(label)
            self.site_table.setItem(row, 4, QtWidgets.QTableWidgetItem(', '.join(settings)))
            delete_btn = QtWidgets.QPushButton('Sil')
            delete_btn.clicked.connect(lambda _, s_id=site.get('id'): self.delete_site(s_id))
            self.site_table.setCellWidget(row, 5, delete_btn)

    def prev_page(self):
        if self.current_page > 1:
            self.current_page -= 1
            self.load_sites()

    def next_page(self):
        if self.current_page < self.total_pages:
            self.current_page += 1
            self.load_sites()

    def delete_site(self, site_id: int):
        if not site_id:
            return
        if not self.client or not self.token:
            self._toast('Önce giriş yapın')
            return
        try:
            self.client.delete_site(self.token, site_id)
            self._toast('Site silindi')
            self.load_sites()
            self.refresh_dashboard()
        except Exception as exc:
            self._toast(f'Silme hatası: {exc}', error=True)

    def _on_site_selected(self):
        items = self.site_table.selectedItems()
        if not items:
            return
        row = items[0].row()
        dwell = self.site_table.item(row, 3).text()
        settings = self.site_table.item(row, 4).text()
        self.preview_label.setText(f'Davranış Önizleme: {dwell} sn • {settings}')

    def start_surf(self):
        if not self.client or not self.token:
            self._toast('Önce giriş yapın')
            return
        if self.worker_thread and self.worker_thread.isRunning():
            self._toast('Zaten çalışıyor')
            return
        self.plan_list.clear()
        self.progress.setValue(0)
        self.countdown_label.setText('Kalan süre: 0 sn / 0 sn')
        self.worker_thread = QtCore.QThread()
        self.worker = SurfWorker(self.token, self.client)
        self.worker.moveToThread(self.worker_thread)
        self.worker_thread.started.connect(self.worker.run)
        self.worker.progress.connect(self._on_progress)
        self.worker.step_changed.connect(self._on_step)
        self.worker.finished.connect(self._on_finished)
        self.worker.failed.connect(self._on_failed)
        self.worker.finished.connect(self.worker_thread.quit)
        self.worker.failed.connect(self.worker_thread.quit)
        self.worker_thread.start()

    def cancel_surf(self):
        if self.worker:
            self.worker.stop()
        self._toast('Surf iptal komutu gönderildi')

    def _on_progress(self, percent: int, elapsed: int, total: int, detail: str):
        self.plan_list.addItem(detail)
        self.progress.setValue(percent)
        self.countdown_label.setText(f'Kalan süre: {elapsed} sn / {total} sn')

    def _on_step(self, detail: str):
        self.plan_list.addItem(detail)

    def _on_finished(self, earned: int):
        self._toast(f'Oturum tamamlandı +{earned} puan')
        self.earnings_label.setText(f'Kazanılan: {earned} puan')
        self.refresh_dashboard()

    def _on_failed(self, message: str):
        self._toast(f'Hata: {message}', error=True)

    def load_mail_settings(self):
        if not self.client or not self.token:
            return
        try:
            config = self.client.mail_settings(self.token)
            to_addr = config.get('to') or config.get('email') or 'info@noasoft.org'
            self.mail_to.setText(to_addr)
        except Exception:
            self.mail_to.setText('info@noasoft.org')

    def send_mail(self):
        if not self.client or not self.token:
            self._toast('Önce giriş yapın')
            return
        payload = {
            'to': self.mail_to.text(),
            'subject': self.mail_subject.text(),
            'message': self.mail_message.toPlainText(),
        }
        try:
            self.client.send_mail(self.token, payload)
            self._toast('Mesaj iletildi')
        except Exception as exc:
            self._toast(f'Gönderim hatası: {exc}', error=True)

    def _toast(self, message: str, error: bool = False):
        color = '#ef4444' if error else '#10b981'
        self.status_label.setStyleSheet(f'font-weight:bold; color:{color};')
        self.status_label.setText(message)


def main():
    import sys
    app = QtWidgets.QApplication(sys.argv)
    window = SurfApp()
    window.show()
    sys.exit(app.exec())


if __name__ == '__main__':
    main()
