import random
import string
import time
from dataclasses import dataclass
from typing import Dict, List, Optional, Tuple

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

    def register(self, email: str, password: str, name: str, password_confirm: str):
        resp = self._request('POST', '/auth/register', json={
            'email': email,
            'password': password,
            'password_confirm': password_confirm,
            'name': name,
        })
        return self._json(resp)

    def login(self, email: str, password: str):
        resp = self._request('POST', '/auth/login', json={'email': email, 'password': password})
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

    def list_sites(self, token: str, page: int = 1):
        resp = self._request('GET', '/sites', token=token, params={'page': page})
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

    def complete_surf(self, token: str, session_id: int, consumed_seconds: int):
        resp = self._request(
            'POST',
            '/surf/complete',
            token=token,
            json={'session_id': session_id, 'consumed_seconds': consumed_seconds},
        )
        return self._json(resp)

    def dashboard(self, token: str):
        resp = self._request('GET', '/dashboard', token=token)
        return self._json(resp)

    def points_history(self, token: str):
        resp = self._request('GET', '/dashboard/history', token=token)
        return self._json(resp).get('history', [])

    def mail_settings(self):
        resp = self._request('GET', '/mail/settings')
        return self._json(resp)

    def send_mail(self, token: str, payload: dict):
        resp = self._request('POST', '/mail/send', token=token, json=payload)
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
    ):
        super().__init__(parent)
        self.token = token
        self.client = client
        self._running = True
        self.browser = None
        self.context = None
        self.page = None
        self._current_mobile = False
        self.mode = mode
        self.task_config = task_config or {}

    def stop(self):
        self._running = False

    def _ensure_page(self, playwright: Playwright, site: dict):
        mobile = bool(site.get('mobile'))
        if self.browser is None:
            self.browser = playwright.chromium.launch(headless=False)
        if self.context is None or self.page is None or self.page.is_closed() or self._current_mobile != mobile:
            if self.context:
                try:
                    self.context.close()
                except Exception:
                    pass
            context_kwargs: Dict[str, object] = {}
            if mobile:
                context_kwargs['user_agent'] = (
                    'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/605.1.15 '
                    '(KHTML, like Gecko) Version/15.0 Mobile/15E148 Safari/604.1'
                )
                context_kwargs['viewport'] = {'width': 390, 'height': 844}
            self.context = self.browser.new_context(**context_kwargs)
            self.page = self.context.new_page()
            self._inject_pointer_overlay(self.page)
            self._current_mobile = mobile
        return self.page

    def _inject_pointer_overlay(self, page):
        try:
            page.add_init_script(
                """
                (() => {
                  const dot = document.createElement('div');
                  dot.id = 'noasoft-pointer';
                  Object.assign(dot.style, {
                    position: 'fixed', width: '16px', height: '16px',
                    borderRadius: '50%', background: 'rgba(16,185,129,0.85)',
                    boxShadow: '0 0 12px rgba(16,185,129,0.8)',
                    zIndex: 2147483647, pointerEvents: 'none',
                    transform: 'translate(-50%,-50%)',
                  });
                  document.addEventListener('mousemove', ev => {
                    dot.style.left = ev.clientX + 'px';
                    dot.style.top = ev.clientY + 'px';
                  });
                  document.body.appendChild(dot);
                })();
                """
            )
        except Exception:
            pass

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
        if flags['realistic'] and remaining > 0:
            steps.append(SurfPlanStep('Metin seçimi', 'Paragrafları işaretle ve kopyala', min(remaining, 5)))
            remaining -= min(remaining, 5)
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
        page = self._ensure_page(playwright, site)
        url = site.get('url')
        self.log.emit(f'Sayfa açılıyor: {url}')
        page.goto(url, wait_until='domcontentloaded', timeout=30000)

        planned_total = max(1, sum(s.seconds for s in plan))
        elapsed = 0
        viewport = page.viewport_size or {'width': 1280, 'height': 720}
        last_mouse: Optional[Tuple[int, int]] = None
        host = urlparse(url).netloc
        runtime_target = 0
        for step in plan:
            if not self._running:
                break
            detail = f"{step.title} — {step.detail}"
            self.step_changed.emit(detail)
            self.log.emit(detail)
            performed = False
            try:
                if 'mouse' in step.title.lower():
                    performed, last_mouse = self._simulate_mouse_moves(page, viewport)
                if 'metin' in step.title.lower():
                    performed = self._simulate_text_highlight(page) or performed
                if 'scroll' in step.title.lower():
                    performed = self._simulate_scroll(page, viewport) or performed
                if 'tıklamalar' in step.title.lower():
                    performed = self._simulate_clicks(page, host) or performed
                if 'form' in step.title.lower():
                    performed = self._simulate_form(page) or performed
                if 'medya' in step.title.lower():
                    actions = site.get('media_actions') or site.get('media_options') or []
                    performed = self._simulate_media(page, actions) or performed
                if 'sayfada' in step.title.lower():
                    performed = True  # sadece bekleme adımı
            except Exception as action_err:
                self.log.emit(f'Eylem hatası: {action_err}')

            self._emit_frame(page, last_mouse)

            duration = step.seconds if performed or 'sayfa' in step.title.lower() or 'bekleme' in step.title.lower() else 0
            runtime_target += duration
            for _ in range(duration):
                if not self._running:
                    break
                elapsed += 1
                total_seconds = max(1, planned_total)
                percent = min(100, int((elapsed / total_seconds) * 100))
                self.progress.emit(percent, elapsed, total_seconds, detail)
                page.wait_for_timeout(1000)
            self._emit_frame(page, last_mouse)
        if elapsed and elapsed < planned_total:
            self.progress.emit(int((elapsed / planned_total) * 100), elapsed, planned_total, 'Plan tamamlandı')
        self.progress.emit(100, elapsed or runtime_target, max(planned_total, runtime_target, 1), 'Tamamlandı')
        return elapsed

    def _simulate_mouse_moves(self, page, viewport: Dict[str, int]) -> Tuple[bool, Optional[Tuple[int, int]]]:
        last_mouse = None
        for _ in range(4):
            x = random.randint(40, viewport['width'] - 40)
            y = random.randint(40, viewport['height'] - 40)
            page.mouse.move(x, y, steps=25)
            last_mouse = (x, y)
        return bool(last_mouse), last_mouse

    def _simulate_scroll(self, page, viewport: Dict[str, int]) -> bool:
        page.mouse.wheel(0, viewport['height'])
        page.wait_for_timeout(400)
        page.mouse.wheel(0, -viewport['height'] // 2)
        return True

    def _simulate_clicks(self, page, host: str) -> bool:
        links = []
        for lnk in page.query_selector_all('a[href]'):
            if not lnk.is_visible():
                continue
            href = lnk.get_attribute('href') or ''
            if href.startswith('#'):
                continue
            target = (lnk.get_attribute('target') or '').lower()
            if target == '_blank':
                continue
            if host and host not in href and href.startswith('http'):
                continue
            links.append(lnk)
        if not links:
            return False
        random.choice(links).click(timeout=5000)
        return True

    def _simulate_text_highlight(self, page) -> bool:
        candidates = page.query_selector_all('p, h1, h2, h3, h4')
        visible = [el for el in candidates if el.is_visible() and (el.text_content() or '').strip()]
        if not visible:
            return False
        target = random.choice(visible)
        box = target.bounding_box()
        if not box:
            return False
        start_x = int(box['x'] + 5)
        start_y = int(box['y'] + box['height'] / 2)
        end_x = int(box['x'] + box['width'] - 5)
        end_y = start_y
        page.mouse.move(start_x, start_y)
        page.mouse.down()
        page.mouse.move(end_x, end_y, steps=20)
        page.mouse.up()
        target.evaluate("el => el.style.textDecoration = 'line-through'")
        page.keyboard.press('Control+C')
        return True

    def _simulate_form(self, page) -> bool:
        fields = [inp for inp in page.query_selector_all('input,textarea') if inp.is_visible()]
        if not fields:
            return False
        target = random.choice(fields)
        target.click()
        filler = 'NoaSoft ' + ''.join(random.choice(string.ascii_letters) for _ in range(6))
        target.fill(filler)
        page.keyboard.press('Control+A')
        page.keyboard.press('Control+C')
        page.keyboard.press('Backspace')
        return True

    def _simulate_media(self, page, actions: List[str]) -> bool:
        media = page.query_selector('video, audio')
        if not media:
            return False
        if not actions:
            actions = ['hover', 'delay', 'human_click', 'pause_play', 'volume', 'fullscreen', 'quality']
        try:
            media.hover()
            page.wait_for_timeout(400 if 'delay' in actions else 200)
            if 'human_click' in actions:
                media.click()
            if 'pause_play' in actions:
                page.keyboard.press('Space')
            if 'fullscreen' in actions:
                page.keyboard.press('KeyF')
            if 'volume' in actions:
                page.keyboard.press('ArrowUp')
            if 'quality' in actions:
                quality_menu = page.query_selector('button[aria-label*="quality" i], [class*="quality"]')
                if quality_menu:
                    quality_menu.click()
            return True
        except Exception as exc:
            self.log.emit(f'Medya etkileşimi atlandı: {exc}')
            return False

    def _emit_frame(self, page, last_mouse: Optional[Tuple[int, int]]):
        try:
            if not page or page.is_closed():
                return
            data = page.screenshot(full_page=False)
            x, y = last_mouse or (-1, -1)
            self.frame.emit(data, x, y)
        except Exception as exc:
            self.log.emit(f'Görüntü yakalama hatası: {exc}')

    def _build_custom_plan(self, dwell: int, flags: dict) -> List[SurfPlanStep]:
        site = {
            'dwell_seconds': dwell,
            'mobile': flags.get('mobile'),
            'realistic': flags.get('realistic'),
            'mouse_moves': flags.get('mouse_moves'),
            'link_clicks': flags.get('link_clicks'),
            'scroll': flags.get('scroll'),
            'form_fill': flags.get('form_fill'),
            'media': flags.get('media'),
            'media_actions': flags.get('media_actions', []),
        }
        return self._build_plan({'plan': [], 'site': site})[0], site

    def _run_google_task(self, playwright: Playwright):
        cfg = self.task_config
        dwell = int(cfg.get('dwell', 30))
        pages = max(1, int(cfg.get('pages', 1)))
        keyword = cfg.get('keyword', '')
        site_url = cfg.get('site_url', '')
        country = cfg.get('country', 'com')
        host = f'https://www.google.{country}'
        self.log.emit(f'Google araması başlıyor ({country})')
        page = self._ensure_page(playwright, cfg)
        page.goto(f'{host}/search?q={requests.utils.quote(keyword)}&hl=en&gl={country}', wait_until='domcontentloaded')
        found = False
        visited_pages = 1
        target = site_url.replace('https://', '').replace('http://', '')
        for idx in range(pages):
            if not self._running:
                break
            links = page.query_selector_all('a[href]')
            for lnk in links:
                href = lnk.get_attribute('href') or ''
                if target and target in href and 'google' not in href:
                    lnk.click()
                    found = True
                    break
            if found:
                break
            next_btn = page.query_selector('a#pnnext, a[aria-label="Sonraki"], a[aria-label="Next"]')
            if next_btn:
                visited_pages += 1
                next_btn.click()
                page.wait_for_timeout(800)
            else:
                break
        if not found:
            self.failed.emit('Site bulunamadı, sonuçlarda yok')
            return
        plan, site_flags = self._build_custom_plan(dwell, cfg)
        consumed = self._apply_actions(playwright, {**site_flags, 'url': page.url}, plan)
        earned = consumed + 50 + (visited_pages * 10)
        self.finished.emit(earned)

    def _run_youtube_task(self, playwright: Playwright):
        cfg = self.task_config
        dwell = int(cfg.get('dwell', 30))
        keyword = cfg.get('keyword', '')
        link = cfg.get('video_link', '')
        pages = max(1, int(cfg.get('pages', 1))) if keyword else 1
        page = self._ensure_page(playwright, cfg)
        if keyword:
            page.goto('https://www.youtube.com/', wait_until='domcontentloaded')
            search_box = page.query_selector('input#search')
            if search_box:
                search_box.fill(keyword)
                search_box.press('Enter')
                page.wait_for_timeout(1200)
            found = False
            visited = 1
            for idx in range(pages):
                for anchor in page.query_selector_all('a#video-title'):
                    href = anchor.get_attribute('href') or ''
                    if link and link.replace('https://www.youtube.com', '') in href:
                        anchor.click()
                        found = True
                        break
                if found:
                    break
                next_btn = page.query_selector('a[aria-label*="Sonraki"], a[aria-label*="Next"]')
                if next_btn:
                    visited += 1
                    next_btn.click()
                    page.wait_for_timeout(800)
                else:
                    break
            pages = visited
        if not keyword and link:
            page.goto(link, wait_until='domcontentloaded')
        plan, site_flags = self._build_custom_plan(dwell, cfg)
        site_flags['media'] = True
        plan.insert(0, SurfPlanStep('Video açılıyor', 'YouTube oynatma', 2))
        consumed = self._apply_actions(playwright, {**site_flags, 'url': page.url}, plan)
        earned = 50 + consumed + (pages * 10 if keyword else 0)
        self.finished.emit(earned)

    def run(self):
        try:
            with sync_playwright() as playwright:
                if self.mode == 'google':
                    self._run_google_task(playwright)
                elif self.mode == 'youtube':
                    self._run_youtube_task(playwright)
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
                        plan, site = self._build_plan(session)
                        session_id = session.get('session_id') or session.get('id')
                        consumed_seconds = self._apply_actions(playwright, site, plan)
                        if not self._running:
                            break
                        try:
                            result = self.client.complete_surf(self.token, int(session_id), consumed_seconds)
                            earned = int(result.get('earned', 0))
                            self.log.emit(f'Oturum tamamlandı: +{earned} puan')
                            self.finished.emit(earned)
                        except Exception as exc:  # noqa: BLE001
                            self.failed.emit(str(exc))
                            break
                if self.context:
                    try:
                        self.context.close()
                    except Exception:
                        pass
                if self.browser:
                    try:
                        self.browser.close()
                    except Exception:
                        pass
        except Exception as exc:  # noqa: BLE001
            self.failed.emit(f'Surf sırasında hata: {exc}')


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
        self.current_email: str = ''
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
        self.tabs.addTab(self._build_dashboard_tab(), 'Puan & Özet')
        self.tabs.addTab(self._build_sites_tab(), 'Siteler')
        self.tabs.addTab(self._build_surf_tab(), 'Surf + Puan')
        self.tabs.addTab(self._build_google_tab(), 'Google Görevi')
        self.tabs.addTab(self._build_youtube_tab(), 'YouTube Görevi')
        self.tabs.addTab(self._build_points_info_tab(), 'Puan Sistemi / Özellikler')
        self.tabs.addTab(self._build_mail_tab(), 'İletişim')
        self.tabs.addTab(self._build_log_tab(), 'Log')
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

        chart_row = QtWidgets.QHBoxLayout()
        self.daily_chart = self._build_chart('Günlük Kazanç')
        self.weekly_chart = self._build_chart('Haftalık Kazanç')
        chart_row.addWidget(self.daily_chart)
        chart_row.addWidget(self.weekly_chart)
        layout.addLayout(chart_row)

        return widget

    def _build_chart(self, title: str) -> QChartView:
        line = QLineSeries()
        line.setColor(QtGui.QColor('#ec4899'))
        bar_set = QBarSet('Toplam')
        bar_set.setColor(QtGui.QColor('#60a5fa'))
        bars = QBarSeries()
        bars.append(bar_set)
        chart = QChart()
        chart.addSeries(bars)
        chart.addSeries(line)
        axis_x = QBarCategoryAxis()
        axis_y = QValueAxis()
        axis_y.setLabelFormat('%d')
        chart.addAxis(axis_x, QtCore.Qt.AlignmentFlag.AlignBottom)
        chart.addAxis(axis_y, QtCore.Qt.AlignmentFlag.AlignLeft)
        bars.attachAxis(axis_x)
        bars.attachAxis(axis_y)
        line.attachAxis(axis_x)
        line.attachAxis(axis_y)
        chart.setTitle(title)
        chart.legend().setVisible(True)
        chart.legend().setLabelColor(QtGui.QColor('#0f172a'))
        view = QChartView(chart)
        view.setRenderHint(QtGui.QPainter.RenderHint.Antialiasing)
        view.setProperty('line_series', line)
        view.setProperty('bar_series', bars)
        view.setProperty('bar_set', bar_set)
        view.setProperty('axis_x', axis_x)
        view.setProperty('axis_y', axis_y)
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
        self.google_site = QtWidgets.QLineEdit()
        self.google_keyword = QtWidgets.QLineEdit()
        self.google_country = QtWidgets.QComboBox()
        countries = [('Global', 'com'), ('Brezilya', 'com.br'), ('Türkiye', 'com.tr'), ('ABD', 'com'), ('Almanya', 'de')]
        for name, code in countries:
            self.google_country.addItem(name, code)
        self.google_pages = QtWidgets.QSpinBox()
        self.google_pages.setRange(1, 10)
        self.google_pages.setValue(3)
        self.google_dwell = QtWidgets.QSpinBox()
        self.google_dwell.setRange(5, 900)
        self.google_dwell.setValue(30)

        self.google_action_boxes = self._build_action_checkboxes()
        form.addRow('Site Adresi', self.google_site)
        form.addRow('Arama Kelimesi', self.google_keyword)
        form.addRow('Ülke', self.google_country)
        form.addRow('Kaç Sayfa Tara', self.google_pages)
        form.addRow('Sitede Kalma (sn)', self.google_dwell)
        form.addRow(self.google_action_boxes['container'])

        btn = QtWidgets.QPushButton('Google Görevini Başlat')
        btn.clicked.connect(self.start_google_task)
        form.addRow(btn)
        return widget

    def _build_youtube_tab(self) -> QtWidgets.QWidget:
        widget = QtWidgets.QWidget()
        form = QtWidgets.QFormLayout(widget)
        self.youtube_keyword = QtWidgets.QLineEdit()
        self.youtube_video = QtWidgets.QLineEdit()
        self.youtube_pages = QtWidgets.QSpinBox()
        self.youtube_pages.setRange(1, 10)
        self.youtube_pages.setValue(2)
        self.youtube_dwell = QtWidgets.QSpinBox()
        self.youtube_dwell.setRange(5, 1200)
        self.youtube_dwell.setValue(60)
        self.youtube_action_boxes = self._build_action_checkboxes(include_media=True)

        form.addRow('Arama Kelimesi (opsiyonel)', self.youtube_keyword)
        form.addRow('Video Linki', self.youtube_video)
        form.addRow('Arama Sayfa Sayısı', self.youtube_pages)
        form.addRow('Video İzleme (sn)', self.youtube_dwell)
        form.addRow(self.youtube_action_boxes['container'])

        btn = QtWidgets.QPushButton('YouTube Görevini Başlat')
        btn.clicked.connect(self.start_youtube_task)
        form.addRow(btn)
        return widget

    def _build_points_info_tab(self) -> QtWidgets.QWidget:
        widget = QtWidgets.QWidget()
        layout = QtWidgets.QVBoxLayout(widget)
        desc = QtWidgets.QLabel('Kazanılabilecek / harcanacak puanların özeti:')
        desc.setStyleSheet('font-weight:bold; font-size:14px;')
        layout.addWidget(desc)
        table = QtWidgets.QTableWidget(0, 3)
        table.setHorizontalHeaderLabels(['İşlem', 'Puan', 'Açıklama'])
        rows = [
            ('Site ekleme', '-Süre', 'Siteyi eklerken süre kadar puan rezerv edilir'),
            ('Surf ödülü', '+Süre', 'Gezinme tamamlanınca süre kadar puan kazanılır'),
            ('Google görevi', '+50 + süre + (10*sayfa)', 'Arama + sitede kalma toplam kazanç'),
            ('YouTube görevi', '+50 + süre + (10*sayfa)', 'Arama aktifse sayfa başı eklenir'),
            ('Günlük limit', 'config', 'max_daily_reward, max_daily_site_visits ile sınırlanır'),
            ('Haftalık/Aylık limit', 'config', 'max_weekly_reward ve max_monthly_reward kontrol edilir'),
        ]
        table.setRowCount(len(rows))
        for idx, row in enumerate(rows):
            for col, val in enumerate(row):
                item = QtWidgets.QTableWidgetItem(val)
                table.setItem(idx, col, item)
        table.horizontalHeader().setStretchLastSection(True)
        layout.addWidget(table)
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
            resp = self.client.login(self.login_email.text(), self.login_password.text())
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
        self._append_log(f'API URL: {base_url}')

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
        limits = dash.get('limits') or {}
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
            history = []
        if not history:
            history = [{'label': 'Gün 1', 'daily': 0, 'weekly': 0}]
        self._fill_chart(self.daily_chart, [(item['label'], item.get('daily', 0)) for item in history])
        self._fill_chart(self.weekly_chart, [(item['label'], item.get('weekly', 0)) for item in history])

    def _fill_chart(self, view: QChartView, items: List[Tuple[str, int]]):
        if not items:
            items = [('0', 0)]
        line: QLineSeries = view.property('line_series')
        bars: QBarSeries = view.property('bar_series')
        bar_set: QBarSet = view.property('bar_set')
        axis_x: QBarCategoryAxis = view.property('axis_x')
        axis_y: QValueAxis = view.property('axis_y')
        line.clear()
        bar_set.remove(0, bar_set.count()) if bar_set.count() else None
        categories = []
        values = []
        for idx, (label, value) in enumerate(items):
            categories.append(label)
            values.append(value)
            line.append(idx, value)
            bar_set.append(value)
        axis_x.clear()
        axis_x.append(categories)
        min_val = min(values + [0])
        max_val = max(values + [0])
        pad = max(10, int((max_val - min_val) * 0.2) + 5)
        axis_y.setRange(min_val - pad, max_val + pad)
        view.chart().update()

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
            'media_actions': [key for key, cb in self.media_option_boxes.items() if cb.isChecked()],
        }
        try:
            self.client.create_site(self.token, payload)
            self._toast('Site eklendi/güncellendi ve puan düşüldü')
            self.load_sites()
            self.refresh_dashboard()
        except Exception as exc:
            self._toast(f'Kayıt hatası: {exc}', error=True)

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
            media_actions = site.get('media_actions') or site.get('media_options') or []
            if media_actions:
                settings.append('Medya: ' + ', '.join(media_actions))
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
        self._launch_worker('surf', {})

    def start_google_task(self):
        if not self.token:
            self._toast('Önce giriş yapın', error=True)
            return
        flags = self._collect_flags(self.google_action_boxes)
        config = {
            'url': self.google_site.text(),
            'site_url': self.google_site.text(),
            'keyword': self.google_keyword.text(),
            'country': self.google_country.currentData(),
            'pages': self.google_pages.value(),
            'dwell': self.google_dwell.value(),
            **flags,
        }
        self._launch_worker('google', config)

    def start_youtube_task(self):
        if not self.token:
            self._toast('Önce giriş yapın', error=True)
            return
        flags = self._collect_flags(self.youtube_action_boxes)
        config = {
            'url': self.youtube_video.text(),
            'video_link': self.youtube_video.text(),
            'keyword': self.youtube_keyword.text(),
            'pages': self.youtube_pages.value(),
            'dwell': self.youtube_dwell.value(),
            **flags,
        }
        self._launch_worker('youtube', config)

    def _collect_flags(self, box: dict) -> dict:
        flags = {key: cb.isChecked() for key, cb in box['flags'].items()}
        media_actions = [key for key, cb in box.get('media', {}).items() if cb.isChecked()]
        flags['media'] = bool(media_actions)
        flags['media_actions'] = media_actions
        return flags

    def _launch_worker(self, mode: str, config: dict):
        self._init_client()
        self.progress.setValue(0)
        self.countdown_label.setText('Kalan süre: 0 sn / 0 sn')
        self.live_view.setPixmap(QtGui.QPixmap())
        self.live_view.setText('Chromium açılıyor...')
        self.start_btn.setEnabled(False)
        self.worker_thread = QtCore.QThread()
        self.worker = SurfWorker(self.token, self.client, mode=mode, task_config=config)
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
        self.worker_thread.start()

    def cancel_surf(self):
        if self.worker:
            self.worker.stop()
        self.start_btn.setEnabled(True)
        self._toast('Surf iptal komutu gönderildi')

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
