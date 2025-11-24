import json
from pathlib import Path
from typing import Dict, Optional


class BrowserManager:
    def __init__(
        self,
        assets_dir: Path,
        log,
        cursor_style: str = 'cursor_1',
        cursor_primary: str = '#0f172a',
        cursor_secondary: str = '#e11d48',
    ):
        self.assets_dir = assets_dir
        self.log = log
        self.browser = None
        self.context = None
        self.page = None
        self._current_mobile = False
        self.cursor_style = cursor_style or 'cursor_1'
        self.cursor_primary = cursor_primary or '#0f172a'
        self.cursor_secondary = cursor_secondary or '#e11d48'

    def ensure_page(self, playwright, site: Dict) -> object:
        mobile = bool(site.get('mobile'))
        profile_dir = self.assets_dir / '.pw-profile'
        if self.context is None or self.page is None or self.page.is_closed() or self._current_mobile != mobile:
            self._close_context()
            context_kwargs: Dict[str, object] = {}
            if mobile:
                context_kwargs['user_agent'] = (
                    'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/605.1.15 '
                    '(KHTML, like Gecko) Version/15.0 Mobile/15E148 Safari/604.1'
                )
                context_kwargs['viewport'] = {'width': 390, 'height': 844}
            self.context = playwright.chromium.launch_persistent_context(
                user_data_dir=str(profile_dir), headless=False, **context_kwargs
            )
            self.browser = self.context
            self.page = self.context.new_page()
            self._inject_pointer_overlay(self.page)
            self._patch_fingerprints(self.page)
            self._current_mobile = mobile
        return self.page


    def _inject_pointer_overlay(self, page):
        try:
            cfg_json = json.dumps(
                {
                    'style': self.cursor_style,
                    'primary': self.cursor_primary,
                    'secondary': self.cursor_secondary,
                }
            )
            script = (
                "(() => {"
                "  const cfg = "
                + cfg_json
                + ";"
                "  const svgFor = () => {"
                "    if (cfg.style === 'cursor_2') return '<svg width=\"28\" height=\"28\" viewBox=\"0 0 28 28\" xmlns=\"http://www.w3.org/2000/svg\"><path d=\"M3 2.5 L24 13.5 L16 16 L13 25 Z\" fill=\"' + cfg.primary + '\" stroke=\"' + cfg.secondary + '\" stroke-width=\"2\" stroke-linejoin=\"round\" /></svg>';"
                "    if (cfg.style === 'cursor_3') return '<svg width=\"20\" height=\"20\" viewBox=\"0 0 20 20\" xmlns=\"http://www.w3.org/2000/svg\"><circle cx=\"10\" cy=\"10\" r=\"7\" fill=\"' + cfg.primary + '\" stroke=\"' + cfg.secondary + '\" stroke-width=\"2\" /></svg>';"
                "    return '<svg width=\"26\" height=\"26\" viewBox=\"0 0 26 26\" xmlns=\"http://www.w3.org/2000/svg\"><path d=\"M2 2 L20 13 L13 14 L11 23 Z\" fill=\"' + cfg.primary + '\" stroke=\"' + cfg.secondary + '\" stroke-width=\"2\" stroke-linejoin=\"round\" /></svg>';"
                "  };"
                "  const attach = () => {"
                "    if (document.getElementById('noasoft-pointer')) return;"
                "    const dot = document.createElement('div');"
                "    dot.id = 'noasoft-pointer';"
                "    Object.assign(dot.style, {position:'fixed',width:'26px',height:'26px',zIndex:2147483647,pointerEvents:'none',transform:'translate(-50%,-50%)'});"
                "    dot.innerHTML = svgFor();"
                "    try { (document.documentElement || document.body).style.cursor = 'none'; } catch (e) {}"
                "    document.addEventListener('mousemove', ev => {dot.style.left = ev.clientX + 'px'; dot.style.top = ev.clientY + 'px';});"
                "    (document.body || document.documentElement).appendChild(dot);"
                "  };"
                "  if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', attach, { once: true }); } else { attach(); }"
                "})();"
            )
            page.add_init_script(script)
        except Exception:
            self.log('İmleç overlay enjekte edilemedi')

    def attach_route_handler(self, pattern: str, handler):
        if not self.context:
            return None
        try:
            return self.context.route(pattern, handler)
        except Exception:
            return None

    def detach_route_handler(self, pattern: str):
        if not self.context:
            return
        try:
            self.context.unroute(pattern)
        except Exception:
            pass

    def _close_context(self):
        if self.context:
            try:
                self.context.close()
            except Exception:
                pass
        self.context = None
        self.page = None

    def close(self):
        self._close_context()
        if self.browser:
            try:
                self.browser.close()
            except Exception:
                pass
            self.browser = None

    def _patch_fingerprints(self, page):
        try:
            page.add_init_script(
                """
                (() => {
                  Object.defineProperty(navigator, 'webdriver', { get: () => false });
                  Object.defineProperty(navigator, 'plugins', { get: () => [1,2,3] });
                  Object.defineProperty(navigator, 'languages', { get: () => ['tr-TR','en-US','en'] });
                  try { navigator.permissions.query = (orig => (params) => orig(params).catch(() => ({state: 'granted'})))(navigator.permissions.query.bind(navigator.permissions)); } catch (e) {}
                })();
                """
            )
        except Exception:
            self.log('Fingerprint yamasi uygulanamadi')
