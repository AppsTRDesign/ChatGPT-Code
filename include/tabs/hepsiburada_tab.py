from __future__ import annotations

import json
import os
from typing import Dict, List, Optional, Tuple

from openpyxl import Workbook
from openpyxl.styles import Alignment, Font
from PyQt6 import QtCore, QtSvgWidgets, QtWidgets
from PyQt6.QtGui import QCursor
from reportlab.lib import colors
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle
from reportlab.lib.units import cm
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.platypus import Paragraph, SimpleDocTemplate, Table, TableStyle

from include.models import APP_TITLE, DEJAVU_FONT_PATH, Product, ProductModel
from include.widgets import ProductTable
from modules.hepsiburada import HepsiburadaScraper


class HepsiburadaTab(QtWidgets.QWidget):
    def __init__(self, parent: Optional[QtWidgets.QWidget] = None):
        super().__init__(parent)
        self.scraper = HepsiburadaScraper()
        self.products: List[Product] = []
        self.dynamic_filters: List[Tuple[str, str]] = []
        self._setup_ui()

    def _setup_ui(self):
        layout = QtWidgets.QVBoxLayout(self)
        layout.addWidget(self._build_header())

        form_layout = QtWidgets.QGridLayout()
        self.search_input = QtWidgets.QLineEdit()
        self.search_input.setPlaceholderText("Aranacak kelime")
        self.search_input.textChanged.connect(self._disable_listing)

        self.page_input = QtWidgets.QSpinBox()
        self.page_input.setMinimum(1)
        self.page_input.setMaximum(50)
        self.page_input.setValue(2)

        self.per_page_input = QtWidgets.QSpinBox()
        self.per_page_input.setMinimum(1)
        self.per_page_input.setMaximum(200)
        self.per_page_input.setValue(50)

        self.fetch_filters_btn = QtWidgets.QPushButton("Filtreleri Getir")
        self.fetch_filters_btn.clicked.connect(self._load_filters)
        self.list_products_btn = QtWidgets.QPushButton("Ürünleri Listele")
        self.list_products_btn.setEnabled(False)
        self.list_products_btn.clicked.connect(self._list_products)

        form_layout.addWidget(QtWidgets.QLabel("Arama"), 0, 0)
        form_layout.addWidget(self.search_input, 0, 1)
        form_layout.addWidget(QtWidgets.QLabel("Sayfa Sayısı"), 0, 2)
        form_layout.addWidget(self.page_input, 0, 3)
        form_layout.addWidget(QtWidgets.QLabel("Sayfa Başına Ürün"), 0, 4)
        form_layout.addWidget(self.per_page_input, 0, 5)
        form_layout.addWidget(self.fetch_filters_btn, 1, 0)
        form_layout.addWidget(self.list_products_btn, 1, 1)

        layout.addLayout(form_layout)

        filter_layout = QtWidgets.QHBoxLayout()
        filter_layout.addWidget(self._build_quick_filters())
        filter_layout.addWidget(self._build_sorting_filters())
        filter_layout.addWidget(self._build_dynamic_filters())
        layout.addLayout(filter_layout)

        self.table = ProductTable()
        layout.addWidget(self.table)

        action_layout = QtWidgets.QHBoxLayout()
        self.select_all_btn = QtWidgets.QPushButton("Hepsini Seç")
        self.select_all_btn.clicked.connect(lambda: self.table.set_all_checked(True))
        self.clear_selection_btn = QtWidgets.QPushButton("Seçimleri Kaldır")
        self.clear_selection_btn.clicked.connect(lambda: self.table.set_all_checked(False))
        self.remove_selected_btn = QtWidgets.QPushButton("Seçilileri Sil")
        self.remove_selected_btn.clicked.connect(self.table.remove_checked)

        export_menu = QtWidgets.QMenu()
        export_menu.addAction("JSON", self._export_json)
        export_menu.addAction("XLSX", self._export_xlsx)
        export_menu.addAction("PDF", self._export_pdf)
        self.export_btn = QtWidgets.QPushButton("Seçilileri Dışa Aktar")
        self.export_btn.setMenu(export_menu)

        action_layout.addWidget(self.select_all_btn)
        action_layout.addWidget(self.clear_selection_btn)
        action_layout.addWidget(self.remove_selected_btn)
        action_layout.addStretch()
        action_layout.addWidget(self.export_btn)

        layout.addLayout(action_layout)

    def _build_header(self) -> QtWidgets.QWidget:
        widget = QtWidgets.QWidget()
        layout = QtWidgets.QHBoxLayout(widget)
        svg_path = os.path.join("assets", "logo.svg")
        if os.path.exists(svg_path):
            svg_widget = QtSvgWidgets.QSvgWidget(svg_path)
            svg_widget.setFixedSize(96, 96)
            layout.addWidget(svg_widget)
        title_layout = QtWidgets.QVBoxLayout()
        title = QtWidgets.QLabel("Hepsiburada Veri Toplayıcı")
        title.setStyleSheet("font-size: 20px; font-weight: bold;")
        subtitle = QtWidgets.QLabel("Playwright + PyQt6 ile hızlı arama, filtreleme ve dışa aktarma")
        subtitle.setStyleSheet("color: #555;")
        title_layout.addWidget(title)
        title_layout.addWidget(subtitle)
        layout.addLayout(title_layout)
        layout.addStretch()
        status = QtWidgets.QLabel("Durum: Hazır")
        status.setStyleSheet("color: green; font-weight: bold;")
        layout.addWidget(status)
        return widget

    def _build_quick_filters(self) -> QtWidgets.QGroupBox:
        box = QtWidgets.QGroupBox("Hızlı Filtreler")
        layout = QtWidgets.QVBoxLayout(box)
        self.quick_filter_checks: Dict[str, QtWidgets.QCheckBox] = {}
        for label in HepsiburadaScraper.QUICK_FILTERS.keys():
            cb = QtWidgets.QCheckBox(label)
            self.quick_filter_checks[label] = cb
            layout.addWidget(cb)
        return box

    def _build_sorting_filters(self) -> QtWidgets.QGroupBox:
        box = QtWidgets.QGroupBox("Sıralama")
        layout = QtWidgets.QVBoxLayout(box)
        self.sorting_buttons: Dict[str, QtWidgets.QRadioButton] = {}
        for label in HepsiburadaScraper.SORTING_OPTIONS.keys():
            rb = QtWidgets.QRadioButton(label)
            self.sorting_buttons[label] = rb
            layout.addWidget(rb)
        return box

    def _build_dynamic_filters(self) -> QtWidgets.QGroupBox:
        box = QtWidgets.QGroupBox("Kategori Filtreleri")
        layout = QtWidgets.QVBoxLayout(box)
        self.dynamic_filter_container = QtWidgets.QScrollArea()
        self.dynamic_filter_container.setWidgetResizable(True)
        inner = QtWidgets.QWidget()
        self.dynamic_filter_layout = QtWidgets.QVBoxLayout(inner)
        self.dynamic_filter_container.setWidget(inner)
        layout.addWidget(self.dynamic_filter_container)
        return box

    def _load_filters(self):
        term = self.search_input.text().strip()
        if not term:
            QtWidgets.QMessageBox.warning(self, APP_TITLE, "Lütfen arama kelimesi girin.")
            return
        self.fetch_filters_btn.setEnabled(False)
        QtWidgets.QApplication.setOverrideCursor(QCursor(QtCore.Qt.CursorShape.BusyCursor))
        try:
            filters = self.scraper.fetch_filters(term)
        finally:
            QtWidgets.QApplication.restoreOverrideCursor()
            self.fetch_filters_btn.setEnabled(True)
        if not filters:
            QtWidgets.QMessageBox.warning(self, APP_TITLE, "Filtreler alınamadı, lütfen tekrar deneyin.")
            self.list_products_btn.setEnabled(False)
            return
        self.dynamic_filters = filters
        for i in reversed(range(self.dynamic_filter_layout.count())):
            item = self.dynamic_filter_layout.itemAt(i)
            widget = item.widget()
            if widget:
                widget.setParent(None)
            else:
                self.dynamic_filter_layout.removeItem(item)
        self.dynamic_filter_checks: Dict[str, QtWidgets.QCheckBox] = {}
        for name, query in filters:
            cb = QtWidgets.QCheckBox(name)
            cb.setProperty("query", query)
            self.dynamic_filter_layout.addWidget(cb)
            self.dynamic_filter_checks[name] = cb
        self.dynamic_filter_layout.addStretch()
        self.list_products_btn.setEnabled(True)

    def _collect_quick_filters(self) -> List[str]:
        return [label for label, cb in self.quick_filter_checks.items() if cb.isChecked()]

    def _collect_sorting(self) -> Optional[str]:
        for label, rb in self.sorting_buttons.items():
            if rb.isChecked():
                return label
        return None

    def _collect_dynamic_filters(self) -> List[str]:
        filters: List[str] = []
        if hasattr(self, "dynamic_filter_checks"):
            for _, cb in self.dynamic_filter_checks.items():
                if cb.isChecked():
                    query = cb.property("query")
                    if query:
                        filters.append(query)
        return filters

    def _list_products(self):
        term = self.search_input.text().strip()
        if not term:
            QtWidgets.QMessageBox.warning(self, APP_TITLE, "Lütfen arama kelimesi girin.")
            return
        quick_filters = self._collect_quick_filters()
        sorting = self._collect_sorting()
        extra_filters = self._collect_dynamic_filters()
        page_limit = self.page_input.value()
        per_page_limit = self.per_page_input.value()
        self.products = self.scraper.fetch_products(term, quick_filters, sorting, extra_filters, page_limit, per_page_limit)
        self.table.populate(self.products)

    def _disable_listing(self):
        self.list_products_btn.setEnabled(False)

    def _export_json(self):
        selected = self.table.checked_products()
        if not selected:
            QtWidgets.QMessageBox.information(self, APP_TITLE, "Lütfen dışa aktarmak için ürün seçin.")
            return
        path, _ = QtWidgets.QFileDialog.getSaveFileName(self, "JSON Olarak Kaydet", filter="JSON (*.json)")
        if not path:
            return
        payload = [ProductModel(**vars(p)).model_dump() for p in selected]
        with open(path, "w", encoding="utf-8") as f:
            json.dump(payload, f, ensure_ascii=False, indent=2)
        QtWidgets.QMessageBox.information(self, APP_TITLE, "JSON dışa aktarımı tamamlandı.")

    def _export_xlsx(self):
        selected = self.table.checked_products()
        if not selected:
            QtWidgets.QMessageBox.information(self, APP_TITLE, "Lütfen dışa aktarmak için ürün seçin.")
            return
        path, _ = QtWidgets.QFileDialog.getSaveFileName(self, "Excel Olarak Kaydet", filter="Excel (*.xlsx)")
        if not path:
            return
        wb = Workbook()
        ws = wb.active
        ws.title = "Hepsiburada"
        headers = ["Ürün Adı", "Fiyat", "Link", "Resim", "Reklam?"]
        ws.append(headers)
        font = Font(name="DejaVu Sans") if os.path.exists(DEJAVU_FONT_PATH) else Font(name="Calibri")
        for product in selected:
            ws.append([product.name, product.price, product.link, product.image, "Evet" if product.is_ad else "Hayır"])
        for column_cells in ws.columns:
            length = max(len(str(cell.value)) for cell in column_cells)
            ws.column_dimensions[column_cells[0].column_letter].width = length + 2
            for cell in column_cells:
                cell.font = font
                cell.alignment = Alignment(vertical="top", wrap_text=True)
        wb.save(path)
        QtWidgets.QMessageBox.information(self, APP_TITLE, "XLSX dışa aktarımı tamamlandı.")

    def _export_pdf(self):
        selected = self.table.checked_products()
        if not selected:
            QtWidgets.QMessageBox.information(self, APP_TITLE, "Lütfen dışa aktarmak için ürün seçin.")
            return
        path, _ = QtWidgets.QFileDialog.getSaveFileName(self, "PDF Olarak Kaydet", filter="PDF (*.pdf)")
        if not path:
            return
        if os.path.exists(DEJAVU_FONT_PATH):
            pdfmetrics.registerFont(TTFont("DejaVuSans", DEJAVU_FONT_PATH))
            font_name = "DejaVuSans"
        else:
            font_name = "Helvetica"
        doc = SimpleDocTemplate(path, pagesize=A4, leftMargin=1.5 * cm, rightMargin=1.5 * cm)
        data = [["Ürün Adı", "Fiyat", "Link", "Resim", "Reklam?"]]
        for product in selected:
            data.append([
                product.name,
                product.price,
                product.link,
                product.image,
                "Evet" if product.is_ad else "Hayır",
            ])
        table = Table(data, repeatRows=1)
        style = TableStyle(
            [
                ("BACKGROUND", (0, 0), (-1, 0), colors.lightblue),
                ("TEXTCOLOR", (0, 0), (-1, 0), colors.white),
                ("GRID", (0, 0), (-1, -1), 0.5, colors.grey),
                ("FONTNAME", (0, 0), (-1, -1), font_name),
                ("ALIGN", (0, 0), (-1, -1), "LEFT"),
            ]
        )
        table.setStyle(style)
        paragraph_style = ParagraphStyle("default", fontName=font_name, fontSize=9, leading=11)
        for row_index in range(1, len(data)):
            for col_index in range(len(data[row_index])):
                table._cellvalues[row_index][col_index] = Paragraph(str(data[row_index][col_index]), paragraph_style)
        doc.build([table])
        QtWidgets.QMessageBox.information(self, APP_TITLE, "PDF dışa aktarımı tamamlandı.")
