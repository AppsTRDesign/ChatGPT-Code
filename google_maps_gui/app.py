"""GUI application for querying Google Maps business details with bilingual support."""
from __future__ import annotations

import csv
import io
import json
import logging
import threading
import time
from contextlib import suppress
from dataclasses import asdict, dataclass
from typing import Callable, Dict, List, Optional

import requests
import tkinter as tk
from tkinter import filedialog, messagebox, ttk

from PIL import Image, ImageTk, UnidentifiedImageError
from playwright.sync_api import (
    TimeoutError as PlaywrightTimeoutError,
    Error as PlaywrightError,
    Locator,
    Page,
    sync_playwright,
)


logging.basicConfig(
    filename="google_maps_gui.log",
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
)


TRANSLATIONS: Dict[str, Dict[str, str]] = {
    "tr": {
        "app_title": "Google Haritalar İşletme Aracı",
        "tab_api": "API ile Tara",
        "tab_bot": "Bot ile Tara",
        "api_key": "API Anahtarı",
        "query": "Arama",
        "language": "Dil",
        "search": "Ara",
        "result_limit": "İşletme Sayısı",
        "results": "Sonuçlar",
        "details": "Detaylar",
        "column_name": "İsim",
        "column_phone": "Telefon",
        "column_rating": "Puan",
        "save_json": "JSON Kaydet",
        "save_csv": "CSV Kaydet",
        "status_ready": "Hazır",
        "status_searching": "Arama sürüyor...",
        "status_scraping_progress": "{}/{} işletme tarandı",
        "status_error": "Hata oluştu",
        "error_title": "Hata",
        "error_missing_key": "Lütfen API anahtarını girin.",
        "error_missing_query": "Lütfen arama terimi girin.",
        "error_check_logs": "Ayrıntılar için günlük dosyasını kontrol edin.",
        "error_unknown": "Bilinmeyen hata oluştu.",
        "error_no_results_to_save": "Kaydedilecek sonuç yok.",
        "info_title": "Bilgi",
        "save_success": "Dosya başarıyla kaydedildi.",
        "save_error": "Dosya kaydedilemedi.",
        "no_results": "Sonuç bulunamadı.",
        "map_preview": "Harita Önizleme",
        "map_preview_placeholder": "Önizleme yok",
        "address": "Adres",
        "opening_hours": "Çalışma Saatleri",
        "attributes": "Hakkında",
        "attributes_none": "Veri bulunamadı",
        "reviews": "Müşteri Yorumları",
        "review_author": "Yazar",
        "review_rating": "Puan",
        "review_time": "Zaman",
        "review_text": "Yorum",
        "ratings_total": "Toplam Değerlendirme",
    },
    "en": {
        "app_title": "Google Maps Business Tool",
        "tab_api": "Scan via API",
        "tab_bot": "Scan via Bot",
        "api_key": "API Key",
        "query": "Query",
        "language": "Language",
        "search": "Search",
        "result_limit": "Business Count",
        "results": "Results",
        "details": "Details",
        "column_name": "Name",
        "column_phone": "Phone",
        "column_rating": "Rating",
        "save_json": "Save JSON",
        "save_csv": "Save CSV",
        "status_ready": "Ready",
        "status_searching": "Searching...",
        "status_scraping_progress": "Scanned {}/{} businesses",
        "status_error": "An error occurred",
        "error_title": "Error",
        "error_missing_key": "Please enter an API key.",
        "error_missing_query": "Please enter a query.",
        "error_check_logs": "Check the log file for details.",
        "error_unknown": "Unknown error.",
        "error_no_results_to_save": "No results to save.",
        "info_title": "Info",
        "save_success": "File saved successfully.",
        "save_error": "Failed to save the file.",
        "no_results": "No results found.",
        "map_preview": "Map Preview",
        "map_preview_placeholder": "No preview",
        "address": "Address",
        "opening_hours": "Opening Hours",
        "attributes": "About",
        "attributes_none": "No information",
        "reviews": "Customer Reviews",
        "review_author": "Author",
        "review_rating": "Rating",
        "review_time": "Time",
        "review_text": "Review",
        "ratings_total": "Total Ratings",
    },
}


@dataclass
class PlaceReview:
    author_name: str
    rating: Optional[float]
    relative_time: Optional[str]
    text: str

    def to_dict(self) -> Dict[str, Optional[str]]:
        return {
            "author_name": self.author_name,
            "rating": self.rating,
            "relative_time": self.relative_time,
            "text": self.text,
        }


@dataclass
class PlaceResult:
    name: str
    formatted_address: str
    formatted_phone_number: Optional[str]
    opening_hours: List[str]
    rating: Optional[float]
    user_ratings_total: Optional[int]
    reviews: List[PlaceReview]
    attributes: Dict[str, List[str]]

    def to_dict(self) -> Dict[str, object]:
        data = asdict(self)
        data["opening_hours"] = self.opening_hours
        data["reviews"] = [review.to_dict() for review in self.reviews]
        data["attributes"] = self.attributes
        return data

    def to_csv_row(self) -> Dict[str, Optional[str]]:
        return {
            "name": self.name,
            "address": self.formatted_address,
            "phone": self.formatted_phone_number or "",
            "opening_hours": " | ".join(self.opening_hours) if self.opening_hours else "",
            "rating": f"{self.rating:.1f}" if self.rating is not None else "",
            "rating_count": "" if self.user_ratings_total is None else str(self.user_ratings_total),
            "reviews": " || ".join(
                f"{review.author_name}: {review.text}" for review in self.reviews
            ),
            "attributes": " || ".join(
                f"{section}: {', '.join(items)}" for section, items in self.attributes.items()
            ),
        }


class GoogleMapsError(Exception):
    """Raised when the Google Maps API returns an error."""


