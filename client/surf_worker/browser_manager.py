import json
from pathlib import Path
from typing import Dict, Optional


class BrowserManager:
    def __init__(
        self,
        assets_dir: Path,
        log,
        ad_html: Optional[str] = None,
        ad_enabled: bool = True,
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
        self.ad_enabled = ad_enabled
        self.ad_html = ad_html or (
            '<a href="https://noasoft.org" target="_blank">'
            '<img src="https://placehold.co/1200x90/1A1A1A/FFFFFF?text=Reklam+Alan%C4%B1" style="width:100%;max-width:1200px;">'
            '</a>'
        )
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
            if self.ad_enabled:
                self._inject_ad_banner(self.page)
                self._inject_click_guard(self.page)
            else:
                self._inject_click_guard(self.page, banner_only=False)
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
                "    wrap.setAttribute('data-noasoft-banner', '1');"
                "    const attach = () => { if (document.body && !document.getElementById('noasoft-banner')) document.body.prepend(wrap); };"
                "    document.addEventListener('DOMContentLoaded', attach, { once: true });"
                "    if (document.readyState !== 'loading') attach();"
                "  } catch (e) { console.warn('Reklam injeksiyonu hatası', e); }"
                "})();"
            )
            page.add_init_script(script)
        except Exception:
            self.log('Reklam alanı enjekte edilemedi')

    def _inject_click_guard(self, page, banner_only: bool = True):
        try:
            block_script = (
                "(() => {"
                "  const bannerSel = '#noasoft-banner';"
                "  const allowBanner = el => el && (el.closest && el.closest(bannerSel));"
                "  document.addEventListener('click', (ev) => {"
                "    if (!ev.isTrusted) return;"
                "    const target = ev.target;"
                "    if (bannerSel && allowBanner(target)) return;"
                "    if (!" + ("true" if banner_only else "false") + ") { ev.preventDefault(); ev.stopImmediatePropagation(); return; }"
                "    ev.preventDefault(); ev.stopImmediatePropagation();"
                "  }, true);"
                "})();"
            )
            page.add_init_script(block_script)
        except Exception:
            self.log('Kullanıcı tıklama koruması eklenemedi')

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
