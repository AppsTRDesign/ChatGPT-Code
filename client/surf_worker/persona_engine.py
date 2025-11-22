from __future__ import annotations

import json
import math
import random
from dataclasses import dataclass
from pathlib import Path
from typing import Dict, List, Optional, Tuple

Point = Tuple[float, float]


@dataclass
class PersonaProfile:
    name: str
    mouse_speed: float
    mouse_smoothness: float
    scroll_intensity: float
    scroll_style: str
    click_frequency: float
    attention_span: float
    hesitation: float
    error_rate: float
    reaction_time_min_ms: int
    reaction_time_max_ms: int
    media_behavior: str
    read_behavior: str


DEFAULT_PROFILES: Dict[str, PersonaProfile] = {
    "fast": PersonaProfile(
        name="fast",
        mouse_speed=0.9,
        mouse_smoothness=0.3,
        scroll_intensity=0.9,
        scroll_style="constant",
        click_frequency=0.3,
        attention_span=0.3,
        hesitation=0.1,
        error_rate=0.05,
        reaction_time_min_ms=80,
        reaction_time_max_ms=200,
        media_behavior="skip",
        read_behavior="skim",
    ),
    "reader": PersonaProfile(
        name="reader",
        mouse_speed=0.4,
        mouse_smoothness=0.8,
        scroll_intensity=0.3,
        scroll_style="stop_and_go",
        click_frequency=0.7,
        attention_span=0.9,
        hesitation=0.6,
        error_rate=0.3,
        reaction_time_min_ms=300,
        reaction_time_max_ms=800,
        media_behavior="focused",
        read_behavior="deep",
    ),
    "clicker": PersonaProfile(
        name="clicker",
        mouse_speed=0.6,
        mouse_smoothness=0.5,
        scroll_intensity=0.4,
        scroll_style="stop_and_go",
        click_frequency=0.9,
        attention_span=0.5,
        hesitation=0.2,
        error_rate=0.25,
        reaction_time_min_ms=120,
        reaction_time_max_ms=400,
        media_behavior="curious",
        read_behavior="scan",
    ),
    "media_fan": PersonaProfile(
        name="media_fan",
        mouse_speed=0.5,
        mouse_smoothness=0.7,
        scroll_intensity=0.5,
        scroll_style="constant",
        click_frequency=0.4,
        attention_span=0.8,
        hesitation=0.4,
        error_rate=0.2,
        reaction_time_min_ms=150,
        reaction_time_max_ms=600,
        media_behavior="intense",
        read_behavior="scan",
    ),
    "hesitant": PersonaProfile(
        name="hesitant",
        mouse_speed=0.3,
        mouse_smoothness=0.5,
        scroll_intensity=0.2,
        scroll_style="micro",
        click_frequency=0.2,
        attention_span=0.4,
        hesitation=0.9,
        error_rate=0.4,
        reaction_time_min_ms=400,
        reaction_time_max_ms=1000,
        media_behavior="random",
        read_behavior="inconsistent",
    ),
}


def _ease_in_out_cubic(t: float) -> float:
    if t < 0.5:
        return 4 * t * t * t
    t2 = (2 * t) - 2
    return 0.5 * t2 * t2 * t2 + 1


def _cubic_bezier(p0: Point, p1: Point, p2: Point, p3: Point, t: float) -> Point:
    x = (
        (1 - t) ** 3 * p0[0]
        + 3 * (1 - t) ** 2 * t * p1[0]
        + 3 * (1 - t) * t**2 * p2[0]
        + t**3 * p3[0]
    )
    y = (
        (1 - t) ** 3 * p0[1]
        + 3 * (1 - t) ** 2 * t * p1[1]
        + 3 * (1 - t) * t**2 * p2[1]
        + t**3 * p3[1]
    )
    return (x, y)


