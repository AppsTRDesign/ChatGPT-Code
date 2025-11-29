from __future__ import annotations

import asyncio
import logging
import re
from typing import Dict, List, Optional, Tuple
from urllib.parse import parse_qs, quote_plus, unquote, urlparse

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

    @staticmethod
    def upscale_image(url: str) -> str:
        """Normalize Hepsiburada product thumbnails to larger, non-webp variants."""
        if not url:
            return url
        cleaned = url.replace("/format:webp", "")
        cleaned = re.sub(r"/\d{2,4}-\d{2,4}/", "/1000-1000/", cleaned)
        return cleaned

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

    async def _launch_async_browser(self, playwright_client):
        try:
            self.logger.info("Chrome kanalı ile başlatılıyor (görünür)")
            return await playwright_client.chromium.launch(headless=False, channel="chrome")
        except Exception as exc:  # pragma: no cover - fallback
            self.logger.warning("Chrome kanalı açılamadı, Chromium kullanılacak: %s", exc)
            return await playwright_client.chromium.launch(headless=False)

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
            page_url = url if page_no == 1 else f"{url}&sayfa={page_no}"
            self.logger.info("Sayfa açılıyor: %s", page_url)
            await page.goto(page_url, wait_until="domcontentloaded", timeout=60000)

            for _ in range(7):
                await page.mouse.wheel(0, 2500)
                await asyncio.sleep(0.6)

            selectors = [
                "li[data-test-id='product-card']",
                "div[data-test-id='product-card']",
                "li[class*='productListContent']",
            ]

            items = []
            for sel in selectors:
                found = await page.query_selector_all(sel)
                if found:
                    items.extend(found)

            page_collected = 0

            for item in items:
                try:
                    title_el = await item.query_selector(
                        "h3, h2, [data-test-id='product-card-name']"
                    )
                    link_el = await item.query_selector(
                        "a[data-test-id='product-card-link'], a"
                    )

                    image = ""

                    img_el = await item.query_selector(
                        "div[class*='hbImageView-module_hbImageViewRoot__'] picture img[class*='hbImageView-module_hbImage__']"
                    )

                    if not img_el:
                        img_el = await item.query_selector(
                            "div[class*='hbImageView-module_hbImageViewRoot__'] picture img"
                        )

                    srcset_el = await item.query_selector(
                        "div[class*='hbImageView-module_hbImageViewRoot__'] picture source[type='image/webp']"
                    )

                    if srcset_el:
                        srcset_val = await srcset_el.get_attribute("srcset")
                        if srcset_val:
                            image = srcset_val.split(" ")[0]

                    if not image and img_el:
                        image = await img_el.get_attribute("src")

                    image = self.upscale_image(image)

                    if not image or "productimages.hepsiburada.net" not in image:
                        continue

                    price_el = await item.query_selector(
                        "span[data-test-id='price-current-price'], div.price-module_finalPrice__LtjvY"
                    )

                    title = await title_el.inner_text() if title_el else ""
                    if not title.strip():
                        continue

                    link = await link_el.get_attribute("href") if link_el else ""
                    price = await price_el.inner_text() if price_el else ""

                    if link.startswith("/"):
                        link = "https://www.hepsiburada.com" + link

                    is_ad = "adservice.hepsiburada.com" in link
                    if is_ad:
                        try:
                            parsed = urlparse(link)
                            qs = parse_qs(parsed.query)
                            redirect = qs.get("redirect", [None])[0]
                            if redirect:
                                link = unquote(redirect)
                        except Exception:
                            pass

                    product_payload = {
                        "title": title.strip(),
                        "price": price.strip(),
                        "link": link,
                        "image": image,
                        "is_ad": is_ad,
                    }

                    products.append(product_payload)
                    self.logger.info("Ürün bulundu: %s", product_payload["title"])

                    page_collected += 1
                    if page_collected >= products_per_page:
                        break

                except Exception:
                    continue

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

        async def _runner():
            async with async_playwright() as p:
                browser = await self._launch_async_browser(p)
                context = await browser.new_context()
                page = await context.new_page()
                base_url = self._build_url(term, quick_filters, sorting, extra_filters, 1)
                try:
                    return await self.fetch_products(
                        page,
                        base_url,
                        max_pages=max_pages,
                        products_per_page=per_page_limit,
                    )
                finally:
                    await browser.close()

        try:
            raw_products = asyncio.run(_runner())
        except Exception:
            self.logger.error("Ürünler alınırken hata oluştu", exc_info=True)
            return []

        products: list[Product] = []
        for item in raw_products:
            products.append(
                Product(
                    name=item.get("title", ""),
                    price=item.get("price"),
                    link=item.get("link", ""),
                    image=item.get("image", ""),
                    is_ad=item.get("is_ad", False),
                )
            )
        self.logger.info("Toplam ürün sayısı: %s", len(products))
        return products
