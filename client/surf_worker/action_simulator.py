from __future__ import annotations

import random
import time
from pathlib import Path
from typing import Callable, Dict, List, Optional, Tuple
from urllib.parse import urlparse

from .human_behavior_engine_v2 import HumanBehaviorEngineV2
from .persona_engine import PersonaEngine, PersonaProfile


class ActionSimulator:
    """
    Playwright aksiyonlarını insansı hale getiren katman.

    - PersonaEngine: mouse path, scroll paterni, click davranışı, medya davranışı
    - HumanBehaviorEngineV2: bu persona üzerine daha üst seviye fizik & pattern mantığı
    """

    def __init__(
        self,
        youtube_handler,
        log: Optional[Callable[[str], None]] = None,
        persona: Optional[PersonaEngine] = None,
    ):
        self.youtube_handler = youtube_handler
        self.log: Callable[[str], None] = log or (lambda _m: None)

        # Varsayılan persona: rastgele seç
        self.persona: PersonaEngine = persona or PersonaEngine.random()
        self.behavior = HumanBehaviorEngineV2(self.persona)
        self.random_words = self._load_words()

    # ------------------------------------------------------------------ #
    # Persona yönetimi
    # ------------------------------------------------------------------ #

    def set_persona(self, persona: Optional[PersonaEngine | PersonaProfile]) -> None:
        """
        Dışarıdan persona değiştirmek için.
        PersonaProfile verilirse yeni PersonaEngine üretilir.
        """
        if persona is None:
            self.persona = PersonaEngine.random()
        elif isinstance(persona, PersonaProfile):
            self.persona = PersonaEngine(persona)
        else:
            self.persona = persona
        self.behavior = HumanBehaviorEngineV2(self.persona)

    # ------------------------------------------------------------------ #
    # Düşük seviye mouse path helper
    # ------------------------------------------------------------------ #

    def _move_mouse_path(
        self,
        page,
        start: Tuple[int, int],
        end: Tuple[int, int],
        persona: Optional[PersonaEngine] = None,
        budget_ms: Optional[int] = None,
    ) -> None:
        """
        Persona + HumanBehaviorEngine v2 üzerinden mouse path üretir
        ve Playwright ile uygular.
        """
        persona = persona or self.persona
        # Eğer dışarıdan farklı persona gelmişse, geçici behavior oluştur
        behavior = self.behavior if persona is self.persona else HumanBehaviorEngineV2(persona)

        deadline = time.monotonic() + budget_ms / 1000 if budget_ms else None
        path = behavior.generate_mouse_path(start, end)
        base_delay = max(20, persona.reaction_delay_ms() // 4)

        for x, y in path:
            if deadline and time.monotonic() >= deadline:
                break
            page.mouse.move(int(x), int(y), steps=1)
            # hafif jitterli bekleme – insan elinin mikro duraksaması
            if random.random() < 0.6:
                jitter = random.uniform(0.5, 1.3)
                wait_ms = int(base_delay * jitter)
                if deadline:
                    remaining = int(max(0, (deadline - time.monotonic()) * 1000))
                    if remaining <= 0:
                        break
                    wait_ms = min(wait_ms, remaining)
                page.wait_for_timeout(wait_ms)

    # ------------------------------------------------------------------ #
    # Mouse hareketleri
    # ------------------------------------------------------------------ #

    def simulate_mouse_moves(
        self,
        page,
        viewport: Dict[str, int],
        persona: Optional[PersonaEngine] = None,
        budget_ms: Optional[int] = None,
    ) -> Tuple[bool, Optional[Tuple[int, int]]]:
        """
        Ekranda birkaç bölge arasında insansı mouse gezinmesi.
        SurfWorker bunu bir “mouse hareketleri” step’i için çağırıyor.
        """
        persona = persona or self.persona
        behavior = self.behavior if persona is self.persona else HumanBehaviorEngineV2(persona)

        deadline = time.monotonic() + budget_ms / 1000 if budget_ms else None
        width = viewport.get("width", 1280)
        height = viewport.get("height", 720)
        if width < 200 or height < 200:
            return False, None

        # Farklı “bölgeler” – üst, orta, alt bölgeler
        zones = [
            (0.25, 0.35),
            (0.45, 0.55),
            (0.65, 0.75),
        ]

        anchor = (
            random.randint(40, width - 40),
            random.randint(60, height - 60),
        )
        page.mouse.move(anchor[0], anchor[1], steps=random.randint(2, 5))
        last_mouse: Optional[Tuple[int, int]] = anchor

        # Kaç bölge gezileceğini kişiliğe göre ayarla
        visits = random.randint(2, 4)
        for zone in zones[:visits]:
            if deadline and time.monotonic() >= deadline:
                break
            if random.random() < 0.2:
                continue
            # hedef nokta
            cx = int(width * random.uniform(*zone))
            cy = int(height * random.uniform(0.25, 0.85))
            tx, ty = persona.maybe_offset_target(cx, cy)

            # HumanBehaviorEngine üzerinden path
            path = behavior.generate_mouse_path(last_mouse, (int(tx), int(ty)))
            for x, y in path:
                if deadline and time.monotonic() >= deadline:
                    break
                page.mouse.move(int(x), int(y), steps=1)
                if random.random() < 0.7:
                    wait_ms = int(persona.reaction_delay_ms() * random.uniform(0.25, 0.9))
                    if deadline:
                        remaining = int(max(0, (deadline - time.monotonic()) * 1000))
                        if remaining <= 0:
                            break
                        wait_ms = min(wait_ms, remaining)
                    page.wait_for_timeout(wait_ms)
            last_mouse = (int(tx), int(ty))

            if random.random() < 0.35:
                # kısa bekleme – sanki bir şey okuyormuş gibi
                wait_ms = int(persona.reaction_delay_ms() * random.uniform(1.0, 2.3))
                if deadline:
                    remaining = int(max(0, (deadline - time.monotonic()) * 1000))
                    if remaining <= 0:
                        break
                    wait_ms = min(wait_ms, remaining)
                page.wait_for_timeout(wait_ms)

        return bool(last_mouse), last_mouse

    # ------------------------------------------------------------------ #
    # Scroll
    # ------------------------------------------------------------------ #

    def simulate_scroll(
        self,
        page,
        viewport: Dict[str, int],
        persona: Optional[PersonaEngine] = None,
        budget_ms: Optional[int] = None,
    ) -> bool:
        """
        Persona scroll paterni + v2 motor üzerinden insansı scroll.
        """
        persona = persona or self.persona
        behavior = self.behavior if persona is self.persona else HumanBehaviorEngineV2(persona)

        total = int(viewport.get("height", 720) * random.uniform(1.2, 2.6))
        direction = 1
        deadline = time.monotonic() + budget_ms / 1000 if budget_ms else None

        deltas = behavior.generate_scroll_deltas(total, direction)
        if not deltas:
            return False

        for delta in deltas:
            if deadline and time.monotonic() >= deadline:
                break
            if delta == 0:
                pause = persona.reaction_delay_ms()
                if deadline:
                    remaining = int(max(0, (deadline - time.monotonic()) * 1000))
                    if remaining <= 0:
                        break
                    pause = min(pause, remaining)
                page.wait_for_timeout(pause)
                continue

            page.mouse.wheel(0, delta)

            # bazen yön değiştir (insanların yukarı aşağı hafif oynaması gibi)
            if random.random() < 0.23:
                direction *= -1

            pause = int(persona.reaction_delay_ms() * random.uniform(0.3, 0.9))
            if deadline:
                remaining = int(max(0, (deadline - time.monotonic()) * 1000))
                if remaining <= 0:
                    break
                pause = min(pause, remaining)
            page.wait_for_timeout(pause)
        return True

    # ------------------------------------------------------------------ #
    # Link tıklamaları
    # ------------------------------------------------------------------ #

    def simulate_clicks(
        self,
        page,
        host: str,
        persona: Optional[PersonaEngine] = None,
        budget_ms: Optional[int] = None,
    ) -> bool:
        """
        Aynı host içindeki linklere insansı tıklama simülasyonu.
        """
        persona = persona or self.persona
        behavior = self.behavior if persona is self.persona else HumanBehaviorEngineV2(persona)
        deadline = time.monotonic() + budget_ms / 1000 if budget_ms else None

        links = []
        for lnk in page.query_selector_all("a[href]"):
            try:
                if not lnk.is_visible():
                    continue
                href = lnk.get_attribute("href") or ""
                if not href or href.startswith("#") or href.lower().startswith("javascript"):
                    continue
                # Reklam bandı içindeki linkleri atla
                try:
                    if lnk.evaluate("el => !!el.closest('#noasoft-banner') || el.getAttribute('data-noasoft-banner') === '1'"):
                        continue
                except Exception:
                    pass
                try:
                    box = lnk.bounding_box() or {}
                    if box and box.get("y", 0) < 130:
                        # Banner bölgesiyle çakışma ihtimali yüksekse geç
                        continue
                except Exception:
                    pass
                parsed = urlparse(href)
                same_host = (not parsed.netloc) or (host and host in parsed.netloc)
                target_attr = (lnk.get_attribute("target") or "").lower()
                if target_attr == "_blank":
                    continue
                if parsed.scheme and parsed.netloc and not same_host:
                    continue
                links.append(lnk)
            except Exception:
                continue

        if not links:
            return False

        attempts = min(4, len(links))
        for idx in range(attempts):
            if deadline and time.monotonic() >= deadline:
                break
            choice = links[idx]
            box = choice.bounding_box() or {"x": 0, "y": 0, "width": 0, "height": 0}
            cx = int(box["x"] + box["width"] * random.uniform(0.2, 0.8))
            cy = int(box["y"] + box["height"] * random.uniform(0.2, 0.8))
            tx, ty = persona.maybe_offset_target(cx, cy)

            start = (
                cx + random.randint(-65, -25),
                cy + random.randint(-45, 35),
            )
            path = behavior.generate_mouse_path(start, (int(tx), int(ty)))
            for x, y in path:
                if deadline and time.monotonic() >= deadline:
                    break
                page.mouse.move(int(x), int(y), steps=1)
                if random.random() < 0.7:
                    pause = int(persona.reaction_delay_ms() * random.uniform(0.4, 1.1))
                    if deadline:
                        remaining = int(max(0, (deadline - time.monotonic()) * 1000))
                        if remaining <= 0:
                            break
                        pause = min(pause, remaining)
                    page.wait_for_timeout(pause)

            pause = random.randint(40, 160)
            if deadline:
                remaining = int(max(0, (deadline - time.monotonic()) * 1000))
                if remaining <= 0:
                    break
                pause = min(pause, remaining)
            page.wait_for_timeout(pause)

            force_click = persona.should_click() or random.random() < 0.6
            if not force_click:
                continue

            try:
                click_timeout = 5000
                if budget_ms:
                    click_timeout = max(800, min(5000, budget_ms))
                choice.click(timeout=click_timeout)
                post_pause = int(persona.reaction_delay_ms() * random.uniform(0.5, 1.2))
                if deadline:
                    remaining = int(max(0, (deadline - time.monotonic()) * 1000))
                    post_pause = min(post_pause, max(0, remaining))
                page.wait_for_timeout(post_pause)
                return True
            except Exception:
                continue
        return False

    # ------------------------------------------------------------------ #
    # Metin vurgulama
    # ------------------------------------------------------------------ #

    def simulate_text_highlight(
        self,
        page,
        persona: Optional[PersonaEngine] = None,
        budget_ms: Optional[int] = None,
    ) -> bool:
        """
        Rastgele bir paragraf / başlık üzerinde mouse ile seçme + kopyalama.
        """
        persona = persona or self.persona
        behavior = self.behavior if persona is self.persona else HumanBehaviorEngineV2(persona)
        deadline = time.monotonic() + budget_ms / 1000 if budget_ms else None

        selectors = ["h1", "h2", "h3", "h4", "h5", "h6", "p"]
        elements = []
        for sel in selectors:
            try:
                elems = page.query_selector_all(sel)
            except Exception:
                elems = []
            for el in elems:
                try:
                    if el.is_visible() and (el.text_content() or "").strip():
                        elements.append(el)
                except Exception:
                    continue
        if not elements:
            return False

        # Önce ilk görünen öğeleri dene, süre varsa bir ikincisini daha seç
        picks = elements[:2]
        success = False
        for target in picks:
            box = target.bounding_box()
            if not box:
                continue
            start_x = int(box["x"] + 5)
            start_y = int(box["y"] + box["height"] * random.uniform(0.35, 0.65))
            end_x = int(box["x"] + box["width"] * random.uniform(0.55, 0.95))
            end_y = start_y

            path = behavior.generate_mouse_path((start_x, start_y), (end_x, end_y))
            if not path:
                continue

            page.mouse.move(start_x, start_y, steps=1)
            page.mouse.down()
            for x, y in path:
                if deadline and time.monotonic() >= deadline:
                    break
                page.mouse.move(int(x), int(y), steps=1)
                if random.random() < 0.6:
                    pause = int(persona.reaction_delay_ms() * random.uniform(0.25, 0.8))
                    if deadline:
                        remaining = int(max(0, (deadline - time.monotonic()) * 1000))
                        if remaining <= 0:
                            break
                        pause = min(pause, remaining)
                    page.wait_for_timeout(pause)
            page.mouse.up()

            try:
                target.evaluate(
                    "el => { el.style.textDecoration = 'underline'; setTimeout(() => el.style.textDecoration='none', 1200); }"
                )
            except Exception:
                pass

            page.keyboard.press("Control+C")

            dwell_factor = persona.dwell_factor_for_text()
            pause = int(persona.reaction_delay_ms() * dwell_factor * 3.5)
            if deadline:
                remaining = int(max(0, (deadline - time.monotonic()) * 1000))
                if remaining <= 0:
                    return True
                pause = min(pause, remaining)
            page.wait_for_timeout(pause)
            success = True
            if deadline and time.monotonic() >= deadline:
                break

        try:
            page.evaluate("document.getSelection && document.getSelection().removeAllRanges && document.getSelection().removeAllRanges();")
        except Exception:
            pass
        return success

    # ------------------------------------------------------------------ #
    # Form etkileşimi
    # ------------------------------------------------------------------ #

    def simulate_form(
        self,
        page,
        persona: Optional[PersonaEngine] = None,
        budget_ms: Optional[int] = None,
    ) -> bool:
        """
        Basit bir input / textarea doldurma + hata yapıp düzeltme simülasyonu.
        """
        persona = persona or self.persona
        deadline = time.monotonic() + budget_ms / 1000 if budget_ms else None
        fields = [
            inp for inp in page.query_selector_all("input,textarea")
            if inp.is_visible()
        ]
        if not fields:
            return False

        target = random.choice(fields)
        try:
            target.click()
        except Exception:
            return False

        filler = self._pick_word() + " " + self._pick_word()

        # yazarken arada yanlış basıp backspace ile düzelt
        for ch in filler:
            if deadline and time.monotonic() >= deadline:
                break
            delay = random.randint(18, 42)
            if deadline:
                remaining = int(max(0, (deadline - time.monotonic()) * 1000))
                if remaining <= 0:
                    break
                delay = min(delay, remaining)
            page.keyboard.type(ch, delay=delay)
            if random.random() < 0.08:
                page.keyboard.press("Backspace")
        if random.random() < 0.5 and (not deadline or time.monotonic() < deadline):
            page.keyboard.press("Space")
            delay = random.randint(18, 42)
            if deadline:
                remaining = int(max(0, (deadline - time.monotonic()) * 1000))
                delay = min(delay, max(1, remaining))
            page.keyboard.type("test", delay=delay)

        page.keyboard.press("Control+A")
        if random.random() < 0.6:
            page.keyboard.press("Control+C")
        if random.random() < 0.4:
            page.keyboard.press("Backspace")

        pause = int(persona.reaction_delay_ms() * random.uniform(0.8, 1.6))
        if deadline:
            remaining = int(max(0, (deadline - time.monotonic()) * 1000))
            pause = min(pause, max(0, remaining))
        page.wait_for_timeout(pause)
        return True

    def _pick_word(self) -> str:
        if not self.random_words:
            return "NoaSoft"
        return random.choice(self.random_words)

    def _load_words(self) -> List[str]:
        try:
            path = Path(__file__).resolve().parents[1] / 'assets' / 'random-words.json'
            if path.exists():
                import json

                with path.open(encoding='utf-8') as f:
                    data = json.load(f)
                    if isinstance(data, list):
                        return [str(x) for x in data if str(x).strip()]
        except Exception:
            return []
        return []

    # ------------------------------------------------------------------ #
    # Medya etkileşimi (genel + YouTube)
    # ------------------------------------------------------------------ #

    def simulate_media(
        self,
        page,
        actions: List[str],
        dwell_hint: int,
        persona: Optional[PersonaEngine] = None,
        budget_ms: Optional[int] = None,
    ) -> bool:
        """
        Medya (video/audio) ile insansı etkileşim.
        - YouTube ise YouTubeHandler.simulate_youtube_media devreye girer
        - Diğer sitelerde basit play/pause/volume/fullscreen/quality davranışı
        """
        persona = persona or self.persona
        deadline = time.monotonic() + budget_ms / 1000 if budget_ms else None

        host = ""
        try:
            host = urlparse(page.url).netloc or ""
        except Exception:
            pass

        media = page.query_selector("video, audio")
        if ("youtube.com" in host or "youtu.be" in host) and media is None:
            media = page.query_selector("video")

        # YouTube ise tamamen YouTubeHandler'a bırak
        if media and ("youtube.com" in host or "youtu.be" in host):
            try:
                return self.youtube_handler.simulate_youtube_media(
                    page,
                    media,
                    actions or [],
                    dwell_hint,
                    persona.profile,
                )
            except Exception as exc:
                self.log(f"YouTube medya simülasyonu hatası: {exc}")
                # generic fallback'e düşebiliriz

        if not media:
            return False

        if not actions:
            # persona'nın medya davranışına göre ağırlıkları çekelim
            weights = self.persona.media_action_weights()
            actions = []
            for key, w in weights.items():
                if random.random() < min(0.9, 0.2 + w * 0.4):
                    actions.append(key)
            if not actions:
                actions = ["hover", "play_pause"]

        weights = self.persona.media_action_weights()

        try:
            media.hover()
            pause = int(persona.reaction_delay_ms() * random.uniform(0.5, 1.3))
            if deadline:
                remaining = int(max(0, (deadline - time.monotonic()) * 1000))
                pause = min(pause, max(0, remaining))
            page.wait_for_timeout(pause)

            # play/pause / click / volume / fullscreen vs.
            if "human_click" in actions:
                media.click()
                pause = persona.reaction_delay_ms()
                if deadline:
                    remaining = int(max(0, (deadline - time.monotonic()) * 1000))
                    pause = min(pause, max(0, remaining))
                page.wait_for_timeout(pause)

            if "pause_play" in actions:
                page.keyboard.press("Space")
                pause = int(persona.reaction_delay_ms() * random.uniform(0.6, 1.2))
                if deadline:
                    remaining = int(max(0, (deadline - time.monotonic()) * 1000))
                    pause = min(pause, max(0, remaining))
                page.wait_for_timeout(pause)
                page.keyboard.press("Space")

            if "fullscreen" in actions and random.random() < weights.get("fullscreen", 0.3):
                page.keyboard.press("KeyF")
                pause = int(persona.reaction_delay_ms() * random.uniform(0.8, 1.6))
                if deadline:
                    remaining = int(max(0, (deadline - time.monotonic()) * 1000))
                    pause = min(pause, max(0, remaining))
                page.wait_for_timeout(pause)

            if "volume" in actions and random.random() < weights.get("volume", 0.5):
                for _ in range(random.randint(1, 3)):
                    if deadline and time.monotonic() >= deadline:
                        break
                    page.keyboard.press(random.choice(["ArrowUp", "ArrowDown"]))
                    pause = random.randint(80, 220)
                    if deadline:
                        remaining = int(max(0, (deadline - time.monotonic()) * 1000))
                        if remaining <= 0:
                            break
                        pause = min(pause, remaining)
                    page.wait_for_timeout(pause)

            if "quality" in actions and random.random() < weights.get("quality_menu", 0.3):
                quality_menu = page.query_selector(
                    'button[aria-label*="quality" i], [class*="quality"]'
                )
                if quality_menu:
                    quality_menu.click()
                    pause = int(persona.reaction_delay_ms() * random.uniform(0.4, 1.0))
                    if deadline:
                        remaining = int(max(0, (deadline - time.monotonic()) * 1000))
                        pause = min(pause, max(0, remaining))
                    page.wait_for_timeout(pause)

            # basit izleme süresi
            if dwell_hint:
                # dwell_hint saniyeyi tam bekleme, biraz kısalt / uzat
                factor = random.uniform(0.5, 1.2)
                wait_ms = int(dwell_hint * 1000 * factor)
                if deadline:
                    remaining = int(max(0, (deadline - time.monotonic()) * 1000))
                    wait_ms = min(wait_ms, max(0, remaining))
                page.wait_for_timeout(wait_ms)

            return True
        except Exception as exc:
            self.log(f"Medya etkileşimi atlandı: {exc}")
            return False


__all__ = ["ActionSimulator", "PersonaEngine", "PersonaProfile"]
