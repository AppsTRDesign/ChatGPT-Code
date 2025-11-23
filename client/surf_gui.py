import os
import sys
import platform
import random
import string
import time
import threading
import uuid
import hashlib
from datetime import datetime
from pathlib import Path
from typing import Any, Dict, List, Optional, Tuple

from urllib.parse import urlparse

import requests
from PyQt6 import QtCore, QtGui, QtWidgets
from PyQt6.QtCharts import (
    QBarCategoryAxis,
    QBarSeries,
    QBarSet,
    QChart,
    QChartView,
    QLineSeries,
    QValueAxis,
)
from PyQt6.QtSvgWidgets import QSvgWidget
from playwright.sync_api import Playwright

CURRENT_DIR = Path(__file__).resolve().parent
if str(CURRENT_DIR) not in sys.path:
    sys.path.insert(0, str(CURRENT_DIR))

from surf_worker import (
    ActionSimulator,
    BrowserManager,
    GeoService,
    GoogleHandler,
    PlanEngine,
    PersonaEngine,
    load_persona_profiles,
    SurfPlanStep,
    TelemetryBuilder,
    YouTubeHandler,
    get_sync_backend,
)

try:
    from reportlab.lib import colors
    from reportlab.lib.pagesizes import A4, landscape
    from reportlab.lib.styles import ParagraphStyle
    from reportlab.lib.units import mm
    from reportlab.pdfbase import pdfmetrics
    from reportlab.pdfbase.ttfonts import TTFont
    from reportlab.platypus import Paragraph, SimpleDocTemplate, Spacer, Table, TableStyle
except Exception:  # noqa: BLE001
    colors = ParagraphStyle = SimpleDocTemplate = None
class ApiClient:
    def __init__(self, base_url: str):
        self.base_url = base_url.rstrip('/')
        self.session = requests.Session()

    def _headers(self, token: Optional[str]) -> Dict[str, str]:
        headers = {'Content-Type': 'application/json'}
        if token:
            headers['Authorization'] = f'Bearer {token}'
        return headers

    def _request(self, method: str, path: str, token: Optional[str] = None, **kwargs):
        url = f'{self.base_url}{path}'
        resp = self.session.request(method, url, headers=self._headers(token), **kwargs)
        if resp.status_code >= 400:
            try:
                payload = resp.json()
                msg = payload.get('error') or payload
            except Exception:
                msg = resp.text
            raise requests.HTTPError(f'{resp.status_code} {msg}')
        return resp

    @staticmethod
    def _json(resp: requests.Response):
        try:
            return resp.json()
        except Exception as exc:
            raise requests.HTTPError(f'Geçersiz JSON yanıtı: {resp.text[:200]}') from exc

    def register(self, email: str, password: str, name: str, password_confirm: str, device_id: str, allow_multi: int = 0, max_multi: int = 5):
        resp = self._request('POST', '/auth/register', json={
            'email': email,
            'password': password,
            'password_confirm': password_confirm,
            'name': name,
            'device_id': device_id,
            'allow_multi_account': allow_multi,
            'max_multi_accounts': max_multi,
        })
        return self._json(resp)

    def login(self, email: str, password: str, device_id: Optional[str] = None):
        payload = {'email': email, 'password': password}
        if device_id:
            payload['device_id'] = device_id
        resp = self._request('POST', '/auth/login', json=payload)
        return self._json(resp)

    def forgot_password(self, email: str):
        resp = self._request('POST', '/auth/forgot', json={'email': email})
        return self._json(resp)

    def profile(self, token: str):
        resp = self._request('GET', '/profile', token=token)
        return self._json(resp).get('user')

    def update_profile(self, token: str, payload: dict):
        resp = self._request('PATCH', '/profile', token=token, json=payload)
        return self._json(resp)

    def list_sites(self, token: str, page: int = 1, sort: Optional[str] = None, direction: str = 'desc'):
        params = {'page': page}
        if sort:
            params['sort'] = sort
            params['dir'] = direction
        resp = self._request('GET', '/sites', token=token, params=params)
        return self._json(resp)

    def create_site(self, token: str, payload: dict):
        resp = self._request('POST', '/sites', token=token, json=payload)
        return self._json(resp)

    def update_site(self, token: str, site_id: int, payload: dict):
        resp = self._request('PATCH', f'/sites/{site_id}', token=token, json=payload)
        return self._json(resp)

    def delete_site(self, token: str, site_id: int):
        resp = self._request('DELETE', f'/sites/{site_id}', token=token)
        return self._json(resp)

    def start_surf(self, token: str):
        resp = self._request('POST', '/surf/start', token=token)
        return self._json(resp)

    def complete_surf(self, token: str, session_id: int, consumed_seconds: int, telemetry: Optional[dict] = None):
        payload = {'session_id': session_id, 'consumed_seconds': consumed_seconds}
        if telemetry:
            payload['telemetry'] = telemetry
        resp = self._request('POST', '/surf/complete', token=token, json=payload)
        return self._json(resp)

    def dashboard(self, token: str):
        resp = self._request('GET', '/dashboard', token=token)
        return self._json(resp)

    def points_history(self, token: str):
        resp = self._request('GET', '/dashboard/history', token=token)
        return self._json(resp).get('history', [])

    def system_stats(self, token: str):
        resp = self._request('GET', '/stats/global', token=token)
        return self._json(resp)

    def mail_settings(self):
        resp = self._request('GET', '/mail/settings')
        return self._json(resp)

    def send_mail(self, token: str, payload: dict):
        resp = self._request('POST', '/mail/send', token=token, json=payload)
        return self._json(resp)

    def site_stats(
        self,
        token: str,
        site_id: int,
        page: int = 1,
        per_page: int = 25,
        sort: Optional[str] = None,
        direction: str = 'desc',
    ):
        params = {'page': page, 'per_page': per_page}
        if sort:
            params['sort'] = sort
            params['dir'] = direction
        resp = self._request('GET', f'/sites/{site_id}/stats', token=token, params=params)
        return self._json(resp)

    def task_config(self, token: str):
        resp = self._request('GET', '/tasks/config', token=token)
        return self._json(resp)


