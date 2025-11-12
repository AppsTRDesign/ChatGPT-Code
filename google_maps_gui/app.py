"""GUI application for querying Google Maps business details with bilingual support."""
from __future__ import annotations

import csv
import io
import json
import logging
import threading
import time
from dataclasses import asdict, dataclass
from typing import Callable, Dict, List, Optional

import requests
import tkinter as tk
from tkinter import filedialog, messagebox, ttk

from selenium import webdriver
from selenium.common.exceptions import (
    NoSuchElementException,
    TimeoutException,
    WebDriverException,
    StaleElementReferenceException,
)
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait
from webdriver_manager.chrome import ChromeDriverManager

from PIL import Image, ImageTk, UnidentifiedImageError


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


class GoogleMapsSeleniumScraper:
    """Automates Google Maps searches via Selenium and extracts business details."""

    TAB_LABELS = {
        "overview": {
            "tr": ["genel bakış", "genel"],
            "en": ["overview"],
            "_default": ["overview"],
        },
        "hours": {
            "tr": ["çalışma saatleri", "saatler"],
            "en": ["hours", "opening hours"],
            "_default": ["hours"],
        },
        "reviews": {
            "tr": ["yorumlar", "değerlendirmeler"],
            "en": ["reviews"],
            "_default": ["reviews"],
        },
        "about": {
            "tr": ["hakkında"],
            "en": ["about"],
            "_default": ["about"],
        },
    }

    def __init__(self, language: str = "tr", limit: int = 5, max_reviews: int = 3) -> None:
        self.language = language
        self.limit = limit
        self.max_reviews = max_reviews

    def search(
        self,
        query: str,
        progress_callback: Optional[Callable[[bytes], None]] = None,
        result_callback: Optional[Callable[[PlaceResult], None]] = None,
    ) -> List[PlaceResult]:
        LOGGER.info("Starting Selenium scrape for query='%s'", query)
        options = webdriver.ChromeOptions()
        options.add_argument("--start-maximized")
        options.add_argument("--disable-notifications")
        options.add_argument("--disable-infobars")
        options.add_argument("--disable-blink-features=AutomationControlled")
        options.add_experimental_option("excludeSwitches", ["enable-automation"])
        options.add_experimental_option("useAutomationExtension", False)

        service = Service(ChromeDriverManager().install())
        driver = webdriver.Chrome(service=service, options=options)
        wait = WebDriverWait(driver, 45)
        results: List[PlaceResult] = []
        seen_names: set[str] = set()

        try:
            driver.get(f"https://www.google.com/maps?hl={self.language}")
            self._inject_fake_cursor(driver)
            self._send_progress_screenshot(driver, progress_callback)
            self._perform_search(driver, wait, query)
            self._wait_for_result_list(driver, wait)
            self._send_progress_screenshot(driver, progress_callback)

            for index in range(self.limit):
                try:
                    article = self._get_article_by_index(driver, wait, index)
                except TimeoutException:
                    LOGGER.warning("Timed out while waiting for result #%d", index + 1)
                    break

                name = self._extract_article_name(article)
                if not name or name in seen_names:
                    LOGGER.debug("Skipping duplicate or unnamed result at index %d", index)
                    continue

                primary_target = self._resolve_click_target(article)
                self._animate_cursor_to_element(driver, primary_target)
                self._send_progress_screenshot(driver, progress_callback)
                self._human_pause(0.45)
                self._click_element(driver, primary_target)
                self._send_progress_screenshot(driver, progress_callback)

                try:
                    self._dismiss_media_overlay(driver, wait)
                    self._wait_for_place_panel(wait)
                except TimeoutException:
                    marker = self._find_map_marker(driver, name)
                    if marker:
                        LOGGER.info("Retrying click on map marker for '%s'", name)
                        self._animate_cursor_to_element(driver, marker)
                        self._send_progress_screenshot(driver, progress_callback)
                        self._human_pause(0.45)
                        self._click_element(driver, marker)
                        self._send_progress_screenshot(driver, progress_callback)
                        try:
                            self._dismiss_media_overlay(driver, wait)
                            self._wait_for_place_panel(wait)
                        except TimeoutException:
                            LOGGER.warning("Details panel did not appear for '%s'", name)
                            continue
                    else:
                        LOGGER.warning("Details panel did not appear for '%s'", name)
                        continue

                self._dismiss_media_overlay(driver, wait)
                self._send_progress_screenshot(driver, progress_callback)

                try:
                    place = self._extract_details(driver, wait)
                except WebDriverException as exc:
                    LOGGER.exception("Failed to extract place details for '%s': %s", name, exc)
                    continue

                results.append(place)
                if result_callback is not None:
                    try:
                        result_callback(place)
                    except Exception:
                        LOGGER.exception("Result callback failed for '%s'", place.name)
                seen_names.add(place.name)
                self._human_pause(0.35)

        finally:
            self._send_progress_screenshot(driver, progress_callback)
            driver.quit()
        LOGGER.info("Selenium scrape finished with %d results", len(results))
        return results

    def _perform_search(self, driver: webdriver.Chrome, wait: WebDriverWait, query: str) -> None:
        search_box = wait.until(EC.element_to_be_clickable((By.ID, "searchboxinput")))
        search_box.clear()
        search_box.send_keys(query)
        search_box.send_keys(Keys.ENTER)

    def _wait_for_result_list(self, driver: webdriver.Chrome, wait: WebDriverWait) -> None:
        wait.until(lambda drv: bool(self._get_result_items(drv)))
        time.sleep(1.0)

    def _get_article_by_index(
        self, driver: webdriver.Chrome, wait: WebDriverWait, index: int
    ):
        wait.until(lambda drv: bool(self._get_result_items(drv)))
        attempts = 0
        while True:
            articles = self._get_result_items(driver)
            if index < len(articles):
                article = articles[index]
                try:
                    driver.execute_script(
                        "arguments[0].scrollIntoView({block: 'center'});",
                        article,
                    )
                except WebDriverException:
                    pass
                wait.until(lambda drv: article.is_displayed())
                return article
            if not self._scroll_results_feed(driver):
                attempts += 1
            else:
                attempts += 1
            if attempts > 5:
                raise TimeoutException("Not enough search results")
            self._human_pause(0.35)

    def _get_result_items(self, driver: webdriver.Chrome):
        items = driver.find_elements(By.CSS_SELECTOR, 'div[role="feed"] div.Nv2PK')
        if items:
            return [item for item in items if item.is_displayed()]
        items = driver.find_elements(By.CSS_SELECTOR, 'div[role="article"]')
        if items:
            return [item for item in items if item.is_displayed()]
        return []

    def _get_results_feed(self, driver: webdriver.Chrome):
        feeds = driver.find_elements(By.CSS_SELECTOR, 'div[role="feed"]')
        for feed in feeds:
            if feed.is_displayed():
                return feed
        return None

    def _scroll_results_feed(self, driver: webdriver.Chrome) -> bool:
        feed = self._get_results_feed(driver)
        if not feed:
            return False
        try:
            driver.execute_script(
                "arguments[0].scrollTop = arguments[0].scrollTop + arguments[0].clientHeight;",
                feed,
            )
            return True
        except WebDriverException:
            return False

    def _resolve_click_target(self, article):
        clickable_selectors = [
            'div.Nv2PK.Q7Pnwc',
            'div.Nv2PK',
            'div[role="article"]',
            'a.hfpxzc',
        ]
        for selector in clickable_selectors:
            try:
                element = article.find_element(By.CSS_SELECTOR, selector)
                if element.is_displayed():
                    return element
            except NoSuchElementException:
                continue
        return article

    def _first_non_empty_text(self, driver: webdriver.Chrome, selectors: List[tuple[str, str]]) -> str:
        for by in selectors:
            try:
                elements = driver.find_elements(*by)
            except WebDriverException:
                continue
            for element in elements:
                text = element.text.strip()
                if text:
                    return text
        return ""

    def _first_attribute_value(
        self, driver: webdriver.Chrome, selectors: List[tuple[str, str]], attribute: str
    ) -> str:
        for by in selectors:
            try:
                elements = driver.find_elements(*by)
            except WebDriverException:
                continue
            for element in elements:
                value = (element.get_attribute(attribute) or "").strip()
                if value:
                    return value
        return ""

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

    def _select_tab(self, driver: webdriver.Chrome, wait: WebDriverWait, key: str) -> bool:
        labels = self._tab_label_candidates(key)
        if not labels:
            return False
        try:
            tabs = driver.find_elements(By.CSS_SELECTOR, 'button[role="tab"]')
        except WebDriverException:
            return False
        normalized_targets = {self._normalize_text(label) for label in labels}
        for tab in tabs:
            label = self._normalize_text(tab.text)
            if label not in normalized_targets:
                continue
            try:
                driver.execute_script(
                    "arguments[0].scrollIntoView({block: 'center'});",
                    tab,
                )
            except WebDriverException:
                pass
            try:
                tab.click()
            except WebDriverException:
                try:
                    driver.execute_script("arguments[0].click();", tab)
                except WebDriverException:
                    continue

            def is_selected() -> bool:
                try:
                    return tab.get_attribute("aria-selected") == "true"
                except StaleElementReferenceException:
                    try:
                        refreshed = driver.find_elements(By.CSS_SELECTOR, 'button[role="tab"]')
                    except WebDriverException:
                        return False
                    for candidate in refreshed:
                        if self._normalize_text(candidate.text) == label:
                            try:
                                return candidate.get_attribute("aria-selected") == "true"
                            except WebDriverException:
                                return False
                    return False

            try:
                wait.until(lambda _: is_selected())
            except TimeoutException:
                pass
            return True
        return False

    def _extract_address(self, driver: webdriver.Chrome) -> str:
        selectors = [
            (By.CSS_SELECTOR, 'button[data-item-id="address"] div[class*="Io6YTe"]'),
            (By.CSS_SELECTOR, 'div[data-item-id="address"] div[class*="Io6YTe"]'),
            (By.CSS_SELECTOR, 'button[aria-label*="Adres"] div[class*="Io6YTe"]'),
            (By.CSS_SELECTOR, 'button[aria-label*="Address"] div[class*="Io6YTe"]'),
            (By.XPATH, '//div[contains(@aria-label, "Adres") or contains(@aria-label, "Address")]//div[contains(@class, "Io6YTe")]'),
        ]
        address = self._first_non_empty_text(driver, selectors)
        if address:
            return address
        generic = driver.find_elements(By.CSS_SELECTOR, 'div.Io6YTe.fontBodyMedium.kR99db.fdkmkc')
        for element in generic:
            text = element.text.strip()
            if text:
                return text
        return ""

    def _extract_phone(self, driver: webdriver.Chrome) -> str:
        selectors = [
            (By.CSS_SELECTOR, 'button[data-item-id^="phone:"] div[class*="Io6YTe"]'),
            (By.CSS_SELECTOR, 'div[data-item-id^="phone:"] div[class*="Io6YTe"]'),
            (By.XPATH, '//div[contains(@aria-label, "Telefon") or contains(@aria-label, "Phone")]//div[contains(@class, "Io6YTe")]'),
        ]
        phone = self._first_non_empty_text(driver, selectors)
        if phone:
            return phone
        fallback = driver.find_elements(By.CSS_SELECTOR, 'div.AeaXub div.Io6YTe')
        for element in fallback:
            text = element.text.strip()
            if text:
                return text
        return ""

    def _extract_rating_info(self, driver: webdriver.Chrome) -> tuple[Optional[float], Optional[int]]:
        rating_text = self._first_non_empty_text(
            driver,
            [
                (By.CSS_SELECTOR, 'div.F7nice span[aria-hidden="true"]'),
                (By.CSS_SELECTOR, 'div.F7nice span:first-child'),
            ],
        )
        rating = self._parse_rating(rating_text)

        rating_count_text = self._first_attribute_value(
            driver,
            [
                (By.CSS_SELECTOR, 'div.F7nice span[role="img"][aria-label*="yorum"]'),
                (By.CSS_SELECTOR, 'div.F7nice span[role="img"][aria-label*="review"]'),
            ],
            "aria-label",
        )
        if not rating_count_text:
            rating_count_text = self._first_non_empty_text(
                driver,
                [(By.CSS_SELECTOR, 'div.F7nice span:last-child')],
            )
        rating_count = self._parse_rating_count(rating_count_text)
        return rating, rating_count

    def _extract_about_sections(self, driver: webdriver.Chrome) -> Dict[str, List[str]]:
        region = self._find_about_region(driver)
        if region is None:
            return {}
        sections: Dict[str, List[str]] = {}
        containers = region.find_elements(By.CSS_SELECTOR, 'div.iP2t7d')
        for container in containers:
            try:
                heading = container.find_element(By.CSS_SELECTOR, 'h2')
            except NoSuchElementException:
                continue
            title = heading.text.strip()
            if not title:
                continue
            entries: List[str] = []
            for item in container.find_elements(By.CSS_SELECTOR, 'ul li'):
                text = item.text.strip()
                if text and text not in entries:
                    entries.append(text)
            if entries:
                sections[title] = entries
        return sections

    def _find_about_region(self, driver: webdriver.Chrome):
        candidates = driver.find_elements(By.CSS_SELECTOR, 'div[aria-label]')
        for candidate in candidates:
            label = (candidate.get_attribute("aria-label") or "").lower()
            if "hakkında" in label or "about" in label:
                if candidate.is_displayed():
                    return candidate
        extras = driver.find_elements(
            By.CSS_SELECTOR, 'div.m6QErb.DxyBCb.kA9KIf.dS8AEf.XiKgde'
        )
        for candidate in extras:
            if candidate.is_displayed():
                return candidate
        return None

    def _extract_article_name(self, article) -> str:
        try:
            heading = article.find_element(By.CSS_SELECTOR, '[role="heading"]')
            name = heading.text.strip()
            if name:
                return name
        except NoSuchElementException:
            pass
        text = article.text.strip()
        return text.split("\n")[0] if text else ""

    def _find_map_marker(self, driver: webdriver.Chrome, name: str):
        normalized = self._normalize_text(name)
        if not normalized:
            return None
        selectors = [
            'button[jsaction*="pane.wfvdle"]',
            'div[jsaction*="pane.wfvdle"]',
        ]
        for selector in selectors:
            for marker in driver.find_elements(By.CSS_SELECTOR, selector):
                label = self._normalize_text(marker.get_attribute("aria-label") or "")
                if not label or "konum" in label:
                    continue
                if normalized in label:
                    return marker
        return None

    def _animate_cursor_to_element(self, driver: webdriver.Chrome, element) -> None:
        self._inject_fake_cursor(driver)
        driver.execute_script(
            "arguments[0].scrollIntoView({block: 'center'});",
            element,
        )
        driver.execute_script(
            """
            const rect = arguments[0].getBoundingClientRect();
            const cursor = document.getElementById('bot-fake-cursor');
            if (!cursor) return;
            const x = rect.left + rect.width / 2;
            const y = rect.top + rect.height / 2;
            cursor.style.transition = 'transform 0.35s ease-out';
            cursor.style.transform = `translate(${x}px, ${y}px)`;
            """,
            element,
        )

    def _click_element(self, driver: webdriver.Chrome, element) -> None:
        try:
            element.click()
        except WebDriverException:
            driver.execute_script("arguments[0].click();", element)

    def _wait_for_place_panel(self, wait: WebDriverWait) -> None:
        wait.until(
            EC.presence_of_element_located((By.CSS_SELECTOR, 'h1[class*="fontHeadlineLarge"]'))
        )

    def _dismiss_media_overlay(self, driver: webdriver.Chrome, wait: WebDriverWait) -> bool:
        overlay_selectors = [
            (By.CSS_SELECTOR, 'div[role="dialog"][aria-label*="foto"]'),
            (By.CSS_SELECTOR, 'div[role="dialog"][aria-label*="resim"]'),
            (By.CSS_SELECTOR, 'div[role="dialog"][aria-label*="görsel"]'),
            (By.CSS_SELECTOR, 'div[role="dialog"][aria-label*="image"]'),
            (By.CSS_SELECTOR, 'div[role="dialog"][aria-label*="photo"]'),
        ]
        for by in overlay_selectors:
            try:
                overlays = driver.find_elements(*by)
            except WebDriverException:
                continue
            for overlay in overlays:
                try:
                    if not overlay.is_displayed():
                        continue
                except WebDriverException:
                    continue
                closed = False
                try:
                    close_buttons = overlay.find_elements(
                        By.CSS_SELECTOR,
                        'button[aria-label*="Kapat"], button[aria-label*="Close"], button[jsaction*="close"], button[aria-label*="Çıkış"], button[aria-label*="Exit"]',
                    )
                except WebDriverException:
                    close_buttons = []
                for button in close_buttons:
                    try:
                        if not button.is_displayed():
                            continue
                        button.click()
                        closed = True
                        break
                    except WebDriverException:
                        try:
                            driver.execute_script("arguments[0].click();", button)
                            closed = True
                            break
                        except WebDriverException:
                            continue
                if not closed:
                    try:
                        driver.find_element(By.TAG_NAME, 'body').send_keys(Keys.ESCAPE)
                        closed = True
                        time.sleep(0.2)
                    except WebDriverException:
                        pass
                if closed:
                    try:
                        wait.until(lambda drv: not overlay.is_displayed())
                    except Exception:
                        pass
                    return True
        return False

    def _send_progress_screenshot(
        self, driver: webdriver.Chrome, callback: Optional[Callable[[bytes], None]]
    ) -> None:
        if not callback:
            return
        try:
            png = driver.get_screenshot_as_png()
        except WebDriverException:
            return
        callback(png)

    def _human_pause(self, delay: float) -> None:
        time.sleep(delay)

    def _normalize_text(self, value: str) -> str:
        return " ".join(value.lower().split())

    def _inject_fake_cursor(self, driver: webdriver.Chrome) -> None:
        driver.execute_script(
            """
            if (document.getElementById('bot-fake-cursor')) return;
            const cursor = document.createElement('div');
            cursor.id = 'bot-fake-cursor';
            cursor.style.position = 'fixed';
            cursor.style.width = '26px';
            cursor.style.height = '26px';
            cursor.style.borderRadius = '50%';
            cursor.style.background = 'rgba(0, 123, 255, 0.9)';
            cursor.style.boxShadow = '0 0 12px rgba(0,0,0,0.35)';
            cursor.style.zIndex = '2147483647';
            cursor.style.pointerEvents = 'none';
            cursor.style.transform = 'translate(-9999px, -9999px)';
            cursor.style.display = 'flex';
            cursor.style.alignItems = 'center';
            cursor.style.justifyContent = 'center';
            cursor.style.color = '#fff';
            cursor.style.fontSize = '12px';
            cursor.style.fontWeight = 'bold';
            cursor.textContent = '●';
            document.body.appendChild(cursor);
        """
        )

    def _extract_details(self, driver: webdriver.Chrome, wait: WebDriverWait) -> PlaceResult:
        name = self._first_non_empty_text(
            driver,
            [
                (By.CSS_SELECTOR, 'h1[class*="fontHeadlineLarge"]'),
                (By.CSS_SELECTOR, 'div[class*="DUwDvf"]'),
            ],
        )

        self._select_tab(driver, wait, "overview")

        address = self._extract_address(driver)
        phone = self._extract_phone(driver)
        rating, rating_count = self._extract_rating_info(driver)

        opening_hours: List[str] = []
        if self._select_tab(driver, wait, "hours"):
            opening_hours = self._extract_hours(driver, wait)
        if not opening_hours:
            opening_hours = self._extract_hours(driver, wait)

        reviews: List[PlaceReview] = []
        if self._select_tab(driver, wait, "reviews"):
            reviews = self._extract_reviews(driver, wait)
        if not reviews:
            reviews = self._extract_reviews(driver, wait)

        attributes: Dict[str, List[str]] = {}
        if self._select_tab(driver, wait, "about"):
            attributes = self._extract_about_sections(driver)
        if not attributes:
            attributes = self._extract_about_sections(driver)

        # Return the panel to overview for user clarity
        self._select_tab(driver, wait, "overview")

        if not name:
            name = "Unknown"

        return PlaceResult(
            name=name,
            formatted_address=address,
            formatted_phone_number=phone or None,
            opening_hours=opening_hours,
            rating=rating,
            user_ratings_total=rating_count,
            reviews=reviews,
            attributes=attributes,
        )

    def _extract_hours(self, driver: webdriver.Chrome, wait: WebDriverWait) -> List[str]:
        hours: List[str] = []
        try:
            wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, 'table.eK4R0e')))
        except TimeoutException:
            pass

        rows = driver.find_elements(By.CSS_SELECTOR, 'table.eK4R0e tbody tr')
        for row in rows:
            cells = row.find_elements(By.CSS_SELECTOR, 'td')
            if len(cells) >= 2:
                day = cells[0].text.strip()
                value = cells[1].text.strip()
                if day and value:
                    hours.append(f"{day}: {value}")
                elif day or value:
                    hours.append(day or value)
            else:
                text = row.text.strip()
                if text:
                    hours.append(text)

        if hours:
            return hours

        fallback_selectors = [
            (By.CSS_SELECTOR, 'div[aria-label*="Saat"] div[class*="fontBodyMedium"]'),
            (By.CSS_SELECTOR, 'div[aria-label*="Hours"] div[class*="fontBodyMedium"]'),
        ]
        seen: set[str] = set()
        for selector in fallback_selectors:
            for element in driver.find_elements(*selector):
                text = element.text.strip()
                if text and text not in seen:
                    hours.append(text)
                    seen.add(text)
        return hours

    def _extract_reviews(self, driver: webdriver.Chrome, wait: WebDriverWait) -> List[PlaceReview]:
        reviews: List[PlaceReview] = []
        try:
            wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, 'div[data-review-id]')))
        except TimeoutException:
            pass
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
        "attributes": "Hakkında",
        "attributes_none": "Bilgi yok",
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
        "status_scraping_progress": "{} / {} işletme işlendi",
        "info_title": "Bilgi",
        "result_limit": "İşletme Sayısı",
        "error_check_logs": "Detaylı bilgi için google_maps_gui.log dosyasını kontrol edin.",
        "error_unknown": "Bilinmeyen bir hata oluştu.",
        "map_preview": "Harita Önizleme",
        "map_preview_placeholder": "Ekran görüntüsü tarama sırasında burada görünecek.",
        "address": "Adres",
    },
    "en": {
        "app_title": "Google Maps Business Tool",
        "tab_api": "Scan with API",
        "tab_bot": "Scan with Bot",
        "api_key": "API Key",
        "query": "Search Query",
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
        "attributes": "About",
        "attributes_none": "No data",
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
        "status_scraping_progress": "{} / {} businesses processed",
        "info_title": "Info",
        "result_limit": "Business Count",
        "error_check_logs": "Check google_maps_gui.log for full details.",
        "error_unknown": "An unknown error occurred.",
        "map_preview": "Map Preview",
        "map_preview_placeholder": "Screenshots from the bot will appear here while it runs.",
        "address": "Address",
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
                scraper = GoogleMapsSeleniumScraper(language=language, limit=limit)
                results = scraper.search(
                    query,
                    progress_callback=self._queue_bot_map_update,
                    result_callback=self._queue_bot_result_append,
                )
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
