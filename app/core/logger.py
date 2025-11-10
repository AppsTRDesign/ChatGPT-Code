from __future__ import annotations

import logging
from pathlib import Path
from typing import Optional

from app.i18n.strings import translator


def configure_logging(log_path: Optional[Path] = None) -> None:
    log_format = "%(asctime)s - %(levelname)s - %(message)s"
    handlers: list[logging.Handler] = [logging.StreamHandler()]
    if log_path:
        log_path.parent.mkdir(parents=True, exist_ok=True)
        handlers.append(logging.FileHandler(log_path, encoding="utf-8"))

    logging.basicConfig(level=logging.INFO, format=log_format, handlers=handlers)


class TranslatingLogRecord(logging.LogRecord):
    def getMessage(self) -> str:  # noqa: D401
        msg = super().getMessage()
        return translator.translate(msg)


class TranslatingLogger(logging.getLoggerClass()):
    def makeRecord(self, *args, **kwargs):  # type: ignore[override]
        record = super().makeRecord(*args, **kwargs)
        if isinstance(record.msg, str) and record.msg.startswith("log."):
            record.__class__ = TranslatingLogRecord
        return record


logging.setLoggerClass(TranslatingLogger)
