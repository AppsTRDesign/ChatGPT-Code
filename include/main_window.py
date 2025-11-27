from __future__ import annotations

from PyQt6 import QtWidgets

from include.models import APP_TITLE
from include.tabs.hepsiburada_tab import HepsiburadaTab


class MainWindow(QtWidgets.QMainWindow):
    def __init__(self):
        super().__init__()
        self.setWindowTitle(APP_TITLE)
        self.resize(1400, 800)
        tabs = QtWidgets.QTabWidget()
        tabs.addTab(HepsiburadaTab(), "Hepsiburada")
        tabs.addTab(QtWidgets.QLabel("Trendyol modülü yakında"), "Trendyol")
        tabs.addTab(QtWidgets.QLabel("n11 modülü yakında"), "n11")
        self.setCentralWidget(tabs)