class SurfWorker(QtCore.QObject):
    progress = QtCore.pyqtSignal(int, int, int, str)
    step_changed = QtCore.pyqtSignal(str)
    finished = QtCore.pyqtSignal(int)
    failed = QtCore.pyqtSignal(str)
    log = QtCore.pyqtSignal(str)
    frame = QtCore.pyqtSignal(bytes, int, int)

    def __init__(
        self,
        token: str,
        client: ApiClient,
        parent: Optional[QtCore.QObject] = None,
        mode: str = 'surf',
        task_config: Optional[dict] = None,
        current_email: Optional[str] = None,
    ):
        super().__init__(parent)
        self.token = token
        self.client = client
        self._running = True
        self.mode = mode
        self.task_config = task_config or {}
        self.current_email = current_email or ''
        # Assets dizini her zaman client/assets altında, çalıştırma konumundan bağımsız
        assets_dir = Path(__file__).resolve().parent / 'assets'
        log_fn = self.log.emit
        ad_html = self.task_config.get('ad_banner_html') or '<a href="https://noasoft.org" target="_blank"><img src="https://placehold.co/1200x90/1A1A1A/FFFFFF?text=Reklam+Alan%C4%B1" style="width:100%;max-width:1200px;"></a>'
        self.browser_mgr = BrowserManager(assets_dir, log_fn, ad_html=ad_html)
        self.youtube_handler = YouTubeHandler(self.browser_mgr, log_fn)
        self.geo_service = GeoService(assets_dir, log_fn)
        self.telemetry_builder = TelemetryBuilder(self.geo_service, log_fn)
        self.plan_engine = PlanEngine()
        self.persona_profiles = load_persona_profiles(assets_dir / 'personas.json')
        self.personality = PersonaEngine.random(self.persona_profiles, seed=self.current_email or None)
        self.action_simulator = ActionSimulator(self.youtube_handler, log_fn, self.personality)
        self.google_handler = GoogleHandler(self.browser_mgr, self.plan_engine, log_fn)
        self.last_site_id: Optional[int] = None
        self._duplicate_skip = 0

    def stop(self):
        self._running = False
        try:
            self.browser_mgr.close()
        except Exception:
            pass

    def _build_plan(self, session_payload: dict) -> Tuple[List[SurfPlanStep], Dict]:
        return self.plan_engine.build_plan(session_payload)

    def _emit_progress_tick(self, started_at: float, planned_total: int, detail: str, elapsed_override: Optional[int] = None) -> None:
        now_elapsed = elapsed_override if elapsed_override is not None else int(time.monotonic() - started_at)
        total_seconds = max(1, planned_total)
        capped = min(now_elapsed, total_seconds)
        percent = min(100, int((capped / total_seconds) * 100))
        self.progress.emit(percent, capped, total_seconds, detail)

    def _progress_pump(self, started_at: float, planned_total: int, detail: str, stop_evt: threading.Event):
        while not stop_evt.wait(0.4):
            self._emit_progress_tick(started_at, planned_total, detail)

    def _apply_actions(
        self,
        playwright: Playwright,
        site: dict,
        plan: List[SurfPlanStep],
        personality: Optional[PersonaEngine] = None,
        initial_elapsed: int = 0,
    ) -> Tuple[int, dict]:
        personality = personality or self.personality
        page = self.browser_mgr.ensure_page(playwright, site)
        url = site.get('url')
        self.log.emit(f'Sayfa açılıyor: {url}')
        page.goto(url, wait_until='domcontentloaded', timeout=30000)

        planned_total = max(1, initial_elapsed + sum(s.seconds for s in plan))
        started_at = time.monotonic() - initial_elapsed
        viewport = page.viewport_size or {'width': 1280, 'height': 720}
        last_mouse: Optional[Tuple[int, int]] = (
            int(viewport.get('width', 1280) / 2),
            int(viewport.get('height', 720) / 2),
        )
        host = urlparse(url).netloc
        metrics = {'clicks': 0, 'scrolls': 0, 'highlights': 0, 'forms': 0, 'media': 0, 'mouse_moves': 0}
        self._emit_progress_tick(started_at, planned_total, 'Başlatılıyor', elapsed_override=initial_elapsed)
        for step in plan:
            if not self._running:
                break
            remaining_time = planned_total - int(time.monotonic() - started_at)
            if remaining_time <= 0:
                break
            detail = f"{step.title} — {step.detail}"
            self.step_changed.emit(detail)
            self.log.emit(detail)
            performed = False
            stop_evt = threading.Event()
            pump = threading.Thread(
                target=self._progress_pump,
                args=(started_at, planned_total, detail, stop_evt),
                daemon=True,
            )
            pump.start()
            try:
                if 'mouse' in step.title.lower():
                    performed, last_mouse = self.action_simulator.simulate_mouse_moves(page, viewport, personality)
                    if performed:
                        metrics['mouse_moves'] += 1
                if 'metin' in step.title.lower():
                    if self.action_simulator.simulate_text_highlight(page, personality):
                        metrics['highlights'] += 1
                        performed = True
                if 'scroll' in step.title.lower():
                    if self.action_simulator.simulate_scroll(page, viewport, personality):
                        metrics['scrolls'] += 1
                        performed = True
                if 'tık' in step.title.lower() or 'link' in step.title.lower():
                    if self.action_simulator.simulate_clicks(page, host, personality):
                        metrics['clicks'] += 1
                        performed = True
                if 'form' in step.title.lower():
                    if self.action_simulator.simulate_form(page, personality):
                        metrics['forms'] += 1
                        performed = True
                if 'medya' in step.title.lower():
                    actions = site.get('media_actions') or site.get('media_options') or []
                    dwell_hint = int(site.get('dwell_seconds', step.seconds))
                    if self.action_simulator.simulate_media(page, actions, dwell_hint, personality):
                        metrics['media'] += 1
                        performed = True
                if 'sayfada' in step.title.lower():
                    performed = True  # sadece bekleme adımı
            except Exception as action_err:
                self.log.emit(f'Eylem hatası: {action_err}')
            finally:
                stop_evt.set()
                pump.join(timeout=0.2)
            self._emit_frame(page, last_mouse)
            elapsed_now = int(time.monotonic() - started_at)
            self._emit_progress_tick(started_at, planned_total, detail, elapsed_override=elapsed_now)
            self._emit_frame(page, last_mouse)
        elapsed_total = int(time.monotonic() - started_at)
        # Planlanan süreden kısaysa kalan süreyi doğal akışta tamamla
        while self._running and elapsed_total < planned_total:
            remaining = planned_total - elapsed_total
            sleep_for = min(0.5, remaining)
            time.sleep(sleep_for)
            elapsed_total = int(time.monotonic() - started_at)
            self._emit_progress_tick(started_at, planned_total, 'Bekleniyor', elapsed_override=elapsed_total)
        final_total = max(planned_total, 1)
        self.progress.emit(100, final_total, final_total, 'Tamamlandı')
        return final_total, metrics

    def _emit_frame(self, page, last_mouse: Optional[Tuple[int, int]]):
        try:
            if not page or page.is_closed():
                return
            data = page.screenshot(full_page=False)
            x, y = last_mouse or (-1, -1)
            self.frame.emit(data, x, y)
        except Exception as exc:
            self.log.emit(f'Görüntü yakalama hatası: {exc}')

    def _build_telemetry(self, site: dict, metrics: dict) -> dict:
        return self.telemetry_builder.build(self.browser_mgr.page, site, metrics)

    def _build_custom_plan(self, dwell: int, flags: dict) -> List[SurfPlanStep]:
        return self.plan_engine.build_custom_plan(dwell, flags)

    def _perform_google(self, playwright: Playwright, cfg: dict, flags: dict) -> Tuple[int, int, dict, str, bool, int]:
        return self.google_handler.perform_google(
            playwright,
            cfg,
            flags,
            self.personality,
            lambda pw, site, plan, persona, initial_elapsed=0: self._apply_actions(
                pw,
                site,
                plan,
                persona,
                initial_elapsed=initial_elapsed,
            ),
        )

    def _perform_youtube(self, playwright: Playwright, cfg: dict, flags: dict) -> Tuple[int, int, dict, str]:
        dwell = int(cfg.get('dwell', 30))
        keyword = cfg.get('keyword', '')
        link = cfg.get('video_link', '')
        search_first = bool(cfg.get('search_first', True) and keyword)
        page = self.browser_mgr.ensure_page(playwright, flags)
        visited, found, search_elapsed = self.youtube_handler.search_and_open(page, keyword, link, search_first) if (keyword or link) else (1, True, 0)
        if not found:
            metrics = {'found': False, 'search_elapsed': search_elapsed}
            return search_elapsed, visited, metrics, page.url
        plan, site_flags = self._build_custom_plan(dwell, {**flags})
        site_flags['media'] = True
        plan.insert(0, SurfPlanStep('Video açılıyor', 'YouTube oynatma', 2))
        consumed, metrics = self._apply_actions(
            playwright,
            {**site_flags, 'url': page.url, 'dwell_seconds': dwell, 'found': found, 'search_elapsed': search_elapsed},
            plan,
            self.personality,
            initial_elapsed=search_elapsed,
        )
        total = max(consumed, search_elapsed + dwell)
        metrics['found'] = found
        metrics['search_elapsed'] = search_elapsed
        return total, visited, metrics, page.url

    def run(self):
        try:
            self.personality = PersonaEngine.random(self.persona_profiles, seed=self.current_email or None)
            self.action_simulator.set_persona(self.personality)
            self.plan_engine.rng = getattr(self.personality, 'rng', None) or self.plan_engine.rng
            backend = get_sync_backend()
            with backend() as playwright:
                if self.mode == 'google':
                    consumed, visited, metrics, surf_url = self._perform_google(playwright, self.task_config, self.task_config)
                    telemetry = self._build_telemetry({'mobile': self.task_config.get('mobile')}, metrics)
                    telemetry['pages_visited'] = visited
                    if metrics.get('recaptcha_blocked'):
                        self.failed.emit('reCAPTCHA doğrulaması atlandı, puan yazılmadı')
                    else:
                        self.finished.emit(consumed + visited)
                elif self.mode == 'youtube':
                    consumed, visited, metrics, surf_url = self._perform_youtube(playwright, self.task_config, self.task_config)
                    telemetry = self._build_telemetry({'mobile': self.task_config.get('mobile')}, metrics)
                    telemetry['pages_visited'] = visited
                    self.finished.emit(consumed + visited)
                else:
                    while self._running:
                        try:
                            session = self.client.start_surf(self.token)
                        except Exception as exc:
                            message = str(exc)
                            if '404' in message:
                                self.failed.emit('Gezilecek uygun site bulunamadı')
                            else:
                                self.failed.emit(message)
                            break
                        site_meta = session.get('site') or {}
                        site_id = site_meta.get('id')
                        if site_id and site_id == self.last_site_id:
                            # Aynı site arka arkaya gelmesin
                            self.log.emit('Aynı site tekrar geldi, yeni hedef bekleniyor...')
                            time.sleep(0.8)
                            continue
                        if site_id:
                            self.last_site_id = site_id
                        mode = session.get('task_mode') or 'standard'
                        session_id = session.get('session_id') or session.get('id')
                        planned_pages = int(session.get('planned_pages') or 0)
                        if mode == 'google':
                            task_cfg = session.get('task') or {}
                            site = site_meta
                            flags = {k: site.get(k) for k in ['mobile', 'realistic', 'mouse_moves', 'link_clicks', 'scroll', 'form_fill', 'media']}
                            flags['media_actions'] = site.get('media_actions') or []
                            task_cfg.setdefault('site_url', site.get('url', ''))
                            consumed_seconds, visited, metrics, surf_url, found, search_elapsed = self._perform_google(playwright, task_cfg, flags)
                            site = {**site, 'url': surf_url, 'found': found, 'search_elapsed': search_elapsed}
                            planned_pages = visited
                        elif mode == 'youtube':
                            task_cfg = session.get('task') or {}
                            site = session.get('site') or {}
                            flags = {k: site.get(k) for k in ['mobile', 'realistic', 'mouse_moves', 'link_clicks', 'scroll', 'form_fill', 'media']}
                            flags['media_actions'] = site.get('media_actions') or []
                            task_cfg.setdefault('site_url', site.get('url', ''))
                            consumed_seconds, visited, metrics, surf_url = self._perform_youtube(playwright, task_cfg, flags)
                            site = {**site, 'url': surf_url}
                            planned_pages = visited
                        else:
                            plan, site = self._build_plan(session)
                            consumed_seconds, metrics = self._apply_actions(playwright, site, plan)
                            visited = planned_pages
                        if not self._running:
                            break
                        try:
                            telemetry = self._build_telemetry(site, metrics)
                            telemetry['found'] = site.get('found')
                            telemetry['search_elapsed'] = site.get('search_elapsed')
                            if planned_pages:
                                telemetry['pages_visited'] = planned_pages
                            result = self.client.complete_surf(self.token, int(session_id), consumed_seconds, telemetry)
                            earned = int(result.get('earned', 0))
                            if metrics.get('recaptcha_blocked'):
                                self.log.emit('reCAPTCHA atlandı, puan hareketi yapılmadı')
                            else:
                                self.log.emit(f'Oturum tamamlandı: +{earned} puan')
                            self.finished.emit(earned)
                        except Exception as exc:  # noqa: BLE001
                            self.failed.emit(str(exc))
                            break
                self.browser_mgr.close()
        except Exception as exc:  # noqa: BLE001
            message = str(exc)
            if 'Target page, context or browser has been closed' in message:
                message = 'Sayfa veya tarayıcı kapandığı için görev tamamlanamadı'
            if 'Execution context was destroyed' in message:
                message = 'Sayfa yeniden yüklendiği için komut yarım kaldı'
            self.failed.emit(f'Surf sırasında hata: {message}')


