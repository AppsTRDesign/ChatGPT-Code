from __future__ import annotations

from typing import List, Optional

from PyQt6 import QtCore, QtWidgets

from include.models import Product


class ProductTable(QtWidgets.QTableWidget):
    HEADERS = ["Seç", "Ürün Adı", "Fiyat", "Link", "Resim", "Reklam?"]

    def __init__(self, parent: Optional[QtWidgets.QWidget] = None):
        super().__init__(0, len(self.HEADERS), parent)
        self.setHorizontalHeaderLabels(self.HEADERS)
        self.setSortingEnabled(True)
        self.horizontalHeader().setSectionResizeMode(QtWidgets.QHeaderView.ResizeMode.Stretch)
        self.verticalHeader().setVisible(False)
        self.setSelectionBehavior(QtWidgets.QAbstractItemView.SelectionBehavior.SelectRows)
        self.setEditTriggers(QtWidgets.QAbstractItemView.EditTrigger.NoEditTriggers)
        self.setRowCount(0)

    def populate(self, products: List[Product]):
        self.setRowCount(0)
        for product in products:
            row = self.rowCount()
            self.insertRow(row)

            checkbox_item = QtWidgets.QTableWidgetItem()
            checkbox_item.setFlags(checkbox_item.flags() | QtCore.Qt.ItemFlag.ItemIsUserCheckable)
            checkbox_item.setCheckState(QtCore.Qt.CheckState.Unchecked)
            checkbox_item.setData(QtCore.Qt.ItemDataRole.UserRole, product)
            self.setItem(row, 0, checkbox_item)

            self.setItem(row, 1, QtWidgets.QTableWidgetItem(product.name or ""))
            price_text = "" if product.price is None else str(product.price)
            self.setItem(row, 2, QtWidgets.QTableWidgetItem(price_text))
            self.setItem(row, 3, QtWidgets.QTableWidgetItem(product.link or ""))
            self.setItem(row, 4, QtWidgets.QTableWidgetItem(product.image or ""))
            self.setItem(
                row,
                5,
                QtWidgets.QTableWidgetItem("Evet" if product.is_ad else "Hayır"),
            )

    def checked_products(self) -> List[Product]:
        products: List[Product] = []
        for row in range(self.rowCount()):
            item = self.item(row, 0)
            if not item or item.checkState() != QtCore.Qt.CheckState.Checked:
                continue

            # Prefer the stored Product object (keeps image/price intact even if cells are empty)
            stored = item.data(QtCore.Qt.ItemDataRole.UserRole)
            if isinstance(stored, Product):
                products.append(stored)
                continue

            name_item = self.item(row, 1)
            price_item = self.item(row, 2)
            link_item = self.item(row, 3)
            image_item = self.item(row, 4)
            ad_item = self.item(row, 5)

            if not all([name_item, price_item, link_item, image_item, ad_item]):
                # Skip incomplete rows to avoid AttributeErrors
                continue

            products.append(
                Product(
                    name=name_item.text(),
                    price=self._parse_price_cell(price_item.text()),
                    link=link_item.text(),
                    image=image_item.text(),
                    is_ad=ad_item.text() == "Evet",
                )
            )
        return products

    @staticmethod
    def _parse_price_cell(value: str) -> float | None:
        try:
            cleaned = (
                value.replace("TL", "")
                .replace("tl", "")
                .replace(" ", "")
                .replace(".", "")
                .replace(",", ".")
            )
            return float(cleaned)
        except Exception:
            return None

    def set_all_checked(self, checked: bool):
        state = QtCore.Qt.CheckState.Checked if checked else QtCore.Qt.CheckState.Unchecked
        for row in range(self.rowCount()):
            item = self.item(row, 0)
            if item:
                item.setCheckState(state)

    def remove_checked(self):
        rows_to_remove = [row for row in range(self.rowCount()) if self.item(row, 0).checkState() == QtCore.Qt.CheckState.Checked]
        for row in reversed(rows_to_remove):
            self.removeRow(row)
