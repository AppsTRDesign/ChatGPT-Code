from __future__ import annotations

import json
from dataclasses import asdict, dataclass
from pathlib import Path
from typing import Dict, List, Optional


@dataclass
class MessageTemplate:
    name: str
    body: str
    media: Optional[str] = None


class TemplateStorage:
    def __init__(self, path: Path) -> None:
        self.path = path
        self.path.parent.mkdir(parents=True, exist_ok=True)
        self._templates: Dict[str, List[MessageTemplate]] = {}
        self._assignments: Dict[str, str] = {}
        self.load()

    def load(self) -> None:
        if not self.path.exists():
            self._templates = {}
            self._assignments = {}
            return
        with self.path.open("r", encoding="utf-8") as fp:
            data = json.load(fp)
        templates_data = data.get("templates", {})
        assignments = data.get("assignments", {})
        parsed: Dict[str, List[MessageTemplate]] = {}
        for session, items in templates_data.items():
            session_key = str(session)
            parsed[session_key] = []
            for item in items or []:
                if not isinstance(item, dict):
                    continue
                name = item.get("name")
                body = item.get("body", "")
                media = item.get("media")
                if not name:
                    continue
                parsed[session_key].append(MessageTemplate(name=name, body=body, media=media))
        self._templates = parsed
        self._assignments = {str(k): str(v) for k, v in assignments.items() if isinstance(k, str) and isinstance(v, str)}

    def save(self) -> None:
        payload = {
            "templates": {
                session: [asdict(template) for template in templates]
                for session, templates in self._templates.items()
            },
            "assignments": self._assignments,
        }
        with self.path.open("w", encoding="utf-8") as fp:
            json.dump(payload, fp, indent=2, ensure_ascii=False)

    def list_templates(self, session_name: str) -> List[MessageTemplate]:
        return list(self._templates.get(session_name, []))

    def get_template(self, session_name: str, template_name: str) -> Optional[MessageTemplate]:
        for template in self._templates.get(session_name, []):
            if template.name == template_name:
                return template
        return None

    def upsert_template(self, session_name: str, template: MessageTemplate) -> None:
        session_templates = self._templates.setdefault(session_name, [])
        for idx, existing in enumerate(session_templates):
            if existing.name == template.name:
                session_templates[idx] = template
                break
        else:
            session_templates.append(template)
        self._templates[session_name] = session_templates

    def delete_template(self, session_name: str, template_name: str) -> None:
        session_templates = self._templates.get(session_name, [])
        filtered = [template for template in session_templates if template.name != template_name]
        if filtered:
            self._templates[session_name] = filtered
        else:
            self._templates.pop(session_name, None)
        if self._assignments.get(session_name) == template_name:
            self._assignments.pop(session_name, None)

    def set_assignment(self, session_name: str, template_name: Optional[str]) -> None:
        if template_name:
            self._assignments[session_name] = template_name
        else:
            self._assignments.pop(session_name, None)

    def get_assignment(self, session_name: str) -> Optional[str]:
        return self._assignments.get(session_name)
