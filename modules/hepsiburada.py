from __future__ import annotations

import logging
from typing import Dict, List, Optional, Tuple
from urllib.parse import quote_plus

from playwright.sync_api import sync_playwright

from include.models import Product


def _parse_price(price_raw: str) -> float | None:
    if not price_raw:
        return None
    try:
        cleaned = (
            price_raw.replace("TL", "")
            .replace(" ", "")
            .replace(".", "")
            .replace(",", ".")
            .strip()
        )
        return float(cleaned)
    except Exception:
        return None


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
                browser = p.chromium.launch(channel="chrome", headless=self.HEADLESS)
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
            term, page_limit, per_page_limit
        )

        from urllib.parse import urlparse, parse_qs, unquote

        with sync_playwright() as p:
            browser = self._launch_browser(p)
            page = browser.new_page()
            page.set_default_timeout(60000)

            try:
                for page_number in range(1, page_limit + 1):

                    url = self._build_url(
                        term, quick_filters, sorting, extra_filters, page_number
                    )
                    self.logger.info("Sayfa açılıyor: %s", url)

                    page.goto(url, wait_until="domcontentloaded")
                    page.wait_for_timeout(1500)

                    # -----------------------------------------
                    # 🔥 SCROLL – senin eski stabil yöntemin
                    # -----------------------------------------
                    for _ in range(7):
                        page.mouse.wheel(0, 3000)
                        page.wait_for_timeout(800)

                    # Kart seçiciler
                    card_selector = (
                        "li[data-test-id='product-card'], "
                        "li[class^='productListContent-']"
                    )
                    cards = page.query_selector_all(card_selector)
                    self.logger.info("Bulunan ürün kartı: %s", len(cards))

                    if not cards:
                        break

                    collected = 0

                    for card in cards:
                        try:
                            # TITLE & LINK
                            title_el = card.query_selector(
                                "a[data-test-id='product-card-name'], h2 a, h3 a"
                            )
                            if not title_el:
                                continue

                            name = (
                                title_el.get_attribute("title")
                                or title_el.inner_text().strip()
                                or ""
                            )

                            link = title_el.get_attribute("href") or ""
                            if link.startswith("/"):
                                link = "https://www.hepsiburada.com" + link

                            # FİYAT
                            price_el = card.query_selector(
                                "[data-test-id^='price-current-price'], "
                                "[data-test-id^='final-price'], "
                                ".price-module_finalPrice__LtjvY"
                            )
                            price = price_el.inner_text().strip() if price_el else ""

                            # GÖRSEL (zorunlu değil)
                            img_el = card.query_selector(
                                "img[src^='https://productimages.hepsiburada.net'], "
                                "img[data-src^='https://productimages.hepsiburada.net'], "
                                "picture img"
                            )
                            image = ""
                            if img_el:
                                image = (
                                    img_el.get_attribute("src")
                                    or img_el.get_attribute("data-src")
                                    or ""
                                )

                            # 🔥 REKLAM TESPİTİ
                            is_ad = False
                            real_link = link

                            if "adservice.hepsiburada.com" in (link or ""):
                                is_ad = True
                                try:
                                    parsed = urlparse(link)
                                    qs = parse_qs(parsed.query)
                                    redirect = qs.get("redirect", [None])[0]
                                    if redirect:
                                        real_link = unquote(redirect)
                                except Exception:
                                    real_link = link

                            numeric_price = _parse_price(price)

                            products.append(
                                Product(
                                    name=name,
                                    price=numeric_price,
                                    link=real_link,
                                    image=image or "",
                                    is_ad=is_ad,
                                )
                            )

                            collected += 1
                            if collected >= per_page_limit:
                                self.logger.info(
                                    "Sayfa başına limit (%s) doldu.", per_page_limit
                                )
                                break

                        except Exception:
                            continue

            except Exception:
                self.logger.exception("Ürünler alınırken hata oluştu")
            finally:
                browser.close()

        self.logger.info("Toplam ürün sayısı: %s", len(products))
        return products

    def collect_products(
        self,
        term: str,
        quick_filters: list[str],
        sorting: str | None,
        extra_filters: list[str],
        max_pages: int,
        per_page_limit: int,
    ) -> list[Product]:
        self.logger.info(
            "Ürünler getiriliyor | arama='%s' sayfa_limit=%s sayfa_başı_limit=%s",
            term,
            max_pages,
            per_page_limit,
        )
        try:
            return self.fetch_products(
                term,
                quick_filters,
                sorting,
                extra_filters,
                max_pages,
                per_page_limit,
            )
        except Exception:
            self.logger.error("Ürünler alınırken hata oluştu", exc_info=True)
            return []
