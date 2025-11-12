"""GUI application for querying Google Maps business details with bilingual support."""
from __future__ import annotations

import csv
import json
import logging
import threading
from dataclasses import asdict, dataclass
from typing import Dict, List, Optional

import requests
import tkinter as tk
from tkinter import filedialog, messagebox, ttk

from selenium import webdriver
from selenium.common.exceptions import NoSuchElementException, TimeoutException, WebDriverException
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait
from webdriver_manager.chrome import ChromeDriverManager


logging.basicConfig(
    filename="google_maps_gui.log",
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
)


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

    def to_dict(self) -> Dict[str, object]:
        data = asdict(self)
        data["opening_hours"] = self.opening_hours
        data["reviews"] = [review.to_dict() for review in self.reviews]
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


class GoogleMapsSeleniumScraper:
    """Automates Google Maps searches via Selenium and extracts business details."""

    def __init__(self, language: str = "tr", limit: int = 5, max_reviews: int = 3) -> None:
        self.language = language
        self.limit = limit
        self.max_reviews = max_reviews

    def search(self, query: str, location: str = "") -> List[PlaceResult]:
        LOGGER.info("Starting Selenium scrape for query='%s' location='%s'", query, location)
        options = webdriver.ChromeOptions()
        options.add_argument("--start-maximized")
        options.add_argument("--disable-notifications")
        options.add_argument("--disable-infobars")
        options.add_experimental_option("excludeSwitches", ["enable-automation"])
        options.add_experimental_option("useAutomationExtension", False)

        service = Service(ChromeDriverManager().install())
        driver = webdriver.Chrome(service=service, options=options)
        wait = WebDriverWait(driver, 30)
        results: List[PlaceResult] = []
        composed_query = f"{query} {location}".strip() if location else query

        try:
            driver.get(f"https://www.google.com/maps?hl={self.language}")
            search_box = wait.until(EC.element_to_be_clickable((By.ID, "searchboxinput")))
            search_box.clear()
            search_box.send_keys(composed_query)
            search_box.send_keys(Keys.ENTER)

            wait.until(EC.presence_of_all_elements_located((By.CSS_SELECTOR, 'div[role="article"]')))

            for index in range(self.limit):
                articles = driver.find_elements(By.CSS_SELECTOR, 'div[role="article"]')
                if index >= len(articles):
                    break
                article = articles[index]
                driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", article)
                wait.until(lambda drv: article.is_displayed())
                try:
                    article.click()
                except WebDriverException:
                    continue

                try:
                    wait.until(
                        EC.presence_of_element_located((By.CSS_SELECTOR, 'h1[class*="fontHeadlineLarge"]'))
                    )
                except TimeoutException:
                    continue

                try:
                    results.append(self._extract_details(driver, wait))
                except WebDriverException as exc:
                    LOGGER.exception("Failed to extract place details: %s", exc)
                    continue
        finally:
            driver.quit()
        LOGGER.info("Selenium scrape finished with %d results", len(results))
        return results

    def _extract_details(self, driver: webdriver.Chrome, wait: WebDriverWait) -> PlaceResult:
        def get_text(by: tuple[str, str]) -> str:
            try:
                element = driver.find_element(*by)
                return element.text.strip()
            except NoSuchElementException:
                return ""

        name = get_text((By.CSS_SELECTOR, 'h1[class*="fontHeadlineLarge"]'))
        address = get_text(
            (By.CSS_SELECTOR, 'button[data-item-id="address"] div[class*="fontBodyMedium"]')
        ) or get_text((By.CSS_SELECTOR, 'div[data-item-id="address"] div[class*="fontBodyMedium"]'))
        phone = get_text(
            (By.CSS_SELECTOR, 'button[data-item-id^="phone"] div[class*="fontBodyMedium"]')
        ) or get_text((By.CSS_SELECTOR, 'div[data-item-id^="phone"] div[class*="fontBodyMedium"]'))

        rating_text = get_text((By.CSS_SELECTOR, 'div.F7nice span[aria-label]'))
        rating = self._parse_rating(rating_text)
        rating_count = self._parse_rating_count(
            get_text((By.CSS_SELECTOR, 'div.F7nice span[aria-label] + span'))
        )

        opening_hours = self._extract_hours(driver, wait)
        reviews = self._extract_reviews(driver)

        return PlaceResult(
            name=name,
            formatted_address=address,
            formatted_phone_number=phone or None,
            opening_hours=opening_hours,
            rating=rating,
            user_ratings_total=rating_count,
            reviews=reviews,
        )

    def _extract_hours(self, driver: webdriver.Chrome, wait: WebDriverWait) -> List[str]:
        hours: List[str] = []
        selectors = [
            (By.XPATH, '//div[contains(@aria-label, "Hours")]/div[contains(@class, "fontBodyMedium")]'),
            (By.XPATH, '//div[contains(@aria-label, "Saat")]/div[contains(@class, "fontBodyMedium")]'),
        ]
        for by in selectors:
            elements = driver.find_elements(*by)
            for element in elements:
                text = element.text.strip()
                if text and text not in hours:
                    hours.append(text)
        if hours:
            return hours

        try:
            hours_button = driver.find_element(By.CSS_SELECTOR, 'button[aria-label*="Hours"]')
            driver.execute_script("arguments[0].click();", hours_button)
            wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, 'div[role="dialog"] table')))
            rows = driver.find_elements(By.CSS_SELECTOR, 'div[role="dialog"] table tr')
            for row in rows:
                text = row.text.strip()
                if text:
                    hours.append(text)
        except (NoSuchElementException, TimeoutException):
            pass
        finally:
            self._close_dialog(driver)
        return hours

    def _close_dialog(self, driver: webdriver.Chrome) -> None:
        try:
            close_button = driver.find_element(By.CSS_SELECTOR, 'button[aria-label="Close"]')
            close_button.click()
        except NoSuchElementException:
            try:
                close_button = driver.find_element(By.CSS_SELECTOR, 'button[aria-label="Kapat"]')
                close_button.click()
            except NoSuchElementException:
                pass

    def _extract_reviews(self, driver: webdriver.Chrome) -> List[PlaceReview]:
        reviews: List[PlaceReview] = []
        review_cards = driver.find_elements(By.CSS_SELECTOR, 'div[data-review-id]')
        if not review_cards:
            review_cards = driver.find_elements(By.CSS_SELECTOR, 'div[jscontroller="MZ93Hf"] div[data-review-id]')

        for card in review_cards[: self.max_reviews]:
            author = self._safe_child_text(card, (By.CSS_SELECTOR, 'div[class*="d4r55"]'))
            rating_value = self._parse_rating(
                self._safe_child_attribute(card, (By.CSS_SELECTOR, 'span[class*="kvMYJc"]'), "aria-label")
            )
            relative_time = self._safe_child_text(card, (By.CSS_SELECTOR, 'span[class*="rsqaWe"]'))
            text = self._safe_child_text(card, (By.CSS_SELECTOR, 'span[class*="wiI7pd"]'))
            reviews.append(
                PlaceReview(
                    author_name=author,
                    rating=rating_value,
                    relative_time=relative_time,
                    text=text,
                )
            )
        return reviews

    def _safe_child_text(self, element, by: tuple[str, str]) -> str:
        try:
            child = element.find_element(*by)
            return child.text.strip()
        except NoSuchElementException:
            return ""

    def _safe_child_attribute(self, element, by: tuple[str, str], attribute: str) -> str:
        try:
            child = element.find_element(*by)
            return child.get_attribute(attribute) or ""
        except NoSuchElementException:
            return ""

    def _parse_rating(self, text: str) -> Optional[float]:
        if not text:
            return None
        for token in text.replace(",", ".").split():
            try:
                return float(token)
            except ValueError:
                continue
        return None

    def _parse_rating_count(self, text: str) -> Optional[int]:
        if not text:
            return None
        digits = "".join(ch for ch in text if ch.isdigit())
        if not digits:
            return None
        try:
            return int(digits)
        except ValueError:
            return None


