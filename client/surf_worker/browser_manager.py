import json
from pathlib import Path
from typing import Dict, Optional


class BrowserManager:
    def __init__(self, assets_dir: Path, log, ad_html: Optional[str] = None):
        self.assets_dir = assets_dir
        self.log = log
        self.browser = None
        self.context = None
        self.page = None
        self._current_mobile = False
        self.ad_html = ad_html or (
            '<a href="https://noasoft.org" target="_blank">'
            '<img src="https://placehold.co/1200x90/1A1A1A/FFFFFF?text=Reklam+Alan%C4%B1" style="width:100%;max-width:1200px;">'
            '</a>'
        )

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
            self._inject_ad_banner(self.page)
            self._patch_fingerprints(self.page)
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

    def _inject_ad_banner(self, page):
        try:
            ad_html_json = json.dumps(self.ad_html)
            script = (
                "(() => {"
                "  try {"
                "    if (document.getElementById('noasoft-banner')) return;"
                "    const wrap = document.createElement('div');"
                "    wrap.id = 'noasoft-banner';"
                "    Object.assign(wrap.style, {position:'fixed',top:'0',left:'0',right:'0',background:'#0f172a',zIndex:2147483646,display:'flex',alignItems:'center',justifyContent:'center',padding:'6px',boxSizing:'border-box'});"
                "    const closeBtn = document.createElement('button');"
                "    closeBtn.innerText = '×';"
                "    Object.assign(closeBtn.style, {position:'absolute',right:'8px',top:'8px',background:'#ef4444',color:'#fff',border:'none',borderRadius:'4px',padding:'4px 8px',cursor:'pointer'});"
                "    closeBtn.addEventListener('click', () => wrap.remove());"
                "    const content = document.createElement('div');"
                f"    content.innerHTML = {ad_html_json};"
                "    Object.assign(content.style, {width:'100%',maxWidth:'1200px'});"
                "    wrap.appendChild(content);"
                "    wrap.appendChild(closeBtn);"
                "    const attach = () => { if (document.body && !document.getElementById('noasoft-banner')) document.body.prepend(wrap); };"
                "    document.addEventListener('DOMContentLoaded', attach);"
                "    attach();"
                "  } catch (e) { console.warn('Reklam injeksiyonu hatası', e); }"
                "})();"
            )
            page.add_init_script(script)
        except Exception:
            self.log('Reklam alanı enjekte edilemedi')

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
