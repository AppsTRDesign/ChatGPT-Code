from pathlib import Path
from typing import Dict, Optional


class BrowserManager:
    def __init__(self, assets_dir: Path, log):
        self.assets_dir = assets_dir
        self.log = log
        self.browser = None
        self.context = None
        self.page = None
        self._current_mobile = False

    def ensure_page(self, playwright, site: Dict) -> object:
        mobile = bool(site.get('mobile'))
        if self.browser is None:
            self.browser = playwright.chromium.launch(headless=False)
        if self.context is None or self.page is None or self.page.is_closed() or self._current_mobile != mobile:
            self._close_context()
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


__all__ = ["BrowserManager"]