class PersonaEngine:
    def __init__(self, profile: PersonaProfile):
        self.profile = profile

    @classmethod
    def from_name(cls, name: str, profiles: Optional[Dict[str, PersonaProfile]] = None) -> "PersonaEngine":
        profiles = profiles or DEFAULT_PROFILES
        if name not in profiles:
            raise ValueError(f"Bilinmeyen persona: {name}")
        return cls(profiles[name])

    @classmethod
    def random(
        cls,
        profiles: Optional[Dict[str, PersonaProfile]] = None,
        weights: Optional[Dict[str, float]] = None,
    ) -> "PersonaEngine":
        profiles = profiles or DEFAULT_PROFILES
        keys = list(profiles.keys())
        if not weights:
            return cls(profiles[random.choice(keys)])
        total = sum(max(0.0, weights.get(k, 0.0)) for k in keys) or 1.0
        r = random.random() * total
        acc = 0.0
        for k in keys:
            acc += max(0.0, weights.get(k, 0.0))
            if r <= acc:
                return cls(profiles[k])
        return cls(profiles[keys[-1]])

    @staticmethod
    def load_profiles(path: Path) -> Dict[str, PersonaProfile]:
        profiles = dict(DEFAULT_PROFILES)
        if not path.exists():
            return profiles
        try:
            data = json.loads(path.read_text(encoding="utf-8"))
            if isinstance(data, list):
                entries = {item.get("name"): item for item in data if isinstance(item, dict) and item.get("name")}
            elif isinstance(data, dict):
                entries = data
            else:
                entries = {}
            for name, raw in entries.items():
                if not isinstance(raw, dict):
                    continue
                try:
                    profiles[name] = PersonaProfile(
                        name=name,
                        mouse_speed=float(raw.get("mouse_speed", 0.5)),
                        mouse_smoothness=float(raw.get("mouse_smoothness", 0.5)),
                        scroll_intensity=float(raw.get("scroll_intensity", 0.5)),
                        scroll_style=str(raw.get("scroll_style", "constant")),
                        click_frequency=float(raw.get("click_frequency", 0.5)),
                        attention_span=float(raw.get("attention_span", 0.5)),
                        hesitation=float(raw.get("hesitation", 0.3)),
                        error_rate=float(raw.get("error_rate", 0.1)),
                        reaction_time_min_ms=int(raw.get("reaction_time_min_ms", 120)),
                        reaction_time_max_ms=int(raw.get("reaction_time_max_ms", 320)),
                        media_behavior=str(raw.get("media_behavior", "curious")),
                        read_behavior=str(raw.get("read_behavior", "scan")),
                    )
                except Exception:
                    continue
        except Exception:
            return profiles
        return profiles

    def reaction_delay_ms(self) -> int:
        low = min(self.profile.reaction_time_min_ms, self.profile.reaction_time_max_ms)
        high = max(self.profile.reaction_time_min_ms, self.profile.reaction_time_max_ms)
        base = random.randint(low, high)
        if random.random() < self.profile.hesitation:
            extra = int(base * random.uniform(0.1, 0.6))
            return base + extra
        return base

    def generate_mouse_path(
        self,
        start: Point,
        end: Point,
        min_steps: int = 25,
        max_steps: int = 60,
    ) -> List[Point]:
        if start == end:
            return [start]
        speed_factor = max(0.05, min(1.0, self.profile.mouse_speed))
        steps = int(min_steps + (1.0 - speed_factor) * (max_steps - min_steps))
        steps = max(min_steps, min(max_steps, steps))
        p0 = start
        p3 = end
        mid_x = (start[0] + end[0]) / 2.0
        dist_x = end[0] - start[0]
        dist_y = end[1] - start[1]
        base_dist = math.hypot(dist_x, dist_y)
        curvature = min(160.0, max(40.0, base_dist * 0.4))
        curvature *= random.uniform(0.5, 1.3)
        p1 = (
            mid_x + random.uniform(-curvature, curvature),
            start[1] + random.uniform(10, curvature),
        )
        p2 = (
            mid_x + random.uniform(-curvature, curvature),
            end[1] - random.uniform(10, curvature),
        )
        smooth = max(0.0, min(1.0, self.profile.mouse_smoothness))
        jitter_scale = 1.0 - smooth
        path: List[Point] = []
        for i in range(steps + 1):
            raw_t = i / max(1, steps)
            t = _ease_in_out_cubic(raw_t)
            x, y = _cubic_bezier(p0, p1, p2, p3, t)
            jitter_x = random.uniform(-3, 3) * jitter_scale
            jitter_y = random.uniform(-3, 3) * jitter_scale
            path.append((x + jitter_x, y + jitter_y))
        if random.random() < self.profile.error_rate * 0.5:
            overshoot_steps = max(3, int(steps * 0.1))
            for i in range(1, overshoot_steps + 1):
                alpha = i / overshoot_steps
                overshoot = (
                    end[0] + random.uniform(-5, 5) * alpha,
                    end[1] + random.uniform(-5, 5) * alpha,
                )
                path.append(overshoot)
            path.append(end)
        return path

    def generate_scroll_pattern(self, total_distance: int, direction: int = 1) -> List[int]:
        total_distance = abs(int(total_distance))
        if total_distance == 0:
            return []
        style = self.profile.scroll_style
        intensity = max(0.05, min(1.0, self.profile.scroll_intensity))
        steps: List[int] = []
        remaining = total_distance
        if style == "constant":
            base_step = int(80 + intensity * 200)
            base_step = max(40, min(360, base_step))
            while remaining > 0:
                delta = min(remaining, int(random.uniform(0.7, 1.3) * base_step))
                steps.append(delta * direction)
                remaining -= delta
        elif style == "stop_and_go":
            while remaining > 0:
                burst = int(80 + intensity * random.uniform(120, 260))
                burst = min(remaining, burst)
                part = 0
                while part < burst:
                    delta = int(random.uniform(40, 120))
                    if part + delta > burst:
                        delta = burst - part
                    steps.append(delta * direction)
                    part += delta
                remaining -= burst
                if random.random() < 0.4:
                    steps.append(0)
        else:
            while remaining > 0:
                delta = int(random.uniform(20, 80))
                delta = min(delta, remaining)
                steps.append(delta * direction)
                remaining -= delta
                if random.random() < 0.3:
                    steps.append(0)
        return steps

    def should_click(self) -> bool:
        freq = max(0.0, min(1.0, self.profile.click_frequency))
        base_prob = freq
        if random.random() < self.profile.hesitation * 0.3:
            base_prob *= random.uniform(0.4, 0.8)
        return random.random() < base_prob

    def maybe_offset_target(self, x: float, y: float) -> Point:
        if random.random() > self.profile.error_rate:
            return (x, y)
        radius = random.uniform(1, 8)
        angle = random.uniform(0, 2 * math.pi)
        return (
            x + radius * math.cos(angle),
            y + radius * math.sin(angle),
        )

    def media_action_weights(self) -> Dict[str, float]:
        behavior = self.profile.media_behavior
        base: Dict[str, float] = {
            "play_pause": 1.0,
            "seek": 0.5,
            "volume": 0.5,
            "fullscreen": 0.3,
            "quality_menu": 0.2,
        }
        if behavior == "skip":
            base.update({
                "play_pause": 0.8,
                "seek": 0.9,
            })
        elif behavior == "focused":
            base.update({
                "play_pause": 1.2,
                "seek": 0.2,
                "volume": 0.6,
                "fullscreen": 0.5,
            })
        elif behavior == "curious":
            base.update({
                "play_pause": 1.1,
                "seek": 0.8,
                "volume": 0.7,
                "quality_menu": 0.6,
            })
        elif behavior == "intense":
            base.update({
                "play_pause": 1.3,
                "seek": 0.9,
                "volume": 0.8,
                "fullscreen": 0.7,
                "quality_menu": 0.7,
            })
        elif behavior == "random":
            for k in base.keys():
                base[k] = random.uniform(0.1, 1.0)
        return base

    def dwell_factor_for_text(self) -> float:
        behavior = self.profile.read_behavior
        if behavior == "skim":
            return random.uniform(0.5, 0.9)
        if behavior == "scan":
            return random.uniform(0.8, 1.1)
        if behavior == "deep":
            return random.uniform(1.1, 1.6)
        if behavior == "inconsistent":
            return random.uniform(0.4, 1.4)
        return 1.0


def load_persona_profiles(path: Path) -> Dict[str, PersonaProfile]:
    return PersonaEngine.load_profiles(path)


__all__ = [
    "PersonaEngine",
    "PersonaProfile",
    "DEFAULT_PROFILES",
    "load_persona_profiles",
]
