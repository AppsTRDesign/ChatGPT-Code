from __future__ import annotations

import logging
from typing import Dict, List, Optional, Tuple
from urllib.parse import quote_plus

from playwright.sync_api import TimeoutError as PlaywrightTimeoutError, sync_playwright

from include.models import Product


class HepsiburadaScraper:
    BASE_URL = "https://www.hepsiburada.com/ara"

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

    def _build_url(self, term: str, quick_filters: List[str], sorting: Optional[str], extra_filters: List[str], page: int) -> str:
        params = [f"q={quote_plus(term)}"]
        for key in quick_filters:
            if key in self.QUICK_FILTERS:
                params.append(self.QUICK_FILTERS[key])
        for item in extra_filters:
            params.append(item)
        if sorting and sorting in self.SORTING_OPTIONS:
            params.append(self.SORTING_OPTIONS[sorting])
        if page > 1:
            params.append(f"sayfa={page}")
        return f"{self.BASE_URL}?" + "&".join(params)

    def fetch_filters(self, term: str) -> List[Tuple[str, str]]:
        self.logger.info("Filtreler getiriliyor (aranan terim: %s)", term)
        filters: List[Tuple[str, str]] = []
        with sync_playwright() as p:
            browser = self._launch_browser(p)
            page = browser.new_page()
            try:
                page.goto(f"{self.BASE_URL}?q={quote_plus(term)}", wait_until="networkidle")
                try:
                    page.wait_for_selector("#VerticalFilter", timeout=7000)
                except PlaywrightTimeoutError:
                    self.logger.warning("Filtre alanı zaman aşımına uğradı")
                    return []
                elements = page.query_selector_all("#VerticalFilter a")
                for el in elements:
                    href = el.get_attribute("href") or ""
                    text = el.inner_text().strip()
                    if href and text:
                        filters.append((text, href.replace("/ara?", "")))
                self.logger.info("%s filtre bulundu", len(filters))
            except Exception:
                self.logger.exception("Filtreler alınırken hata oluştu")
            finally:
                browser.close()
        return filters

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
            try:
                for page_number in range(1, page_limit + 1):
                    url = self._build_url(term, quick_filters, sorting, extra_filters, page_number)
                    self.logger.info("Sayfa açılıyor: %s", url)
                    page.goto(url, wait_until="networkidle")
                    page.wait_for_timeout(1500)
                    card_selector = "li[class^='productListContent-']"
                    cards = page.query_selector_all(card_selector)
                    if not cards:
                        self.logger.info("Kart bulunamadı, döngü sonlandırılıyor (sayfa=%s)", page_number)
                        break
                    collected = 0
                    for card in cards:
                        title_el = card.query_selector("h2 a, h3 a")
                        price_el = card.query_selector("[data-test-id^='final-price'], .price-module_finalPrice__LtjvY")
                        img_el = card.query_selector("picture img[src^='https://productimages.hepsiburada.net']")
                        link = title_el.get_attribute("href") if title_el else ""
                        name = title_el.get_attribute("title") if title_el else ""
                        price = price_el.inner_text().strip() if price_el else ""
                        image = img_el.get_attribute("src") if img_el else ""
                        if not image or "https://productimages.hepsiburada.net" not in image:
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
                            self.logger.info("Sayfa başına limit (%s) doldu", per_page_limit)
                            break
            except Exception:
                self.logger.exception("Ürünler alınırken hata oluştu")
            finally:
                browser.close()
        return products
