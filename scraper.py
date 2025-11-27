from __future__ import annotations

import logging
import sys
from pathlib import Path

from PyQt6 import QtWidgets

from include.main_window import MainWindow


LOG_FILE = Path(__file__).resolve().parent / "scraper.log"


def _configure_logging():
    LOG_FILE.touch(exist_ok=True)
    logging.basicConfig(
        level=logging.INFO,
        format="%(asctime)s [%(levelname)s] %(name)s - %(message)s",
        handlers=[
            logging.FileHandler(LOG_FILE, encoding="utf-8"),
            logging.StreamHandler(sys.stdout),
        ],
        force=True,
    )


def _install_exception_hook():
    def handle_exception(exc_type, exc_value, exc_traceback):
        if issubclass(exc_type, KeyboardInterrupt):
            sys.__excepthook__(exc_type, exc_value, exc_traceback)
            return
        logging.exception("Beklenmeyen hata", exc_info=(exc_type, exc_value, exc_traceback))

    sys.excepthook = handle_exception


def main():
    _configure_logging()
    _install_exception_hook()
    logging.info("NoaSoft Scraper başlatılıyor")
    app = QtWidgets.QApplication([])
    window = MainWindow()
    window.show()
    exit_code = app.exec()
    logging.info("Uygulama kapatıldı (kod=%s)", exit_code)
    return exit_code


if __name__ == "__main__":
    sys.exit(main())
