from dataclasses import dataclass
from typing import Dict, List, Tuple


@dataclass
class SurfPlanStep:
    title: str
    detail: str
    seconds: int


class PlanEngine:
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
        steps: List[SurfPlanStep] = [SurfPlanStep('Sayfa açılıyor', 'URL yükleniyor', min(5, dwell))]
        remaining = max(5, dwell - 5)
        if flags['mouse_moves']:
            steps.append(SurfPlanStep('Mouse hareketleri', 'Rastgele bölgeler üzerinde dolaşma', min(remaining, 8)))
            remaining -= min(remaining, 8)
        if flags['realistic'] and remaining > 0:
            steps.append(SurfPlanStep('Metin seçimi', 'Paragrafları işaretle ve kopyala', min(remaining, 5)))
            remaining -= min(remaining, 5)
        if flags['scroll']:
            steps.append(SurfPlanStep('Scroll', 'Aşağı-yukarı kaydırma', min(remaining, 6)))
            remaining -= min(remaining, 6)
        if flags['link_clicks']:
            steps.append(SurfPlanStep('Tıklamalar', 'İç linklere doğal tıklamalar', min(remaining, 8)))
            remaining -= min(remaining, 8)
        if flags['form_fill']:
            steps.append(SurfPlanStep('Form doldurma', 'Input odak ve sahte yazım', min(remaining, 6)))
            remaining -= min(remaining, 6)
        if flags['media']:
            steps.append(SurfPlanStep('Medya kontrolü', 'Video/Audio oynatma, ses ve kalite', min(remaining, 12)))
            remaining -= min(remaining, 12)
        if remaining > 0:
            steps.append(SurfPlanStep('Sayfada kalma', 'Okuma ve bekleme', remaining))
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