class SurfApp(QtWidgets.QMainWindow):
    def __init__(self):
        super().__init__()
        self.setWindowTitle('NoaSoft AutoSurf Kontrol Paneli')
        self.setMinimumSize(820, 640)
        self.worker = None
        self.worker_thread = None
        self.persona_profiles = None
        self.resize(1220, 780)
        self.client: Optional[ApiClient] = None
        self.token: Optional[str] = None
        self.worker_thread: Optional[QtCore.QThread] = None
        self.worker: Optional[SurfWorker] = None
        self.current_page = 1
        self.total_pages = 1
        self.current_email: str = ''
        self.sites_cache: Dict[int, dict] = {}
        self.task_points: Dict[str, int] = {}
        self.points_history_data: Dict[str, list] = {}
        self.system_history_data: Dict[str, list] = {}
        self.reward_limits: Dict[str, Optional[int]] = {}
        assets_dir = Path(__file__).resolve().parent / 'assets'
        self.persona_profiles = load_persona_profiles(assets_dir / 'personas.json')
        self.personality = PersonaEngine.random(self.persona_profiles, seed=None)
        self.device_id = self._device_id()
        self.live_timer = QtCore.QTimer(self)
        self.live_timer.setInterval(10000)
        self.live_timer.timeout.connect(self._refresh_live_data)
        self.google_enabled = True
        self.youtube_enabled = True
        self._build_ui()

    def _validate_url(self, url: str) -> bool:
        parsed = urlparse(url)
        if parsed.scheme not in {'http', 'https'} or not parsed.netloc:
            return False
        try:
            resp = requests.head(url, timeout=5, allow_redirects=True)
            return resp.status_code < 400
        except Exception:
            return False

    def _device_id(self) -> str:
        cache_path = CURRENT_DIR / '.device_id'
        try:
            if cache_path.exists():
                cached = cache_path.read_text(encoding='utf-8').strip()
                if cached:
                    return cached
        except Exception:
            pass
        raw = f"{platform.node()}-{uuid.getnode()}-{sys.platform}"
        digest = hashlib.sha256(raw.encode('utf-8')).hexdigest()[:32]
        try:
            cache_path.write_text(digest, encoding='utf-8')
        except Exception:
            pass
        return digest

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
        self.reg_password_confirm = QtWidgets.QLineEdit()
        self.reg_password_confirm.setEchoMode(QtWidgets.QLineEdit.EchoMode.Password)
        self.reg_name = QtWidgets.QLineEdit()
        register_btn = QtWidgets.QPushButton('Kayıt Ol')
        register_btn.clicked.connect(self.register)
        form.addRow('Email', self.reg_email)
        form.addRow('Şifre', self.reg_password)
        form.addRow('Şifre (Tekrar)', self.reg_password_confirm)
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
        self.dashboard_tab = self._build_dashboard_tab()
        self.sites_tab = self._build_sites_tab()
        self.surf_tab = self._build_surf_tab()
        self.google_tab = self._build_google_tab()
        self.youtube_tab = self._build_youtube_tab()
        self.points_info_tab = self._build_points_info_tab()
        self.system_stats_tab = self._build_system_stats_tab()
        self.mail_tab = self._build_mail_tab()
        self.log_tab = self._build_log_tab()

        self._tab_sequence = [
            ('dashboard_tab', self.dashboard_tab, 'Puan/Özet'),
            ('sites_tab', self.sites_tab, 'Siteler'),
            ('surf_tab', self.surf_tab, 'Surf + Puan'),
            ('google_tab', self.google_tab, 'Google Görevi'),
            ('youtube_tab', self.youtube_tab, 'YouTube Görevi'),
            ('points_info_tab', self.points_info_tab, 'Puan Sistemi / Özellikler'),
            ('system_stats_tab', self.system_stats_tab, 'İstatistikler'),
            ('mail_tab', self.mail_tab, 'İletişim'),
            ('log_tab', self.log_tab, 'Log'),
        ]

        # Varsayılan olarak hepsi açık; config geldiğinde görünürlük yeniden ayarlanacak
        self._rebuild_tabs(google_on=True, youtube_on=True)
        layout.addWidget(self.tabs)
        return widget

    def _build_dashboard_tab(self) -> QtWidgets.QWidget:
        widget = QtWidgets.QWidget()
        layout = QtWidgets.QVBoxLayout(widget)
        layout.setSpacing(10)
        layout.setAlignment(QtCore.Qt.AlignmentFlag.AlignTop)

        self.warning_bar = QtWidgets.QLabel()
        self.warning_bar.setVisible(False)
        self.warning_bar.setStyleSheet('background:#f43f5e; color:white; padding:8px 12px; border-radius:8px; font-weight:bold;')
        layout.addWidget(self.warning_bar)

        self.summary_label = QtWidgets.QLabel('Günlük: 0 • Haftalık: 0')
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

        selector_row = QtWidgets.QHBoxLayout()
        selector_row.addWidget(QtWidgets.QLabel('Gösterim'))
        self.points_range = QtWidgets.QComboBox()
        for label, key in [('Günlük', 'daily'), ('Haftalık', 'weekly'), ('Aylık', 'monthly')]:
            self.points_range.addItem(label, key)
        selector_row.addWidget(self.points_range)
        selector_row.addStretch()
        layout.addLayout(selector_row)

        toggle_row = QtWidgets.QHBoxLayout()
        self.points_toggle_earned = self._build_series_toggle('Kazanılan', '#3b82f6')
        self.points_toggle_spent = self._build_series_toggle('Harcanan', '#ef4444')
        for cb in (self.points_toggle_earned, self.points_toggle_spent):
            cb.toggled.connect(self._render_points_chart)
            toggle_row.addWidget(cb)
        toggle_row.addStretch()
        layout.addLayout(toggle_row)

        self.points_chart = QChartView()
        self.points_chart.setRenderHint(QtGui.QPainter.RenderHint.Antialiasing)
        layout.addWidget(self.points_chart)
        self.points_range.currentIndexChanged.connect(self._render_points_chart)

        return widget

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
        self.media_cb.toggled.connect(self._toggle_media_options)

        self.media_option_boxes: Dict[str, QtWidgets.QCheckBox] = {
            'hover': QtWidgets.QCheckBox('Mouse ile videonun üstüne gelme'),
            'delay': QtWidgets.QCheckBox('Birkaç saniye bekleme'),
            'human_click': QtWidgets.QCheckBox('İnsan davranışı tıklaması'),
            'pause_play': QtWidgets.QCheckBox('Videoyu durdur / devam ettir'),
            'volume': QtWidgets.QCheckBox('Ses açma'),
            'fullscreen': QtWidgets.QCheckBox('Tam ekran (YouTube f)'),
            'quality': QtWidgets.QCheckBox('Kalite menüsü'),
        }

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

        media_layout = QtWidgets.QVBoxLayout()
        media_layout.addWidget(QtWidgets.QLabel('Medya aksiyonları (medya aktifse):'))
        for cb in self.media_option_boxes.values():
            cb.setEnabled(False)
            media_layout.addWidget(cb)
        form.addLayout(media_layout, 4, 0, 1, 2)

        add_btn = QtWidgets.QPushButton('Siteyi Kaydet')
        add_btn.clicked.connect(self.add_or_update_site)
        add_btn.setStyleSheet('padding:10px 16px; font-weight:bold; background:#2563eb; color:white; border-radius:8px;')
        form.addWidget(add_btn, 5, 0, 1, 2)

        outer.addLayout(form)

        self.preview_label = QtWidgets.QLabel('Davranış Önizleme: Henüz seçilmedi')
        self.preview_label.setStyleSheet('font-weight:bold; color:#111827;')
        outer.addWidget(self.preview_label)

        self.site_table = QtWidgets.QTableWidget(0, 6)
        self.site_table.setHorizontalHeaderLabels(['ID', 'Site Adı', 'URL', 'Süre', 'Ayarlar', 'İşlemler'])
        self.site_table.horizontalHeader().setStretchLastSection(True)
        self.site_table.setSortingEnabled(True)
        self.site_table.setSelectionBehavior(QtWidgets.QAbstractItemView.SelectionBehavior.SelectRows)
        self.site_table.itemSelectionChanged.connect(self._on_site_selected)
        self._make_copy_only(self.site_table)
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

        self.live_view = QtWidgets.QLabel('Canlı surf önizlemesi bekleniyor')
        self.live_view.setAlignment(QtCore.Qt.AlignmentFlag.AlignCenter)
        self.live_view.setMinimumHeight(260)
        self.live_view.setStyleSheet('background:#0b1224; color:#cbd5e1; border:1px solid #1f2937; border-radius:10px;')
        layout.addWidget(self.live_view)

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

    def _build_google_tab(self) -> QtWidgets.QWidget:
        widget = QtWidgets.QWidget()
        form = QtWidgets.QFormLayout(widget)

        self.google_name = QtWidgets.QLineEdit()
        self.google_url = QtWidgets.QLineEdit()
        self.google_keyword = QtWidgets.QLineEdit()
        self.google_country = QtWidgets.QComboBox()
        self._populate_countries(self.google_country)
        self.google_pages = QtWidgets.QSpinBox()
        self.google_pages.setRange(1, 10)
        self.google_pages.setValue(3)
        self.google_dwell = QtWidgets.QSpinBox()
        self.google_dwell.setRange(5, 900)
        self.google_dwell.setValue(40)

        self.google_actions = self._build_action_checkboxes(include_media=True)

        form.addRow('Site adı', self.google_name)
        form.addRow('URL', self.google_url)
        form.addRow('Arama kelimesi', self.google_keyword)
        form.addRow('Ülke', self.google_country)
        form.addRow('Kaç sayfa tara', self.google_pages)
        form.addRow('Sitede kalma (sn)', self.google_dwell)
        form.addRow(self.google_actions['container'])

        btn_row = QtWidgets.QHBoxLayout()
        save_btn = QtWidgets.QPushButton('Google görevli site ekle')
        save_btn.clicked.connect(self.add_google_site)
        save_btn.setStyleSheet('padding:10px 14px; font-weight:bold;')
        btn_row.addWidget(save_btn)
        form.addRow(btn_row)
        return widget

    def _build_youtube_tab(self) -> QtWidgets.QWidget:
        widget = QtWidgets.QWidget()
        form = QtWidgets.QFormLayout(widget)

        self.youtube_name = QtWidgets.QLineEdit()
        self.youtube_url = QtWidgets.QLineEdit()
        self.youtube_keyword = QtWidgets.QLineEdit()
        self.youtube_search_cb = QtWidgets.QCheckBox('Önce arama yap, sonra videoya gir')
        self.youtube_search_cb.setChecked(False)
        self.youtube_keyword.setEnabled(False)
        self.youtube_search_cb.toggled.connect(self.youtube_keyword.setEnabled)
        self.youtube_pages = QtWidgets.QSpinBox()
        self.youtube_pages.setRange(1, 10)
        self.youtube_pages.setValue(2)
        self.youtube_dwell = QtWidgets.QSpinBox()
        self.youtube_dwell.setRange(5, 1200)
        self.youtube_dwell.setValue(90)

        self.youtube_actions = self._build_action_checkboxes(include_media=True)

        form.addRow('Site adı', self.youtube_name)
        form.addRow('URL', self.youtube_url)
        form.addRow(self.youtube_search_cb)
        form.addRow('Arama kelimesi', self.youtube_keyword)
        form.addRow('Arama sayfa sayısı', self.youtube_pages)
        form.addRow('İzleme süresi (sn)', self.youtube_dwell)
        form.addRow(self.youtube_actions['container'])

        btn_row = QtWidgets.QHBoxLayout()
        save_btn = QtWidgets.QPushButton('YouTube görevli site ekle')
        save_btn.clicked.connect(self.add_youtube_site)
        save_btn.setStyleSheet('padding:10px 14px; font-weight:bold;')
        btn_row.addWidget(save_btn)
        form.addRow(btn_row)
        return widget

    def _build_points_info_tab(self) -> QtWidgets.QWidget:
        widget = QtWidgets.QWidget()
        layout = QtWidgets.QVBoxLayout(widget)
        layout.setAlignment(QtCore.Qt.AlignmentFlag.AlignTop)
        desc = QtWidgets.QLabel('Kazanılabilecek / harcanacak puanların özeti:')
        desc.setStyleSheet('font-weight:bold; font-size:14px;')
        layout.addWidget(desc)
        cards = [
            ('Site ekleme', '- Süre', 'Siteyi havuza açarken süre kadar puan ayrılır, surf tamamlanınca gerçek harcama yapılır'),
            ('Surf ödülü', '+ Süre', 'Başka kullanıcı siteyi gezdiğinde geçirilen süre kadar puan kazanılır'),
            ('Google görevi', '+ (Arama süresi + süre + sayfa*10)', 'Arama görünürlüğü + sayfa gezinmesi + sitede kalma'),
            ('YouTube görevi', '+ (Arama süresi + izleme süresi)', 'Video izlenmesi ve medya aksiyonları'),
            ('SEO katkısı', '+ Etkileşim', 'Kaydırma, tıklama, form ve medya aksiyonları kaliteyi artırır'),
            ('Puan akışı', '+/− Anlık', 'Görev sonunda site sahibinden düşüp gezene eklenir'),
            ('Topluluk havuzu', '+ Pasif kazanç', 'Diğer kullanıcılar sitenizi gezerken puan toplarsınız'),
        ]
        grid = QtWidgets.QGridLayout()
        grid.setSpacing(12)
        for idx, (title, pts, desc_text) in enumerate(cards):
            box = QtWidgets.QGroupBox(title)
            box.setStyleSheet(
                'QGroupBox { font-weight:bold; border:1px solid #e2e8f0; border-radius:8px; padding:8px; }'
                'QGroupBox:hover { border-color:#0ea5e9; background:#f8fafc; }'
            )
            inner = QtWidgets.QVBoxLayout(box)
            pts_label = QtWidgets.QLabel(pts)
            pts_label.setStyleSheet('color:#0ea5e9; font-size:13px; font-weight:bold;')
            body = QtWidgets.QLabel(desc_text)
            body.setWordWrap(True)
            inner.addWidget(pts_label)
            inner.addWidget(body)
            grid.addWidget(box, idx // 2, idx % 2)
        layout.addLayout(grid)
        return widget

    def _build_system_stats_tab(self) -> QtWidgets.QWidget:
        widget = QtWidgets.QWidget()
        layout = QtWidgets.QVBoxLayout(widget)
        layout.setAlignment(QtCore.Qt.AlignmentFlag.AlignTop)

        title = QtWidgets.QLabel('Genel İstatistikler')
        title.setStyleSheet('font-weight:bold; font-size:14px;')
        layout.addWidget(title)

        cards_layout = QtWidgets.QHBoxLayout()
        cards_layout.setSpacing(12)
        self.system_cards: Dict[str, QtWidgets.QLabel] = {}
        card_defs = [
            ('Sites', '#0ea5e9', 'sites'),
            ('Kullanıcılar', '#8b5cf6', 'users'),
            ('Kazanılan', '#22c55e', 'earned'),
            ('Harcanan', '#ef4444', 'spent'),
            ('Ziyaretler', '#f59e0b', 'visits'),
        ]
        for title_txt, color, key in card_defs:
            frame = QtWidgets.QFrame()
            frame.setStyleSheet(f'background: #f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:10px;')
            vbox = QtWidgets.QVBoxLayout(frame)
            label = QtWidgets.QLabel(title_txt)
            label.setAlignment(QtCore.Qt.AlignmentFlag.AlignCenter)
            label.setStyleSheet('font-weight:bold;')
            value = QtWidgets.QLabel('0')
            value.setAlignment(QtCore.Qt.AlignmentFlag.AlignCenter)
            value.setStyleSheet(f'color:{color}; font-size:18px; font-weight:bold;')
            vbox.addWidget(label)
            vbox.addWidget(value)
            cards_layout.addWidget(frame)
            self.system_cards[key] = value
        layout.addLayout(cards_layout)

        range_row = QtWidgets.QHBoxLayout()
        range_row.addWidget(QtWidgets.QLabel('Periyot'))
        self.system_range = QtWidgets.QComboBox()
        for label, key in [('Günlük', 'daily'), ('Haftalık', 'weekly'), ('Aylık', 'monthly')]:
            self.system_range.addItem(label, key)
        range_row.addWidget(self.system_range)

        range_row.addStretch()
        layout.addLayout(range_row)

        toggle_row = QtWidgets.QHBoxLayout()
        self.system_toggle_visit = self._build_series_toggle('Ziyaret', '#0ea5e9')
        self.system_toggle_earned = self._build_series_toggle('Kazanılan', '#22c55e')
        self.system_toggle_spent = self._build_series_toggle('Harcanan', '#ef4444')
        for cb in (self.system_toggle_visit, self.system_toggle_earned, self.system_toggle_spent):
            cb.toggled.connect(self._render_system_chart)
            toggle_row.addWidget(cb)
        toggle_row.addStretch()
        layout.addLayout(toggle_row)

        self.system_chart = QChartView()
        self.system_chart.setRenderHint(QtGui.QPainter.RenderHint.Antialiasing)
        layout.addWidget(self.system_chart)
        self.system_range.currentIndexChanged.connect(self._render_system_chart)
        return widget

    def _build_action_checkboxes(self, include_media: bool = True) -> dict:
        container = QtWidgets.QGroupBox('Görev Aksiyonları')
        grid = QtWidgets.QGridLayout(container)
        flags = {
            'mobile': QtWidgets.QCheckBox('Mobil UA'),
            'realistic': QtWidgets.QCheckBox('Gerçekçi gezinme'),
            'mouse_moves': QtWidgets.QCheckBox('Mouse hareketleri'),
            'scroll': QtWidgets.QCheckBox('Scroll'),
            'link_clicks': QtWidgets.QCheckBox('Link tıklama'),
            'form_fill': QtWidgets.QCheckBox('Form doldurma'),
        }
        for idx, cb in enumerate(flags.values()):
            grid.addWidget(cb, idx // 2, idx % 2)
        media_boxes = {
            'hover': QtWidgets.QCheckBox('Mouse ile videonun üstüne gelme'),
            'delay': QtWidgets.QCheckBox('Birkaç saniye bekleme'),
            'human_click': QtWidgets.QCheckBox('İnsan davranışı tıklama'),
            'pause_play': QtWidgets.QCheckBox('Videoyu durdur / devam ettir'),
            'volume': QtWidgets.QCheckBox('Ses açma'),
            'fullscreen': QtWidgets.QCheckBox('Tam ekran (YouTube f)'),
            'quality': QtWidgets.QCheckBox('Kalite menüsü'),
        }
        if include_media:
            media_group = QtWidgets.QGroupBox('Medya seçenekleri')
            vbox = QtWidgets.QVBoxLayout(media_group)
            for cb in media_boxes.values():
                vbox.addWidget(cb)
            grid.addWidget(media_group, 3, 0, 1, 2)
        return {'container': container, 'flags': flags, 'media': media_boxes}

    def _populate_countries(self, combo: QtWidgets.QComboBox):
        combo.clear()
        for name, code in [
            ('Global', 'com'),
            ('Türkiye', 'com.tr'),
            ('ABD', 'com'),
            ('Almanya', 'de'),
            ('Brezilya', 'com.br'),
            ('Hollanda', 'nl'),
            ('İngiltere', 'uk'),
            ('Kanada', 'ca'),
            ('İspanya', 'es'),
            ('Fransa', 'fr'),
        ]:
            combo.addItem(name, code)

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

    def _build_log_tab(self) -> QtWidgets.QWidget:
        widget = QtWidgets.QWidget()
        layout = QtWidgets.QVBoxLayout(widget)
        self.log_box = QtWidgets.QPlainTextEdit()
        self.log_box.setReadOnly(True)
        self.log_box.setPlaceholderText('İstek ve hata çıktıları burada listelenir')
        layout.addWidget(self.log_box)
        copy_btn = QtWidgets.QPushButton('Logu Kopyala')
        copy_btn.clicked.connect(self._copy_log)
        layout.addWidget(copy_btn)
        return widget

    def register(self):
        self._init_client()
        if self.reg_password.text() != self.reg_password_confirm.text():
            self._toast('Şifreler eşleşmiyor', error=True)
            return
        try:
            resp = self.client.register(
                self.reg_email.text(),
                self.reg_password.text(),
                self.reg_name.text(),
                self.reg_password_confirm.text(),
                self.device_id,
            )
            self.token = resp.get('token')
            self.current_email = self.reg_email.text()
            self._toast('Kayıt başarılı, token alındı')
            self._after_login()
        except Exception as exc:
            self._toast(f'Hata: {exc}', error=True)

    def login(self):
        self._init_client()
        try:
            resp = self.client.login(self.login_email.text(), self.login_password.text(), device_id=self.device_id)
            self.token = resp.get('token')
            self.current_email = self.login_email.text()
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
        self._stop_worker(force=True)
        self.live_timer.stop()
        self.token = None
        self.logout_btn.setVisible(False)
        self.stack.setCurrentIndex(0)
        self.current_email = ''
        self.sites_cache = {}
        self.task_points = {}
        self.reward_limits = {}
        self.current_page = 1
        self.total_pages = 1
        self.last_site_id = None
        self._duplicate_skip = 0
        self.points_card.setText('Puan: 0')
        self.status_label.setText('Hazır')
        self.site_table.setRowCount(0)
        self.log_box.clear()
        self.earnings_label.setText('Kazanılan puanlar burada görüntülenecek')
        self.preview_label.setText('Davranış Önizleme')
        self.summary_label.setText('Günlük: 0 • Haftalık: 0')
        self.remaining_bar.setValue(0)
        self.timer_label.setText('Animasyonlu Sayaç: 0 sn')
        self.warning_bar.setVisible(False)
        self.points_history_data = {
            'daily': [{'label': '0', 'earned': 0, 'spent': 0}],
            'weekly': [{'label': '0', 'earned': 0, 'spent': 0}],
            'monthly': [{'label': '0', 'earned': 0, 'spent': 0}],
        }
        self.system_history_data = {}
        if hasattr(self, 'points_chart'):
            self._render_points_chart()
        if hasattr(self, 'system_chart'):
            self._render_system_chart()
        self._reset_forms()
        self._toast('Çıkış yapıldı')

    def _reset_forms(self):
        for edit in [
            self.reg_email,
            self.reg_password,
            self.reg_password_confirm,
            self.reg_name,
            self.login_email,
            self.login_password,
            self.reset_email,
            self.site_name,
            self.site_url,
            self.google_name,
            self.google_url,
            self.google_keyword,
            self.youtube_name,
            self.youtube_url,
            self.youtube_keyword,
        ]:
            edit.clear()
        for spin in [self.dwell_spin, self.google_pages, self.google_dwell, self.youtube_pages, self.youtube_dwell]:
            spin.setValue(spin.minimum())
        for cb in [
            self.mobile_cb,
            self.realistic_cb,
            self.mouse_cb,
            self.click_cb,
            self.scroll_cb,
            self.form_cb,
            self.media_cb,
            *self.media_option_boxes.values(),
        ]:
            cb.setChecked(False)
        for container in (self.google_actions, self.youtube_actions):
            for cb in container['flags'].values():
                cb.setChecked(False)
            for cb in container.get('media', {}).values():
                cb.setChecked(False)
        if hasattr(self, 'youtube_search_cb'):
            self.youtube_search_cb.setChecked(False)
        self.google_country.setCurrentIndex(0)
        self.youtube_pages.setValue(self.youtube_pages.minimum())
        self.live_view.setPixmap(QtGui.QPixmap())
        self.live_view.setText('Chromium bekleme modunda')

    def _make_copy_only(self, table: QtWidgets.QTableWidget):
        table.setEditTriggers(QtWidgets.QAbstractItemView.EditTrigger.NoEditTriggers)
        table.itemClicked.connect(lambda item: self._copy_item(item))
        table.itemDoubleClicked.connect(lambda item: self._copy_item(item))

    def _copy_item(self, item: Optional[QtWidgets.QTableWidgetItem]):
        if not item:
            return
        QtWidgets.QApplication.clipboard().setText(item.text())
        self.status_label.setText('Kopyalandı')

    def _rebuild_tabs(self, google_on: bool, youtube_on: bool):
        """Tab sırasını koruyarak Google/YouTube sekmelerini aç/kapat."""
        if not hasattr(self, 'tabs'):
            return
        current_title = None
        if self.tabs.count():
            current_idx = self.tabs.currentIndex()
            if current_idx >= 0:
                current_title = self.tabs.tabText(current_idx)

        while self.tabs.count():
            self.tabs.removeTab(0)

        for key, widget, title in self._tab_sequence:
            if key == 'google_tab' and not google_on:
                continue
            if key == 'youtube_tab' and not youtube_on:
                continue
            self.tabs.addTab(widget, title)

        if current_title:
            for idx in range(self.tabs.count()):
                if self.tabs.tabText(idx) == current_title:
                    self.tabs.setCurrentIndex(idx)
                    break

    def _init_client(self):
        base_url = self.base_url_input.text().strip()
        if not base_url:
            raise ValueError('API URL boş olamaz')
        self.client = ApiClient(base_url)
        self._append_log(f'API URL: {base_url}')

    def _after_login(self):
        self.logout_btn.setVisible(True)
        self.stack.setCurrentIndex(1)
        self.points_card.setText('Puan yükleniyor...')
        self.log_box.clear()
        if not self.persona_profiles:
            assets_dir = Path(__file__).resolve().parent / 'assets'
            self.persona_profiles = load_persona_profiles(assets_dir / 'personas.json')
        self.personality = PersonaEngine.random(self.persona_profiles, seed=self.current_email or None)
        self._fetch_task_points()
        self.refresh_dashboard()
        self.live_timer.start()

    def _fetch_task_points(self):
        if not self.client or not self.token:
            return
        try:
            self.task_points = self.client.task_config(self.token) or {}
            self._append_log('Görev puan bilgisi alındı')
        except Exception as exc:  # noqa: BLE001
            self._append_log(f'Görev puan konfigürasyonu alınamadı: {exc}')
        self._apply_task_tab_visibility()
        self.load_sites()
        self.load_mail_settings()
        self.refresh_system_stats()

    def _apply_task_tab_visibility(self):
        if not hasattr(self, 'tabs'):
            return
        google_on = bool(int(self.task_points.get('google_tasks_enabled', 1)))
        youtube_on = bool(int(self.task_points.get('youtube_tasks_enabled', 1)))
        self._rebuild_tabs(google_on, youtube_on)

    def refresh_dashboard(self):
        if not self.client or not self.token:
            self._toast('Önce giriş yapın')
            return
        try:
            dash = self.client.dashboard(self.token)
        except Exception as exc:
            self._toast(f'Dashboard hatası: {exc}', error=True)
            self.points_card.setText('Puan: 0')
            return
        points = int(dash.get('points', 0))
        self.points_card.setText(f"Puan: {points}")
        daily = dash.get('daily', 0)
        weekly = dash.get('weekly', 0)
        remaining = dash.get('remaining_seconds', 0)
        total = dash.get('total_seconds', max(1, remaining))
        percent = min(100, int((remaining / max(1, total)) * 100))
        limits = dash.get('limits') or {}
        self.reward_limits = limits or {}
        caps_txt = []
        if limits.get('daily_left') is not None:
            caps_txt.append(f"Günlük limit: {limits.get('daily_left')} kaldı")
        if limits.get('weekly_left') is not None:
            caps_txt.append(f"Haftalık limit: {limits.get('weekly_left')} kaldı")
        if limits.get('monthly_left') is not None:
            caps_txt.append(f"Aylık limit: {limits.get('monthly_left')} kaldı")
        caps_line = ' • '.join(caps_txt)
        suffix = f" • {caps_line}" if caps_line else ''
        self.summary_label.setText(f"Günlük: {daily} • Haftalık: {weekly}{suffix}")
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
            history = {}
        if not isinstance(history, dict) or not history:
            history = {
                'daily': [{'label': '0', 'earned': 0, 'spent': 0}],
                'weekly': [{'label': '0', 'earned': 0, 'spent': 0}],
                'monthly': [{'label': '0', 'earned': 0, 'spent': 0}],
            }
        self.points_history_data = history
        self._render_points_chart()

    def _build_series_toggle(self, label: str, color: str) -> QtWidgets.QCheckBox:
        cb = QtWidgets.QCheckBox(label)
        cb.setChecked(True)
        base_style = f'QCheckBox::indicator {{ width:16px; height:16px; }} QCheckBox {{ color:{color}; font-weight:bold; }} '
        cb.setProperty('base_style', base_style)
        cb.setStyleSheet(base_style + 'text-decoration:none;')
        cb.toggled.connect(lambda: self._refresh_toggle_style(cb))
        return cb

    @staticmethod
    def _refresh_toggle_style(cb: QtWidgets.QCheckBox):
        base_style = cb.property('base_style') or cb.styleSheet()
        if cb.isChecked():
            cb.setStyleSheet(base_style + ' text-decoration:none;')
        else:
            cb.setStyleSheet(base_style + ' text-decoration:line-through; color:#94a3b8;')

    def _render_chart_generic(self, view: QChartView, categories: List[str], series_defs: List[dict], mode: str = 'line'):
        chart = QChart()
        axis_x = QBarCategoryAxis()
        axis_y = QValueAxis()
        axis_y.setLabelFormat('%d')
        axis_x.append(categories or ['0'])
        chart.addAxis(axis_x, QtCore.Qt.AlignmentFlag.AlignBottom)
        chart.addAxis(axis_y, QtCore.Qt.AlignmentFlag.AlignLeft)
        combined: List[int] = [0]

        if mode == 'bar':
            bars = QBarSeries(); bars.setBarWidth(0.9)
            for ser in series_defs:
                if not ser.get('enabled', True):
                    continue
                bset = QBarSet(ser.get('name', ''))
                bset.setColor(QtGui.QColor(ser.get('color', '#0ea5e9')))
                for val in ser.get('values', []):
                    bset.append(val)
                    combined.append(val)
                bars.append(bset)
            chart.addSeries(bars)
            bars.attachAxis(axis_x)
            bars.attachAxis(axis_y)
        else:
            for ser in series_defs:
                if not ser.get('enabled', True):
                    continue
                line = QLineSeries()
                line.setName(ser.get('name', ''))
                line.setColor(QtGui.QColor(ser.get('color', '#0ea5e9')))
                for idx, val in enumerate(ser.get('values', [])):
                    line.append(idx, val)
                    combined.append(val)
                chart.addSeries(line)
                line.attachAxis(axis_x)
                line.attachAxis(axis_y)

        min_val = min(combined)
        max_val = max(combined)
        pad = max(5, int((max_val - min_val) * 0.25) + 5)
        axis_y.setRange(min_val - pad, max_val + pad)
        chart.legend().setVisible(True)
        view.setChart(chart)

    def _render_points_chart(self):
        if not hasattr(self, 'points_chart'):
            return
        period = self.points_range.currentData() if hasattr(self, 'points_range') else 'daily'
        history = self.points_history_data.get(period, []) if isinstance(self.points_history_data, dict) else []
        if not history:
            history = [{'label': '0', 'earned': 0, 'spent': 0}]
        categories = [str(row.get('label', idx)) for idx, row in enumerate(history)]
        series_defs = [
            {
                'name': 'Kazanılan',
                'color': '#3b82f6',
                'values': [int(row.get('earned', 0)) for row in history],
                'enabled': getattr(self, 'points_toggle_earned', None).isChecked() if hasattr(self, 'points_toggle_earned') else True,
            },
            {
                'name': 'Harcanan',
                'color': '#ef4444',
                'values': [int(row.get('spent', 0)) for row in history],
                'enabled': getattr(self, 'points_toggle_spent', None).isChecked() if hasattr(self, 'points_toggle_spent') else True,
            },
        ]
        self._render_chart_generic(self.points_chart, categories, series_defs, 'bar')

    def _render_system_chart(self):
        if not hasattr(self, 'system_chart'):
            return
        period = self.system_range.currentData() if hasattr(self, 'system_range') else 'daily'
        visits_key = {
            'daily': 'daily_visits',
            'weekly': 'weekly_visits',
            'monthly': 'monthly_visits',
        }.get(period, 'daily_visits')
        visit_history = self.system_history_data.get(visits_key, []) if isinstance(self.system_history_data, dict) else []
        point_history = self.system_history_data.get(period, []) if isinstance(self.system_history_data, dict) else []
        labels = []
        visit_vals: List[int] = []
        spent_vals: List[int] = []
        earned_vals: List[int] = []
        max_len = max(len(visit_history), len(point_history)) or 1
        for idx in range(max_len):
            v_row = visit_history[idx] if idx < len(visit_history) else {}
            p_row = point_history[idx] if idx < len(point_history) else {}
            labels.append(str(v_row.get('label') or p_row.get('label') or idx))
            visit_vals.append(int(v_row.get('visits', 0) or 0))
            earned_vals.append(int(p_row.get('earned', 0) or 0))
            spent_vals.append(int(p_row.get('spent', 0) or 0))

        series_defs = [
            {
                'name': 'Ziyaret',
                'color': '#0ea5e9',
                'values': visit_vals,
                'enabled': getattr(self, 'system_toggle_visit', None).isChecked() if hasattr(self, 'system_toggle_visit') else True,
            },
            {
                'name': 'Kazanılan',
                'color': '#22c55e',
                'values': earned_vals,
                'enabled': getattr(self, 'system_toggle_earned', None).isChecked() if hasattr(self, 'system_toggle_earned') else True,
            },
            {
                'name': 'Harcanan',
                'color': '#ef4444',
                'values': spent_vals,
                'enabled': getattr(self, 'system_toggle_spent', None).isChecked() if hasattr(self, 'system_toggle_spent') else True,
            },
        ]
        self._render_chart_generic(self.system_chart, labels, series_defs, 'bar')

    def refresh_system_stats(self):
        if not self.client or not self.token:
            return
        try:
            stats = self.client.system_stats(self.token) or {}
        except Exception as exc:  # noqa: BLE001
            self._append_log(f'Sistem istatistiği alınamadı: {exc}')
            return
        summary = stats.get('summary', {})
        for key, label in (self.system_cards or {}).items():
            label.setText(str(summary.get(key, 0)))
        history = stats.get('history', {}) if isinstance(stats, dict) else {}
        self.system_history_data = history
        self._render_system_chart()

    def add_or_update_site(self):
        if not self.client or not self.token:
            self._toast('Önce giriş yapın')
            return
        if not self.site_name.text().strip() or not self.site_url.text().strip():
            self._toast('Site adı ve URL alanları zorunludur', error=True)
            return
        if not self._validate_url(self.site_url.text().strip()):
            self._toast('Geçerli ve erişilebilir bir URL girin', error=True)
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
            'media_actions': [key for key, cb in self.media_option_boxes.items() if cb.isChecked()],
            'google_enabled': False,
            'google_keyword': '',
            'google_country': '',
            'google_pages': 0,
            'google_dwell': 0,
            'youtube_enabled': False,
            'youtube_keyword': '',
            'youtube_link': '',
            'youtube_pages': 0,
            'youtube_dwell': 0,
        }
        try:
            self.client.create_site(self.token, payload)
            self._toast('Site kaydedildi ve havuza eklendi')
            self.load_sites()
            self.refresh_dashboard()
        except Exception as exc:
            self._toast(f'Kayıt hatası: {exc}', error=True)

    def add_google_site(self):
        if not self.client or not self.token:
            self._toast('Önce giriş yapın')
            return
        if not self.google_name.text().strip() or not self.google_url.text().strip() or not self.google_keyword.text().strip():
            self._toast('Google görevi için ad, URL ve arama kelimesi gerekli', error=True)
            return
        if not self._validate_url(self.google_url.text().strip()):
            self._toast('Geçerli ve erişilebilir bir Google hedef URL\'si girin', error=True)
            return
        flags = self._collect_flags(self.google_actions)
        payload = {
            'name': self.google_name.text(),
            'url': self.google_url.text(),
            'dwell_seconds': self.google_dwell.value(),
            'mobile': flags.get('mobile'),
            'realistic': flags.get('realistic'),
            'mouse_moves': flags.get('mouse_moves'),
            'link_clicks': flags.get('link_clicks'),
            'scroll': flags.get('scroll'),
            'form_fill': flags.get('form_fill'),
            'media': flags.get('media'),
            'media_actions': flags.get('media_actions', []),
            'google_enabled': True,
            'google_keyword': self.google_keyword.text(),
            'google_country': self.google_country.currentData(),
            'google_pages': self.google_pages.value(),
            'google_dwell': self.google_dwell.value(),
            'youtube_enabled': False,
            'youtube_keyword': '',
            'youtube_link': '',
            'youtube_pages': 0,
            'youtube_dwell': 0,
        }
        try:
            self.client.create_site(self.token, payload)
            self._toast('Google görevli site kaydedildi ve havuza eklendi')
            self.load_sites()
            self.refresh_dashboard()
        except Exception as exc:  # noqa: BLE001
            self._toast(f'Google kayıt hatası: {exc}', error=True)

    def add_youtube_site(self):
        if not self.client or not self.token:
            self._toast('Önce giriş yapın')
            return
        if not self.youtube_name.text().strip() or not self.youtube_url.text().strip():
            self._toast('YouTube görevi için ad ve URL zorunlu', error=True)
            return
        if not self._validate_url(self.youtube_url.text().strip()):
            self._toast('Geçerli ve erişilebilir bir YouTube linki girin', error=True)
            return
        if self.youtube_search_cb.isChecked() and not self.youtube_keyword.text().strip():
            self._toast('Arama seçiliyse anahtar kelime gerekli', error=True)
            return
        flags = self._collect_flags(self.youtube_actions)
        payload = {
            'name': self.youtube_name.text(),
            'url': self.youtube_url.text(),
            'dwell_seconds': self.youtube_dwell.value(),
            'mobile': flags.get('mobile'),
            'realistic': flags.get('realistic'),
            'mouse_moves': flags.get('mouse_moves'),
            'link_clicks': flags.get('link_clicks'),
            'scroll': flags.get('scroll'),
            'form_fill': flags.get('form_fill'),
            'media': flags.get('media'),
            'media_actions': flags.get('media_actions', []),
            'google_enabled': False,
            'google_keyword': '',
            'google_country': '',
            'google_pages': 0,
            'google_dwell': 0,
            'youtube_enabled': True,
            'youtube_keyword': self.youtube_keyword.text() if self.youtube_search_cb.isChecked() else '',
            'youtube_link': self.youtube_url.text(),
            'youtube_pages': self.youtube_pages.value(),
            'youtube_dwell': self.youtube_dwell.value(),
        }
        try:
            self.client.create_site(self.token, payload)
            self._toast('YouTube görevli site kaydedildi ve havuza eklendi')
            self.load_sites()
            self.refresh_dashboard()
        except Exception as exc:  # noqa: BLE001
            self._toast(f'YouTube kayıt hatası: {exc}', error=True)

    def _toggle_media_options(self, checked: bool):
        for cb in self.media_option_boxes.values():
            cb.setEnabled(checked)
            if not checked:
                cb.setChecked(False)

    def load_sites(self):
        if not self.client or not self.token:
            self._toast('Önce giriş yapın')
            return
        try:
            data = self.client.list_sites(self.token, page=self.current_page)
        except Exception as exc:
            self._toast(f'Site liste hatası: {exc}', error=True)
            return
        sites: List[dict] = []
        if isinstance(data, dict):
            for key in ('items', 'data', 'sites'):
                if data.get(key):
                    sites = data.get(key, [])
                    break
        elif isinstance(data, list):
            sites = data
        self.sites_cache = {int(s.get('id')): s for s in sites if s.get('id')}
        self.total_pages = max(1, int(data.get('pages', 1))) if isinstance(data, dict) else 1
        self.page_label.setText(f'Sayfa {self.current_page}/{self.total_pages}')
        self.site_table.setRowCount(len(sites))
        if not sites:
            self._append_log('Kayıtlı site bulunamadı veya yetkisiz istek yanıtı geldi')
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
            if site.get('google_enabled'):
                settings.append('Google görevi')
            if site.get('youtube_enabled'):
                settings.append('YouTube görevi')
            media_actions = site.get('media_actions') or site.get('media_options') or []
            if media_actions:
                settings.append('Medya: ' + ', '.join(media_actions))
            self.site_table.setItem(row, 4, QtWidgets.QTableWidgetItem(', '.join(settings)))
            action_widget = QtWidgets.QWidget()
            action_layout = QtWidgets.QHBoxLayout(action_widget)
            action_layout.setContentsMargins(0, 0, 0, 0)
            edit_btn = QtWidgets.QPushButton('Düzenle')
            details_btn = QtWidgets.QPushButton('Detaylar')
            details_btn.clicked.connect(lambda _, s_id=site.get('id'): self.show_site_details(s_id))
            delete_btn = QtWidgets.QPushButton('Sil')
            delete_btn.clicked.connect(lambda _, s_id=site.get('id'): self.delete_site(s_id))
            edit_btn.clicked.connect(lambda _, s_id=site.get('id'): self.edit_site(s_id))
            for btn in (edit_btn, details_btn, delete_btn):
                btn.setStyleSheet('padding:4px 8px;')
                action_layout.addWidget(btn)
            action_layout.addStretch()
            self.site_table.setCellWidget(row, 5, action_widget)

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

    def edit_site(self, site_id: int):
        site = self.sites_cache.get(int(site_id)) if hasattr(self, 'sites_cache') else None
        if not site:
            self._toast('Site bilgisi bulunamadı', error=True)
            return
        dialog = QtWidgets.QDialog(self)
        dialog.setWindowTitle('Siteyi Güncelle')
        form = QtWidgets.QFormLayout(dialog)

        name_edit = QtWidgets.QLineEdit(site.get('name', ''))
        url_edit = QtWidgets.QLineEdit(site.get('url', ''))
        dwell_spin = QtWidgets.QSpinBox()
        dwell_spin.setRange(5, 3600)
        dwell_spin.setValue(int(site.get('dwell_seconds') or 30))

        flags_box = self._build_action_checkboxes(include_media=True)
        for key, cb in flags_box['flags'].items():
            cb.setChecked(bool(site.get(key)))
        for key, cb in flags_box['media'].items():
            actions = site.get('media_actions') or []
            cb.setChecked(key in actions)

        is_google = bool(site.get('google_enabled'))
        is_youtube = bool(site.get('youtube_enabled'))

        google_keyword = QtWidgets.QLineEdit(site.get('google_keyword') or '')
        google_country = QtWidgets.QComboBox()
        self._populate_countries(google_country)
        idx = max(0, google_country.findData(site.get('google_country') or 'com'))
        google_country.setCurrentIndex(idx)
        google_pages = QtWidgets.QSpinBox()
        google_pages.setRange(1, 10)
        google_pages.setValue(int(site.get('google_pages') or 1))
        google_dwell = QtWidgets.QSpinBox()
        google_dwell.setRange(5, 900)
        google_dwell.setValue(int(site.get('google_dwell') or site.get('dwell_seconds') or 30))

        youtube_keyword = QtWidgets.QLineEdit(site.get('youtube_keyword') or '')
        youtube_search_cb = QtWidgets.QCheckBox('Önce arama yap, sonra videoya gir')
        youtube_search_cb.setChecked(bool(site.get('youtube_keyword')))
        youtube_keyword.setEnabled(youtube_search_cb.isChecked())
        youtube_search_cb.toggled.connect(youtube_keyword.setEnabled)
        youtube_link = QtWidgets.QLineEdit(site.get('youtube_link') or site.get('url') or '')
        youtube_dwell = QtWidgets.QSpinBox()
        youtube_dwell.setRange(5, 1200)
        youtube_dwell.setValue(int(site.get('youtube_dwell') or site.get('dwell_seconds') or 30))

        form.addRow('Site adı', name_edit)
        if not is_youtube:
            form.addRow('URL', url_edit)
            form.addRow('Süre (sn)', dwell_spin)
        if is_google:
            form.addRow('Google kelime', google_keyword)
            form.addRow('Google ülke', google_country)
            form.addRow('Google sayfa', google_pages)
            form.addRow('Google süre', google_dwell)
        elif is_youtube:
            form.addRow(youtube_search_cb)
            form.addRow('YouTube kelime', youtube_keyword)
            form.addRow('YouTube link', youtube_link)
            form.addRow('YouTube süre', youtube_dwell)
        form.addRow(flags_box['container'])

        btns = QtWidgets.QDialogButtonBox(QtWidgets.QDialogButtonBox.StandardButton.Save | QtWidgets.QDialogButtonBox.StandardButton.Cancel)
        form.addRow(btns)

        def _save():
            if not name_edit.text().strip() or not url_edit.text().strip():
                self._toast('Ad ve URL boş olamaz', error=True)
                return
            if not self._validate_url(url_edit.text().strip()):
                self._toast('Geçerli ve erişilebilir bir URL girin', error=True)
                return
            payload = {
                'name': name_edit.text(),
                'url': url_edit.text(),
                'dwell_seconds': dwell_spin.value(),
                'mobile': flags_box['flags']['mobile'].isChecked(),
                'realistic': flags_box['flags']['realistic'].isChecked(),
                'mouse_moves': flags_box['flags']['mouse_moves'].isChecked(),
                'link_clicks': flags_box['flags']['link_clicks'].isChecked(),
                'scroll': flags_box['flags']['scroll'].isChecked(),
                'form_fill': flags_box['flags']['form_fill'].isChecked(),
                'media': any(cb.isChecked() for cb in flags_box['media'].values()),
                'media_actions': [k for k, cb in flags_box['media'].items() if cb.isChecked()],
                'google_enabled': is_google,
                'google_keyword': google_keyword.text(),
                'google_country': google_country.currentData(),
                'google_pages': google_pages.value(),
                'google_dwell': google_dwell.value(),
                'youtube_enabled': is_youtube,
                'youtube_keyword': youtube_keyword.text() if is_youtube and youtube_search_cb.isChecked() else '',
                'youtube_link': youtube_link.text(),
                'youtube_dwell': youtube_dwell.value(),
            }
            if is_youtube:
                payload['url'] = youtube_link.text()
                payload['dwell_seconds'] = youtube_dwell.value()
            if payload['google_enabled'] and (not payload['google_keyword'] or not payload['url']):
                self._toast('Google görevi için kelime ve URL gerekli', error=True)
                return
            if payload['google_enabled'] and not self._validate_url(payload['url']):
                self._toast('Geçerli bir Google hedef URL\'si girin', error=True)
                return
            if payload['youtube_enabled'] and youtube_search_cb.isChecked() and not youtube_keyword.text().strip():
                self._toast('YouTube araması için kelime girilmelidir', error=True)
                return
            if payload['youtube_enabled'] and not payload['youtube_link']:
                self._toast('YouTube linki boş olamaz', error=True)
                return
            if payload['youtube_enabled'] and not self._validate_url(payload['youtube_link']):
                self._toast('Geçerli ve erişilebilir bir YouTube linki girin', error=True)
                return
            try:
                self.client.update_site(self.token, int(site_id), payload)
                self._toast('Site güncellendi')
                self.load_sites()
                dialog.accept()
            except Exception as exc:  # noqa: BLE001
                self._toast(f'Güncelleme hatası: {exc}', error=True)

        btns.accepted.connect(_save)
        btns.rejected.connect(dialog.reject)
        dialog.exec()

    def _on_site_selected(self):
        items = self.site_table.selectedItems()
        if not items:
            return
        row = items[0].row()
        dwell = self.site_table.item(row, 3).text()
        settings = self.site_table.item(row, 4).text()
        self.preview_label.setText(f'Davranış Önizleme: {dwell} sn • {settings}')

    def show_site_details(self, site_id: int):
        if not site_id or not self.client or not self.token:
            self._toast('Önce giriş yapın', error=True)
            return
        dialog = QtWidgets.QDialog(self)
        dialog.setWindowTitle('Site İstatistikleri')
        dialog.resize(1040, 780)
        layout = QtWidgets.QVBoxLayout(dialog)

        tabs = QtWidgets.QTabWidget()
        layout.addWidget(tabs)

        # --- İstatistikler Sekmesi ---
        stats_tab = QtWidgets.QWidget()
        stats_layout = QtWidgets.QVBoxLayout(stats_tab)
        stats_layout.setContentsMargins(8, 8, 8, 8)

        summary_label = QtWidgets.QLabel()
        summary_label.setStyleSheet('font-weight:bold; font-size:15px;')
        points_label = QtWidgets.QLabel()
        points_label.setStyleSheet('font-weight:bold; color:#0f172a;')
        stats_layout.addWidget(summary_label)
        stats_layout.addWidget(points_label)

        range_row = QtWidgets.QHBoxLayout()
        range_row.addWidget(QtWidgets.QLabel('Periyot'))
        site_range = QtWidgets.QComboBox()
        for label, key in [('Günlük', 'daily'), ('Haftalık', 'weekly'), ('Aylık', 'monthly')]:
            site_range.addItem(label, key)
        range_row.addWidget(site_range)
        range_row.addStretch()
        stats_layout.addLayout(range_row)

        toggle_row = QtWidgets.QHBoxLayout()
        visit_toggle = self._build_series_toggle('Ziyaret', '#0ea5e9')
        spent_toggle = self._build_series_toggle('Harcanan', '#ef4444')
        for cb in (visit_toggle, spent_toggle):
            toggle_row.addWidget(cb)
        toggle_row.addStretch()
        stats_layout.addLayout(toggle_row)

        chart_row = QtWidgets.QHBoxLayout()
        stats_layout.addLayout(chart_row)
        site_chart = QChartView()
        site_chart.setRenderHint(QtGui.QPainter.RenderHint.Antialiasing)
        chart_row.addWidget(site_chart)

        table = QtWidgets.QTableWidget()
        mandatory_headers = ['Tarih', 'Ziyaretçi', 'Ülke', 'Şehir', 'Platform', 'Cihaz', 'Ziyaret', 'Scroll', 'Vurgu', 'Form', 'Medya']
        table.setColumnCount(len(mandatory_headers))
        table.setHorizontalHeaderLabels(mandatory_headers)
        table.setSortingEnabled(True)
        self._make_copy_only(table)
        stats_layout.addWidget(table)

        pagination_row = QtWidgets.QHBoxLayout()
        prev_btn = QtWidgets.QPushButton('Önceki')
        next_btn = QtWidgets.QPushButton('Sonraki')
        page_label = QtWidgets.QLabel()
        for btn in (prev_btn, next_btn):
            btn.setStyleSheet('padding:6px 10px;')
        pagination_row.addWidget(prev_btn)
        pagination_row.addWidget(next_btn)
        pagination_row.addWidget(page_label)
        pagination_row.addStretch()
        stats_layout.addLayout(pagination_row)

        # --- Rapor Ayarları Sekmesi ---
        report_tab = QtWidgets.QWidget()
        report_layout = QtWidgets.QVBoxLayout(report_tab)
        report_layout.setAlignment(QtCore.Qt.AlignmentFlag.AlignTop)
        report_layout.setContentsMargins(10, 10, 10, 10)

        report_info = QtWidgets.QLabel(
            'PDF çıktısında Ülke / Şehir / Platform / Cihaz / Ziyaret alanları zorunludur.\n'
            'IP, ek geo detayları, kullanıcı ajanı ve ek metrikleri isteğe göre açıp kapatabilirsiniz.'
        )
        report_info.setWordWrap(True)
        report_layout.addWidget(report_info)

        options = QtWidgets.QGroupBox('Opsiyonel Alanlar')
        form = QtWidgets.QFormLayout(options)
        include_ip = QtWidgets.QCheckBox('IP + ASN + ISP + Ağ')
        include_ip.setChecked(True)
        include_geo = QtWidgets.QCheckBox('Ülke kodu + Koordinat')
        include_geo.setChecked(True)
        include_ua = QtWidgets.QCheckBox('User Agent')
        include_ua.setChecked(False)
        include_metrics = QtWidgets.QCheckBox('Scroll / Vurgu / Form / Medya metrikleri')
        include_metrics.setChecked(True)
        orientation_box = QtWidgets.QComboBox()
        orientation_box.addItem('Dikey (A4)', 'portrait')
        orientation_box.addItem('Yatay (A4)', 'landscape')
        form.addRow(include_ip)
        form.addRow(include_geo)
        form.addRow(include_ua)
        form.addRow(include_metrics)
        form.addRow('Sayfa yönü', orientation_box)
        report_layout.addWidget(options)

        export_btn = QtWidgets.QPushButton('PDF olarak dışa aktar')
        export_btn.setStyleSheet('padding:8px 12px; font-weight:bold;')
        report_layout.addWidget(export_btn, alignment=QtCore.Qt.AlignmentFlag.AlignRight)

        tabs.addTab(stats_tab, 'İstatistikler')
        tabs.addTab(report_tab, 'Rapor Ayarları')

        state = {
            'page': 1,
            'stats': None,
            'history': {},
            'chart': site_chart,
            'range_box': site_range,
            'report_settings': {
                'ip': include_ip.isChecked(),
                'geo': include_geo.isChecked(),
                'ua': include_ua.isChecked(),
                'metrics': include_metrics.isChecked(),
                'orientation': orientation_box.currentData(),
            },
        }

        def _update_report_settings():
            state['report_settings']['ip'] = include_ip.isChecked()
            state['report_settings']['geo'] = include_geo.isChecked()
            state['report_settings']['ua'] = include_ua.isChecked()
            state['report_settings']['metrics'] = include_metrics.isChecked()
            state['report_settings']['orientation'] = orientation_box.currentData()

        for cb in (include_ip, include_geo, include_ua, include_metrics):
            cb.toggled.connect(_update_report_settings)
        orientation_box.currentIndexChanged.connect(_update_report_settings)

        def render_chart():
            history = state.get('history', {}) or {}
            period = state['range_box'].currentData()
            visit_rows = history.get('charts', {}).get(period, [])
            point_rows = history.get('point_charts', {}).get(period, [])
            categories: List[str] = []
            visits: List[int] = []
            spent_vals: List[int] = []
            max_len = max(len(visit_rows), len(point_rows)) or 1
            for idx in range(max_len):
                v_row = visit_rows[idx] if idx < len(visit_rows) else {}
                p_row = point_rows[idx] if idx < len(point_rows) else {}
                categories.append(str(v_row.get('label') or p_row.get('label') or idx))
                visits.append(int(v_row.get('visits', 0) or 0))
                spent_vals.append(int(p_row.get('spent', 0) or 0))

            series_defs = [
                {
                    'name': 'Ziyaret',
                    'color': '#0ea5e9',
                    'values': visits,
                    'enabled': visit_toggle.isChecked(),
                },
                {
                    'name': 'Harcanan',
                    'color': '#ef4444',
                    'values': spent_vals,
                    'enabled': spent_toggle.isChecked(),
                },
            ]
            self._render_chart_generic(site_chart, categories, series_defs, 'bar')

        site_range.currentIndexChanged.connect(render_chart)
        visit_toggle.toggled.connect(render_chart)
        spent_toggle.toggled.connect(render_chart)

        def render(stats: dict):
            state['stats'] = stats
            summary = stats.get('summary', {})
            points = stats.get('points', {})
            summary_label.setText(
                f"Toplam ziyaret: {summary.get('total_visits', 0)}"
            )
            points_label.setText(
                f"Puanlar — Harcanan: {points.get('spent', 0)}"
            )

            state['history'] = stats
            render_chart()

            events = stats.get('events', [])
            def _fmt_date(val: str) -> str:
                try:
                    return datetime.fromisoformat(str(val)).strftime('%d/%m/%Y %H:%M')
                except Exception:
                    return str(val)
            table.setRowCount(len(events))
            for r, ev in enumerate(events):
                row_items = [
                    _fmt_date(ev.get('created_at', '')),
                    ev.get('surfer_name') or ev.get('surfer_email') or '',
                    ev.get('country', ''),
                    ev.get('city', ''),
                    ev.get('platform', ''),
                    ev.get('device', ''),
                    str(ev.get('visits', ev.get('pages', 1) or 1)),
                    str(ev.get('scrolls', 0)),
                    str(ev.get('highlights', 0)),
                    str(ev.get('forms', 0)),
                    str(ev.get('media', 0)),
                ]
                for c, text in enumerate(row_items):
                    table.setItem(r, c, QtWidgets.QTableWidgetItem(text))
            table.resizeColumnsToContents()

            pagination = stats.get('pagination', {})
            page = int(pagination.get('page', 1))
            total_pages = int(pagination.get('total_pages', 1))
            page_label.setText(f'Sayfa {page}/{total_pages}')
            prev_btn.setEnabled(page > 1)
            next_btn.setEnabled(page < total_pages)
            state['page'] = page

            # rapor ayarlarını güncelle
            state['report_settings']['ip'] = include_ip.isChecked()
            state['report_settings']['geo'] = include_geo.isChecked()
            state['report_settings']['ua'] = include_ua.isChecked()
            state['report_settings']['metrics'] = include_metrics.isChecked()

        def load(page: int = 1):
            try:
                stats = self.client.site_stats(self.token, int(site_id), page)
                render(stats)
            except Exception as exc:  # noqa: BLE001
                self._toast(f'Detay yüklenemedi: {exc}', error=True)

        prev_btn.clicked.connect(lambda: load(max(1, state['page'] - 1)))
        next_btn.clicked.connect(lambda: load(state['page'] + 1))
        export_btn.clicked.connect(lambda: self._export_stats_pdf(state['stats'] or {}, state['report_settings'], state['range_box'].currentData()))

        load(1)
        dialog.exec()

    def _export_stats_pdf(self, stats: dict, settings: Optional[dict] = None, period: str = 'daily'):
        if not SimpleDocTemplate:
            self._toast('PDF modülü yüklü değil (reportlab)', error=True)
            return
        if not stats:
            self._toast('Önce istatistik yükleyin', error=True)
            return
        filename, _ = QtWidgets.QFileDialog.getSaveFileName(self, 'PDF kaydet', 'site-raporu.pdf', 'PDF Files (*.pdf)')
        if not filename:
            return
        orientation = (settings or {}).get('orientation') or 'portrait'
        page_size = A4 if orientation == 'portrait' else landscape(A4)
        doc = SimpleDocTemplate(filename, pagesize=page_size, leftMargin=18 * mm, rightMargin=18 * mm)
        font_name = 'Helvetica'
        font_bold = 'Helvetica-Bold'
        assets_dir = Path(__file__).resolve().parent / 'assets'
        try:
            regular = assets_dir / 'DejaVuSans.ttf'
            bold = assets_dir / 'DejaVuSans-Bold.ttf'
            if regular.exists():
                pdfmetrics.registerFont(TTFont('NoaSoftFont', str(regular)))
                if bold.exists():
                    pdfmetrics.registerFont(TTFont('NoaSoftFont-Bold', str(bold)))
                    font_bold = 'NoaSoftFont-Bold'
                font_name = 'NoaSoftFont'
        except Exception:
            pass
        settings = settings or {}
        style = ParagraphStyle('body', fontName=font_name, fontSize=10, leading=14)
        table_style = ParagraphStyle('table', fontName=font_name, fontSize=8, leading=10)
        period_title = {'daily': 'Günlük', 'weekly': 'Haftalık', 'monthly': 'Aylık'}.get(period, period)
        story = [Paragraph(f'<b>NoaSoft Autosurf Site Raporu ({period_title})</b>', ParagraphStyle('title', fontName=font_bold, fontSize=14))]
        story.append(Spacer(1, 8))
        summary = stats.get('summary', {})
        points = stats.get('points', {})
        story.append(Paragraph(f"Toplam ziyaret: {summary.get('total_visits', 0)}", style))
        story.append(Paragraph(
            f"Puanlar — Harcanan: {points.get('spent', 0)}",
            style,
        ))
        story.append(Spacer(1, 6))

        chart_rows = (stats.get('charts', {}) or {}).get(period, [])
        point_rows = (stats.get('point_charts', {}) or {}).get(period, [])
        if chart_rows or point_rows:
            visit_total = sum(int(r.get('visits', 0) or 0) for r in chart_rows)
            earned_total = sum(int(r.get('earned', 0) or 0) for r in point_rows)
            spent_total = sum(int(r.get('spent', 0) or 0) for r in point_rows)
            story.append(
                Paragraph(
                    f"Seçilen dönem ({period_title}) — Ziyaret: {visit_total} • Kazanılan: {earned_total} • Harcanan: {spent_total}",
                    style,
                )
            )
            story.append(Spacer(1, 4))

        include_ip = bool(settings.get('ip'))
        include_geo = bool(settings.get('geo'))
        include_ua = bool(settings.get('ua'))
        include_metrics = bool(settings.get('metrics', True))

        def _fmt_date(val: str) -> str:
            try:
                return datetime.fromisoformat(str(val)).strftime('%d/%m/%Y %H:%M')
            except Exception:
                return str(val)

        headers: List[str] = ['Tarih', 'Ziyaretçi', 'Ülke', 'Şehir', 'Platform', 'Cihaz', 'Ziyaret']
        if include_metrics:
            headers.extend(['Scroll', 'Vurgu', 'Form', 'Medya'])
        if include_geo:
            headers.extend(['Ülke Kod', 'Koordinat'])
        if include_ip:
            headers.extend(['IP', 'ASN', 'ISP', 'Ağ'])
        if include_ua:
            headers.append('User Agent')

        table_data: List[List[Any]] = [headers]
        for ev in stats.get('events', [])[:80]:
            row: List[Any] = [
                _fmt_date(ev.get('created_at', '')),
                ev.get('surfer_name') or ev.get('surfer_email') or '',
                ev.get('country', ''),
                ev.get('city', ''),
                ev.get('platform', ''),
                ev.get('device', ''),
                str(ev.get('visits', ev.get('pages', 1) or 1)),
            ]
            if include_metrics:
                row.extend([
                    str(ev.get('scrolls', 0)),
                    str(ev.get('highlights', 0)),
                    str(ev.get('forms', 0)),
                    str(ev.get('media', 0)),
                ])
            if include_geo:
                coord_txt = ''
                if ev.get('latitude') is not None and ev.get('longitude') is not None:
                    coord_txt = f"{ev.get('latitude'):.3f}, {ev.get('longitude'):.3f}"
                row.extend([
                    ev.get('country_code', ''),
                    coord_txt,
                ])
            if include_ip:
                row.extend([
                    ev.get('ip', ''),
                    str(ev.get('asn', '') or ''),
                    ev.get('isp', '') or '',
                    ev.get('network', '') or '',
                ])
            if include_ua:
                row.append(ev.get('user_agent', ''))

            # Wrap long cells to avoid taşma
            wrapped = [Paragraph(str(cell), table_style) for cell in row]
            table_data.append(wrapped)

        col_widths = None
        try:
            # Basit genişlik ayarı: uzun metinler için daha geniş sütunlar
            base = doc.width
            col_count = len(headers)
            min_width = base / col_count
            widths: List[float] = []
            for head in headers:
                if head in {'User Agent', 'ISP', 'Ağ'}:
                    widths.append(min_width * 1.4)
                elif head in {'Koordinat'}:
                    widths.append(min_width * 1.1)
                else:
                    widths.append(min_width * 0.9)
            col_widths = widths
        except Exception:
            col_widths = None

        table = Table(table_data, repeatRows=1, colWidths=col_widths)
        table.setStyle(TableStyle([
            ('FONT', (0, 0), (-1, -1), font_name),
            ('BACKGROUND', (0, 0), (-1, 0), colors.HexColor('#0f172a')),
            ('TEXTCOLOR', (0, 0), (-1, 0), colors.white),
            ('GRID', (0, 0), (-1, -1), 0.25, colors.grey),
            ('BACKGROUND', (0, 1), (-1, -1), colors.whitesmoke),
            ('VALIGN', (0, 0), (-1, -1), 'TOP'),
        ]))
        story.append(table)
        story.append(Spacer(1, 8))

        point_table = Table([
            ['Harcanan'],
            [str(points.get('spent', 0))],
        ])
        point_table.setStyle(TableStyle([
            ('FONT', (0, 0), (-1, -1), font_name),
            ('BACKGROUND', (0, 0), (-1, 0), colors.HexColor('#0ea5e9')),
            ('TEXTCOLOR', (0, 0), (-1, 0), colors.white),
            ('GRID', (0, 0), (-1, -1), 0.25, colors.grey),
        ]))
        story.append(point_table)
        story.append(Spacer(1, 12))
        story.append(Paragraph(f'Oluşturulma: {datetime.now().strftime("%d.%m.%Y %H:%M")}', style))
        doc.build(story)
        self._toast('PDF oluşturuldu')

    def start_surf(self):
        if not self.client or not self.token:
            self._toast('Önce giriş yapın')
            return
        if self.worker_thread and self.worker_thread.isRunning():
            self._toast('Zaten çalışıyor')
            return
        limits = getattr(self, 'reward_limits', {}) or {}
        exhausted = [
            name
            for name, value in (
                ('Günlük', limits.get('daily_left')),
                ('Haftalık', limits.get('weekly_left')),
                ('Aylık', limits.get('monthly_left')),
            )
            if value is not None and value <= 0
        ]
        if exhausted:
            self._toast(f"{', '.join(exhausted)} limitiniz dolu, lütfen daha sonra deneyin.", error=True)
            return
        self._launch_worker('surf', {'task_points': self.task_points})

    def _collect_flags(self, box: dict) -> dict:
        flags = {key: cb.isChecked() for key, cb in box['flags'].items()}
        media_actions = [key for key, cb in box.get('media', {}).items() if cb.isChecked()]
        flags['media'] = bool(media_actions)
        flags['media_actions'] = media_actions
        return flags

    def _clear_layout(self, layout: QtWidgets.QLayout):
        while layout.count():
            item = layout.takeAt(0)
            widget = item.widget()
            if widget:
                widget.deleteLater()
            elif item.layout():
                self._clear_layout(item.layout())

    def _launch_worker(self, mode: str, config: dict):
        self._init_client()
        self._stop_worker(force=True)
        self.progress.setValue(0)
        self.countdown_label.setText('Kalan süre: 0 sn / 0 sn')
        self.live_view.setPixmap(QtGui.QPixmap())
        self.live_view.setText('Chromium açılıyor...')
        self.start_btn.setEnabled(False)
        ad_html = None
        if isinstance(self.task_points, dict):
            ad_html = self.task_points.get('ad_banner_html')
        if not ad_html:
            ad_html = '<a href="https://noasoft.org" target="_blank"><img src="https://placehold.co/1200x90/1A1A1A/FFFFFF?text=Reklam+Alan%C4%B1" style="width:100%;max-width:1200px;"></a>'
        config = {**config, 'ad_banner_html': ad_html}
        self.worker_thread = QtCore.QThread()
        self.worker = SurfWorker(
            self.token,
            self.client,
            mode=mode,
            task_config=config,
            current_email=self.current_email or self.login_email.text() or self.reg_email.text(),
        )
        self.worker.moveToThread(self.worker_thread)
        self.worker_thread.started.connect(self.worker.run)
        self.worker.progress.connect(self._on_progress)
        self.worker.step_changed.connect(self._on_step)
        self.worker.finished.connect(self._on_finished)
        self.worker.failed.connect(self._on_failed)
        self.worker.log.connect(self._append_log)
        self.worker.frame.connect(self._on_frame)
        self.worker.finished.connect(self.worker_thread.quit)
        self.worker.failed.connect(self.worker_thread.quit)
        self.worker_thread.finished.connect(lambda: self.start_btn.setEnabled(True))
        self.worker_thread.finished.connect(self._cleanup_worker)
        self.worker_thread.start()

    def cancel_surf(self):
        self._stop_worker(force=True)
        self.start_btn.setEnabled(True)
        self._toast('Surf iptal komutu gönderildi')

    def _stop_worker(self, force: bool = False):
        if self.worker:
            try:
                self.worker.stop()
            except Exception:
                pass
        if self.worker_thread:
            try:
                self.worker_thread.quit()
                wait_ms = 3000 if force else 1000
                self.worker_thread.wait(wait_ms)
            except Exception:
                pass
        self.worker = None
        self.worker_thread = None
        if hasattr(self, 'live_view'):
            self.live_view.setPixmap(QtGui.QPixmap())
            self.live_view.setText('Durum: bekliyor')

    def _cleanup_worker(self):
        self.worker = None
        self.worker_thread = None

    def _on_progress(self, percent: int, elapsed: int, total: int, detail: str):
        self.progress.setValue(percent)
        self.countdown_label.setText(f'Kalan süre: {elapsed} sn / {total} sn')

    def _on_step(self, detail: str):
        self.status_label.setText(detail)

    def _on_frame(self, data: bytes, x: int, y: int):
        if not hasattr(self, 'live_view'):
            return
        pixmap = QtGui.QPixmap()
        pixmap.loadFromData(data)
        if x >= 0 and y >= 0:
            painter = QtGui.QPainter(pixmap)
            pen = QtGui.QPen(QtGui.QColor('#10b981'))
            pen.setWidth(6)
            painter.setPen(pen)
            painter.drawEllipse(QtCore.QPoint(x, y), 10, 10)
            painter.end()
        scaled = pixmap.scaled(
            self.live_view.size(),
            QtCore.Qt.AspectRatioMode.KeepAspectRatio,
            QtCore.Qt.TransformationMode.SmoothTransformation,
        )
        self.live_view.setPixmap(scaled)
        self.live_view.setText('')

    def _on_finished(self, earned: int):
        self._toast(f'Oturum tamamlandı +{earned} puan')
        self.earnings_label.setText(f'Kazanılan: {earned} puan')
        self.refresh_dashboard()

    def _on_failed(self, message: str):
        self._toast(f'Hata: {message}', error=True)
        self.start_btn.setEnabled(True)

    def _refresh_live_data(self):
        if not self.client or not self.token:
            return
        self.refresh_dashboard()
        self.refresh_system_stats()

    def load_mail_settings(self):
        if not self.client or not self.token:
            return
        try:
            config = self.client.mail_settings()
            to_addr = config.get('to') or config.get('email') or 'info@noasoft.org'
            self.mail_to.setText(to_addr)
        except Exception:
            self.mail_to.setText('info@noasoft.org')

    def send_mail(self):
        if not self.client or not self.token:
            self._toast('Önce giriş yapın')
            return
        subject = self.mail_subject.text().strip() or 'Autosurf talebi'
        body = self.mail_message.toPlainText().strip()
        if not body:
            self._toast('Mesaj içeriği boş olamaz', error=True)
            return
        payload = {
            'to': self.mail_to.text(),
            'subject': subject,
            'body': body,
            'email': self.current_email or self.login_email.text() or self.reg_email.text(),
        }
        try:
            self.client.send_mail(self.token, payload)
            self._toast('Mesaj iletildi')
        except Exception as exc:
            self._toast(f'Gönderim hatası: {exc}', error=True)

    def _copy_log(self):
        clipboard = QtWidgets.QApplication.clipboard()
        clipboard.setText(self.log_box.toPlainText())

    def _append_log(self, message: str):
        timestamp = QtCore.QDateTime.currentDateTime().toString('hh:mm:ss')
        if hasattr(self, 'log_box'):
            self.log_box.appendPlainText(f'[{timestamp}] {message}')

    def _toast(self, message: str, error: bool = False):
        color = '#ef4444' if error else '#10b981'
        self.status_label.setStyleSheet(f'font-weight:bold; color:{color};')
        self.status_label.setText(message)
        self._append_log(message)


def main():
    import sys
    app = QtWidgets.QApplication(sys.argv)
    window = SurfApp()
    window.show()
    sys.exit(app.exec())


if __name__ == '__main__':
    main()
