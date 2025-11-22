import random
import string
from dataclasses import dataclass
from typing import Callable, Dict, List, Optional, Tuple
from urllib.parse import urlparse


@dataclass
class Personality:
    label: str
    mouse_jitter: float
    accel_bias: float
    scroll_momentum: float
    click_imprecision: float
    typing_delay: Tuple[int, int]
    hover_latency: Tuple[int, int]
    scroll_tick: Tuple[int, int]
    pause_factor: float

    @classmethod
    def random_profile(cls) -> "Personality":
        presets = [
            cls("enerjik", 0.8, 0.65, 0.9, 0.12, (20, 45), (60, 140), (220, 520), 0.9),
            cls("dikkatli", 0.35, 0.5, 0.6, 0.06, (35, 75), (120, 260), (180, 420), 1.1),
            cls("gezen", 0.55, 0.35, 0.75, 0.08, (28, 60), (80, 200), (260, 620), 1.0),
        ]
        return random.choice(presets)


class ActionSimulator:
    def __init__(
        self,
        youtube_handler,
        log: Optional[Callable[[str], None]] = None,
    ):
        self.youtube_handler = youtube_handler
        self.log = log or (lambda msg: None)

    # -------------------- mouse helpers --------------------
    def _ease(self, t: float) -> float:
        return 3 * t * t - 2 * t * t * t

    def _jitter(self, scale: float) -> float:
        return random.uniform(-1, 1) * scale

    def _move_mouse_path(self, page, start: Tuple[int, int], end: Tuple[int, int], personality: Personality):
        steps = random.randint(9, 18)
        dx = end[0] - start[0]
        dy = end[1] - start[1]
        for i in range(steps + 1):
            t = i / steps
            eased = self._ease(t) if random.random() > 0.2 else t
            nx = start[0] + dx * eased + self._jitter(personality.mouse_jitter * 10)
            ny = start[1] + dy * eased + self._jitter(personality.mouse_jitter * 10)
            page.mouse.move(int(nx), int(ny), steps=1)
            if random.random() < 0.25:
                page.wait_for_timeout(random.randint(12, 28))

    def simulate_mouse_moves(self, page, viewport: Dict[str, int], personality: Personality) -> Tuple[bool, Optional[Tuple[int, int]]]:
        last_mouse = None
        zones = [
            (0.25, 0.35),
            (0.45, 0.55),
            (0.65, 0.75),
        ]
        anchor = (random.randint(40, viewport["width"] - 40), random.randint(60, viewport["height"] - 60))
        page.mouse.move(anchor[0], anchor[1], steps=random.randint(2, 5))
        last_mouse = anchor
        for zone in zones:
            if random.random() < 0.2:
                continue
            target = (
                int(viewport["width"] * random.uniform(*zone)),
                int(viewport["height"] * random.uniform(0.25, 0.85)),
            )
            self._move_mouse_path(page, last_mouse, target, personality)
            last_mouse = target
            page.wait_for_timeout(random.randint(80, 160))
        return bool(last_mouse), last_mouse

    # -------------------- scroll --------------------
    def simulate_scroll(self, page, viewport: Dict[str, int], personality: Personality) -> bool:
        ticks = random.randint(2, 4)
        direction = 1
        for _ in range(ticks):
            delta = random.randint(*personality.scroll_tick)
            page.mouse.wheel(0, delta * direction)
            if random.random() < 0.3:
                direction *= -1
            page.wait_for_timeout(int(random.uniform(120, 260) * personality.pause_factor))
        return True

    # -------------------- clicking --------------------
    def simulate_clicks(self, page, host: str, personality: Personality) -> bool:
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
        choice = random.choice(links)
        box = choice.bounding_box() or {'x': 0, 'y': 0, 'width': 0, 'height': 0}
        cx = int(box['x'] + box['width'] * random.uniform(0.2, 0.8))
        cy = int(box['y'] + box['height'] * random.uniform(0.2, 0.8))
        page.mouse.move(cx + self._jitter(personality.click_imprecision * 15), cy + self._jitter(personality.click_imprecision * 15), steps=random.randint(3, 7))
        page.wait_for_timeout(random.randint(*personality.hover_latency))
        page.mouse.down()
        page.wait_for_timeout(random.randint(30, 80))
        page.mouse.up()
        if random.random() < 0.2:
            page.wait_for_timeout(random.randint(50, 120))
        return True

    # -------------------- text highlight --------------------
    def simulate_text_highlight(self, page, personality: Personality) -> bool:
        candidates = page.query_selector_all('p, h1, h2, h3, h4')
        visible = [el for el in candidates if el.is_visible() and (el.text_content() or '').strip()]
        if not visible:
            return False
        target = random.choice(visible)
        box = target.bounding_box()
        if not box:
            return False
        start = (int(box['x'] + 6), int(box['y'] + box['height'] * 0.4))
        end = (int(box['x'] + box['width'] - 6), start[1] + random.randint(-3, 3))
        self._move_mouse_path(page, start, end, personality)
        page.mouse.down()
        self._move_mouse_path(page, start, end, personality)
        page.mouse.up()
        target.evaluate("el => el.style.textDecoration = 'line-through'")
        page.keyboard.press('Control+C')
        return True

    # -------------------- form fill --------------------
    def simulate_form(self, page, personality: Personality) -> bool:
        fields = [inp for inp in page.query_selector_all('input,textarea') if inp.is_visible()]
        if not fields:
            return False
        target = random.choice(fields)
        target.click()
        filler = 'NoaSoft ' + ''.join(random.choice(string.ascii_letters) for _ in range(6))
        for ch in filler:
            page.keyboard.type(ch, delay=random.randint(*personality.typing_delay))
            if random.random() < 0.08:
                page.keyboard.press('Backspace')
        page.keyboard.press('Control+A')
        if random.random() < 0.6:
            page.keyboard.press('Control+C')
        if random.random() < 0.4:
            page.keyboard.press('Backspace')
        return True

    # -------------------- media --------------------
    def simulate_media(self, page, actions: List[str], dwell_hint: int, personality: Personality) -> bool:
        media = page.query_selector('video, audio')
        if not media:
            return False
        if not actions:
            actions = ['hover', 'delay', 'human_click', 'pause_play', 'volume', 'fullscreen', 'quality']
        try:
            media.hover()
            page.wait_for_timeout(random.randint(*personality.hover_latency))
            host = urlparse(page.url).netloc
            if 'youtube.com' in host:
                return self.youtube_handler.simulate_youtube_media(page, media, actions, dwell_hint, personality)
            if 'human_click' in actions:
                media.click()
            if 'pause_play' in actions:
                page.keyboard.press('Space')
            if 'fullscreen' in actions and random.random() < 0.4:
                page.keyboard.press('KeyF')
            if 'volume' in actions:
                page.keyboard.press(random.choice(['ArrowUp', 'ArrowDown']))
            if 'quality' in actions:
                quality_menu = page.query_selector('button[aria-label*="quality" i], [class*="quality"]')
                if quality_menu:
                    quality_menu.click()
            return True
        except Exception as exc:
            self.log(f'Medya etkileşimi atlandı: {exc}')
            return False


__all__ = ["ActionSimulator", "Personality"]