TRANSLATIONS = {
    "tr": {
        "app_title": "Google Harita İşletme Aracı",
        "tab_api": "API ile Tara",
        "tab_bot": "Bot ile Tara",
        "api_key": "API Anahtarı",
        "query": "Arama Sorgusu",
        "location_hint": "(Şehir veya bölge ekleyebilirsiniz)",
        "location": "Konum",
        "language": "Dil",
        "search": "Ara",
        "results": "Sonuçlar",
        "details": "Detaylar",
        "status_ready": "Hazır",
        "status_searching": "Aranıyor...",
        "status_error": "Bir hata oluştu",
        "error_missing_key": "Lütfen geçerli bir API anahtarı girin.",
        "error_missing_query": "Lütfen arama sorgusunu doldurun.",
        "error_title": "Hata",
        "no_results": "Sonuç bulunamadı.",
        "column_name": "İsim",
        "column_phone": "Telefon",
        "column_rating": "Puan",
        "opening_hours": "Çalışma Saatleri",
        "reviews": "Müşteri Yorumları",
        "review_author": "Yazar",
        "review_rating": "Puan",
        "review_time": "Zaman",
        "review_text": "Yorum",
        "ratings_total": "Toplam Oy",
        "save_json": "JSON Kaydet",
        "save_csv": "CSV Kaydet",
        "save_success": "Dosya kaydedildi.",
        "save_error": "Dosya kaydedilirken bir hata oluştu.",
        "error_no_results_to_save": "Kaydedilecek veri bulunamadı.",
        "status_scraping": "Tarama yapılıyor...",
        "info_title": "Bilgi",
        "result_limit": "İşletme Sayısı",
        "error_check_logs": "Detaylı bilgi için google_maps_gui.log dosyasını kontrol edin.",
        "error_unknown": "Bilinmeyen bir hata oluştu.",
    },
    "en": {
        "app_title": "Google Maps Business Tool",
        "tab_api": "Scan with API",
        "tab_bot": "Scan with Bot",
        "api_key": "API Key",
        "query": "Search Query",
        "location_hint": "(You may add a city or region)",
        "location": "Location",
        "language": "Language",
        "search": "Search",
        "results": "Results",
        "details": "Details",
        "status_ready": "Ready",
        "status_searching": "Searching...",
        "status_error": "An error occurred",
        "error_missing_key": "Please provide a valid API key.",
        "error_missing_query": "Please enter a search query.",
        "error_title": "Error",
        "no_results": "No results found.",
        "column_name": "Name",
        "column_phone": "Phone",
        "column_rating": "Rating",
        "opening_hours": "Opening Hours",
        "reviews": "Customer Reviews",
        "review_author": "Author",
        "review_rating": "Rating",
        "review_time": "Time",
        "review_text": "Review",
        "ratings_total": "Total Reviews",
        "save_json": "Save JSON",
        "save_csv": "Save CSV",
        "save_success": "File saved successfully.",
        "save_error": "An error occurred while saving the file.",
        "error_no_results_to_save": "No data available to save.",
        "status_scraping": "Scraping...",
        "info_title": "Info",
        "result_limit": "Business Count",
        "error_check_logs": "Check google_maps_gui.log for full details.",
        "error_unknown": "An unknown error occurred.",
    },
}


