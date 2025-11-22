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

    def _move_mouse_path(self, page, start: Tuple[int, int], end: Tuple[int, int], persona: Optional[PersonaEngine] = None):
        persona = persona or self.persona
        path = persona.generate_mouse_path(start, end)
        for x, y in path:
            page.mouse.move(int(x), int(y), steps=1)
            if self.rng.random() < 0.2:
                page.wait_for_timeout(self.rng.randint(8, 22))

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
            page.wait_for_timeout(persona.reaction_delay_ms())
        return bool(last_mouse), last_mouse

    def simulate_scroll(self, page, viewport: Dict[str, int], persona: Optional[PersonaEngine] = None) -> bool:
        persona = persona or self.persona
        total = int(viewport.get("height", 720) * self.rng.uniform(1.2, 2.6))
        direction = 1
        for delta in persona.generate_scroll_pattern(total, direction):
            if delta == 0:
                page.wait_for_timeout(persona.reaction_delay_ms())
                continue
            page.mouse.wheel(0, delta)
            if self.rng.random() < 0.25:
                direction *= -1
            page.wait_for_timeout(int(persona.reaction_delay_ms() * self.rng.uniform(0.3, 0.7)))
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
        start = (cx + self.rng.randint(-25, 25), cy + self.rng.randint(-25, 25))
        self._move_mouse_path(page, start, (int(target_x), int(target_y)), persona)
        page.wait_for_timeout(self.rng.randint(40, 120))
        if not persona.should_click():
            return False
        page.mouse.down()
        page.wait_for_timeout(self.rng.randint(30, 90))
        page.mouse.up()
        if self.rng.random() < 0.2:
            page.wait_for_timeout(self.rng.randint(50, 120))
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
            page.wait_for_timeout(int(240 * dwell_scale))
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
            page.keyboard.type(ch, delay=self.rng.randint(18, 42))
            if self.rng.random() < 0.08:
                page.keyboard.press('Backspace')
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
