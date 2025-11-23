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

    def _wait_recaptcha_manual(self, page, rng) -> Tuple[bool, float]:
        """Kullanıcıya reCAPTCHA'yı elle çözmesi için fırsat tanır.

        Dönüş: (çözüldü mü, duraksama_süresi)
        """
        selectors = [
            '.recaptcha-checkbox',
            '.rc-anchor-checkbox',
            'iframe[src*="recaptcha"]',
            '.goog-inline-block.recaptcha-checkbox-unchecked',
        ]
        start = time.monotonic()
        box = None
        for sel in selectors:
            try:
                box = page.query_selector(sel)
                if box:
                    break
            except Exception:
                continue
        if not box:
            return True, 0.0
        try:
            if box.tag_name().lower() == 'iframe':
                frame = box.content_frame()
                if frame:
                    box = frame.query_selector('.recaptcha-checkbox-border') or box
        except Exception:
            pass
        try:
            box.scroll_into_view_if_needed()
        except Exception:
            pass
        page.wait_for_timeout(rng.randint(400, 900))
        self.log('reCAPTCHA algılandı, lütfen tarayıcıda elle geçin. 45 saniye bekleniyor...')
        solved = False
        try:
            page.wait_for_selector('.recaptcha-checkbox-checked, .recaptcha-success', timeout=45000)
            solved = True
        except Exception:
            solved = False
        pause = time.monotonic() - start
        return solved, pause

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
            box = page.wait_for_selector('textarea#APjFqb, textarea.gLFyf, input[name="q"]', timeout=7000)
            box.click()
            for ch in keyword:
                box.type(ch, delay=rng.randint(40, 110))
            box.press('Enter')
        except Exception:
            page.goto(f'{host}/search?q={requests.utils.quote(keyword)}&hl=en&gl={country}', wait_until='domcontentloaded')

        found = False
        visited_pages = 1
        target = site_url.replace('https://', '').replace('http://', '')
        limit_ms = 90000

        search_start = time.monotonic()
        deadline = search_start + 90
        page_index = 0
        while time.monotonic() < deadline:
            if rng.random() < 0.65:
                self._serp_hover(page, rng)
            try:
                results = page.query_selector_all('div.N54PNb.BToiNc a.zReHs[href]') or []
            except Exception:
                results = []
            for lnk in results:
                href = lnk.get_attribute('href') or ''
                if target and target in href and 'google' not in href:
                    try:
                        box = lnk.bounding_box() or {'x': 0, 'y': 0, 'width': 12, 'height': 12}
                        lnk.hover()
                        page.wait_for_timeout(rng.randint(120, 260))
                        lnk.click(position={
                            'x': rng.uniform(2, box.get('width', 12)),
                            'y': rng.uniform(2, box.get('height', 12)),
                        })
                        page.wait_for_load_state('domcontentloaded', timeout=10000)
                    except Exception:
                        pass
                    found = True
                    break
            if found:
                break
            elapsed_ms = (time.monotonic() - search_start) * 1000
            if elapsed_ms > limit_ms:
                break
            solved, pause = self._wait_recaptcha_manual(page, rng)
            if not solved:
                recaptcha_blocked = True
                deadline += pause
                break
            if pause:
                deadline += pause
            pagination_rows = page.query_selector_all('tr.mYW5bd td.NKTSme a') or []
            target_page = None
            if pagination_rows and page_index < len(pagination_rows) and visited_pages < pages:
                target_page = pagination_rows[page_index]
            elif visited_pages < pages:
                target_page = page.query_selector('a#pnnext, a[aria-label="Sonraki"], a[aria-label="Next"]')
            if target_page and visited_pages < pages:
                page_index += 1
                visited_pages += 1
                try:
                    target_page.click()
                    page.wait_for_load_state('domcontentloaded', timeout=12000)
                except Exception:
                    page.wait_for_timeout(rng.randint(500, 900))
            else:
                page.wait_for_timeout(rng.randint(380, 620))
        search_elapsed = int(time.monotonic() - search_start)
        if recaptcha_blocked:
            return search_elapsed, visited_pages, {'recaptcha_blocked': True}, page.url, found, search_elapsed
        if not found:
            return search_elapsed, visited_pages, {'found': False, 'search_elapsed': search_elapsed}, page.url, found, search_elapsed
        plan, site_flags = self.plan_engine.build_custom_plan(dwell, {**flags})
        consumed, metrics = apply_actions(
            playwright,
            {**site_flags, 'url': page.url},
            plan,
            personality,
            initial_elapsed=search_elapsed,
        )
        total = max(consumed, search_elapsed + dwell)
        metrics['found'] = True
        metrics['search_elapsed'] = search_elapsed
        return total, visited_pages, metrics, page.url, found, search_elapsed


__all__ = ["GoogleHandler"]
