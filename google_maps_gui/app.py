"""GUI application for querying Google Maps business details with bilingual support."""
from __future__ import annotations

import threading
from dataclasses import dataclass
from typing import Dict, List, Optional

import requests
import tkinter as tk
from tkinter import messagebox, ttk


@dataclass
class PlaceReview:
    author_name: str
    rating: Optional[float]
    relative_time: Optional[str]
    text: str


@dataclass
class PlaceResult:
    name: str
    formatted_address: str
    formatted_phone_number: Optional[str]
    opening_hours: List[str]
    rating: Optional[float]
    user_ratings_total: Optional[int]
    reviews: List[PlaceReview]


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


TRANSLATIONS = {
    "tr": {
        "app_title": "Google Harita İşletme Aracı",
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
    },
    "en": {
        "app_title": "Google Maps Business Tool",
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
    },
}


class Application(tk.Tk):
    def __init__(self) -> None:
        super().__init__()
        self.selected_language = tk.StringVar(value="tr")
        self.title(self._("app_title"))
        self.geometry("900x600")

        self._results: List[PlaceResult] = []

        self._create_widgets()
        self._layout_widgets()
        self._bind_events()
        self._update_translations()

    def _create_widgets(self) -> None:
        self.api_key_label = ttk.Label(self, text="")
        self.api_key_entry = ttk.Entry(self, show="*")

        self.query_label = ttk.Label(self, text="")
        self.query_entry = ttk.Entry(self)

        self.location_label = ttk.Label(self, text="")
        self.location_entry = ttk.Entry(self)
        self.location_hint_label = ttk.Label(self, text="", foreground="gray")

        self.language_label = ttk.Label(self, text="")
        self.language_combo = ttk.Combobox(
            self,
            textvariable=self.selected_language,
            values=["tr", "en"],
            state="readonly",
        )

        self.search_button = ttk.Button(self, text="", command=self._on_search)

        self.status_var = tk.StringVar(value=self._("status_ready"))
        self.status_label = ttk.Label(self, textvariable=self.status_var)

        self.results_label = ttk.Label(self, text="")
        self.results_tree = ttk.Treeview(
            self,
            columns=("name", "phone", "rating"),
            show="headings",
            height=10,
        )
        self.results_tree.heading("name", text="")
        self.results_tree.heading("phone", text="")
        self.results_tree.heading("rating", text="")
        self.results_tree.column("name", width=240)
        self.results_tree.column("phone", width=180)
        self.results_tree.column("rating", width=140, anchor=tk.CENTER)

        self.details_label = ttk.Label(self, text="")
        self.details_text = tk.Text(self, wrap=tk.WORD, state=tk.DISABLED, height=14)
        self.details_scroll = ttk.Scrollbar(self, orient=tk.VERTICAL, command=self.details_text.yview)
        self.details_text.configure(yscrollcommand=self.details_scroll.set)

    def _layout_widgets(self) -> None:
        padding = {"padx": 10, "pady": 5}

        self.api_key_label.grid(row=0, column=0, sticky=tk.W, **padding)
        self.api_key_entry.grid(row=0, column=1, columnspan=3, sticky=tk.EW, **padding)

        self.query_label.grid(row=1, column=0, sticky=tk.W, **padding)
        self.query_entry.grid(row=1, column=1, columnspan=3, sticky=tk.EW, **padding)

        self.location_label.grid(row=2, column=0, sticky=tk.W, **padding)
        self.location_entry.grid(row=2, column=1, sticky=tk.EW, **padding)
        self.location_hint_label.grid(row=2, column=2, sticky=tk.W, **padding)

        self.language_label.grid(row=3, column=0, sticky=tk.W, **padding)
        self.language_combo.grid(row=3, column=1, sticky=tk.W, **padding)

        self.search_button.grid(row=3, column=3, sticky=tk.E, **padding)

        self.results_label.grid(row=4, column=0, sticky=tk.W, **padding)
        self.results_tree.grid(row=5, column=0, columnspan=3, sticky=tk.NSEW, padx=(10, 0), pady=5)
        self.details_label.grid(row=4, column=3, sticky=tk.W, **padding)
        self.details_text.grid(row=5, column=3, sticky=tk.NSEW, padx=(0, 10), pady=5)
        self.details_scroll.grid(row=5, column=4, sticky=tk.NS, pady=5)

        self.status_label.grid(row=6, column=0, columnspan=4, sticky=tk.W, **padding)

        self.columnconfigure(1, weight=1)
        self.columnconfigure(2, weight=1)
        self.columnconfigure(3, weight=1)
        self.rowconfigure(5, weight=1)

    def _bind_events(self) -> None:
        self.language_combo.bind("<<ComboboxSelected>>", lambda _: self._update_translations())
        self.results_tree.bind("<<TreeviewSelect>>", lambda _: self._on_select_result())

    def _on_search(self) -> None:
        api_key = self.api_key_entry.get().strip()
        query = self.query_entry.get().strip()
        location = self.location_entry.get().strip()
        if not api_key:
            messagebox.showerror(self._("error_title"), self._("error_missing_key"))
            return
        if not query:
            messagebox.showerror(self._("error_title"), self._("error_missing_query"))
            return

        language = self.selected_language.get()
        search_query = f"{query} {location}".strip() if location else query
        self.status_var.set(self._("status_searching"))
        self.search_button.config(state=tk.DISABLED)

        def worker() -> None:
            try:
                client = GoogleMapsClient(api_key)
                results = client.search_places(search_query, language=language)
            except requests.RequestException as exc:
                self._handle_error(str(exc))
                return
            except GoogleMapsError as exc:
                self._handle_error(str(exc))
                return
            self.after(0, lambda: self._update_results(results))

        threading.Thread(target=worker, daemon=True).start()

    def _handle_error(self, message: str) -> None:
        def callback() -> None:
            self.status_var.set(self._("status_error"))
            messagebox.showerror(self._("error_title"), message)
            self.search_button.config(state=tk.NORMAL)

        self.after(0, callback)

    def _update_results(self, results: List[PlaceResult]) -> None:
        self._results = results
        for item in self.results_tree.get_children():
            self.results_tree.delete(item)
        if not results:
            self.status_var.set(self._("no_results"))
            self.search_button.config(state=tk.NORMAL)
            self._clear_details()
            return
        for index, result in enumerate(results):
            phone = result.formatted_phone_number or "-"
            rating_display = "-"
            if result.rating is not None:
                rating_display = f"{result.rating:.1f}"
                if result.user_ratings_total is not None:
                    rating_display = f"{rating_display} ({result.user_ratings_total})"
            self.results_tree.insert(
                "",
                tk.END,
                iid=str(index),
                values=(result.name, phone, rating_display),
            )
        self.status_var.set(self._("status_ready"))
        self.search_button.config(state=tk.NORMAL)
        self.results_tree.selection_set("0")
        self.results_tree.focus("0")
        self._show_details(0)

    def _clear_details(self) -> None:
        self.details_text.config(state=tk.NORMAL)
        self.details_text.delete("1.0", tk.END)
        self.details_text.config(state=tk.DISABLED)

    def _on_select_result(self) -> None:
        selection = self.results_tree.selection()
        if not selection:
            return
        index = int(selection[0])
        self._show_details(index)

    def _show_details(self, index: int) -> None:
        if index >= len(self._results):
            return
        result = self._results[index]
        lines = [
            f"{self._('column_name')}: {result.name}",
            f"{self._('column_phone')}: {result.formatted_phone_number or '-'}",
            f"{self._('column_rating')}: {result.rating or '-'}",
        ]
        if result.user_ratings_total is not None:
            lines.append(f"{self._('ratings_total')}: {result.user_ratings_total}")
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

        self.details_text.config(state=tk.NORMAL)
        self.details_text.delete("1.0", tk.END)
        self.details_text.insert(tk.END, "\n".join(lines))
        self.details_text.config(state=tk.DISABLED)

    def _update_translations(self) -> None:
        self.title(self._("app_title"))
        self.api_key_label.config(text=self._("api_key"))
        self.query_label.config(text=self._("query"))
        self.location_label.config(text=self._("location"))
        self.location_hint_label.config(text=self._("location_hint"))
        self.language_label.config(text=self._("language"))
        self.search_button.config(text=self._("search"))
        self.results_label.config(text=self._("results"))
        self.details_label.config(text=self._("details"))
        self.results_tree.heading("name", text=self._("column_name"))
        self.results_tree.heading("phone", text=self._("column_phone"))
        self.results_tree.heading("rating", text=self._("column_rating"))
        self.status_var.set(self._("status_ready"))
        # Refresh details panel if something is selected
        self._on_select_result()

    def _(self, key: str) -> str:
        language = self.selected_language.get()
        return TRANSLATIONS.get(language, TRANSLATIONS["tr"]).get(key, key)


def main() -> None:
    app = Application()
    app.mainloop()


if __name__ == "__main__":
    main()
