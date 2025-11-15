from __future__ import annotations

from dataclasses import dataclass, field
from pathlib import Path
from typing import Dict, List


DEFAULT_TIMEZONE = "Europe/Istanbul"
DEFAULT_LANGUAGE = "tr"


@dataclass
class RateLimitConfig:
    join_interval: float = 45.0
    group_join_interval: float = 15.0
    message_interval: float = 5.0
    scan_interval: float = 1.0
    flood_wait_handling: bool = True

    def to_dict(self) -> Dict[str, float]:
        return {
            "join_interval": self.join_interval,
            "group_join_interval": self.group_join_interval,
            "message_interval": self.message_interval,
            "scan_interval": self.scan_interval,
            "flood_wait_handling": self.flood_wait_handling,
        }

    @classmethod
    def from_dict(cls, data: Dict[str, float]) -> "RateLimitConfig":
        return cls(
            join_interval=float(data.get("join_interval", 45.0)),
            group_join_interval=float(data.get("group_join_interval", 15.0)),
            message_interval=float(data.get("message_interval", 5.0)),
            scan_interval=float(data.get("scan_interval", 1.0)),
            flood_wait_handling=bool(data.get("flood_wait_handling", True)),
        )


@dataclass
class AppSettings:
    api_id: int | None = None
    api_hash: str | None = None
    language: str = DEFAULT_LANGUAGE
    timezone: str = DEFAULT_TIMEZONE
    rate_limit: RateLimitConfig = field(default_factory=RateLimitConfig)
    session_directory: Path = field(default_factory=lambda: Path("session"))
    user_directory: Path = field(default_factory=lambda: Path("users"))
    log_directory: Path = field(default_factory=lambda: Path("logs"))
    max_active_messages: int = 5000

    def ensure_directories(self) -> None:
        self.session_directory.mkdir(parents=True, exist_ok=True)
        self.user_directory.mkdir(parents=True, exist_ok=True)
        self.log_directory.mkdir(parents=True, exist_ok=True)

    def to_dict(self) -> Dict[str, object]:
        return {
            "api_id": self.api_id,
            "api_hash": self.api_hash,
            "language": self.language,
            "timezone": self.timezone,
            "rate_limit": self.rate_limit.to_dict(),
            "session_directory": str(self.session_directory),
            "user_directory": str(self.user_directory),
            "log_directory": str(self.log_directory),
            "max_active_messages": self.max_active_messages,
        }

    @classmethod
    def from_dict(cls, data: Dict[str, object]) -> "AppSettings":
        return cls(
            api_id=data.get("api_id"),
            api_hash=data.get("api_hash"),
            language=str(data.get("language", DEFAULT_LANGUAGE)),
            timezone=str(data.get("timezone", DEFAULT_TIMEZONE)),
            rate_limit=RateLimitConfig.from_dict(data.get("rate_limit", {})),
            session_directory=Path(data.get("session_directory", "session")),
            user_directory=Path(data.get("user_directory", "users")),
            log_directory=Path(data.get("log_directory", "logs")),
            max_active_messages=int(data.get("max_active_messages", 5000)),
        )


class SettingsRepository:
    def __init__(self, path: Path) -> None:
        self.path = path
        self.path.parent.mkdir(parents=True, exist_ok=True)

    def load(self) -> AppSettings:
        if not self.path.exists():
            return AppSettings()
        import json

        with self.path.open("r", encoding="utf-8") as fp:
            data = json.load(fp)
        return AppSettings.from_dict(data)

    def save(self, settings: AppSettings) -> None:
        import json

        with self.path.open("w", encoding="utf-8") as fp:
            json.dump(settings.to_dict(), fp, indent=2, ensure_ascii=False)


POPULAR_TIMEZONES: List[str] = [
    "UTC",
    "Europe/Istanbul",
    "Europe/London",
    "Europe/Paris",
    "Europe/Berlin",
    "Europe/Moscow",
    "Europe/Madrid",
    "Europe/Rome",
    "Europe/Amsterdam",
    "Europe/Athens",
    "Europe/Zurich",
    "Europe/Vienna",
    "Europe/Warsaw",
    "Europe/Prague",
    "Europe/Stockholm",
    "Asia/Dubai",
    "Asia/Tehran",
    "Asia/Tokyo",
    "Asia/Shanghai",
    "Asia/Singapore",
    "Asia/Hong_Kong",
    "Asia/Kuala_Lumpur",
    "Asia/Seoul",
    "Asia/Jakarta",
    "Asia/Karachi",
    "Asia/Calcutta",
    "Asia/Bangkok",
    "America/New_York",
    "America/Chicago",
    "America/Los_Angeles",
    "America/Sao_Paulo",
    "America/Mexico_City",
    "America/Toronto",
    "Australia/Sydney",
    "Australia/Melbourne",
]
