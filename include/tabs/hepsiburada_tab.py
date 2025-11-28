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
        self.dynamic_filter_main_layout = QtWidgets.QVBoxLayout(inner)
        self.dynamic_filter_container.setWidget(inner)
        layout.addWidget(self.dynamic_filter_container)
        return box

    def _apply_groupbox_style(self, group_box: QtWidgets.QGroupBox, index: int):
        colors = [
            ("#ff9a00", "#ffb347"),
            ("#ff3d77", "#ff6b9c"),
            ("#00c6ff", "#0072ff"),
            ("#7b2ff7", "#f107a3"),
            ("#42e695", "#3bb2b8"),
            ("#ff512f", "#dd2476"),
        ]
        c1, c2 = colors[index % len(colors)]

        style = f"""
        QGroupBox {{
            margin-top: 12px;
            font-weight: bold;
            border: 2px solid {c2};
            border-radius: 8px;
            padding: 8px;
            background: qlineargradient(
                x1:0, y1:0, x2:1, y2:1,
                stop:0 {c1},
                stop:1 white
            );
        }}
        QGroupBox:title {{
            subcontrol-origin: margin;
            left: 10px;
            top: -2px;
        }}
        """

        group_box.setStyleSheet(style)

    def _styled_slider(self, slider: QtWidgets.QSlider):
        slider.setStyleSheet(
            """
        QSlider::groove:horizontal {
            height: 8px;
            border-radius: 4px;
            background: qlineargradient(
                x1:0, y1:0, x2:1, y2:0,
                stop:0 #ff9a00,
                stop:1 #ff3d77
            );
        }
        QSlider::handle:horizontal {
            background: #ffffff;
            border: 2px solid #ff3d77;
            width: 18px;
            margin: -6px 0;
            border-radius: 9px;
        }
        QSlider::handle:hover {
            background: #ffe5ef;
        }
    """
        )

    def _create_brand_filter_widget(self, items):
        container = QtWidgets.QWidget()
        layout = QtWidgets.QVBoxLayout(container)

        search_box = QtWidgets.QLineEdit()
        search_box.setPlaceholderText("Marka ara…")
        layout.addWidget(search_box)

        list_widget = QtWidgets.QListWidget()
        list_widget.setSelectionMode(QtWidgets.QAbstractItemView.SelectionMode.NoSelection)

        model = QtCore.QStringListModel([name for name, _ in items])
        proxy = QtCore.QSortFilterProxyModel()
        proxy.setSourceModel(model)
        proxy.setFilterCaseSensitivity(QtCore.Qt.CaseSensitivity.CaseInsensitive)

        search_box.textChanged.connect(proxy.setFilterFixedString)

        def refresh_list():
            list_widget.clear()
            for i in range(proxy.rowCount()):
                text = proxy.index(i, 0).data()
                checkbox = QtWidgets.QCheckBox(text)

                for name, query in items:
                    if name == text:
                        checkbox.setProperty("query", query)
                        self.dynamic_filter_checks[name] = checkbox

                item = QtWidgets.QListWidgetItem(list_widget)
                list_widget.setItemWidget(item, checkbox)

        refresh_list()
        proxy.rowsInserted.connect(refresh_list)
        proxy.rowsRemoved.connect(refresh_list)

        layout.addWidget(list_widget)

        return container

    def _render_filters_grouped(self, filters):
        for i in reversed(range(self.dynamic_filter_main_layout.count())):
            item = self.dynamic_filter_main_layout.itemAt(i)
            widget = item.widget()
            if widget:
                widget.setParent(None)

        if isinstance(filters, list):
            grouped = {}
            for name, query in filters:
                if ":" in name:
                    group, item_text = name.split(":", 1)
                else:
                    group, item_text = "Diğer", name
                grouped.setdefault(group.strip(), []).append(
                    {"type": "checkbox", "label": item_text.strip(), "value": query}
                )
            filters = grouped

        self.dynamic_filter_checks = {}

        for idx, (group_name, items) in enumerate(filters.items()):
            group_box = QtWidgets.QGroupBox(group_name)
            group_layout = QtWidgets.QVBoxLayout(group_box)

            checkboxes: List[QtWidgets.QCheckBox] = []
            search_boxes: List[QtWidgets.QLineEdit] = []
            not_found_label = QtWidgets.QLabel("Sonuç bulunamadı")
            not_found_label.setStyleSheet("color: #b00020; font-style: italic;")
            not_found_label.hide()

            for item in items:
                item_type = item.get("type", "checkbox") if isinstance(item, dict) else "checkbox"

                if item_type == "searchbox":
                    search = QtWidgets.QLineEdit()
                    search.setPlaceholderText(item.get("placeholder", "Filtrele"))
                    search_boxes.append(search)
                    group_layout.addWidget(search)
                    continue

                if item_type in {"range", "range-slider"}:
                    h = QtWidgets.QHBoxLayout()
                    self.price_min = QtWidgets.QSlider(QtCore.Qt.Orientation.Horizontal)
                    self.price_max = QtWidgets.QSlider(QtCore.Qt.Orientation.Horizontal)

                    try:
                        min_val = int(item.get("min", 0))
                        max_val = int(item.get("max", 20000))
                    except Exception:
                        min_val, max_val = 0, 20000

                    self.price_min.setRange(min_val, max_val)
                    self.price_max.setRange(min_val, max_val)
                    self.price_min.setValue(min_val)
                    self.price_max.setValue(max_val)

                    self._styled_slider(self.price_min)
                    self._styled_slider(self.price_max)

                    h.addWidget(QtWidgets.QLabel("Min"))
                    h.addWidget(self.price_min)
                    h.addWidget(QtWidgets.QLabel("Max"))
                    h.addWidget(self.price_max)

                    group_layout.addLayout(h)
                    continue

                label_text = item.get("label") if isinstance(item, dict) else str(item)
                query_val = ""
                if isinstance(item, dict):
                    query_val = item.get("query") or item.get("value", "")
                if query_val and not query_val.startswith(("filtreler=", "puan=", "siralama=")):
                    query_val = f"filtreler={query_val}"

                cb = QtWidgets.QCheckBox(label_text)
                cb.setProperty("query", query_val)
                checkboxes.append(cb)
                group_layout.addWidget(cb)
                self.dynamic_filter_checks[label_text] = cb

            if search_boxes and checkboxes:
                def make_filter(_: QtWidgets.QLineEdit):
                    def _filter(text: str):
                        text_lower = text.lower()
                        visible = 0
                        for cb in checkboxes:
                            match = text_lower in cb.text().lower()
                            cb.setVisible(match)
                            if match:
                                visible += 1
                        not_found_label.setVisible(visible == 0)
                    return _filter

                for search in search_boxes:
                    search.textChanged.connect(make_filter(search))

                group_layout.addWidget(not_found_label)
            elif search_boxes:
                group_layout.addWidget(not_found_label)

            self._apply_groupbox_style(group_box, idx)
            self.dynamic_filter_main_layout.addWidget(group_box)

        self.dynamic_filter_main_layout.addStretch()

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
        self._render_filters_grouped(filters)
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