class Application(tk.Tk):
    def __init__(self) -> None:
        super().__init__()
        self.selected_language = tk.StringVar(value="tr")
        self.title(self._("app_title"))
        self.geometry("1000x650")

        self._api_results: List[PlaceResult] = []
        self._bot_results: List[PlaceResult] = []

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

        self.api_location_label = ttk.Label(self.api_tab, text="")
        self.api_location_entry = ttk.Entry(self.api_tab)
        self.api_location_hint_label = ttk.Label(self.api_tab, text="", foreground="gray")

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

        self.bot_location_label = ttk.Label(self.bot_tab, text="")
        self.bot_location_entry = ttk.Entry(self.bot_tab)
        self.bot_location_hint_label = ttk.Label(self.bot_tab, text="", foreground="gray")

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
        self.api_tab.rowconfigure(5, weight=1)

        self.api_key_label.grid(row=0, column=0, sticky=tk.W, **api_padding)
        self.api_key_entry.grid(row=0, column=1, columnspan=3, sticky=tk.EW, **api_padding)

        self.api_query_label.grid(row=1, column=0, sticky=tk.W, **api_padding)
        self.api_query_entry.grid(row=1, column=1, columnspan=3, sticky=tk.EW, **api_padding)

        self.api_location_label.grid(row=2, column=0, sticky=tk.W, **api_padding)
        self.api_location_entry.grid(row=2, column=1, sticky=tk.EW, **api_padding)
        self.api_location_hint_label.grid(row=2, column=2, sticky=tk.W, **api_padding)

        self.language_label.grid(row=3, column=0, sticky=tk.W, **api_padding)
        self.language_combo.grid(row=3, column=1, sticky=tk.W, **api_padding)
        self.api_limit_label.grid(row=3, column=2, sticky=tk.W, **api_padding)
        self.api_limit_spin.grid(row=3, column=3, sticky=tk.W, **api_padding)
        self.api_search_button.grid(row=3, column=4, sticky=tk.E, **api_padding)

        self.api_results_label.grid(row=4, column=0, sticky=tk.W, **api_padding)
        self.api_results_tree.grid(row=5, column=0, columnspan=3, sticky=tk.NSEW, padx=(10, 0), pady=5)
        self.api_details_label.grid(row=4, column=3, sticky=tk.W, **api_padding)
        self.api_details_text.grid(row=5, column=3, sticky=tk.NSEW, padx=(0, 10), pady=5)
        self.api_details_scroll.grid(row=5, column=4, sticky=tk.NS, pady=5)

        self.api_save_json_button.grid(row=6, column=0, sticky=tk.W, **api_padding)
        self.api_save_csv_button.grid(row=6, column=1, sticky=tk.W, **api_padding)
        self.api_status_label.grid(row=6, column=3, columnspan=2, sticky=tk.E, **api_padding)

        bot_padding = {"padx": 10, "pady": 5}
        self.bot_tab.columnconfigure(1, weight=1)
        self.bot_tab.columnconfigure(2, weight=1)
        self.bot_tab.columnconfigure(3, weight=1)
        self.bot_tab.columnconfigure(4, weight=0)
        self.bot_tab.rowconfigure(5, weight=1)

        self.bot_query_label.grid(row=0, column=0, sticky=tk.W, **bot_padding)
        self.bot_query_entry.grid(row=0, column=1, columnspan=3, sticky=tk.EW, **bot_padding)

        self.bot_location_label.grid(row=1, column=0, sticky=tk.W, **bot_padding)
        self.bot_location_entry.grid(row=1, column=1, sticky=tk.EW, **bot_padding)
        self.bot_location_hint_label.grid(row=1, column=2, sticky=tk.W, **bot_padding)

        self.bot_language_label.grid(row=2, column=0, sticky=tk.W, **bot_padding)
        self.bot_language_combo.grid(row=2, column=1, sticky=tk.W, **bot_padding)
        self.bot_limit_label.grid(row=2, column=2, sticky=tk.W, **bot_padding)
        self.bot_limit_spin.grid(row=2, column=3, sticky=tk.W, **bot_padding)
        self.bot_search_button.grid(row=2, column=4, sticky=tk.E, **bot_padding)

        self.bot_results_label.grid(row=3, column=0, sticky=tk.W, **bot_padding)
        self.bot_results_tree.grid(row=4, column=0, columnspan=3, sticky=tk.NSEW, padx=(10, 0), pady=5)
        self.bot_details_label.grid(row=3, column=3, sticky=tk.W, **bot_padding)
        self.bot_details_text.grid(row=4, column=3, sticky=tk.NSEW, padx=(0, 10), pady=5)
        self.bot_details_scroll.grid(row=4, column=4, sticky=tk.NS, pady=5)

        self.bot_save_json_button.grid(row=5, column=0, sticky=tk.W, **bot_padding)
        self.bot_save_csv_button.grid(row=5, column=1, sticky=tk.W, **bot_padding)
        self.bot_status_label.grid(row=5, column=3, columnspan=2, sticky=tk.E, **bot_padding)

    def _bind_events(self) -> None:
        self.language_combo.bind("<<ComboboxSelected>>", lambda _: self._update_translations())
        self.api_results_tree.bind("<<TreeviewSelect>>", lambda _: self._on_select_api_result())
        self.bot_results_tree.bind("<<TreeviewSelect>>", lambda _: self._on_select_bot_result())

    def _on_api_search(self) -> None:
        api_key = self.api_key_entry.get().strip()
        query = self.api_query_entry.get().strip()
        location = self.api_location_entry.get().strip()
        if not api_key:
            messagebox.showerror(self._("error_title"), self._("error_missing_key"))
            return
        if not query:
            messagebox.showerror(self._("error_title"), self._("error_missing_query"))
            return

        language = self.selected_language.get()
        search_query = f"{query} {location}".strip() if location else query
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
                results = client.search_places(search_query, language=language, limit=limit)
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
        location = self.bot_location_entry.get().strip()
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
        self.bot_status_var.set(self._("status_scraping"))
        self.bot_search_button.config(state=tk.DISABLED)

        def worker() -> None:
            try:
                scraper = GoogleMapsSeleniumScraper(language=language, limit=limit)
                results = scraper.search(query, location)
            except WebDriverException as exc:
                LOGGER.exception("Selenium WebDriver error: %s", exc)
                self._handle_bot_error(str(exc))
                return
            except Exception as exc:  # broad for Selenium edge cases
                LOGGER.exception("Unexpected Selenium error: %s", exc)
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
        self._bot_results = results
        for item in self.bot_results_tree.get_children():
            self.bot_results_tree.delete(item)
        if not results:
            self.bot_status_var.set(self._("no_results"))
            self.bot_search_button.config(state=tk.NORMAL)
            self._clear_bot_details()
            return
        for index, result in enumerate(results):
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
        self.bot_status_var.set(self._("status_ready"))
        self.bot_search_button.config(state=tk.NORMAL)
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
        lines.append(f"{self._('location')}: {result.formatted_address or '-'}")
        lines.append("")
        lines.append(f"{self._('opening_hours')}:")
        if result.opening_hours:
            lines.extend(f"  - {item}" for item in result.opening_hours)
        else:
            lines.append("  - -")
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
                    fieldnames = ["name", "address", "phone", "opening_hours", "rating", "rating_count", "reviews"]
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
        self.api_location_label.config(text=self._("location"))
        self.api_location_hint_label.config(text=self._("location_hint"))
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
        self.bot_location_label.config(text=self._("location"))
        self.bot_location_hint_label.config(text=self._("location_hint"))
        self.bot_language_label.config(text=self._("language"))
        self.bot_search_button.config(text=self._("search"))
        self.bot_limit_label.config(text=self._("result_limit"))
        self.bot_results_label.config(text=self._("results"))
        self.bot_details_label.config(text=self._("details"))
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