class GoogleMapsClient:
    """Lightweight client for the Google Maps Places API."""

    BASE_URL = "https://maps.googleapis.com/maps/api/place"

    def __init__(self, api_key: str) -> None:
        self.api_key = api_key

    def search_places(self, query: str, language: str = "tr", limit: int = 5) -> List[PlaceResult]:
        """Search for places and retrieve detailed information for each result."""
        params = {
            "query": query,
            "language": language,
        }
        search_response = self._request("textsearch", params)
        results = search_response.get("results", [])
        if not results:
            return []

        detailed_results: List[PlaceResult] = []
        for place in results[:limit]:
            place_id = place.get("place_id")
            if not place_id:
                continue
            detailed = self._request(
                "details",
                {
                    "place_id": place_id,
                    "language": language,
                    "fields": (
                        "name,formatted_address,formatted_phone_number,"
                        "opening_hours,rating,user_ratings_total,reviews"
                    ),
                },
            )
            result = detailed.get("result")
            if not result:
                continue
            opening_hours = result.get("opening_hours", {}).get("weekday_text", [])
            reviews_data = result.get("reviews", [])
            reviews = [
                PlaceReview(
                    author_name=review.get("author_name", ""),
                    rating=review.get("rating"),
                    relative_time=review.get("relative_time_description"),
                    text=review.get("text", ""),
                )
                for review in reviews_data
            ]
            detailed_results.append(
                PlaceResult(
                    name=result.get("name", ""),
                    formatted_address=result.get("formatted_address", ""),
                    formatted_phone_number=result.get("formatted_phone_number"),
                    opening_hours=opening_hours,
                    rating=result.get("rating"),
                    user_ratings_total=result.get("user_ratings_total"),
                    reviews=reviews,
                    attributes={},
                )
            )
        return detailed_results

    def _request(self, endpoint: str, params: Dict[str, str]) -> Dict:
        url = f"{self.BASE_URL}/{endpoint}/json"
        payload = {**params, "key": self.api_key}
        response = requests.get(url, params=payload, timeout=20)
        response.raise_for_status()
        data = response.json()
        status = data.get("status")
        if status not in {"OK", "ZERO_RESULTS"}:
            raise GoogleMapsError(data.get("error_message") or status or "UNKNOWN_ERROR")
        return data


LOGGER = logging.getLogger(__name__)



