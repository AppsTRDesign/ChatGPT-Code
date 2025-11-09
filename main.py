"""Entry point for the Telegram automation GUI."""
from __future__ import annotations

import asyncio
import json
import sys
from pathlib import Path
from typing import Tuple

from PySide6.QtWidgets import QApplication, QInputDialog, QMessageBox
from qasync import QEventLoop

from app.gui import MainWindow
from app.telethon_manager import TelethonManager
from app.translations import Translator

CONFIG_FILE = Path("config.json")


def load_api_credentials(translator: Translator) -> Tuple[int, str]:
    api_id: int | None = None
    api_hash: str | None = None
    if CONFIG_FILE.exists():
        try:
            data = json.loads(CONFIG_FILE.read_text(encoding="utf-8"))
            api_id = int(data.get("api_id")) if data.get("api_id") else None
            api_hash = data.get("api_hash")
        except (ValueError, OSError, TypeError):
            api_id = None
            api_hash = None
    if api_id and api_hash:
        return api_id, api_hash

    # Prompt the user for the credentials using modal dialogs
    api_id_value, ok = QInputDialog.getInt(
        None,
        translator.tr("app_title"),
        translator.tr("api_id_prompt"),
    )
    if not ok:
        raise RuntimeError(translator.tr("api_error"))
    api_hash_value, ok = QInputDialog.getText(
        None,
        translator.tr("app_title"),
        translator.tr("api_hash_prompt"),
    )
    if not ok or not api_hash_value:
        raise RuntimeError(translator.tr("api_error"))

    data = {}
    if CONFIG_FILE.exists():
        try:
            data = json.loads(CONFIG_FILE.read_text(encoding="utf-8"))
        except (ValueError, OSError):
            data = {}
    data.update({"api_id": api_id_value, "api_hash": api_hash_value})
    CONFIG_FILE.write_text(json.dumps(data, indent=2), encoding="utf-8")
    return int(api_id_value), str(api_hash_value)


def main() -> None:
    app = QApplication(sys.argv)
    translator = Translator("en")
    loop = QEventLoop(app)
    asyncio.set_event_loop(loop)
    try:
        api_id, api_hash = load_api_credentials(translator)
    except RuntimeError as exc:
        QMessageBox.critical(None, translator.tr("app_title"), str(exc))
        sys.exit(1)

    manager = TelethonManager(api_id=api_id, api_hash=api_hash, loop=loop)
    window = MainWindow(manager=manager, translator=translator, loop=loop)
    window.show()
    with loop:
        loop.run_forever()


if __name__ == "__main__":
    main()
