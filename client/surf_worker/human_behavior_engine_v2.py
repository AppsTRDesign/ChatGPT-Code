from __future__ import annotations

import random
from dataclasses import dataclass
from typing import Any, Dict, List, Optional, Tuple

try:
    # Paket içinde kullanırken
    from .persona_engine import PersonaEngine, PersonaProfile
except ImportError:  # pragma: no cover - standalone kullanım
    from persona_engine import PersonaEngine, PersonaProfile  # type: ignore


@dataclass
class BehaviorStep:
    """
    Tek bir insansı davranış bloğunu temsil eder.

    Örnekler:
        kind="scroll_block", duration_sec=4, meta={"distance": 1200, "direction": 1}
        kind="mouse_explore", duration_sec=3, meta={"style": "focus"}
    """

    kind: str
    duration_sec: int
    meta: Dict[str, Any]


class HumanBehaviorEngineV2:
    """
    Advanced Human Behavior Engine v2

    - PersonaEngine üzerine kurulu yüksek seviyeli davranış motoru.
    - Oturum boyunca hangi aksiyonların hangi sırayla, ne süreyle yapılacağını hesaplar.
    - Düşük seviye mouse/scroll path üretimi için PersonaEngine'i kullanır.
    """

    # Desteklenen davranış türleri
    SUPPORTED_KINDS = [
        "mouse_explore",
        "scroll_block",
        "read_section",
        "click_link",
        "form_interact",
        "media_interact",
        "idle",
    ]

    def __init__(
        self,
        persona: PersonaEngine,
        profile: Optional[PersonaProfile] = None,
        rng: Optional[random.Random] = None,
    ) -> None:
        self.persona = persona
        self.profile = profile or persona.profile
        self.rng = rng or random.Random()

    # ------------------------------------------------------------------ #
    # YÜKSEK SEVİYE PLANLAYICI
    # ------------------------------------------------------------------ #

    def build_session_plan(
        self,
        total_seconds: int,
        flags: Optional[Dict[str, bool]] = None,
    ) -> List[BehaviorStep]:
        """
        Yüksek seviyeli bir oturum planı üretir.

        flags:
            {
              "mouse_moves": True,
              "scroll": True,
              "link_clicks": True,
              "form_fill": False,
              "media": False,
            }

        Geri dönüş:
            List[BehaviorStep]
        """
        total_seconds = max(3, int(total_seconds))
        flags = flags or {}
        allowed = self._allowed_kinds_from_flags(flags)

        steps: List[BehaviorStep] = []
        remaining = total_seconds

        # Başlangıçta kısa bir "sayfa açılıyor / stabilize oluyor" periyodu
        initial = min(5, remaining)
        steps.append(BehaviorStep("idle", initial, {"reason": "page_load"}))
        remaining -= initial

        prev_kind: Optional[str] = None
        while remaining > 0:
            kind = self._pick_next_kind(prev_kind, allowed)
            if kind is None:
                # hiç aksiyon yoksa kalan süreyi idle ile doldur
                steps.append(BehaviorStep("idle", remaining, {"reason": "fallback"}))
                break

            # Bu aksiyon için süre hesapla
            block = self._compute_block_duration(kind, remaining)
            if block <= 0:
                break

            meta: Dict[str, Any] = {}
            if kind == "scroll_block":
                meta.update(self._build_scroll_meta())
            elif kind == "mouse_explore":
                meta.update(
                    {"style": "focus" if self.profile.attention_span > 0.7 else "skim"}
                )
            elif kind == "click_link":
                meta.update({"max_attempts": 3})
            elif kind == "media_interact":
                meta.update(self._build_media_meta())
            elif kind == "form_interact":
                meta.update({"max_fields": self._estimate_form_fields()})
            elif kind == "read_section":
                meta.update({"depth_factor": self.persona.dwell_factor_for_text()})
            elif kind == "idle":
                meta.update({"reason": "natural_pause"})

            steps.append(BehaviorStep(kind, block, meta))
            remaining -= block
            prev_kind = kind

        return self._post_process_plan(steps, total_seconds)

    # ------------------------------------------------------------------ #
    # PLAN SONRASI HAFİF DÜZELTMELER
    # ------------------------------------------------------------------ #

    def _post_process_plan(self, steps: List[BehaviorStep], total: int) -> List[BehaviorStep]:
        """Süreyi hafif düzeltir, çok kısa blokları birleştirir."""
        if not steps:
            return []

        # 1) ardışık idle blokları birleştir
        merged: List[BehaviorStep] = []
        for step in steps:
            if merged and step.kind == "idle" and merged[-1].kind == "idle":
                merged[-1].duration_sec += step.duration_sec
                merged[-1].meta.setdefault("merged", 0)
                merged[-1].meta["merged"] += 1
            else:
                merged.append(step)

        # 2) toplam süreyi ayarla
        total_now = sum(s.duration_sec for s in merged)
        if total_now < total:
            # son idle'a ekle ya da yoksa yeni idle ekle
            extra = total - total_now
            if merged and merged[-1].kind == "idle":
                merged[-1].duration_sec += extra
            else:
                merged.append(BehaviorStep("idle", extra, {"reason": "padding"}))
        elif total_now > total:
            # sondan kırp
            excess = total_now - total
            for step in reversed(merged):
                if step.duration_sec > excess:
                    step.duration_sec -= excess
                    break
                else:
                    excess -= step.duration_sec
                    step.duration_sec = 0
            merged = [s for s in merged if s.duration_sec > 0]

        return merged

    # ------------------------------------------------------------------ #
    # AKSİYON SEÇİMİ & SÜRE HESAPLAMA
    # ------------------------------------------------------------------ #

    def _allowed_kinds_from_flags(self, flags: Dict[str, bool]) -> List[str]:
        base = ["mouse_explore", "scroll_block", "read_section", "idle"]
        if flags.get("link_clicks"):
            base.append("click_link")
        if flags.get("form_fill"):
            base.append("form_interact")
        if flags.get("media"):
            base.append("media_interact")
        return base

    def _pick_next_kind(
        self,
        prev_kind: Optional[str],
        allowed: List[str],
    ) -> Optional[str]:
        if not allowed:
            return None

        # Basit bir transition ağırlığı
        weights: Dict[str, float] = {}
        for kind in allowed:
            w = 1.0
            if kind == "scroll_block":
                w += self.profile.scroll_intensity * 1.2
            if kind == "mouse_explore":
                w += self.profile.mouse_speed * 0.8
            if kind == "click_link":
                w += self.profile.click_frequency * 0.9
            if kind == "media_interact":
                media = self.profile.media_behavior
                if media in ("intense", "curious", "focused"):
                    w += 1.0
            if kind == "read_section":
                w += self.profile.attention_span * 1.1

            # Önceki aksiyonla aynıysa biraz azalt (döngü hissini kırmak için)
            if prev_kind and kind == prev_kind:
                w *= 0.65

            weights[kind] = max(0.05, w)

        total = sum(weights.values())
        r = self.rng.random() * total
        acc = 0.0
        for kind, w in weights.items():
            acc += w
            if r <= acc:
                return kind
        return allowed[-1]

    def _compute_block_duration(self, kind: str, remaining: int) -> int:
        # Kişiliğin attention span'ine göre temel aralık
        att = max(0.1, min(1.5, self.profile.attention_span or 0.7))
        base_min, base_max = 2, 14

        if kind == "mouse_explore":
            base_min, base_max = 2, 6
        elif kind == "scroll_block":
            base_min, base_max = 2, 5
        elif kind == "read_section":
            base_min, base_max = 4, 18
        elif kind == "click_link":
            base_min, base_max = 2, 5
        elif kind == "form_interact":
            base_min, base_max = 3, 10
        elif kind == "media_interact":
            base_min, base_max = 6, 24
        elif kind == "idle":
            base_min, base_max = 1, 6

        base = self.rng.uniform(base_min, base_max) * att
        block = int(round(base))
        block = max(1, min(block, remaining))

        # Bazen minik varyasyon
        if self.rng.random() < 0.3:
            jitter = self.rng.choice([-1, 1])
            block = max(1, min(block + jitter, remaining))

        return block

    # ------------------------------------------------------------------ #
    # SCROLL / MEDYA META ÜRETİCİLER
    # ------------------------------------------------------------------ #

    def _build_scroll_meta(self) -> Dict[str, Any]:
        direction = 1
        if self.rng.random() < 0.2:
            direction = -1
        distance = int(self.rng.uniform(600, 2200) * self.profile.scroll_intensity)
        return {"direction": direction, "distance": max(200, distance)}

    def _build_media_meta(self) -> Dict[str, Any]:
        weights = self.persona.media_action_weights()
        enabled: List[str] = []
        for key, w in weights.items():
            # Ağırlığa göre medya aksiyonlarını aç/kapat
            if self.rng.random() < min(0.9, 0.2 + w * 0.4):
                enabled.append(key)
        return {
            "actions": enabled or ["play_pause"],
            "noise_ms": self.rng.randint(800, 2800),
        }

    def _estimate_form_fields(self) -> int:
        # Basit bir tahmin, ileride DOM analizine göre override edilebilir
        base = 1 + int(self.rng.random() * 3)
        if self.profile.attention_span > 0.75:
            base += 1
        return min(6, base)

    # ------------------------------------------------------------------ #
    # DÜŞÜK SEVİYELİ YARDIMCILAR (Playwright entegrasyonu için)
    # ------------------------------------------------------------------ #

    def generate_mouse_path(
        self,
        start: Tuple[int, int],
        end: Tuple[int, int],
    ) -> List[Tuple[int, int]]:
        """
        PersonaEngine'in mouse path üreticisini kullanarak integer piksel noktaları döndürür.
        """
        pts = self.persona.generate_mouse_path(start, end)
        return [(int(x), int(y)) for (x, y) in pts]

    def generate_scroll_deltas(self, distance: int, direction: int = 1) -> List[int]:
        """
        PersonaEngine'in scroll pattern üreticisine deleger.
        """
        return self.persona.generate_scroll_pattern(distance, direction)


__all__ = ["BehaviorStep", "HumanBehaviorEngineV2"]
