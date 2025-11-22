import random
import string
from typing import Callable, Dict, List, Optional, Tuple
from urllib.parse import urlparse

from .persona_engine import PersonaEngine, PersonaProfile


class ActionSimulator:
    def __init__(
        self,
        youtube_handler,
        log: Optional[Callable[[str], None]] = None,
        persona: Optional[PersonaEngine] = None,
    ):
        self.youtube_handler = youtube_handler
        self.log = log or (lambda msg: None)
        self.persona = persona or PersonaEngine.random()
        self.rng = getattr(self.persona, "rng", random.Random())

    def set_persona(self, persona: PersonaEngine):
        self.persona = persona
        self.rng = getattr(self.persona, "rng", random.Random())

    def _human_sleep(self, base_ms: int, spread: float = 0.35) -> None:
        jitter = base_ms * spread
        delay = max(5, int(self.rng.uniform(base_ms - jitter, base_ms + jitter)))
        if self.rng.random() < 0.15:
            delay += self.persona.reaction_delay_ms()
        page_delay = min(900, delay)
        if page_delay > 0:
            self.log(f"[Sim] Bekleme: {page_delay}ms")
            # page is provided at call sites via lambdas; avoid passing page here to keep API minimal

    def _wait_page(self, page, base_ms: int, spread: float = 0.35):
        jitter = base_ms * spread
        delay = max(5, int(self.rng.uniform(base_ms - jitter, base_ms + jitter)))
        if self.rng.random() < 0.15:
            delay += self.persona.reaction_delay_ms()
        delay = min(900, delay)
        if delay > 0:
            page.wait_for_timeout(delay)

    def _move_mouse_path(self, page, start: Tuple[int, int], end: Tuple[int, int], persona: Optional[PersonaEngine] = None):
        persona = persona or self.persona
        path = persona.generate_mouse_path(start, end)
        micro_pause = self.persona.reaction_delay_ms()
        for idx, (x, y) in enumerate(path):
            steps = self.rng.randint(1, 3)
            page.mouse.move(int(x), int(y), steps=steps)
            if idx and idx % self.rng.randint(6, 12) == 0:
                self._wait_page(page, self.rng.randint(12, 42), spread=0.5)
            elif self.rng.random() < 0.28:
                self._wait_page(page, self.rng.randint(8, 26), spread=0.35)
        if self.rng.random() < 0.25:
            self._wait_page(page, micro_pause, spread=0.4)

    def simulate_mouse_moves(self, page, viewport: Dict[str, int], persona: Optional[PersonaEngine] = None) -> Tuple[bool, Optional[Tuple[int, int]]]:
        persona = persona or self.persona
        last_mouse: Optional[Tuple[int, int]] = None
        zones = [
            (0.25, 0.35),
            (0.45, 0.55),
            (0.65, 0.75),
        ]
        anchor = (self.rng.randint(40, viewport["width"] - 40), self.rng.randint(60, viewport["height"] - 60))
        page.mouse.move(anchor[0], anchor[1], steps=self.rng.randint(2, 5))
        last_mouse = anchor
        for zone in zones:
            if self.rng.random() < 0.2:
                continue
            target = (
                int(viewport["width"] * self.rng.uniform(*zone)),
                int(viewport["height"] * self.rng.uniform(0.25, 0.85)),
            )
            self._move_mouse_path(page, last_mouse, target, persona)
            last_mouse = target
            hover_jitter = max(20, int(persona.reaction_delay_ms() * self.rng.uniform(0.6, 1.6)))
            self._wait_page(page, hover_jitter, spread=0.4)
            if self.rng.random() < 0.25:
                micro_target = (
                    max(5, min(viewport["width"] - 5, target[0] + self.rng.randint(-30, 30))),
                    max(5, min(viewport["height"] - 5, target[1] + self.rng.randint(-30, 30))),
                )
                self._move_mouse_path(page, last_mouse, micro_target, persona)
                last_mouse = micro_target
                self._wait_page(page, self.rng.randint(25, 90), spread=0.5)
        return bool(last_mouse), last_mouse

    def simulate_scroll(self, page, viewport: Dict[str, int], persona: Optional[PersonaEngine] = None) -> bool:
        persona = persona or self.persona
        total = int(viewport.get("height", 720) * self.rng.uniform(1.2, 2.8))
        direction = 1
        ticks = persona.generate_scroll_pattern(total, direction)
        last_direction = direction
        for delta in ticks:
            if delta == 0:
                self._wait_page(page, self.rng.randint(80, 220), spread=0.6)
                continue
            if self.rng.random() < 0.18:
                delta = int(delta * self.rng.uniform(0.5, 1.4))
            if self.rng.random() < 0.12:
                page.keyboard.press(self.rng.choice(["ArrowDown", "ArrowUp"]))
            page.mouse.wheel(0, delta)
            if self.rng.random() < 0.22:
                last_direction *= -1
            if self.rng.random() < 0.18:
                page.mouse.wheel(0, last_direction * self.rng.randint(40, 180))
            self._wait_page(page, int(persona.reaction_delay_ms() * self.rng.uniform(0.25, 0.85)), spread=0.45)
        return True

    def simulate_clicks(self, page, host: str, persona: Optional[PersonaEngine] = None) -> bool:
        persona = persona or self.persona
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
        choice = self.rng.choice(links)
        box = choice.bounding_box() or {'x': 0, 'y': 0, 'width': 0, 'height': 0}
        cx = int(box['x'] + box['width'] * self.rng.uniform(0.2, 0.8))
        cy = int(box['y'] + box['height'] * self.rng.uniform(0.2, 0.8))
        target_x, target_y = persona.maybe_offset_target(cx, cy)
        start = (cx + self.rng.randint(-35, 35), cy + self.rng.randint(-35, 35))
        self._move_mouse_path(page, start, (int(target_x), int(target_y)), persona)
        hover_time = max(25, int(self.persona.reaction_delay_ms() * self.rng.uniform(0.5, 1.4)))
        self._wait_page(page, hover_time, spread=0.5)
        if not persona.should_click():
            return False
        if self.rng.random() < 0.28:
            near_hover = (
                int(target_x + self.rng.randint(-12, 12)),
                int(target_y + self.rng.randint(-12, 12)),
            )
            self._move_mouse_path(page, (int(target_x), int(target_y)), near_hover, persona)
            self._wait_page(page, self.rng.randint(20, 80), spread=0.4)
        page.mouse.down()
        self._wait_page(page, self.rng.randint(30, 120), spread=0.6)
        page.mouse.up()
        if self.rng.random() < 0.2:
            self._wait_page(page, self.rng.randint(50, 160), spread=0.4)
        return True

    def simulate_text_highlight(self, page, persona: Optional[PersonaEngine] = None) -> bool:
        persona = persona or self.persona
        candidates = page.query_selector_all('p, h1, h2, h3, h4')
        visible = [el for el in candidates if el.is_visible() and (el.text_content() or '').strip()]
        if not visible:
            return False
        target = self.rng.choice(visible)
        box = target.bounding_box()
        if not box:
            return False
        start = (int(box['x'] + 6), int(box['y'] + box['height'] * 0.4))
        end = (int(box['x'] + box['width'] - 6), start[1] + self.rng.randint(-3, 3))
        self._move_mouse_path(page, start, end, persona)
        page.mouse.down()
        self._move_mouse_path(page, start, end, persona)
        page.mouse.up()
        target.evaluate("el => el.style.textDecoration = 'line-through'")
        page.keyboard.press('Control+C')
        dwell_scale = persona.dwell_factor_for_text()
        if dwell_scale > 1.0:
            self._wait_page(page, int(240 * dwell_scale), spread=0.4)
        return True

    def simulate_form(self, page, persona: Optional[PersonaEngine] = None) -> bool:
        persona = persona or self.persona
        fields = [inp for inp in page.query_selector_all('input,textarea') if inp.is_visible()]
        if not fields:
            return False
        target = self.rng.choice(fields)
        target.click()
        filler = 'NoaSoft ' + ''.join(self.rng.choice(string.ascii_letters) for _ in range(6))
        for ch in filler:
            page.keyboard.type(ch, delay=self.rng.randint(18, 46))
            if self.rng.random() < 0.12:
                page.keyboard.press('Backspace')
            if self.rng.random() < 0.08:
                self._wait_page(page, self.rng.randint(30, 90), spread=0.4)
        page.keyboard.press('Control+A')
        if self.rng.random() < 0.6:
            page.keyboard.press('Control+C')
        if self.rng.random() < 0.4:
            page.keyboard.press('Backspace')
        return True

    def simulate_media(self, page, actions: List[str], dwell_hint: int, persona: Optional[PersonaEngine] = None) -> bool:
        persona = persona or self.persona
        media = page.query_selector('video, audio')
        if not media:
            return False
        if not actions:
            actions = ['hover', 'delay', 'human_click', 'pause_play', 'volume', 'fullscreen', 'quality']
        try:
            media.hover()
            page.wait_for_timeout(self.rng.randint(80, 180))
            host = urlparse(page.url).netloc
            if 'youtube.com' in host:
                return self.youtube_handler.simulate_youtube_media(page, media, actions, dwell_hint, persona.profile)
            weights = persona.media_action_weights()
            if 'human_click' in actions:
                media.click()
            if 'pause_play' in actions and self.rng.random() < weights.get('play_pause', 0.5):
                page.keyboard.press('Space')
            if 'fullscreen' in actions and self.rng.random() < weights.get('fullscreen', 0.3):
                page.keyboard.press('KeyF')
            if 'volume' in actions and self.rng.random() < weights.get('volume', 0.4):
                page.keyboard.press(self.rng.choice(['ArrowUp', 'ArrowDown']))
            if 'quality' in actions and self.rng.random() < weights.get('quality_menu', 0.2):
                quality_menu = page.query_selector('button[aria-label*="quality" i], [class*="quality"]')
                if quality_menu:
                    quality_menu.click()
            if dwell_hint:
                page.wait_for_timeout(int(dwell_hint * 180))
            return True
        except Exception as exc:
            self.log(f'Medya etkileşimi atlandı: {exc}')
            return False


__all__ = ["ActionSimulator", "PersonaEngine", "PersonaProfile"]