class GoogleMapsPlaywrightScraper:
    """Automates Google Maps searches with Playwright and embeds previews."""

    MAP_URL = "https://www.google.com/maps"

    TAB_LABELS = {
        "overview": {"tr": ["genel bakış", "genel"], "en": ["overview"], "_default": ["overview"]},
        "hours": {
            "tr": ["çalışma saatleri", "saatler"],
            "en": ["hours", "opening hours"],
            "_default": ["hours"],
        },
        "reviews": {"tr": ["yorumlar", "değerlendirmeler"], "en": ["reviews"], "_default": ["reviews"]},
        "about": {"tr": ["hakkında"], "en": ["about"], "_default": ["about"]},
    }

    def __init__(self, language: str = "tr", limit: int = 5, max_reviews: int = 3) -> None:
        self.language = language or "tr"
        self.limit = limit
        self.max_reviews = max_reviews

    def search(
        self,
        query: str,
        progress_callback: Optional[Callable[[bytes], None]] = None,
        result_callback: Optional[Callable[[PlaceResult], None]] = None,
    ) -> List[PlaceResult]:
        LOGGER.info("Starting Playwright scrape for query='%s'", query)
        results: List[PlaceResult] = []
        seen_names: set[str] = set()

        try:
            with sync_playwright() as playwright:
                browser = playwright.chromium.launch(
                    headless=True,
                    args=[
                        "--disable-notifications",
                        "--disable-infobars",
                        "--disable-blink-features=AutomationControlled",
                    ],
                )
                context = browser.new_context(
                    locale=self._locale(), viewport={"width": 1280, "height": 900}
                )
                page = context.new_page()
                page.goto(f"{self.MAP_URL}?hl={self.language}", wait_until="domcontentloaded", timeout=60000)
                self._send_progress_screenshot(page, progress_callback)
                self._perform_search(page, query)
                self._wait_for_result_list(page)
                self._send_progress_screenshot(page, progress_callback)

                index = 0
                while index < self.limit:
                    try:
                        article = self._get_article_locator(page, index)
                    except PlaywrightTimeoutError:
                        LOGGER.warning("Timed out while waiting for result #%d", index + 1)
                        break

                    card_name = self._extract_article_name(article)
                    article.click(delay=60)
                    page.wait_for_timeout(450)
                    try:
                        active_name = self._wait_for_place_panel(page, card_name)
                    except PlaywrightTimeoutError:
                        LOGGER.warning("Details panel did not appear for '%s'", card_name or "(unknown)")
                        index += 1
                        continue

                    self._send_progress_screenshot(page, progress_callback)
                    place = self._extract_details(page)
                    if not place.name:
                        place = PlaceResult(
                            name=active_name or card_name or "",
                            formatted_address=place.formatted_address,
                            formatted_phone_number=place.formatted_phone_number,
                            opening_hours=place.opening_hours,
                            rating=place.rating,
                            user_ratings_total=place.user_ratings_total,
                            reviews=place.reviews,
                            attributes=place.attributes,
                        )
                    normalized = self._normalize_text(place.name)
                    if normalized in seen_names:
                        index += 1
                        continue

                    results.append(place)
                    seen_names.add(normalized)
                    if result_callback:
                        try:
                            result_callback(place)
                        except Exception:
                            LOGGER.exception("Result callback failed for '%s'", place.name)
                    page.wait_for_timeout(350)
                    index += 1

                self._send_progress_screenshot(page, progress_callback)
                context.close()
                browser.close()

        except PlaywrightError:
            LOGGER.exception("Playwright automation failed")
            raise

        LOGGER.info("Playwright scrape finished with %d results", len(results))
        return results

    def _locale(self) -> str:
        normalized = (self.language or "en").lower()
        mapping = {"tr": "tr-TR", "en": "en-US"}
        return mapping.get(normalized, "en-US")

    def _perform_search(self, page: Page, query: str) -> None:
        search_box = page.wait_for_selector("input#searchboxinput", timeout=45000)
        search_box.fill("")
        search_box.type(query, delay=40)
        page.keyboard.press("Enter")
        page.wait_for_timeout(600)

    def _wait_for_result_list(self, page: Page) -> None:
        page.wait_for_selector('div[role="article"]', timeout=45000)
        page.wait_for_timeout(1000)

    def _get_article_locator(self, page: Page, index: int) -> Locator:
        attempts = 0
        while True:
            articles = self._get_result_articles(page)
            if index < articles.count():
                locator = articles.nth(index)
                locator.scroll_into_view_if_needed()
                page.wait_for_timeout(250)
                return locator
            if not self._scroll_results_feed(page):
                attempts += 1
            else:
                attempts += 1
            if attempts > 8:
                raise PlaywrightTimeoutError("Not enough search results")
            page.wait_for_timeout(450)

    def _get_result_articles(self, page: Page) -> Locator:
        return page.locator('div[role="feed"] div[role="article"], div[role="article"]')

    def _scroll_results_feed(self, page: Page) -> bool:
        feed = page.locator('div[role="feed"]').first
        if feed.count() == 0:
            page.mouse.wheel(0, 600)
            return True
        handle = feed.element_handle()
        if not handle:
            return False
        handle.evaluate("el => { el.scrollTop = el.scrollTop + el.clientHeight; }")
        return True

    def _extract_article_name(self, locator: Locator) -> str:
        candidates = [
            "div.Nv2PK span",
            "[role=heading]",
            "div.fontHeadlineSmall",
            "div.qBF1Pd",
        ]
        for selector in candidates:
            item = locator.locator(selector)
            if item.count():
                text = self._safe_inner_text(item.first)
                if text:
                    return text
        return ""

    def _wait_for_place_panel(self, page: Page, expected_name: Optional[str]) -> str:
        expected_normalized = self._normalize_text(expected_name or "")
        start = time.time()
        while time.time() - start < 25:
            heading = page.locator('div[role="main"] h1, div[role="main"] div[class*="DUwDvf"]').first
            text = self._safe_inner_text(heading, timeout=1500)
            if text:
                normalized = self._normalize_text(text)
                if not expected_normalized or expected_normalized in normalized:
                    return text
            page.wait_for_timeout(400)
        raise PlaywrightTimeoutError("Place details did not load in time")

    def _extract_details(self, page: Page) -> PlaceResult:
        name = self._first_text(
            page,
            [
                'div[role="main"] h1[class*="fontHeadlineLarge"]',
                'div[role="main"] div[class*="DUwDvf"]',
            ],
        )

        self._select_tab(page, "overview")

        address = self._extract_address(page)
        phone = self._extract_phone(page) or None
        rating, rating_count = self._extract_rating_info(page)

        opening_hours: List[str] = []
        if self._select_tab(page, "hours"):
            opening_hours = self._extract_hours(page)
        if not opening_hours:
            opening_hours = self._extract_hours(page)

        reviews: List[PlaceReview] = []
        if self._select_tab(page, "reviews"):
            reviews = self._extract_reviews(page)
        if not reviews:
            reviews = self._extract_reviews(page)

        attributes: Dict[str, List[str]] = {}
        if self._select_tab(page, "about"):
            attributes = self._extract_attributes(page)
        if not attributes:
            attributes = self._extract_attributes(page)

        return PlaceResult(
            name=name,
            formatted_address=address,
            formatted_phone_number=phone,
            opening_hours=opening_hours,
            rating=rating,
            user_ratings_total=rating_count,
            reviews=reviews,
            attributes=attributes,
        )

    def _first_text(self, page: Page, selectors: List[str]) -> str:
        for selector in selectors:
            locator = page.locator(selector)
            if locator.count():
                text = self._safe_inner_text(locator.first)
                if text:
                    return text
        return ""

    def _extract_address(self, page: Page) -> str:
        selectors = [
            'button[data-item-id*="address"] div.Io6YTe',
            'div[data-item-id*="address"] div.Io6YTe',
            'div.Io6YTe.fontBodyMedium.kR99db',
        ]
        return self._first_text(page, selectors)

    def _extract_phone(self, page: Page) -> str:
        selectors = [
            'button[data-item-id*="phone"] div.Io6YTe',
            'div[data-item-id*="phone"] div.Io6YTe',
            'div.AeaXub div.Io6YTe',
        ]
        return self._first_text(page, selectors)

    def _extract_rating_info(self, page: Page) -> tuple[Optional[float], Optional[int]]:
        rating_text = self._first_text(page, ['div.F7nice span[aria-hidden="true"]', 'div.F7nice span[role="img"]'])
        rating: Optional[float] = None
        if rating_text:
            rating_text = rating_text.replace(",", ".").split()[0]
            with suppress(ValueError):
                rating = float(rating_text)
        count_text = self._first_text(page, ['div.F7nice span[aria-label]', 'div.F7nice span[role="img"]'])
        rating_count: Optional[int] = None
        if count_text:
            digits = "".join(ch for ch in count_text if ch.isdigit())
            if digits:
                rating_count = int(digits)
        if rating_count is None:
            label = self._safe_get_attribute(page.locator('div.F7nice span[aria-label]').first, 'aria-label')
            if label:
                digits = "".join(ch for ch in label if ch.isdigit())
                if digits:
                    rating_count = int(digits)
        return rating, rating_count

    def _extract_hours(self, page: Page) -> List[str]:
        rows = page.locator('table[role="grid"] tr, table.eK4R0e tr')
        opening_hours: List[str] = []
        count = rows.count()
        for idx in range(count):
            row = rows.nth(idx)
            day = self._safe_inner_text(row.locator('td').nth(0))
            value = self._safe_inner_text(row.locator('td').nth(1))
            if not value:
                value = " | ".join(
                    filter(
                        None,
                        [self._safe_inner_text(row.locator('li').nth(i)) for i in range(row.locator('li').count())],
                    )
                )
            if day and value:
                opening_hours.append(f"{day}: {value}")
        return opening_hours

    def _extract_reviews(self, page: Page) -> List[PlaceReview]:
        reviews_locator = page.locator('div[data-review-id]')
        reviews: List[PlaceReview] = []
        count = min(reviews_locator.count(), self.max_reviews)
        for idx in range(count):
            review = reviews_locator.nth(idx)
            author = self._safe_inner_text(review.locator('div.d4r55, button.al6Kxe div.d4r55').first)
            rating_text = self._safe_get_attribute(review.locator('span.kvMYJc').first, "aria-label")
            rating: Optional[float] = None
            if rating_text:
                digits = "".join(ch for ch in rating_text if ch.isdigit() or ch in {",", "."})
                digits = digits.replace(",", ".")
                with suppress(ValueError):
                    rating = float(digits)
            relative = self._safe_inner_text(review.locator('span.rsqaWe').first)
            content = self._safe_inner_text(review.locator('div.MyEned span.wiI7pd, div.MyEned').first)
            if not content:
                content = self._safe_inner_text(review.locator('span.wiI7pd').first)
            reviews.append(
                PlaceReview(
                    author_name=author,
                    rating=rating,
                    relative_time=relative or None,
                    text=content,
                )
            )
        return reviews

    def _extract_attributes(self, page: Page) -> Dict[str, List[str]]:
        container = page.locator('div[aria-label*="hakkında"], div[aria-label*="about"]').first
        if container.count() == 0:
            return {}
        try:
            data = container.evaluate(
                """
                (root) => {
                  return Array.from(root.querySelectorAll('h2')).map(header => {
                    const title = header.innerText.trim();
                    const section = header.parentElement;
                    const items = section ? Array.from(section.querySelectorAll('ul li span')).map(span => (span.getAttribute('aria-label') || span.innerText || '').trim()).filter(Boolean) : [];
                    return { title, items };
                  });
                }
                """
            )
        except PlaywrightError:
            return {}
        attributes: Dict[str, List[str]] = {}
        if not data:
            return attributes
        for entry in data:
            title = (entry.get("title") or "").strip()
            items = [item for item in entry.get("items", []) if item]
            if title and items:
                attributes[title] = items
        return attributes

    def _select_tab(self, page: Page, key: str) -> bool:
        candidates = self._tab_label_candidates(key)
        if not candidates:
            return False
        tabs = page.locator('[role="tab"]')
        count = tabs.count()
        for idx in range(count):
            tab = tabs.nth(idx)
            label = self._normalize_text(self._safe_inner_text(tab))
            if not label:
                continue
            for candidate in candidates:
                if self._normalize_text(candidate) in label:
                    tab.click()
                    page.wait_for_timeout(400)
                    return True
        return False

    def _tab_label_candidates(self, key: str) -> List[str]:
        mapping = self.TAB_LABELS.get(key, {})
        labels = mapping.get(self.language, []) + mapping.get("_default", [])
        seen: set[str] = set()
        ordered: List[str] = []
        for label in labels:
            normalized = self._normalize_text(label)
            if normalized not in seen:
                seen.add(normalized)
                ordered.append(label)
        return ordered

    def _safe_inner_text(self, locator: Locator, timeout: float = 1200) -> str:
        if locator is None:
            return ""
        try:
            text = locator.inner_text(timeout=timeout)
        except PlaywrightTimeoutError:
            return ""
        except PlaywrightError:
            return ""
        return text.strip()

    def _safe_get_attribute(self, locator: Locator, name: str, timeout: float = 1200) -> str:
        if locator is None:
            return ""
        try:
            value = locator.get_attribute(name, timeout=timeout)  # type: ignore[arg-type]
        except PlaywrightTimeoutError:
            return ""
        except PlaywrightError:
            return ""
        return value or ""

    def _normalize_text(self, value: str) -> str:
        return " ".join((value or "").lower().split())

    def _send_progress_screenshot(self, page: Page, callback: Optional[Callable[[bytes], None]]) -> None:
        if not callback:
            return
        with suppress(PlaywrightError):
            png = page.screenshot(full_page=True)
            callback(png)
