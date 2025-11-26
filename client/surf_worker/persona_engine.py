from __future__ import annotations

import json
import math
import random
from dataclasses import asdict, dataclass
from datetime import datetime
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
    extends: Optional[str] = None
    mix: Optional[List[Dict[str, float]]] = None


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
    def __init__(
        self,
        profile: PersonaProfile,
        seed: Optional[str] = None,
        memory_key: Optional[str] = None,
        rng: Optional[random.Random] = None,
    ):
        self.profile = profile
        self.seed = str(seed) if seed is not None else None
        self.rng = rng or random.Random()
        if seed is not None:
            self.rng.seed(str(seed))
        self.memory_key = memory_key or profile.name
        self.memory: Dict[str, float] = {
            "sessions": 0,
            "click_success": 0,
            "scroll_depth": 0,
            "media_actions": 0,
        }
        self.dynamic_layer = self._build_dynamic_layer()

    @classmethod
    def from_name(
        cls,
        name: str,
        profiles: Optional[Dict[str, PersonaProfile]] = None,
        seed: Optional[str] = None,
        memory_key: Optional[str] = None,
    ) -> "PersonaEngine":
        profiles = profiles or DEFAULT_PROFILES
        if name not in profiles:
            raise ValueError(f"Bilinmeyen persona: {name}")
        return cls(profiles[name], seed=seed, memory_key=memory_key)

    @classmethod
    def random(
        cls,
        profiles: Optional[Dict[str, PersonaProfile]] = None,
        weights: Optional[Dict[str, float]] = None,
        seed: Optional[str] = None,
        memory_key: Optional[str] = None,
    ) -> "PersonaEngine":
        profiles = profiles or DEFAULT_PROFILES
        keys = list(profiles.keys())
        rng = random.Random()
        if seed is not None:
            rng.seed(str(seed))
        if not weights:
            return cls(profiles[rng.choice(keys)], seed=seed, memory_key=memory_key, rng=rng)
        total = sum(max(0.0, weights.get(k, 0.0)) for k in keys) or 1.0
        r = rng.random() * total
        acc = 0.0
        for k in keys:
            acc += max(0.0, weights.get(k, 0.0))
            if r <= acc:
                return cls(profiles[k], seed=seed, memory_key=memory_key, rng=rng)
        return cls(profiles[keys[-1]], seed=seed, memory_key=memory_key, rng=rng)

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
        except Exception:
            return profiles

        resolved: Dict[str, PersonaProfile] = {}

        def as_profile(name: str, raw: dict, stack: Optional[List[str]] = None) -> PersonaProfile:
            stack = stack or []
            if name in resolved:
                return resolved[name]
            if name in stack:
                return DEFAULT_PROFILES.get(name, DEFAULT_PROFILES["fast"])
            stack.append(name)
            base_name = raw.get("extends")
            base_profile = None
            if base_name:
                parent = entries.get(base_name)
                if isinstance(parent, dict):
                    base_profile = as_profile(base_name, parent, stack)
                elif base_name in profiles:
                    base_profile = profiles[base_name]
            base_profile = base_profile or profiles.get(name) or list(profiles.values())[0]
            base_dict = asdict(base_profile)

            mix_list = raw.get("mix")
            if isinstance(mix_list, list) and mix_list:
                total_weight = 0.0
                accum = {k: 0.0 for k in base_dict.keys() if k not in {"name", "scroll_style", "media_behavior", "read_behavior", "extends", "mix"}}
                for item in mix_list:
                    if not isinstance(item, dict) or not item.get("name"):
                        continue
                    weight = float(item.get("weight", 1.0))
                    total_weight += max(weight, 0.0)
                    ref_name = item["name"]
                    ref_raw = entries.get(ref_name)
                    ref_profile = profiles.get(ref_name)
                    if isinstance(ref_raw, dict):
                        ref_profile = as_profile(ref_name, ref_raw, stack)
                    if not ref_profile:
                        continue
                    ref_dict = asdict(ref_profile)
                    for key in accum:
                        accum[key] += ref_dict.get(key, 0.0) * weight
                if total_weight > 0:
                    for key in accum:
                        base_dict[key] = (base_dict.get(key, 0.0) + accum[key]) / (1.0 + total_weight)

            for key, value in raw.items():
                if key == "name":
                    continue
                if key in base_dict:
                    base_dict[key] = value
            base_dict["name"] = name
            resolved_profile = PersonaProfile(**base_dict)
            resolved[name] = resolved_profile
            stack.pop()
            return resolved_profile

        for name, raw in entries.items():
            if not isinstance(raw, dict):
                continue
            try:
                profiles[name] = as_profile(name, raw)
            except Exception:
                continue

        return profiles

    def _build_dynamic_layer(self) -> Dict[str, float]:
        now = datetime.now()
        hour = now.hour
        circadian = 0.9 if hour < 6 else 1.05 if 9 <= hour <= 18 else 0.95
        jitter = self.rng.uniform(0.9, 1.1)
        return {
            "speed_scale": jitter * circadian,
            "attention_scale": self.rng.uniform(0.85, 1.2),
            "scroll_scale": self.rng.uniform(0.9, 1.25),
            "click_bias": self.rng.uniform(0.9, 1.2),
            "error_bias": self.rng.uniform(0.8, 1.1),
        }

    def reaction_delay_ms(self) -> int:
        low = min(self.profile.reaction_time_min_ms, self.profile.reaction_time_max_ms)
        high = max(self.profile.reaction_time_min_ms, self.profile.reaction_time_max_ms)
        base = self.rng.randint(low, high)
        if self.rng.random() < self.profile.hesitation:
            extra = int(base * self.rng.uniform(0.1, 0.6) * self.dynamic_layer["attention_scale"])
            return base + extra
        return int(base * self.dynamic_layer["attention_scale"])

    def generate_mouse_path(
        self,
        start: Point,
        end: Point,
        min_steps: int = 25,
        max_steps: int = 60,
    ) -> List[Point]:
        if start == end:
            return [start]
        speed_factor = max(0.05, min(1.0, self.profile.mouse_speed * self.dynamic_layer["speed_scale"]))
        steps = int(min_steps + (1.0 - speed_factor) * (max_steps - min_steps))
        steps = max(min_steps, min(max_steps, steps))
        p0 = start
        p3 = end
        mid_x = (start[0] + end[0]) / 2.0
        dist_x = end[0] - start[0]
        dist_y = end[1] - start[1]
        base_dist = math.hypot(dist_x, dist_y)
        curvature = min(160.0, max(40.0, base_dist * 0.4))
        curvature *= self.rng.uniform(0.5, 1.3)
        p1 = (
            mid_x + self.rng.uniform(-curvature, curvature),
            start[1] + self.rng.uniform(10, curvature),
        )
        p2 = (
            mid_x + self.rng.uniform(-curvature, curvature),
            end[1] - self.rng.uniform(10, curvature),
        )
        smooth = max(0.0, min(1.0, self.profile.mouse_smoothness))
        jitter_scale = 1.0 - smooth
        path: List[Point] = []
        for i in range(steps + 1):
            raw_t = i / max(1, steps)
            t = _ease_in_out_cubic(raw_t)
            x, y = _cubic_bezier(p0, p1, p2, p3, t)
            jitter_x = self.rng.uniform(-3, 3) * jitter_scale
            jitter_y = self.rng.uniform(-3, 3) * jitter_scale
            path.append((x + jitter_x, y + jitter_y))
        if self.rng.random() < self.profile.error_rate * 0.5 * self.dynamic_layer["error_bias"]:
            overshoot_steps = max(3, int(steps * 0.1))
            for i in range(1, overshoot_steps + 1):
                alpha = i / overshoot_steps
                overshoot = (
                    end[0] + self.rng.uniform(-5, 5) * alpha,
                    end[1] + self.rng.uniform(-5, 5) * alpha,
                )
                path.append(overshoot)
            path.append(end)
        return path

    def generate_scroll_pattern(self, total_distance: int, direction: int = 1) -> List[int]:
        total_distance = abs(int(total_distance))
        if total_distance == 0:
            return []
        style = self.profile.scroll_style
        intensity = max(0.05, min(1.0, self.profile.scroll_intensity * self.dynamic_layer["scroll_scale"]))
        steps: List[int] = []
        remaining = total_distance
        if style == "constant":
            base_step = int(80 + intensity * 200)
            base_step = max(40, min(360, base_step))
            while remaining > 0:
                delta = min(remaining, int(self.rng.uniform(0.7, 1.3) * base_step))
                steps.append(delta * direction)
                remaining -= delta
        elif style == "stop_and_go":
            while remaining > 0:
                burst = int(80 + intensity * self.rng.uniform(120, 260))
                burst = min(remaining, burst)
                part = 0
                while part < burst:
                    delta = int(self.rng.uniform(40, 120))
                    if part + delta > burst:
                        delta = burst - part
                    steps.append(delta * direction)
                    part += delta
                remaining -= burst
                if self.rng.random() < 0.4:
                    steps.append(0)
        else:
            while remaining > 0:
                delta = int(self.rng.uniform(20, 80))
                delta = min(delta, remaining)
                steps.append(delta * direction)
                remaining -= delta
                if self.rng.random() < 0.3:
                    steps.append(0)
        return steps

    def should_click(self) -> bool:
        freq = max(0.0, min(1.0, self.profile.click_frequency * self.dynamic_layer["click_bias"]))
        base_prob = freq
        if self.rng.random() < self.profile.hesitation * 0.3:
            base_prob *= self.rng.uniform(0.4, 0.8)
        return self.rng.random() < base_prob

    def maybe_offset_target(self, x: float, y: float) -> Point:
        if self.rng.random() > self.profile.error_rate * self.dynamic_layer["error_bias"]:
            return (x, y)
        radius = self.rng.uniform(1, 8)
        angle = self.rng.uniform(0, 2 * math.pi)
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
                base[k] = self.rng.uniform(0.1, 1.0)
        return base

    def dwell_factor_for_text(self) -> float:
        behavior = self.profile.read_behavior
        if behavior == "skim":
            return self.rng.uniform(0.5, 0.9)
        if behavior == "scan":
            return self.rng.uniform(0.8, 1.1)
        if behavior == "deep":
            return self.rng.uniform(1.1, 1.6)
        if behavior == "inconsistent":
            return self.rng.uniform(0.4, 1.4)
        return 1.0

    # -- Hafıza ve varyasyon katmanı -----------------------------------------

    def remember_session(self, stats: Optional[Dict[str, float]] = None) -> None:
        stats = stats or {}
        self.memory["sessions"] += 1
        self.memory["click_success"] += float(stats.get("clicks", 0))
        self.memory["scroll_depth"] += float(stats.get("scroll_px", 0))
        self.memory["media_actions"] += float(stats.get("media_actions", 0))
        decay = 0.92
        self.dynamic_layer["click_bias"] *= decay + 0.08 * (1.0 if stats.get("clicks", 0) else 0.8)
        self.dynamic_layer["speed_scale"] *= decay + 0.08
        self.dynamic_layer["attention_scale"] *= decay + 0.1

    def mix_with(self, other: PersonaProfile, strength: float = 0.5) -> "PersonaEngine":
        strength = max(0.0, min(1.0, strength))
        base = asdict(self.profile)
        overlay = asdict(other)
        for key, value in base.items():
            if isinstance(value, (int, float)) and key in overlay:
                base[key] = value * (1 - strength) + overlay[key] * strength
            elif key not in {"name", "extends", "mix"} and key in overlay:
                base[key] = overlay[key]
        base["name"] = f"{self.profile.name}_mix"
        return PersonaEngine(PersonaProfile(**base), seed=self.seed, memory_key=self.memory_key, rng=self.rng)


def load_persona_profiles(path: Path) -> Dict[str, PersonaProfile]:
    return PersonaEngine.load_profiles(path)


__all__ = [
    "PersonaEngine",
    "PersonaProfile",
    "DEFAULT_PROFILES",
    "load_persona_profiles",
]
