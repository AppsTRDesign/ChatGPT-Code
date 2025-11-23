import random
import time
import requests
from typing import Dict, Tuple


class GoogleHandler:
    def __init__(self, browser_manager, plan_engine, log):
        self.browser_manager = browser_manager
        self.plan_engine = plan_engine
        self.log = log

    def _serp_hover(self, page, rng):
        try:
            cards = page.query_selector_all('div#search .g, div#search [data-sokoban-container]')
            if not cards:
                return
            card = rng.choice(cards)
            card.hover()
            snippets = card.query_selector_all('span, div')
            readable = [s for s in snippets if (s.text_content() or '').strip()]
            if readable and rng.random() < 0.55:
                target = rng.choice(readable)
                text = (target.text_content() or '')[:120]
                target.hover()
                page.mouse.down()
                page.mouse.up()
                if text:
                    page.keyboard.press('Control+C')
            if rng.random() < 0.35:
                page.wait_for_timeout(rng.randint(180, 520))
        except Exception:
            pass

    def _try_recaptcha(self, page, rng) -> bool:
        """
        Bazı sonuç sayfalarında çıkan reCAPTCHA kutusunu tıklamayı dener.
        Başarıyla kapatılırsa True, aksi halde False döner.
        """
        selectors = [
            'div.recaptcha-checkbox',
            '.rc-anchor-checkbox',
            '.recaptcha-checkbox-border',
            'iframe[src*="recaptcha"]',
        ]
        try:
            box = None
            for sel in selectors:
                box = page.query_selector(sel)
                if box:
                    break
            if not box:
                return True  # recaptcha yok
            # iframe içindeyse frame'e geç
            if box.tag_name().lower() == 'iframe':
                frame = box.content_frame()
                if frame:
                    inner = frame.query_selector('.recaptcha-checkbox-border')
                    if inner:
                        inner.click()
                        frame.wait_for_timeout(rng.randint(600, 1200))
                        return True
            box.click()
            page.wait_for_timeout(rng.randint(600, 1200))
            # kutu kayboldu mu kontrol et
            still = page.query_selector('.recaptcha-checkbox-unchecked, .rc-anchor-alert')
            return still is None
        except Exception:
            return False

    def perform_google(self, playwright, cfg: dict, flags: dict, personality, apply_actions) -> Tuple[int, int, Dict, str, bool, int]:
        dwell = int(cfg.get('dwell', 30))
        pages = max(1, int(cfg.get('pages', 1)))
        keyword = cfg.get('keyword', '')
        site_url = cfg.get('site_url', '')
        country = cfg.get('country', 'com')
        host = f'https://www.google.{country}'
        self.log(f'Google araması başlıyor ({country})')
        page = self.browser_manager.ensure_page(playwright, flags)
        rng = getattr(personality, 'rng', random.Random())
        page.goto(host, wait_until='domcontentloaded')
        recaptcha_blocked = False
        try:
            box = page.wait_for_selector('input[name="q"]', timeout=5000)
            box.click()
            for ch in keyword:
                box.type(ch, delay=rng.randint(40, 110))
            box.press('Enter')
            if not self._try_recaptcha(page, rng):
                self.log('reCAPTCHA çözülemedi, görev atlanıyor')
                recaptcha_blocked = True
        except Exception:
            page.goto(f'{host}/search?q={requests.utils.quote(keyword)}&hl=en&gl={country}', wait_until='domcontentloaded')

        found = False
        visited_pages = 1
        target = site_url.replace('https://', '').replace('http://', '')
        limit_ms = 90000

        start = page._impl_obj._loop.time() if hasattr(page, '_impl_obj') else time.time()
        search_start = time.monotonic()
        for _ in range(pages):
            if rng.random() < 0.65:
                self._serp_hover(page, rng)
            links = page.query_selector_all('a[href]')
            for lnk in links:
                href = lnk.get_attribute('href') or ''
                if target and target in href and 'google' not in href:
                    lnk.hover()
                    page.wait_for_timeout(rng.randint(140, 420))
                    lnk.click(position={
                        'x': rng.uniform(4, 14),
                        'y': rng.uniform(4, 14),
                    })
                    found = True
                    break
            if found:
                break
            if (page._impl_obj._loop.time() - start if hasattr(page, '_impl_obj') else time.time() - start) * 1000 > limit_ms:
                break
            next_btn = page.query_selector('a#pnnext, a[aria-label="Sonraki"], a[aria-label="Next"]')
            if next_btn:
                visited_pages += 1
                next_btn.click()
                page.wait_for_timeout(rng.randint(620, 1200))
            else:
                break
        search_elapsed = int(time.monotonic() - search_start)
        if recaptcha_blocked:
            return search_elapsed, visited_pages, {'recaptcha_blocked': True}, page.url, found, search_elapsed
        if not found:
            self.log('Aranan site bulunamadı, kalan süreyi sitede gezinerek tamamlıyoruz')
        plan, site_flags = self.plan_engine.build_custom_plan(dwell, {**flags})
        consumed, metrics = apply_actions(
            playwright,
            {**site_flags, 'url': page.url},
            plan,
            personality,
            initial_elapsed=search_elapsed,
        )
        total = max(consumed, search_elapsed + dwell)
        return total, visited_pages, metrics, page.url, found, search_elapsed


__all__ = ["GoogleHandler"]