class Application(tk.Tk):
    def __init__(self) -> None:
        super().__init__()
        self.selected_language = tk.StringVar(value="tr")
        self.title(self._("app_title"))
        self.geometry("1000x650")

        self._api_results: List[PlaceResult] = []
        self._bot_results: List[PlaceResult] = []
        self._bot_map_photo: Optional[ImageTk.PhotoImage] = None
        self._bot_active_limit: int = 0

        self._create_widgets()
        self._layout_widgets()
        self._bind_events()
        self._update_translations()

    def _create_widgets(self) -> None:
        self.notebook = ttk.Notebook(self)

        # API tab widgets
        self.api_tab = ttk.Frame(self.notebook)
        self.api_key_label = ttk.Label(self.api_tab, text="")
        self.api_key_entry = ttk.Entry(self.api_tab, show="*")

        self.api_query_label = ttk.Label(self.api_tab, text="")
        self.api_query_entry = ttk.Entry(self.api_tab)

        self.language_label = ttk.Label(self.api_tab, text="")
        self.language_combo = ttk.Combobox(
            self.api_tab,
            textvariable=self.selected_language,
            values=["tr", "en"],
            state="readonly",
        )

        self.api_limit_label = ttk.Label(self.api_tab, text="")
        self.api_limit_spin = ttk.Spinbox(
            self.api_tab,
            from_=1,
            to=20,
            width=5,
        )
        self._set_spin_value(self.api_limit_spin, "5")

        self.api_search_button = ttk.Button(self.api_tab, text="", command=self._on_api_search)

        self.api_status_var = tk.StringVar(value=self._("status_ready"))
        self.api_status_label = ttk.Label(self.api_tab, textvariable=self.api_status_var)

        self.api_results_label = ttk.Label(self.api_tab, text="")
        self.api_results_tree = ttk.Treeview(
            self.api_tab,
            columns=("name", "phone", "rating"),
            show="headings",
            height=10,
        )
        self.api_results_tree.heading("name", text="")
        self.api_results_tree.heading("phone", text="")
        self.api_results_tree.heading("rating", text="")
        self.api_results_tree.column("name", width=240)
        self.api_results_tree.column("phone", width=180)
        self.api_results_tree.column("rating", width=140, anchor=tk.CENTER)

        self.api_details_label = ttk.Label(self.api_tab, text="")
        self.api_details_text = tk.Text(self.api_tab, wrap=tk.WORD, state=tk.DISABLED, height=14)
        self.api_details_scroll = ttk.Scrollbar(
            self.api_tab, orient=tk.VERTICAL, command=self.api_details_text.yview
        )
        self.api_details_text.configure(yscrollcommand=self.api_details_scroll.set)

        self.api_save_json_button = ttk.Button(
            self.api_tab, text="", command=lambda: self._save_results(self._api_results, "json")
        )
        self.api_save_csv_button = ttk.Button(
            self.api_tab, text="", command=lambda: self._save_results(self._api_results, "csv")
        )

        # Bot tab widgets
        self.bot_tab = ttk.Frame(self.notebook)
        self.bot_query_label = ttk.Label(self.bot_tab, text="")
        self.bot_query_entry = ttk.Entry(self.bot_tab)

        self.bot_language_label = ttk.Label(self.bot_tab, text="")
        self.bot_language_combo = ttk.Combobox(
            self.bot_tab,
            values=["tr", "en"],
            state="readonly",
        )
        self.bot_language_combo.set("tr")

        self.bot_limit_label = ttk.Label(self.bot_tab, text="")
        self.bot_limit_spin = ttk.Spinbox(
            self.bot_tab,
            from_=1,
            to=20,
            width=5,
        )
        self._set_spin_value(self.bot_limit_spin, "5")

        self.bot_search_button = ttk.Button(self.bot_tab, text="", command=self._on_bot_search)
        self.bot_status_var = tk.StringVar(value=self._("status_ready"))
        self.bot_status_label = ttk.Label(self.bot_tab, textvariable=self.bot_status_var)

        self.bot_results_label = ttk.Label(self.bot_tab, text="")
        self.bot_results_tree = ttk.Treeview(
            self.bot_tab,
            columns=("name", "phone", "rating"),
            show="headings",
            height=10,
        )
        self.bot_results_tree.heading("name", text="")
        self.bot_results_tree.heading("phone", text="")
        self.bot_results_tree.heading("rating", text="")
        self.bot_results_tree.column("name", width=240)
        self.bot_results_tree.column("phone", width=180)
        self.bot_results_tree.column("rating", width=140, anchor=tk.CENTER)

        self.bot_details_label = ttk.Label(self.bot_tab, text="")
        self.bot_details_text = tk.Text(self.bot_tab, wrap=tk.WORD, state=tk.DISABLED, height=14)
        self.bot_details_scroll = ttk.Scrollbar(
            self.bot_tab, orient=tk.VERTICAL, command=self.bot_details_text.yview
        )
        self.bot_details_text.configure(yscrollcommand=self.bot_details_scroll.set)

        self.bot_map_label = ttk.Label(self.bot_tab, text="")
        self.bot_map_canvas = ttk.Label(
            self.bot_tab,
            text="",
            anchor=tk.CENTER,
            relief=tk.SUNKEN,
            borderwidth=1,
            width=40,
            padding=5,
            wraplength=260,
        )

        self.bot_save_json_button = ttk.Button(
            self.bot_tab, text="", command=lambda: self._save_results(self._bot_results, "json")
        )
        self.bot_save_csv_button = ttk.Button(
            self.bot_tab, text="", command=lambda: self._save_results(self._bot_results, "csv")
        )

        self.notebook.add(self.api_tab, text="")
        self.notebook.add(self.bot_tab, text="")

    def _layout_widgets(self) -> None:
        self.notebook.pack(fill=tk.BOTH, expand=True)

        api_padding = {"padx": 10, "pady": 5}
        self.api_tab.columnconfigure(1, weight=1)
        self.api_tab.columnconfigure(2, weight=1)
        self.api_tab.columnconfigure(3, weight=1)
        self.api_tab.columnconfigure(4, weight=0)
        self.api_tab.rowconfigure(4, weight=1)

        self.api_key_label.grid(row=0, column=0, sticky=tk.W, **api_padding)
        self.api_key_entry.grid(row=0, column=1, columnspan=3, sticky=tk.EW, **api_padding)

        self.api_query_label.grid(row=1, column=0, sticky=tk.W, **api_padding)
        self.api_query_entry.grid(row=1, column=1, columnspan=3, sticky=tk.EW, **api_padding)

        self.language_label.grid(row=2, column=0, sticky=tk.W, **api_padding)
        self.language_combo.grid(row=2, column=1, sticky=tk.W, **api_padding)
        self.api_limit_label.grid(row=2, column=2, sticky=tk.W, **api_padding)
        self.api_limit_spin.grid(row=2, column=3, sticky=tk.W, **api_padding)
        self.api_search_button.grid(row=2, column=4, sticky=tk.E, **api_padding)

        self.api_results_label.grid(row=3, column=0, sticky=tk.W, **api_padding)
        self.api_results_tree.grid(row=4, column=0, columnspan=3, sticky=tk.NSEW, padx=(10, 0), pady=5)
        self.api_details_label.grid(row=3, column=3, sticky=tk.W, **api_padding)
        self.api_details_text.grid(row=4, column=3, sticky=tk.NSEW, padx=(0, 10), pady=5)
        self.api_details_scroll.grid(row=4, column=4, sticky=tk.NS, pady=5)

        self.api_save_json_button.grid(row=5, column=0, sticky=tk.W, **api_padding)
        self.api_save_csv_button.grid(row=5, column=1, sticky=tk.W, **api_padding)
        self.api_status_label.grid(row=5, column=3, columnspan=2, sticky=tk.E, **api_padding)

        bot_padding = {"padx": 10, "pady": 5}
        self.bot_tab.columnconfigure(0, weight=0)
        self.bot_tab.columnconfigure(1, weight=1)
        self.bot_tab.columnconfigure(2, weight=0)
        self.bot_tab.columnconfigure(3, weight=0)
        self.bot_tab.columnconfigure(4, weight=1)
        self.bot_tab.rowconfigure(3, weight=1)
        self.bot_tab.rowconfigure(5, weight=1)

        self.bot_query_label.grid(row=0, column=0, sticky=tk.W, **bot_padding)
        self.bot_query_entry.grid(row=0, column=1, columnspan=2, sticky=tk.EW, **bot_padding)
        self.bot_search_button.grid(row=0, column=3, sticky=tk.E, **bot_padding)
        self.bot_map_label.grid(row=0, column=4, sticky=tk.W, **bot_padding)

        self.bot_language_label.grid(row=1, column=0, sticky=tk.W, **bot_padding)
        self.bot_language_combo.grid(row=1, column=1, sticky=tk.W, **bot_padding)
        self.bot_limit_label.grid(row=1, column=2, sticky=tk.W, **bot_padding)
        self.bot_limit_spin.grid(row=1, column=3, sticky=tk.W, **bot_padding)
        self.bot_map_canvas.grid(row=1, column=4, rowspan=3, sticky=tk.NSEW, padx=(0, 10), pady=5)

        self.bot_results_label.grid(row=2, column=0, sticky=tk.W, **bot_padding)
        self.bot_results_tree.grid(row=3, column=0, columnspan=4, sticky=tk.NSEW, padx=(10, 0), pady=5)

        self.bot_details_label.grid(row=4, column=0, sticky=tk.W, **bot_padding)
        self.bot_details_text.grid(row=5, column=0, columnspan=4, sticky=tk.NSEW, padx=(10, 0), pady=5)
        self.bot_details_scroll.grid(row=5, column=4, sticky=tk.NS, pady=5)

        self.bot_save_json_button.grid(row=6, column=0, sticky=tk.W, **bot_padding)
        self.bot_save_csv_button.grid(row=6, column=1, sticky=tk.W, **bot_padding)
        self.bot_status_label.grid(row=6, column=3, columnspan=2, sticky=tk.E, **bot_padding)

    def _bind_events(self) -> None:
        self.language_combo.bind("<<ComboboxSelected>>", lambda _: self._update_translations())
        self.api_results_tree.bind("<<TreeviewSelect>>", lambda _: self._on_select_api_result())
        self.bot_results_tree.bind("<<TreeviewSelect>>", lambda _: self._on_select_bot_result())

    def _on_api_search(self) -> None:
        api_key = self.api_key_entry.get().strip()
        query = self.api_query_entry.get().strip()
        if not api_key:
            messagebox.showerror(self._("error_title"), self._("error_missing_key"))
            return
        if not query:
            messagebox.showerror(self._("error_title"), self._("error_missing_query"))
            return

        language = self.selected_language.get()
        try:
            limit = int(self.api_limit_spin.get())
        except (ValueError, tk.TclError):
            limit = 5
        limit = max(1, min(limit, 20))
        self._set_spin_value(self.api_limit_spin, str(limit))
        self.api_status_var.set(self._("status_searching"))
        self.api_search_button.config(state=tk.DISABLED)

        def worker() -> None:
            try:
                client = GoogleMapsClient(api_key)
                results = client.search_places(query, language=language, limit=limit)
            except requests.RequestException as exc:
                LOGGER.exception("HTTP error while calling Places API: %s", exc)
                self._handle_api_error(str(exc))
                return
            except GoogleMapsError as exc:
                LOGGER.exception("Places API returned an error: %s", exc)
                self._handle_api_error(str(exc))
                return
            LOGGER.info("Places API request completed with %d results", len(results))
            self.after(0, lambda: self._update_api_results(results))

        threading.Thread(target=worker, daemon=True).start()

    def _on_bot_search(self) -> None:
        query = self.bot_query_entry.get().strip()
        if not query:
            messagebox.showerror(self._("error_title"), self._("error_missing_query"))
            return

        language = self.bot_language_combo.get() or "tr"
        try:
            limit = int(self.bot_limit_spin.get())
        except (ValueError, tk.TclError):
            limit = 5
        limit = max(1, min(limit, 20))
        self._set_spin_value(self.bot_limit_spin, str(limit))
        self._bot_active_limit = limit
        self.bot_status_var.set(self._("status_scraping_progress").format(0, limit))
        self.bot_search_button.config(state=tk.DISABLED)
        self._set_bot_map_placeholder()
        self._clear_bot_details()
        for item in self.bot_results_tree.get_children():
            self.bot_results_tree.delete(item)
        self._bot_results.clear()

        def worker() -> None:
            try:
                scraper = GoogleMapsPlaywrightScraper(language=language, limit=limit)
                results = scraper.search(
                    query,
                    progress_callback=self._queue_bot_map_update,
                    result_callback=self._queue_bot_result_append,
                )
            except PlaywrightError as exc:
                LOGGER.exception("Playwright error: %s", exc)
                self._handle_bot_error(str(exc))
                return
            except Exception as exc:  # broad fallback for automation edge cases
                LOGGER.exception("Unexpected Playwright error: %s", exc)
                self._handle_bot_error(str(exc))
                return
            self.after(0, lambda: self._update_bot_results(results))

        threading.Thread(target=worker, daemon=True).start()

    def _handle_api_error(self, message: str) -> None:
        LOGGER.error("API error surfaced to UI: %s", message or "(empty message)")

        def callback() -> None:
            self.api_status_var.set(self._("status_error"))
            display_message = message or self._("error_unknown")
            messagebox.showerror(
                self._("error_title"),
                f"{display_message}\n\n{self._('error_check_logs')}",
            )
            self.api_search_button.config(state=tk.NORMAL)

        self.after(0, callback)

    def _handle_bot_error(self, message: str) -> None:
        LOGGER.error("Bot error surfaced to UI: %s", message or "(empty message)")

        def callback() -> None:
            self.bot_status_var.set(self._("status_error"))
            display_message = message or self._("error_unknown")
            messagebox.showerror(
                self._("error_title"),
                f"{display_message}\n\n{self._('error_check_logs')}",
            )
            self.bot_search_button.config(state=tk.NORMAL)

        self.after(0, callback)

    def _update_api_results(self, results: List[PlaceResult]) -> None:
        self._api_results = results
        for item in self.api_results_tree.get_children():
            self.api_results_tree.delete(item)
        if not results:
            self.api_status_var.set(self._("no_results"))
            self.api_search_button.config(state=tk.NORMAL)
            self._clear_api_details()
            return
        for index, result in enumerate(results):
            phone = result.formatted_phone_number or "-"
            rating_display = "-"
            if result.rating is not None:
                rating_display = f"{result.rating:.1f}"
                if result.user_ratings_total is not None:
                    rating_display = f"{rating_display} ({result.user_ratings_total})"
            self.api_results_tree.insert(
                "",
                tk.END,
                iid=str(index),
                values=(result.name, phone, rating_display),
            )
        self.api_status_var.set(self._("status_ready"))
        self.api_search_button.config(state=tk.NORMAL)
        self.api_results_tree.selection_set("0")
        self.api_results_tree.focus("0")
        self._show_api_details(0)

    def _update_bot_results(self, results: List[PlaceResult]) -> None:
        existing_items = self.bot_results_tree.get_children()
        self._bot_results = results
        if not results:
            for item in existing_items:
                self.bot_results_tree.delete(item)
            self.bot_status_var.set(self._("no_results"))
            self.bot_search_button.config(state=tk.NORMAL)
            self._clear_bot_details()
            return

        def compute_rating_display(place: PlaceResult) -> str:
            if place.rating is None:
                return "-"
            display = f"{place.rating:.1f}"
            if place.user_ratings_total is not None:
                display = f"{display} ({place.user_ratings_total})"
            return display

        if len(existing_items) != len(results):
            for item in existing_items:
                self.bot_results_tree.delete(item)
            for index, result in enumerate(results):
                self.bot_results_tree.insert(
                    "",
                    tk.END,
                    iid=str(index),
                    values=(
                        result.name,
                        result.formatted_phone_number or "-",
                        compute_rating_display(result),
                    ),
                )
        else:
            for index, result in enumerate(results):
                self.bot_results_tree.item(
                    str(index),
                    values=(
                        result.name,
                        result.formatted_phone_number or "-",
                        compute_rating_display(result),
                    ),
                )

        self.bot_status_var.set(self._("status_ready"))
        self.bot_search_button.config(state=tk.NORMAL)
        if not self.bot_results_tree.selection():
            self.bot_results_tree.selection_set("0")
            self.bot_results_tree.focus("0")
            self._show_bot_details(0)

    def _clear_api_details(self) -> None:
        self.api_details_text.config(state=tk.NORMAL)
        self.api_details_text.delete("1.0", tk.END)
        self.api_details_text.config(state=tk.DISABLED)

    def _clear_bot_details(self) -> None:
        self.bot_details_text.config(state=tk.NORMAL)
        self.bot_details_text.delete("1.0", tk.END)
        self.bot_details_text.config(state=tk.DISABLED)

    def _queue_bot_map_update(self, image_bytes: bytes) -> None:
        self.after(0, lambda: self._update_bot_map_image(image_bytes))

    def _queue_bot_result_append(self, result: PlaceResult) -> None:
        self.after(0, lambda: self._append_bot_result(result))

    def _append_bot_result(self, result: PlaceResult) -> None:
        index = len(self._bot_results)
        self._bot_results.append(result)
        phone = result.formatted_phone_number or "-"
        rating_display = "-"
        if result.rating is not None:
            rating_display = f"{result.rating:.1f}"
            if result.user_ratings_total is not None:
                rating_display = f"{rating_display} ({result.user_ratings_total})"
        self.bot_results_tree.insert(
            "",
            tk.END,
            iid=str(index),
            values=(result.name, phone, rating_display),
        )
        limit = max(self._bot_active_limit, len(self._bot_results))
        self.bot_status_var.set(
            self._("status_scraping_progress").format(len(self._bot_results), limit)
        )
        self.bot_results_tree.selection_set(str(index))
        self.bot_results_tree.focus(str(index))
        self._show_bot_details(index)

    def _update_bot_map_image(self, image_bytes: bytes) -> None:
        if not image_bytes:
            self._set_bot_map_placeholder()
            return
        try:
            screenshot = Image.open(io.BytesIO(image_bytes))
        except (UnidentifiedImageError, OSError):
            self._set_bot_map_placeholder()
            return
        screenshot.thumbnail((520, 360))
        self._bot_map_photo = ImageTk.PhotoImage(screenshot)
        self.bot_map_canvas.configure(image=self._bot_map_photo, text="")

    def _set_bot_map_placeholder(self) -> None:
        self._bot_map_photo = None
        self.bot_map_canvas.configure(image="", text=self._("map_preview_placeholder"))

    def _on_select_api_result(self) -> None:
        selection = self.api_results_tree.selection()
        if not selection:
            return
        index = int(selection[0])
        self._show_api_details(index)

    def _on_select_bot_result(self) -> None:
        selection = self.bot_results_tree.selection()
        if not selection:
            return
        index = int(selection[0])
        self._show_bot_details(index)

    def _show_api_details(self, index: int) -> None:
        if index >= len(self._api_results):
            return
        result = self._api_results[index]
        self._render_details(self.api_details_text, result)

    def _show_bot_details(self, index: int) -> None:
        if index >= len(self._bot_results):
            return
        result = self._bot_results[index]
        self._render_details(self.bot_details_text, result)

    def _render_details(self, text_widget: tk.Text, result: PlaceResult) -> None:
        lines = [
            f"{self._('column_name')}: {result.name}",
            f"{self._('column_phone')}: {result.formatted_phone_number or '-'}",
            f"{self._('column_rating')}: {result.rating or '-'}",
        ]
        if result.user_ratings_total is not None:
            lines.append(f"{self._('ratings_total')}: {result.user_ratings_total}")
        lines.append(f"{self._('address')}: {result.formatted_address or '-'}")
        lines.append("")
        lines.append(f"{self._('opening_hours')}:")
        if result.opening_hours:
            lines.extend(f"  - {item}" for item in result.opening_hours)
        else:
            lines.append("  - -")
        lines.append("")
        lines.append(f"{self._('attributes')}:")
        if result.attributes:
            for section, values in result.attributes.items():
                pretty_values = ", ".join(values) if values else self._('attributes_none')
                lines.append(f"  - {section}: {pretty_values}")
        else:
            lines.append(f"  - {self._('attributes_none')}")
        lines.append("")
        lines.append(f"{self._('reviews')}:")
        if result.reviews:
            for review in result.reviews:
                review_lines = [
                    f"  {self._('review_author')}: {review.author_name or '-'}",
                ]
                if review.rating is not None:
                    review_lines.append(f"  {self._('review_rating')}: {review.rating}")
                if review.relative_time:
                    review_lines.append(f"  {self._('review_time')}: {review.relative_time}")
                if review.text:
                    review_lines.append(f"  {self._('review_text')}: {review.text}")
                lines.extend(review_lines)
                lines.append("")
        else:
            lines.append("  - -")

        text_widget.config(state=tk.NORMAL)
        text_widget.delete("1.0", tk.END)
        text_widget.insert(tk.END, "\n".join(lines))
        text_widget.config(state=tk.DISABLED)

    def _set_spin_value(self, spinbox: ttk.Spinbox, value: str) -> None:
        try:
            spinbox.set(value)
        except (AttributeError, tk.TclError):
            spinbox.delete(0, tk.END)
            spinbox.insert(0, value)

    def _save_results(self, results: List[PlaceResult], file_format: str) -> None:
        if not results:
            messagebox.showinfo(self._("info_title"), self._("error_no_results_to_save"))
            return
        if file_format == "json":
            path = filedialog.asksaveasfilename(
                defaultextension=".json",
                filetypes=[("JSON", "*.json")],
            )
            if not path:
                return
            try:
                with open(path, "w", encoding="utf-8") as output:
                    json.dump([result.to_dict() for result in results], output, ensure_ascii=False, indent=2)
            except OSError:
                messagebox.showerror(self._("error_title"), self._("save_error"))
                return
        else:
            path = filedialog.asksaveasfilename(
                defaultextension=".csv",
                filetypes=[("CSV", "*.csv")],
            )
            if not path:
                return
            try:
                with open(path, "w", encoding="utf-8", newline="") as output:
                    fieldnames = [
                        "name",
                        "address",
                        "phone",
                        "opening_hours",
                        "rating",
                        "rating_count",
                        "reviews",
                        "attributes",
                    ]
                    writer = csv.DictWriter(output, fieldnames=fieldnames)
                    writer.writeheader()
                    for result in results:
                        writer.writerow(result.to_csv_row())
            except OSError:
                messagebox.showerror(self._("error_title"), self._("save_error"))
                return
        messagebox.showinfo(self._("info_title"), self._("save_success"))

    def _update_translations(self) -> None:
        self.title(self._("app_title"))
        self.notebook.tab(0, text=self._("tab_api"))
        self.notebook.tab(1, text=self._("tab_bot"))

        self.api_key_label.config(text=self._("api_key"))
        self.api_query_label.config(text=self._("query"))
        self.language_label.config(text=self._("language"))
        self.api_search_button.config(text=self._("search"))
        self.api_limit_label.config(text=self._("result_limit"))
        self.api_results_label.config(text=self._("results"))
        self.api_details_label.config(text=self._("details"))
        self.api_results_tree.heading("name", text=self._("column_name"))
        self.api_results_tree.heading("phone", text=self._("column_phone"))
        self.api_results_tree.heading("rating", text=self._("column_rating"))
        self.api_save_json_button.config(text=self._("save_json"))
        self.api_save_csv_button.config(text=self._("save_csv"))

        self.bot_query_label.config(text=self._("query"))
        self.bot_language_label.config(text=self._("language"))
        self.bot_search_button.config(text=self._("search"))
        self.bot_limit_label.config(text=self._("result_limit"))
        self.bot_results_label.config(text=self._("results"))
        self.bot_details_label.config(text=self._("details"))
        self.bot_map_label.config(text=self._("map_preview"))
        if self._bot_map_photo is None:
            self._set_bot_map_placeholder()
        self.bot_results_tree.heading("name", text=self._("column_name"))
        self.bot_results_tree.heading("phone", text=self._("column_phone"))
        self.bot_results_tree.heading("rating", text=self._("column_rating"))
        self.bot_save_json_button.config(text=self._("save_json"))
        self.bot_save_csv_button.config(text=self._("save_csv"))

        self.api_status_var.set(self._("status_ready"))
        self.bot_status_var.set(self._("status_ready"))

        self._on_select_api_result()
        self._on_select_bot_result()

    def _(self, key: str) -> str:
        language = self.selected_language.get()
        return TRANSLATIONS.get(language, TRANSLATIONS["tr"]).get(key, key)


def main() -> None:
    app = Application()
    app.mainloop()


if __name__ == "__main__":
    main()
