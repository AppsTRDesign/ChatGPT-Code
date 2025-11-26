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

    def _build_equal_steps(self, dwell: int, site: dict) -> List[SurfPlanStep]:
        """Split the dwell time evenly across the selected actions.

        Countdown starts as soon as the page is ready; every enabled action
        gets an equal slice of the requested dwell time so toplam süre ne eksik
        ne fazla olur. If dwell < step_count, only the first ``dwell`` actions
        get a 1-second slot.
        """

        flags = {
            "mobile": site.get("mobile"),
            "realistic": site.get("realistic"),
            "mouse_moves": site.get("mouse_moves"),
            "link_clicks": site.get("link_clicks"),
            "scroll": site.get("scroll"),
            "form_fill": site.get("form_fill"),
            "media": site.get("media"),
        }

        # Page load stabilisation counts as the first slice to keep the total
        # aligned with the requested dwell.
        actions: List[SurfPlanStep] = [
            SurfPlanStep("Sayfa açılıyor", "URL yükleniyor", 0),
        ]
        if flags["mouse_moves"]:
            actions.append(SurfPlanStep("Mouse hareketleri", "Rastgele bölgeler", 0))
        if flags["realistic"]:
            actions.append(SurfPlanStep("Metin seçimi", "Paragrafları işaretle ve kopyala", 0))
        if flags["scroll"]:
            actions.append(SurfPlanStep("Scroll", "Aşağı-yukarı kaydırma", 0))
        if flags["link_clicks"]:
            actions.append(SurfPlanStep("Tıklamalar", "İç linklere doğal tıklamalar", 0))
        if flags["form_fill"]:
            actions.append(SurfPlanStep("Form doldurma", "Input odak ve sahte yazım", 0))
        if flags["media"]:
            actions.append(SurfPlanStep("Medya kontrolü", "Video/Audio oynatma, ses ve kalite", 0))

        # Herhangi bir aksiyon yoksa yine de tek blokta kalmayı planla.
        if not actions:
            actions.append(SurfPlanStep("Sayfada kalma", "Okuma ve bekleme", dwell))

        step_count = len(actions)
        base = dwell // step_count if step_count else dwell
        remainder = dwell - (base * step_count)

        steps: List[SurfPlanStep] = []
        for idx, action in enumerate(actions):
            seconds = base + (1 if idx < remainder else 0)
            if seconds <= 0:
                continue
            steps.append(SurfPlanStep(action.title, action.detail, seconds))

        # Toplam süreyi tam doldurmak için son bir blok ekle.
        consumed = sum(s.seconds for s in steps)
        if consumed < dwell:
            steps.append(SurfPlanStep("Sayfada kalma", "Okuma ve bekleme", dwell - consumed))
        return steps

    def build_plan(self, session_payload: dict) -> Tuple[List[SurfPlanStep], Dict]:
        plan_data = session_payload.get('plan') or []
        site = session_payload.get('site', {})
        if plan_data:
            return [SurfPlanStep(**step) for step in plan_data], site

        dwell = int(site.get('dwell_seconds', 30))
        steps = self._build_equal_steps(dwell, site)
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
