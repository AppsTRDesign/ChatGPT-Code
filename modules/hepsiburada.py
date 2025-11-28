from __future__ import annotations

import logging
from typing import Dict, List, Optional, Tuple
from urllib.parse import quote_plus

from playwright.sync_api import sync_playwright

from include.models import Product


class HepsiburadaScraper:
    BASE_URL = "https://www.hepsiburada.com/ara"
    HEADLESS = False

    QUICK_FILTERS: Dict[str, str] = {
        "Hızlı Teslimat": "filtreler=VariantList.VariantListing.ShipmentDay:Hızlı%20Teslimat",
        "Avantajlı Ürünler": "filtreler=VariantList.VariantListing.TagList.avantajli%E2%82%AC2dfiyatlar:avantajli%E2%82%AC2dfiyatlar",
        "İndirimli Ürünler": "filtreler=VariantList.VariantListing.TagList.HasDiscountWithConditionsGroup:HasDiscountWithConditionsGroup",
        "Puan 4-5 Yıldız": "puan=4.5-max",
        "Puan 4 ve Üzeri": "puan=4-max",
        "Puan 3 ve Üzeri": "puan=3-max",
        "Fotoğraflı Değerlendirme": "filtreler=fotograf:1-max",
    }

    SORTING_OPTIONS: Dict[str, str] = {
        "En Düşük Fiyat": "siralama=artanfiyat",
        "En Yüksek Fiyat": "siralama=azalanfiyat",
        "Çok Satanlar": "siralama=coksatan",
        "Çok Değerlendirilenler": "siralama=yorumsayisi",
        "Yüksek Puanlılar": "siralama=degerlendirmepuani",
        "İndirim Oranı": "siralama=indirimurunler",
        "Yeni Eklenenler": "siralama=enyeni",
    }

    def __init__(self) -> None:
        self.logger = logging.getLogger(self.__class__.__name__)

    def _launch_browser(self, playwright_client):
        try:
            self.logger.info("Chrome kanalı ile başlatılıyor (görünür)")
            return playwright_client.chromium.launch(headless=False, channel="chrome")
        except Exception as exc:
            self.logger.warning("Chrome kanalı açılamadı, Chromium kullanılacak: %s", exc)
            return playwright_client.chromium.launch(headless=False)

    def _build_url(
        self,
        term: str,
        quick_filters: List[str],
        sorting: Optional[str],
        extra_filters: List[str],
        page: int,
    ) -> str:
        params = [f"q={quote_plus(term)}"]
        filter_parts: List[str] = []

        def _consume_filter_value(value: str):
            if not value:
                return
            if value.startswith("filtreler="):
                filter_parts.append(value.replace("filtreler=", "", 1))
            else:
                params.append(value)

        for key in quick_filters:
            if key in self.QUICK_FILTERS:
                _consume_filter_value(self.QUICK_FILTERS[key])

        for item in extra_filters:
            _consume_filter_value(item)

        if filter_parts:
            params.append(f"filtreler={';'.join(filter_parts)}")

        if sorting and sorting in self.SORTING_OPTIONS:
            params.append(self.SORTING_OPTIONS[sorting])
        if page > 1:
            params.append(f"sayfa={page}")
        return f"{self.BASE_URL}?" + "&".join(params)

    def fetch_filters(self, term: str):
        try:
            self.logger.info("Filtreler getiriliyor (aranan terim: %s)", term)

            with sync_playwright() as p:
                browser = p.chromium.launch(channel="chrome", headless=False)
                page = browser.new_page()

                url = f"https://www.hepsiburada.com/ara?q={quote_plus(term)}"
                self.logger.info("Sayfa açılıyor: %s", url)

                page.goto(url, wait_until="domcontentloaded", timeout=60000)

                page.wait_for_selector("div.VerticalFilter", timeout=20000)

                filter_groups = page.query_selector_all(
                    "div[data-test-id='collapse-container']"
                )
                if not filter_groups:
                    self.logger.warning("Filtre konteyneri bulunamadı!")
                    return None

                results = {}

                for group in filter_groups:
                    title_el = group.query_selector("div[data-test-id='collapse-title']")
                    if not title_el:
                        continue

                    title = title_el.inner_text().strip()
                    content = group.query_selector("div[data-test-id='collapse-content']")
                    if not content:
                        continue

                    group_items = []

                    checkbox_items = content.query_selector_all("input[type='checkbox']")
                    for chk in checkbox_items:
                        classlist = chk.get_attribute("class") or ""
                        if "switch" in classlist.lower():
                            continue

                        label_el = chk.evaluate_handle("node => node.closest('label')")
                        if label_el:
                            text_el = label_el.query_selector(
                                "div.seoAnchorLink-nCW0yP4qoVI_AhEjVAY_"
                            )
                            if text_el:
                                val = chk.get_attribute("value") or ""
                                name_attr = chk.get_attribute("name") or ""
                                label_text = text_el.inner_text().strip()
                                query_val = (
                                    f"{name_attr}:{val}" if name_attr and val else val
                                )
                                group_items.append(
                                    {
                                        "type": "checkbox",
                                        "label": label_text,
                                        "value": query_val,
                                    }
                                )

                    searchbox = content.query_selector("input[placeholder='Filtrele']")
                    if searchbox:
                        group_items.append(
                            {"type": "searchbox", "placeholder": "Filtrele"}
                        )

                    slider = content.query_selector(
                        ".price-range-slider, .rangeSlider, input[type='range']"
                    )
                    if slider:
                        group_items.append(
                            {
                                "type": "range",
                                "min": slider.get_attribute("min") or "0",
                                "max": slider.get_attribute("max") or "999999",
                            }
                        )

                    if group_items:
                        results[title] = group_items

                browser.close()
                return results

        except Exception:
            self.logger.error("Filtreler alınırken hata oluştu", exc_info=True)
            return None

    def fetch_products(
        self,
        term: str,
        quick_filters: List[str],
        sorting: Optional[str],
        extra_filters: List[str],
        page_limit: int,
        per_page_limit: int,
    ) -> List[Product]:
        products: List[Product] = []
        self.logger.info(
            "Ürünler getiriliyor | arama='%s' sayfa_limit=%s sayfa_başı_limit=%s",
            term,
            page_limit,
            per_page_limit,
        )

        with sync_playwright() as p:
            browser = self._launch_browser(p)
            page = browser.new_page()
            page.set_default_timeout(50000)

            try:
                for page_number in range(1, page_limit + 1):
                    url = self._build_url(term, quick_filters, sorting, extra_filters, page_number)
                    self.logger.info("Sayfa açılıyor: %s", url)

                    # ❌ networkidle yok
                    page.goto(url, wait_until="domcontentloaded")

                    # ✔ ürün konteyneri bekle
                    try:
                        page.wait_for_selector("li[class^='productListContent-']", timeout=10000)
                    except Exception:
                        page.wait_for_timeout(1200)

                    page.wait_for_timeout(800)  # Stabilizasyon

                    card_selector = "li[class^='productListContent-']"
                    cards = page.query_selector_all(card_selector)

                    if not cards:
                        self.logger.info("Kart bulunamadı, döngü sonlandırılıyor (sayfa=%s)", page_number)
                        break

                    collected = 0

                    for card in cards:
                        try:
                            title_el = card.query_selector("h2 a, h3 a")
                            price_el = card.query_selector("[data-test-id^='final-price'], .price-module_finalPrice__LtjvY")
                            img_el = card.query_selector("picture img[src^='https://productimages.hepsiburada.net']")

                            link = title_el.get_attribute("href") if title_el else ""
                            name = title_el.get_attribute("title") if title_el else ""
                            price = price_el.inner_text().strip() if price_el else ""
                            image = img_el.get_attribute("src") if img_el else ""

                            if not image:
                                continue

                            is_ad = link.startswith("https://adservice.hepsiburada.com") if link else False

                            products.append(
                                Product(
                                    name=name or "",
                                    price=price or "",
                                    link=link or "",
                                    image=image,
                                    is_ad=is_ad,
                                )
                            )

                            collected += 1
                            if collected >= per_page_limit:
                                break

                        except Exception:
                            continue

            except Exception:
                self.logger.exception("Ürünler alınırken hata oluştu")
            finally:
                browser.close()

        return products
