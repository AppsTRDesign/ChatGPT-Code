from __future__ import annotations

from urllib.parse import urlparse


def normalize_entity(entity: str) -> str:
    """Trim message identifiers from Telegram links for Telethon requests."""
    if not entity:
        return entity
    cleaned = entity.strip()
    lower = cleaned.lower()
    if lower.startswith("http://") or lower.startswith("https://"):
        parsed = urlparse(cleaned)
        if parsed.netloc.lower().endswith("t.me"):
            path_parts = [part for part in parsed.path.split("/") if part]
            cleaned_path = ""
            if path_parts:
                if path_parts[0] in {"c", "s"} and len(path_parts) > 1:
                    cleaned_path = "/".join(path_parts[:2])
                else:
                    cleaned_path = path_parts[0]
            scheme = parsed.scheme or "https"
            base = f"{scheme}://{parsed.netloc}"
            if cleaned_path:
                return f"{base}/{cleaned_path}"
            return base
    return cleaned
