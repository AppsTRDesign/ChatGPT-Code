"""Launch a Playwright-controlled Google Maps window for manual selector testing."""
from __future__ import annotations

import argparse
from contextlib import suppress

from playwright.sync_api import TimeoutError as PlaywrightTimeoutError
from playwright.sync_api import sync_playwright

from google_maps_gui.app import GoogleMapsPlaywrightScraper


def open_dev_browser(query: str, language: str, headless: bool) -> None:
    """Open Chromium with the same locale and UA as the production scraper."""
    scraper = GoogleMapsPlaywrightScraper(language=language or "tr", limit=1)
    with sync_playwright() as playwright:
        browser = playwright.chromium.launch(
            headless=headless,
            args=[
                "--disable-notifications",
                "--disable-infobars",
                "--disable-blink-features=AutomationControlled",
            ],
        )
        context = browser.new_context(
            locale=scraper._locale(),  # pylint: disable=protected-access
            viewport={"width": 1400, "height": 900},
            screen={"width": 1400, "height": 900},
            user_agent=scraper._user_agent(),  # pylint: disable=protected-access
        )
        page = context.new_page()
        page.goto(
            f"{scraper.MAP_URL}?hl={language}",  # pylint: disable=protected-access
            wait_until="load",
            timeout=90_000,
        )
        with suppress(PlaywrightTimeoutError):
            scraper._handle_privacy_dialog(page)  # pylint: disable=protected-access
        if query:
            scraper._perform_search(page, query)  # pylint: disable=protected-access
            with suppress(PlaywrightTimeoutError):
                scraper._wait_for_result_list(page)  # pylint: disable=protected-access
        print("Chromium penceresi açıldı. Test tamamlandığında bu konsola geri dönüp Enter'a basın.")
        try:
            input("Çıkmak için Enter'a basın...")
        finally:
            context.close()
            browser.close()


def main() -> None:
    parser = argparse.ArgumentParser(
        description=(
            "Google Maps seçicilerini manuel test etmek için üretimle aynı ayarlarda Chromium açar."
        )
    )
    parser.add_argument("query", nargs="?", default="", help="Opsiyonel arama sorgusu")
    parser.add_argument(
        "--language",
        "-l",
        default="tr",
        choices=["tr", "en"],
        help="Açılacak Google Haritalar arayüzünün dili",
    )
    parser.add_argument(
        "--headless",
        action="store_true",
        help="Chromium'u arka planda (CI testi için) aç",
    )
    args = parser.parse_args()
    open_dev_browser(args.query, args.language, args.headless)


if __name__ == "__main__":
    main()
