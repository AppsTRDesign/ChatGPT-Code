import random
from dataclasses import dataclass
from datetime import datetime
from typing import Dict, List, Optional, Tuple


@dataclass
class SurfPlanStep:
    title: str
    detail: str
    seconds: int


class PlanEngine:
    def __init__(self, rng: Optional[random.Random] = None):
        self.rng = rng or random.Random()

    def _mood(self) -> str:
        hour = datetime.now().hour
        if 0 <= hour < 6:
            return self.rng.choice(["uykulu", "dalgın", "aceleci"])
        if 6 <= hour < 12:
            return self.rng.choice(["meraklı", "dikkatli", "dengeci"])
        if 12 <= hour < 18:
            return self.rng.choice(["enerjik", "dengeci", "gezgin"])
        return self.rng.choice(["gezgin", "sabırsız", "dikkatli"])

    def _jitter_seconds(self, base: int, low: float = 0.7, high: float = 1.3) -> int:
        return max(1, int(base * self.rng.uniform(low, high)))

    def _maybe_branch(self, flag: bool) -> bool:
        if not flag:
            return False
        return self.rng.random() > 0.18

    def build_plan(self, session_payload: dict) -> Tuple[List[SurfPlanStep], Dict]:
        plan_data = session_payload.get('plan') or []
        site = session_payload.get('site', {})
        if plan_data:
            return [SurfPlanStep(**step) for step in plan_data], site

        dwell = int(site.get('dwell_seconds', 30))
        flags = {
            'mobile': site.get('mobile'),
            'realistic': site.get('realistic'),
            'mouse_moves': site.get('mouse_moves'),
            'link_clicks': site.get('link_clicks'),
            'scroll': site.get('scroll'),
            'form_fill': site.get('form_fill'),
            'media': site.get('media'),
        }
        mood = self._mood()
        steps: List[SurfPlanStep] = [SurfPlanStep('Sayfa açılıyor', 'URL yükleniyor', min(5, dwell))]
        remaining = max(5, dwell - 5)

        def add_step(title: str, detail: str, cap: int):
            nonlocal remaining
            if remaining <= 0:
                return
            chunk = self._jitter_seconds(min(remaining, cap))
            remaining -= chunk
            steps.append(SurfPlanStep(title, detail, chunk))

        if self._maybe_branch(flags['mouse_moves']):
            add_step('Mouse hareketleri', 'Rastgele bölgeler üzerinde dolaşma', 8)
        if self._maybe_branch(flags['realistic']) and remaining > 0:
            add_step('Metin seçimi', 'Paragrafları işaretle ve kopyala', 5)
        if self._maybe_branch(flags['scroll']):
            add_step('Scroll', 'Aşağı-yukarı kaydırma', 6)
        if self._maybe_branch(flags['link_clicks']):
            add_step('Tıklamalar', 'İç linklere doğal tıklamalar', 8)
        if self._maybe_branch(flags['form_fill']):
            add_step('Form doldurma', 'Input odak ve sahte yazım', 6)
        if self._maybe_branch(flags['media']):
            add_step('Medya kontrolü', 'Video/Audio oynatma, ses ve kalite', 12)

        if mood in {"gezgin", "sabırsız"} and remaining > 4 and self.rng.random() < 0.35:
            add_step('Keşif', 'Hızlı gezinme ve çıkış', min(remaining, 6))
        if remaining > 0:
            add_step('Sayfada kalma', 'Okuma ve bekleme', remaining)

        if self.rng.random() < 0.15:
            steps.append(SurfPlanStep('Erken çıkış', 'Plan dışı kısa gezinme', self._jitter_seconds(3, 0.5, 1.5)))
        return steps, site

    def build_custom_plan(self, dwell: int, flags: dict) -> Tuple[List[SurfPlanStep], dict]:
        site = {
            'dwell_seconds': dwell,
            'mobile': flags.get('mobile'),
            'realistic': flags.get('realistic'),
            'mouse_moves': flags.get('mouse_moves'),
            'link_clicks': flags.get('link_clicks'),
            'scroll': flags.get('scroll'),
            'form_fill': flags.get('form_fill'),
            'media': flags.get('media'),
            'media_actions': flags.get('media_actions', []),
        }
        return self.build_plan({'plan': [], 'site': site})[0], site


__all__ = ["PlanEngine", "SurfPlanStep"]
