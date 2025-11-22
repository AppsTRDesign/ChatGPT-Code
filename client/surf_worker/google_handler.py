import requests
from typing import Dict, Tuple


class GoogleHandler:
    def __init__(self, browser_manager, plan_engine, log):
        self.browser_manager = browser_manager
        self.plan_engine = plan_engine
        self.log = log

    def perform_google(self, playwright, cfg: dict, flags: dict, personality, apply_actions) -> Tuple[int, int, Dict, str]:
        dwell = int(cfg.get('dwell', 30))
        pages = max(1, int(cfg.get('pages', 1)))
        keyword = cfg.get('keyword', '')
        site_url = cfg.get('site_url', '')
        country = cfg.get('country', 'com')
        host = f'https://www.google.{country}'
        self.log(f'Google araması başlıyor ({country})')
        page = self.browser_manager.ensure_page(playwright, flags)
        page.goto(f'{host}/search?q={requests.utils.quote(keyword)}&hl=en&gl={country}', wait_until='domcontentloaded')
        found = False
        visited_pages = 1
        target = site_url.replace('https://', '').replace('http://', '')
        for _ in range(pages):
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
            raise RuntimeError('Site bulunamadı, sonuçlarda yok')
        plan, site_flags = self.plan_engine.build_custom_plan(dwell, {**flags})
        consumed, metrics = apply_actions(playwright, {**site_flags, 'url': page.url}, plan, personality)
        return consumed, visited_pages, metrics, page.url


__all__ = ["GoogleHandler"]
