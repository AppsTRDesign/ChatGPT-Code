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
        self.setRowCount(len(products))

        for row, product in enumerate(products):
            checkbox_item = QtWidgets.QTableWidgetItem()
            checkbox_item.setFlags(
                QtCore.Qt.ItemFlag.ItemIsEnabled | QtCore.Qt.ItemFlag.ItemIsUserCheckable
            )
            checkbox_item.setCheckState(QtCore.Qt.CheckState.Unchecked)
            self.setItem(row, 0, checkbox_item)

            self.setItem(row, 1, QtWidgets.QTableWidgetItem(product.name or ""))
            price_text = str(product.price) if product.price is not None else ""
            self.setItem(row, 2, QtWidgets.QTableWidgetItem(price_text))
            self.setItem(row, 3, QtWidgets.QTableWidgetItem(product.link or ""))
            self.setItem(row, 4, QtWidgets.QTableWidgetItem(product.image or ""))
            self.setItem(row, 5, QtWidgets.QTableWidgetItem("Evet" if product.is_ad else "Hayır"))

    def checked_products(self):
        results = []
        for row in range(self.rowCount()):
            chk = self.item(row, 0)
            if chk and chk.checkState() == QtCore.Qt.CheckState.Checked:
                name = self.item(row, 1).text() if self.item(row, 1) else ""
                price = self.item(row, 2).text() if self.item(row, 2) else ""
                link = self.item(row, 3).text() if self.item(row, 3) else ""
                image = self.item(row, 4).text() if self.item(row, 4) else ""
                is_ad = self.item(row, 5).text() if self.item(row, 5) else ""

                results.append(
                    {
                        "name": name,
                        "price": price,
                        "link": link,
                        "image": image,
                        "is_ad": (is_ad == "Evet"),
                    }
                )

        return results

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
