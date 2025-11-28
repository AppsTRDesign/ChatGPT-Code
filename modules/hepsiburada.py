from __future__ import annotations

import asyncio
import logging
from typing import Dict, List, Optional, Tuple
from urllib.parse import parse_qs, quote_plus, urlparse

from playwright.async_api import async_playwright
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

    async def fetch_products(
        self,
        page,
        url: str,
        max_pages: int = 1,
        products_per_page: int = 50,
    ) -> list[dict]:

        products: list[dict] = []

        for page_no in range(1, max_pages + 1):

            final_url = f"{url}&sayfa={page_no}"
            print(f"📄 {page_no}. sayfa açılıyor → {final_url}")

            await page.goto(final_url, timeout=60000)

            try:
                await page.wait_for_load_state("domcontentloaded", timeout=30000)
            except:
                pass

            for _ in range(7):
                await page.mouse.wheel(0, 3000)
                await asyncio.sleep(0.8)

            cards = await page.query_selector_all(
                "li[data-test-id='product-card'], li[class*='productListContent']"
            )

            print(f"🔍 Bulunan ürün kartı: {len(cards)}")

            for card in cards:
                try:
                    title_el = await card.query_selector(
                        "h3[data-test-id='product-card-name'], h3 a, h2 a"
                    )
                    title = (
                        (await title_el.inner_text()).strip()
                        if title_el
                        else "İsim yok"
                    )

                    link_el = await card.query_selector(
                        "a[data-test-id='product-card-link'], h3 a, h2 a"
                    )

                    href = await link_el.get_attribute("href") if link_el else None

                    if href and href.startswith("/"):
                        link = "https://hepsiburada.com" + href
                    else:
                        link = href

                    is_ad = False
                    if link and "adservice.hepsiburada.com" in link:
                        is_ad = True
                        real_url = parse_qs(urlparse(link).query).get("redirect", [None])[0]
                        link = real_url or link

                    price_el = await card.query_selector(
                        "[data-test-id='price-current-price'], "
                        "span[data-test-id='price-current-price'], "
                        "div.price-module_finalPrice__LtjvY"
                    )
                    price_raw = (
                        (await price_el.inner_text()).strip()
                        if price_el
                        else "0"
                    )
                    price = price_raw

                    img_el = await card.query_selector("img")
                    image = await img_el.get_attribute("src") if img_el else None

                    product = {
                        "title": title,
                        "price": price,
                        "link": link,
                        "image": image,
                        "is_ad": is_ad,
                    }

                    products.append(product)

                except:
                    continue

        return products

    async def _launch_browser_async(self, playwright_client):
        try:
            self.logger.info("Chrome kanalı ile başlatılıyor (görünür)")
            return await playwright_client.chromium.launch(
                headless=self.HEADLESS, channel="chrome"
            )
        except Exception as exc:
            self.logger.warning("Chrome kanalı açılamadı, Chromium kullanılacak: %s", exc)
            return await playwright_client.chromium.launch(headless=self.HEADLESS)

    async def _collect_products_async(
        self,
        term: str,
        quick_filters: list[str],
        sorting: str | None,
        extra_filters: list[str],
        max_pages: int,
        per_page_limit: int,
    ) -> list[Product]:
        url = self._build_url(term, quick_filters, sorting, extra_filters, 1)
        async with async_playwright() as p:
            browser = await self._launch_browser_async(p)
            page = await browser.new_page()
            page.set_default_timeout(60000)
            raw_products = await self.fetch_products(
                page, url, max_pages=max_pages, products_per_page=per_page_limit
            )
            await browser.close()

        return [
            Product(
                name=item.get("title", ""),
                price=item.get("price"),
                link=item.get("link", ""),
                image=item.get("image", ""),
                is_ad=item.get("is_ad", False),
            )
            for item in raw_products
        ]

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
            return asyncio.run(
                self._collect_products_async(
                    term, quick_filters, sorting, extra_filters, max_pages, per_page_limit
                )
            )
        except Exception:
            self.logger.error("Ürünler alınırken hata oluştu", exc_info=True)
            return []
