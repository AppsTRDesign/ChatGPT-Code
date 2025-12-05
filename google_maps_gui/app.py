"""GUI application for querying Google Maps business details with bilingual support."""
from __future__ import annotations

import csv
import os
import html
import io
import json
import logging
import re
import unicodedata
import textwrap
import threading
import time
import urllib.parse
from contextlib import suppress
from dataclasses import dataclass, field
from datetime import datetime
from pathlib import Path
from typing import Callable, Dict, List, Optional

import requests
import tkinter as tk
from tkinter import filedialog, messagebox, ttk

from PIL import Image, ImageDraw, ImageTk, UnidentifiedImageError
from fpdf import FPDF, XPos, YPos
from playwright.sync_api import (
    TimeoutError as PlaywrightTimeoutError,
    Error as PlaywrightError,
    Locator,
    Page,
    sync_playwright,
)
from playwright._impl._errors import TargetClosedError
from openpyxl import Workbook

from .license_manager import LicenseError, LicenseManager


logging.basicConfig(
    filename="google_maps_gui.log",
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
)


BASE_DIR = Path(__file__).resolve().parent
PDF_FONT_PATH = BASE_DIR / "assets" / "fonts" / "DejaVuSans.ttf"
CITIES_PATH = BASE_DIR / "assets" / "cities.json"
SETTINGS_PATH = BASE_DIR / "settings.json"


def classify_phone(tel: Optional[str]) -> str:
    if not tel:
        return "Bilinmiyor"
    t = re.sub(r"[\s\-()]", "", tel)
    if t.startswith("05") or (t.startswith("5") and len(t) >= 10):
        return "Cep"
    if t.startswith("444"):
        return "Kurumsal (444)"
    if t.startswith("850") or t.startswith("0850"):
        return "Kurumsal (850)"
    if re.match(r"0[2-9][0-9]{9}", t):
        return "Sabit Hat"
    return "Bilinmiyor"


def upscale_img(url: str) -> str:
    if not url:
        return ""

    # Normalize protocol-less images
    if url.startswith("//"):
        url = f"https:{url}"

    # Normalize query-based dimensions (e.g., w=408&h=240)
    def repl_query(m: re.Match[str]) -> str:
        w = int(m.group(1))
        h = int(m.group(2))
        return f"w={w*10}&h={h*10}"

    def repl_dash(m: re.Match[str]) -> str:
        w = int(m.group(1))
        h = int(m.group(2))
        return f"w{w*10}-h{h*10}"

    url = re.sub(r"w=(\d+)&h=(\d+)", repl_query, url)

    url = re.sub(r"w(\d+)-h(\d+)", repl_dash, url)

    return url


def slugify_category(text: str) -> str:
    text = (text or "").strip().lower()
    if not text:
        return ""
    normalized = unicodedata.normalize("NFKD", text)
    # Keep common Turkish characters while stripping other accents
    normalized = "".join(ch for ch in normalized if not unicodedata.combining(ch))
    normalized = re.sub(r"[^a-z0-9çğıöşü\s-]", "", normalized)
    normalized = re.sub(r"[\s_]+", "-", normalized)
    normalized = normalized.strip("-")
    return normalized


def sanitize_phone(phone: Optional[str], address: str) -> Optional[str]:
    if not phone:
        return None
    trimmed = phone.strip()
    if not trimmed:
        return None
    normalized_phone = " ".join(trimmed.split()).lower()
    normalized_address = " ".join((address or "").split()).lower()
    if not trimmed[0].isdigit() and normalized_phone == normalized_address:
        return None
    return trimmed


def extract_lat_lng_from_link(link: str) -> tuple[str, str]:
    if not link:
        return "", ""
    m = re.search(r"@(-?\d+\.\d+),(-?\d+\.\d+)", link)
    if m:
        return m.group(1), m.group(2)
    m = re.search(r"!3d(-?\d+\.\d+)!4d(-?\d+\.\d+)", link)
    if m:
        return m.group(1), m.group(2)
    return "", ""


TRANSLATIONS: Dict[str, Dict[str, str]] = {
    "tr": {
        "app_title": "Google Haritalar İşletme Aracı",
        "tab_api": "API ile Tara",
        "tab_bot": "Bot ile Tara",
        "tab_settings": "Ayarlar",
        "tab_api_settings": "API Ayarları",
        "api_key": "API Anahtarı",
        "query": "Arama",
        "language": "Dil",
        "search": "Ara",
        "result_limit": "İşletme Sayısı",
        "results": "Sonuçlar",
        "details": "Detaylar",
        "search_panel": "Arama Ayarları",
        "column_name": "İsim",
        "column_phone": "Telefon",
        "column_rating": "Puan",
        "column_category": "Kategori",
        "column_phone_type": "Telefon Tipi",
        "column_lat": "Enlem",
        "column_lng": "Boylam",
        "column_website": "Web Sitesi",
        "column_image": "İşletme Görseli",
        "review_limit": "Yorum Sayısı",
        "save_json": "JSON Kaydet",
        "save_csv": "CSV Kaydet",
        "save_pdf": "PDF Kaydet",
        "transfer_data": "Verileri Aktar",
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
        "reviews": "Müşteri Yorumları",
        "review_author": "Yazar",
        "review_rating": "Puan",
        "review_time": "Zaman",
        "review_text": "Yorum",
        "review_profile": "Profil Fotoğrafı",
        "review_media": "Yorum Fotoğrafları",
        "ratings_total": "Toplam Değerlendirme",
        "busy_hours": "Yoğun Saatler",
        "save_xlsx": "XLSX Kaydet",
        "field_section": "Veri Alanları",
        "field_name": "İsim",
        "field_formatted_address": "Adres",
        "field_formatted_phone_number": "Telefon",
        "field_business_type": "İşletme Türü",
        "field_opening_hours": "Çalışma Saatleri",
        "field_rating": "Puan",
        "field_user_ratings_total": "Toplam Değerlendirme",
        "field_location": "İşletme Konumu",
        "field_business_image": "İşletme Görseli",
        "field_website": "Web Sitesi",
        "field_reviews": "Müşteri Yorumları",
        "field_review_photo_urls": "Yorum Fotoğrafları",
        "field_text_extra": "Yorum Ek Bilgileri",
        "field_busy_hours": "Yoğun Saatler",
        "city": "Şehir",
        "settings_panel": "Genel Ayarlar",
        "settings_search_limit": "Maksimum İşletme Sayısı",
        "settings_review_limit": "Maksimum Yorum Sayısı",
        "settings_api_url": "API Uç Noktası",
        "settings_api_token": "API Anahtarı",
        "settings_apply": "Ayarları Uygula",
        "settings_api_panel": "API Köprüsü",
        "settings_api_hint": "Sonuçları aktarırken kullanılacak uç nokta ve anahtar",
        "error_missing_api_url": "Lütfen API uç noktasını girin.",
        "transfer_success": "Veriler API'ye aktarıldı.",
        "transfer_failed": "Veri aktarımı başarısız oldu.",
        "settings_saved": "Ayarlar güncellendi.",
        "log_search_started": "Bot araması başlatıldı: {query}",
        "log_click_card": "Liste öğesine tıklanıyor: {name}",
        "log_panel_opened": "Detay paneli açıldı: {name}",
        "log_panel_failed": "Detay paneli açılamadı: {name}",
        "log_result_captured": "İşletme verileri alındı: {name}",
        "log_search_finished": "Bot taraması tamamlandı. Toplam veri: {count}",
        "log_search_failed": "Bot taraması hata verdi: {message}",
        "app_tagline": "API ve bot taramalarını tek panelde birleştirin",
        "tab_license": "Lisans",
        "license_info_group": "Lisans Bilgileri",
        "license_activation_group": "Lisans Aktivasyonu",
        "license_machine_id": "Makine Kimliği",
        "license_copy_id": "Kimliği Kopyala",
        "license_status_valid": "Lisans durumu: Aktif",
        "license_status_invalid": "Lisans durumu: Pasif",
        "license_days_left": "Kalan Gün",
        "license_expiry": "Bitiş Tarihi",
        "license_duration": "Lisans Süresi",
        "license_key": "Lisans Anahtarı",
        "license_activate": "Lisansı Etkinleştir",
        "license_machine_copied": "Kimlik panoya kopyalandı.",
        "license_missing_key": "Lisans anahtarı gerekli.",
        "license_invalid_key": "Lisans anahtarı doğrulanamadı.",
        "license_success": "Lisans başarıyla etkinleştirildi.",
        "license_required": "Devam etmeden önce geçerli bir lisans etkinleştirmeniz gerekir.",
        "duration_years": "yıl",
        "duration_months": "ay",
        "duration_days": "gün",
        "pdf_title": "Google Haritalar Raporu",
        "pdf_unknown": "Bilinmiyor",
    },
    "en": {
        "app_title": "Google Maps Business Tool",
        "tab_api": "Scan via API",
        "tab_bot": "Scan via Bot",
        "tab_settings": "Settings",
        "tab_api_settings": "API Bridge",
        "api_key": "API Key",
        "query": "Query",
        "language": "Language",
        "search": "Search",
        "result_limit": "Business Count",
        "results": "Results",
        "details": "Details",
        "search_panel": "Search Options",
        "column_name": "Name",
        "column_phone": "Phone",
        "column_rating": "Rating",
        "column_category": "Category",
        "column_phone_type": "Phone Type",
        "column_lat": "Latitude",
        "column_lng": "Longitude",
        "column_website": "Website",
        "column_image": "Business Image",
        "review_limit": "Review Count",
        "save_json": "Save JSON",
        "save_csv": "Save CSV",
        "save_pdf": "Save PDF",
        "transfer_data": "Transfer Data",
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
        "reviews": "Customer Reviews",
        "review_author": "Author",
        "review_rating": "Rating",
        "review_time": "Time",
        "review_text": "Review",
        "review_profile": "Profile Photo",
        "review_media": "Review Photos",
        "ratings_total": "Total Ratings",
        "busy_hours": "Popular Times",
        "save_xlsx": "Save XLSX",
        "field_section": "Data Fields",
        "field_name": "Name",
        "field_formatted_address": "Address",
        "field_formatted_phone_number": "Phone",
        "field_business_type": "Business Type",
        "field_opening_hours": "Opening Hours",
        "field_rating": "Rating",
        "field_user_ratings_total": "Rating Count",
        "field_location": "Business Location",
        "field_business_image": "Business Image",
        "field_website": "Website",
        "field_reviews": "Customer Reviews",
        "field_review_photo_urls": "Review Photos",
        "field_text_extra": "Review Extras",
        "field_busy_hours": "Popular Times",
        "city": "City",
        "settings_panel": "Global Settings",
        "settings_search_limit": "Max Business Count",
        "settings_review_limit": "Max Review Count",
        "settings_api_url": "API Endpoint",
        "settings_api_token": "API Token",
        "settings_apply": "Apply Settings",
        "settings_api_panel": "API Bridge",
        "settings_api_hint": "Endpoint and token used when transferring results",
        "error_missing_api_url": "Please enter an API endpoint.",
        "transfer_success": "Data transferred successfully.",
        "transfer_failed": "Data transfer failed.",
        "settings_saved": "Settings updated.",
        "log_search_started": "Bot scan started: {query}",
        "log_click_card": "Clicking result card: {name}",
        "log_panel_opened": "Details panel opened: {name}",
        "log_panel_failed": "Details panel failed: {name}",
        "log_result_captured": "Captured business data: {name}",
        "log_search_finished": "Bot scan finished. Total results: {count}",
        "log_search_failed": "Bot scan failed: {message}",
        "app_tagline": "Blend API and bot scans in one workspace",
        "tab_license": "License",
        "license_info_group": "License Status",
        "license_activation_group": "License Activation",
        "license_machine_id": "Machine ID",
        "license_copy_id": "Copy ID",
        "license_status_valid": "License status: Active",
        "license_status_invalid": "License status: Inactive",
        "license_days_left": "Days Remaining",
        "license_expiry": "Expiration",
        "license_duration": "License Duration",
        "license_key": "License Key",
        "license_activate": "Activate License",
        "license_machine_copied": "Machine ID copied to clipboard.",
        "license_missing_key": "License key is required.",
        "license_invalid_key": "License key could not be validated.",
        "license_success": "License activated successfully.",
        "license_required": "You must activate a valid license before running scans.",
        "duration_years": "years",
        "duration_months": "months",
        "duration_days": "days",
        "pdf_title": "Google Maps Report",
        "pdf_unknown": "Unknown",
    },
}


@dataclass
class PlaceReview:
    author_name: str
    rating: Optional[float]
    relative_time: Optional[str]
    text: str
    profile_photo_url: Optional[str] = None
    text_extra: Dict[str, str] = field(default_factory=dict)
    review_photo_urls: List[str] = field(default_factory=list)

    def to_dict(self) -> Dict[str, object]:
        return {
            "author_name": self.author_name,
            "rating": self.rating,
            "relative_time": self.relative_time,
            "text": self.text,
            "profile_photo_url": self.profile_photo_url,
            "text_extra": self.text_extra,
            "review_photo_urls": list(self.review_photo_urls),
        }


@dataclass
class PlaceResult:
    name: str
    formatted_address: str
    formatted_phone_number: Optional[str]
    telephone_type: str
    business_type: Optional[str]
    category_slug: str
    business_image: str
    latitude: str
    longitude: str
    website: Optional[str]
    opening_hours: List[str]
    rating: Optional[float]
    user_ratings_total: Optional[int]
    reviews: List[PlaceReview]
    busy_hours: Dict[str, List[Dict[str, str]]] = field(default_factory=dict)
    city_name: str = ""

    def to_dict(self) -> Dict[str, object]:
        return {
            "city_name": self.city_name,
            "name": self.name,
            "formatted_address": self.formatted_address,
            "formatted_phone_number": self.formatted_phone_number,
            "telephone_type": self.telephone_type,
            "business_type": self.business_type,
            "category_slug": self.category_slug,
            "business_image": self.business_image,
            "latitude": self.latitude,
            "longitude": self.longitude,
            "website": self.website,
            "opening_hours": list(self.opening_hours),
            "rating": self.rating,
            "user_ratings_total": self.user_ratings_total,
            "reviews": [review.to_dict() for review in self.reviews],
            "busy_hours": self.busy_hours,
        }

    def to_csv_row(self) -> Dict[str, Optional[str]]:
        return {
            "city_name": self.city_name,
            "name": self.name,
            "address": self.formatted_address,
            "phone": self.formatted_phone_number or "",
            "telephone_type": self.telephone_type or "",
            "category": self.business_type or "",
            "category_slug": self.category_slug or "",
            "business_image": self.business_image or "",
            "latitude": self.latitude or "",
            "longitude": self.longitude or "",
            "website": self.website or "",
            "opening_hours": " | ".join(self.opening_hours) if self.opening_hours else "",
            "rating": f"{self.rating:.1f}" if self.rating is not None else "",
            "rating_count": "" if self.user_ratings_total is None else str(self.user_ratings_total),
            "reviews": " || ".join(
                " ".join(
                    filter(
                        None,
                        [
                            review.author_name,
                            f"({review.profile_photo_url})" if review.profile_photo_url else "",
                            (
                                f"[Photos: {', '.join(review.review_photo_urls)}]"
                                if review.review_photo_urls
                                else ""
                            ),
                            review.text,
                        ],
                    )
                ).strip()
                for review in self.reviews
            ),
            "busy_hours": json.dumps(self.busy_hours, ensure_ascii=False) if self.busy_hours else "",
        }


def split_review_text(text: str) -> tuple[str, Dict[str, str]]:
    """Return review text without attempting to extract structured metadata."""

    return (text or "").strip(), {}


FIELD_OPTION_KEYS = [
    "name",
    "formatted_address",
    "formatted_phone_number",
    "business_type",
    "opening_hours",
    "rating",
    "user_ratings_total",
    "location",
    "business_image",
    "website",
    "reviews",
    "review_photo_urls",
    "text_extra",
    "busy_hours",
]


@dataclass
class FieldSelection:
    name: bool = True
    formatted_address: bool = True
    formatted_phone_number: bool = True
    business_type: bool = True
    opening_hours: bool = False
    rating: bool = True
    user_ratings_total: bool = True
    location: bool = True
    business_image: bool = True
    website: bool = False
    reviews: bool = False
    review_photo_urls: bool = False
    text_extra: bool = False
    busy_hours: bool = False
    category_slug: bool = True

    def wants_reviews(self) -> bool:
        return self.reviews

    def apply(self, result: PlaceResult) -> PlaceResult:
        if not self.name:
            result.name = ""
        if not self.formatted_address:
            result.formatted_address = ""
        if not self.formatted_phone_number:
            result.formatted_phone_number = None
            result.telephone_type = ""
        if not self.business_type:
            result.business_type = None
            result.category_slug = ""
        if not self.category_slug:
            result.category_slug = ""
        if not self.opening_hours:
            result.opening_hours = []
        if not self.rating:
            result.rating = None
        if not self.user_ratings_total:
            result.user_ratings_total = None
        if not self.location:
            result.latitude = ""
            result.longitude = ""
        if not self.business_image:
            result.business_image = ""
        if not self.website:
            result.website = None
        if not self.busy_hours:
            result.busy_hours = {}
        if not self.wants_reviews():
            result.reviews = []
        else:
            if not self.review_photo_urls:
                for review in result.reviews:
                    review.review_photo_urls = []
            if not self.text_extra:
                for review in result.reviews:
                    review.text_extra = {}
        return result


class ResultPdfExporter:
    """Generate a UTF-8 friendly PDF report for place results."""

    MAX_REVIEWS = 5
    WRAP_WIDTH = 95

    def __init__(self, font_path: Path) -> None:
        self.font_path = font_path

    def export(
        self,
        results: List[PlaceResult],
        output_path: str,
        translate: Callable[[str], str],
    ) -> None:
        if not self.font_path.exists():
            raise FileNotFoundError(self.font_path)
        pdf = FPDF()
        pdf.set_auto_page_break(auto=True, margin=15)
        pdf.add_font("DejaVu", "", str(self.font_path))
        pdf.add_font("DejaVu", "B", str(self.font_path))
        pdf.add_page()
        pdf.set_font("DejaVu", "B", 16)
        pdf.cell(0, 10, translate("pdf_title"), new_x=XPos.LMARGIN, new_y=YPos.NEXT)
        pdf.ln(4)
        for index, result in enumerate(results, 1):
            self._write_result_block(pdf, index, result, translate)
        pdf.output(output_path)

    def _write_result_block(
        self,
        pdf: FPDF,
        index: int,
        result: PlaceResult,
        translate: Callable[[str], str],
    ) -> None:
        name = result.name or translate("pdf_unknown")
        pdf.set_font("DejaVu", "B", 12)
        self._write_wrapped(pdf, f"{index}. {name}", line_height=8)
        pdf.set_font("DejaVu", "", 10)
        details = [
            f"{translate('column_phone')}: {result.formatted_phone_number or '-'}",
            f"{translate('column_phone_type')}: {result.telephone_type or '-'}",
            f"{translate('column_category')}: {result.business_type or '-'}",
            f"{translate('column_rating')}: {result.rating if result.rating is not None else '-'}",
            f"{translate('ratings_total')}: {result.user_ratings_total if result.user_ratings_total is not None else '-'}",
            f"{translate('address')}: {result.formatted_address or '-'}",
        ]
        if result.website:
            details.append(f"{translate('column_website')}: {result.website}")
        if result.business_image:
            details.append(f"{translate('column_image')}: {result.business_image}")
        if result.latitude or result.longitude:
            details.append(
                f"{translate('column_lat')}/{translate('column_lng')}: {result.latitude or '-'}, {result.longitude or '-'}"
            )
        for line in details:
            self._write_wrapped(pdf, line)
        if result.opening_hours:
            pdf.ln(1)
            pdf.set_font("DejaVu", "B", 10)
            pdf.cell(0, 6, translate("opening_hours"), new_x=XPos.LMARGIN, new_y=YPos.NEXT)
            pdf.set_font("DejaVu", "", 10)
            for row in result.opening_hours:
                self._write_wrapped(pdf, f"  • {row}", line_height=5)
        if result.busy_hours:
            pdf.ln(1)
            pdf.set_font("DejaVu", "B", 10)
            pdf.cell(0, 6, translate("busy_hours"), new_x=XPos.LMARGIN, new_y=YPos.NEXT)
            pdf.set_font("DejaVu", "", 10)
            for day, slots in result.busy_hours.items():
                self._write_wrapped(pdf, f"  {day}:", line_height=5)
                for slot in slots:
                    self._write_wrapped(
                        pdf,
                        f"    {slot.get('time', '')}: {slot.get('busy', '')}",
                        line_height=5,
                    )
        if result.reviews:
            pdf.ln(1)
            pdf.set_font("DejaVu", "B", 10)
            pdf.cell(0, 6, translate("reviews"), new_x=XPos.LMARGIN, new_y=YPos.NEXT)
            pdf.set_font("DejaVu", "", 10)
            for review in result.reviews[: self.MAX_REVIEWS]:
                self._write_review(pdf, review, translate)
        pdf.ln(2)

    def _write_review(self, pdf: FPDF, review: PlaceReview, translate: Callable[[str], str]) -> None:
        self._write_wrapped(pdf, f"  {translate('review_author')}: {review.author_name or '-'}", line_height=5)
        if review.rating is not None:
            self._write_wrapped(pdf, f"    {translate('review_rating')}: {review.rating}", line_height=5)
        if review.relative_time:
            self._write_wrapped(pdf, f"    {translate('review_time')}: {review.relative_time}", line_height=5)
        if review.profile_photo_url:
            self._write_wrapped(
                pdf,
                f"    {translate('review_profile')}: {review.profile_photo_url}",
                line_height=5,
            )
        if review.review_photo_urls:
            self._write_wrapped(
                pdf,
                f"    {translate('review_media')}: {', '.join(review.review_photo_urls)}",
                line_height=5,
            )
        self._write_wrapped(
            pdf,
            f"    {translate('review_text')}: {review.text or '-'}",
            line_height=5,
        )
        if review.text_extra:
            for key, value in review.text_extra.items():
                self._write_wrapped(pdf, f"      - {key}: {value}", line_height=5)
        pdf.ln(1)

    def _write_wrapped(self, pdf: FPDF, text: str, line_height: float = 6) -> None:
        content = self._wrap_text(text)
        pdf.multi_cell(0, line_height, content, new_x=XPos.LMARGIN, new_y=YPos.NEXT)

    def _wrap_text(self, text: Optional[str]) -> str:
        if text is None:
            return ""
        raw_lines = text.splitlines() or [""]
        wrapped_lines: List[str] = []
        for paragraph in raw_lines:
            if not paragraph:
                wrapped_lines.append("")
                continue
            prefix_len = len(paragraph) - len(paragraph.lstrip())
            prefix = paragraph[:prefix_len]
            content = paragraph[prefix_len:].strip()
            if not content:
                wrapped_lines.append(prefix.rstrip())
                continue
            effective_width = max(20, self.WRAP_WIDTH - prefix_len)
            chunks = textwrap.wrap(
                content,
                width=effective_width,
                break_long_words=True,
                break_on_hyphens=False,
            )
            if not chunks:
                wrapped_lines.append(prefix + content)
                continue
            wrapped_lines.append(prefix + chunks[0])
            for chunk in chunks[1:]:
                wrapped_lines.append(" " * prefix_len + chunk)
        return "\n".join(wrapped_lines)


class GoogleMapsError(Exception):
    """Raised when the Google Maps API returns an error."""


class GoogleMapsClient:
    """Lightweight client for the Google Maps Places API."""

    BASE_URL = "https://maps.googleapis.com/maps/api/place"

    def __init__(self, api_key: str) -> None:
        self.api_key = api_key

    def search_places(
        self,
        query: str,
        language: str = "tr",
        limit: int = 5,
        field_selection: Optional[FieldSelection] = None,
        review_limit: int = 5,
    ) -> List[PlaceResult]:
        """Search for places and retrieve detailed information for each result."""
        params = {
            "query": query,
            "language": language,
        }
        search_response = self._request("textsearch", params)
        results = search_response.get("results", [])
        if not results:
            return []

        selection = field_selection or FieldSelection()
        review_cap = max(0, review_limit)
        detailed_results: List[PlaceResult] = []
        for place in results[:limit]:
            place_id = place.get("place_id")
            if not place_id:
                continue
            detail_fields = ["name"]
            if selection.formatted_address:
                detail_fields.append("formatted_address")
            if selection.formatted_phone_number:
                detail_fields.append("formatted_phone_number")
            if selection.opening_hours:
                detail_fields.append("opening_hours")
            if selection.rating:
                detail_fields.append("rating")
            if selection.user_ratings_total:
                detail_fields.append("user_ratings_total")
            if selection.website:
                detail_fields.append("website")
            if selection.wants_reviews():
                detail_fields.append("reviews")
            detailed = self._request(
                "details",
                {
                    "place_id": place_id,
                    "language": language,
                    "fields": ",".join(detail_fields + ["website", "geometry"]),
                },
            )
            result = detailed.get("result")
            if not result:
                continue
            opening_hours = result.get("opening_hours", {}).get("weekday_text", [])
            reviews_data = result.get("reviews", [])
            seen_review_signatures: set[str] = set()
            reviews: List[PlaceReview] = []
            if selection.wants_reviews():
                for review in reviews_data:
                    signature = self._review_signature(
                        review.get("author_name"),
                        review.get("text"),
                        review.get("relative_time_description"),
                    )
                    if signature in seen_review_signatures:
                        continue
                    seen_review_signatures.add(signature)
                    content, extras = split_review_text(review.get("text", ""))
                    reviews.append(
                        PlaceReview(
                            author_name=review.get("author_name", ""),
                            rating=review.get("rating"),
                            relative_time=review.get("relative_time_description"),
                            text=content,
                            profile_photo_url=upscale_img(review.get("profile_photo_url")),
                            text_extra=extras,
                            review_photo_urls=[],
                        )
                    )
                    if len(reviews) >= review_cap:
                        break
            business_type = self._format_business_type(result.get("types", []))
            category_slug = slugify_category(business_type) if business_type else ""
            detailed_results.append(
                selection.apply(
                    PlaceResult(
                        name=result.get("name", ""),
                        formatted_address=result.get("formatted_address", ""),
                        formatted_phone_number=sanitize_phone(
                            result.get("formatted_phone_number"),
                            result.get("formatted_address", ""),
                        ),
                        telephone_type=classify_phone(
                            sanitize_phone(
                                result.get("formatted_phone_number"),
                                result.get("formatted_address", ""),
                            )
                            or "",
                        ),
                        business_type=business_type,
                        category_slug=category_slug,
                        business_image="",
                        latitude=str(result.get("geometry", {}).get("location", {}).get("lat", "")),
                        longitude=str(result.get("geometry", {}).get("location", {}).get("lng", "")),
                        website=result.get("website"),
                        opening_hours=opening_hours,
                        rating=result.get("rating"),
                        user_ratings_total=result.get("user_ratings_total"),
                        reviews=reviews,
                        busy_hours={},
                    )
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

    def _format_business_type(self, types: List[str]) -> Optional[str]:
        for value in types or []:
            if not value:
                continue
            cleaned = value.replace("_", " ").strip()
            if cleaned:
                return cleaned.title()
        return None

    def _review_signature(
        self,
        author: Optional[str],
        text: Optional[str],
        relative_time: Optional[str],
    ) -> str:
        parts = []
        for value in (author, text, relative_time):
            normalized = " ".join((value or "").split()).lower()
            parts.append(normalized)
        return "||".join(parts)


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

    def __init__(
        self,
        language: str = "tr",
        limit: int = 5,
        max_reviews: int = 3,
        field_selection: Optional[FieldSelection] = None,
        city_center: Optional[tuple[str, str]] = None,
        city_name: str = "",
    ) -> None:
        self.language = language or "tr"
        self.limit = limit
        self.max_reviews = max(0, max_reviews)
        self.field_selection = field_selection or FieldSelection()
        self.city_center = city_center
        self.city_name = city_name or ""

    def search(
        self,
        query: str,
        progress_callback: Optional[Callable[[bytes], None]] = None,
        result_callback: Optional[Callable[[PlaceResult], None]] = None,
        attempt_callback: Optional[Callable[[int, Optional[str]], None]] = None,
        event_callback: Optional[Callable[[str, Dict[str, str]], None]] = None,
    ) -> List[PlaceResult]:
        LOGGER.info("Starting Playwright scrape for query='%s'", query)
        results: List[PlaceResult] = []
        seen_locations: set[tuple[str, str, str]] = set()
        self._emit_event(event_callback, "search_started", {"query": query})

        browser = None
        context = None
        try:
            with sync_playwright() as playwright:
                browser = playwright.chromium.launch(
                    headless=False,
                    args=[
                        "--disable-notifications",
                        "--disable-infobars",
                        "--disable-blink-features=AutomationControlled",
                    ],
                )
                context = browser.new_context(
                    locale=self._locale(),
                    viewport={"width": 1280, "height": 900},
                    screen={"width": 1280, "height": 900},
                    user_agent=self._user_agent(),
                )
                page = context.new_page()
                start_url = self._build_search_url(query)
                page.goto(start_url, wait_until="load", timeout=90000)
                self._ensure_fake_cursor(page)
                self._handle_privacy_dialog(page)
                self._send_progress_screenshot(page, progress_callback)
                if not self.city_center:
                    self._perform_search(page, query)
                self._ensure_fake_cursor(page)
                self._handle_privacy_dialog(page)
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
                    cursor_position = self._move_fake_cursor_to_locator(page, article)
                    if cursor_position:
                        self._send_progress_screenshot(page, progress_callback)
                    self._emit_event(event_callback, "click_card", {"name": card_name or f"#{index + 1}"})
                    clicked = False
                    if cursor_position:
                        with suppress(PlaywrightError):
                            page.mouse.click(cursor_position[0], cursor_position[1], delay=70)
                            clicked = True
                    if not clicked:
                        self._click_article_card(article)
                    if cursor_position:
                        self._animate_fake_click(page, cursor_position)
                        self._send_progress_screenshot(page, progress_callback)
                    if attempt_callback:
                        with suppress(Exception):
                            attempt_callback(index + 1, card_name)
                    page.wait_for_timeout(450)
                    self._send_progress_screenshot(page, progress_callback)
                    try:
                        active_name = self._wait_for_place_panel(page, card_name)
                    except PlaywrightTimeoutError:
                        LOGGER.warning("Details panel did not appear for '%s'", card_name or "(unknown)")
                        self._emit_event(
                            event_callback,
                            "panel_failed",
                            {"name": card_name or f"#{index + 1}"},
                        )
                        self._send_progress_screenshot(page, progress_callback)
                        index += 1
                        continue

                    self._emit_event(event_callback, "panel_opened", {"name": active_name or card_name or ""})
                    self._send_progress_screenshot(page, progress_callback)
                    place = self._extract_details(page)
                    if not place.name:
                        place.name = active_name or card_name or ""
                    dedup_key = (
                        self._normalize_text(place.formatted_address),
                        place.latitude,
                        place.longitude,
                    )
                    if all(dedup_key):
                        if dedup_key in seen_locations:
                            index += 1
                            continue
                        seen_locations.add(dedup_key)

                    results.append(place)
                    self._emit_event(event_callback, "result_captured", {"name": place.name})
                    if result_callback:
                        try:
                            result_callback(place)
                        except Exception:
                            LOGGER.exception("Result callback failed for '%s'", place.name)
                    page.wait_for_timeout(350)
                    index += 1

                self._send_progress_screenshot(page, progress_callback)
                with suppress(PlaywrightError, TargetClosedError):
                    context.close()
                with suppress(PlaywrightError, TargetClosedError):
                    browser.close()

        except TargetClosedError as exc:
            self._emit_event(
                event_callback,
                "search_cancelled",
                {"message": str(exc) or "browser closed"},
            )
            LOGGER.info("Playwright window closed by user; scan aborted")
            with suppress(PlaywrightError, TargetClosedError):
                if context:
                    context.close()
            with suppress(PlaywrightError, TargetClosedError):
                if browser:
                    browser.close()
            return results
        except PlaywrightError as exc:
            self._emit_event(event_callback, "search_failed", {"message": str(exc) or "unknown"})
            LOGGER.exception("Playwright automation failed")
            raise
        finally:
            self._emit_event(event_callback, "search_finished", {"count": str(len(results))})

        LOGGER.info("Playwright scrape finished with %d results", len(results))
        return results

    def _locale(self) -> str:
        normalized = (self.language or "en").lower()
        mapping = {"tr": "tr-TR", "en": "en-US"}
        return mapping.get(normalized, "en-US")

    def _build_search_url(self, query: str) -> str:
        encoded_query = urllib.parse.quote_plus(query)
        if self.city_center and all(self.city_center):
            lat, lng = self.city_center
            return (
                f"{self.MAP_URL}/search/{encoded_query}/@{lat},{lng},13z"
                f"/data=!3m1!4b1?hl={self.language}&tentry=ttu"
            )
        return f"{self.MAP_URL}?hl={self.language}"

    def _perform_search(self, page: Page, query: str) -> None:
        search_box = page.wait_for_selector("input#searchboxinput", timeout=60000)
        search_box.fill("")
        search_box.type(query, delay=40)
        page.keyboard.press("Enter")
        page.wait_for_timeout(600)

    def _wait_for_result_list(self, page: Page) -> None:
        selectors = [
            'div[role="feed"] div.Nv2PK',
            'div.Nv2PK',
            'div[role="feed"] div[role="article"]',
        ]
        deadline = time.time() + 75
        while time.time() < deadline:
            self._handle_privacy_dialog(page)
            for selector in selectors:
                articles = page.locator(selector)
                if not articles.count():
                    continue
                try:
                    articles.first.wait_for(state="visible", timeout=2000)
                    self._ensure_fake_cursor(page)
                    page.wait_for_timeout(600)
                    return
                except PlaywrightTimeoutError:
                    continue
            page.wait_for_timeout(500)
        raise PlaywrightTimeoutError("Search results did not appear in time")

    def _user_agent(self) -> str:
        return (
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 "
            "(KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36"
        )

    def _handle_privacy_dialog(self, page: Page) -> None:
        selectors = [
            "button:has-text('Kabul et')",
            "button:has-text('Tümünü kabul et')",
            "button:has-text('Kabul Et')",
            "button:has-text('Tümünü Kabul Et')",
            "button:has-text('Accept all')",
            "button:has-text('Accept All')",
            "button:has-text('I agree')",
            "#introAgreeButton",
        ]
        contexts = [page, *page.frames]
        for context in contexts:
            for selector in selectors:
                locator = context.locator(selector)
                if not locator.count():
                    continue
                try:
                    target = locator.first
                    if target.is_visible():
                        target.click(delay=30)
                        page.wait_for_timeout(800)
                        return
                except PlaywrightError:
                    continue

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
        return page.locator('div[role="feed"] div.Nv2PK, div.Nv2PK')

    def _click_article_card(self, locator: Locator) -> None:
        target = locator.locator("a.hfpxzc[aria-label]").first
        if not target.count():
            target = locator.locator('a[href]').first
        try:
            if target.count():
                with suppress(PlaywrightError):
                    target.evaluate("el => el.removeAttribute('target')")
                target.click(button="left", delay=70)
                return
            locator.click(delay=60)
        except PlaywrightError:
            locator.click(force=True)

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

    def _scroll_reviews_pane(self, page: Page) -> bool:
        containers = [
            'div.m6QErb[aria-label][jslog]',
            'div.m6QErb.XiKgde',
            'div[aria-label*="yorum"]',
            'div[aria-label*="review"]',
        ]
        for selector in containers:
            locator = page.locator(selector).first
            if locator.count() == 0:
                continue
            handle = locator.element_handle()
            if not handle:
                continue
            try:
                handle.evaluate("el => { el.scrollTop = el.scrollTop + (el.clientHeight || 600); }")
                return True
            except PlaywrightError:
                continue
        with suppress(PlaywrightError):
            page.mouse.wheel(0, 800)
            return True
        return False

    def _wait_for_review_loader(self, page: Page) -> None:
        loaders = [
            "div.qjESne",
            "div[jscontroller*='lV5qQe']",
            "div[jscontroller*='NXZ1r']",
            "div[role='progressbar']",
        ]
        for selector in loaders:
            locator = page.locator(selector)
            if locator.count():
                with suppress(PlaywrightError):
                    locator.first.wait_for(state="hidden", timeout=2500)
                break
        page.wait_for_timeout(150)

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
        heading_selectors = [
            'div[role="main"] h1[class*="fontHeadline"], div[role="main"] h1.DUwDvf',
            'div[role="main"] div[class*="DUwDvf"]',
            'h1.DUwDvf',
            'div.DUwDvf',
        ]
        while time.time() - start < 25:
            for selector in heading_selectors:
                heading = page.locator(selector).first
                if heading.count() == 0:
                    continue
                text = self._safe_inner_text(heading, timeout=1500)
                if text:
                    normalized = self._normalize_text(text)
                    if not expected_normalized or expected_normalized in normalized:
                        return text
            page.wait_for_timeout(400)
        raise PlaywrightTimeoutError("Place details did not load in time")

    def _extract_details(self, page: Page) -> PlaceResult:
        selection = self.field_selection

        # 1) İşletme görseli
        business_image = self._extract_default_image(page) if selection.business_image else ""

        # 2) Detaylar (belirtilen sıraya göre)
        self._select_tab(page, "overview")

        name = self._first_text(
            page,
            [
                'div[role="main"] h1[class*="fontHeadlineLarge"]',
                'div[role="main"] div[class*="DUwDvf"]',
                'h1.DUwDvf',
                'div.DUwDvf',
            ],
        )

        rating: Optional[float] = None
        rating_count: Optional[int] = None
        if selection.rating or selection.user_ratings_total:
            rating, rating_count = self._extract_rating_info(page)

        business_type = self._extract_business_type(page) if selection.business_type else ""

        address = self._extract_address(page) if selection.formatted_address else ""

        opening_hours: List[str] = []
        if selection.opening_hours:
            if self._select_tab(page, "hours"):
                opening_hours = self._extract_hours(page)
            if not opening_hours:
                opening_hours = self._extract_hours(page)

        website = self._extract_website(page) if selection.website else None

        phone = self._extract_phone(page) if selection.formatted_phone_number else None
        phone = sanitize_phone(phone, address)
        phone_type = classify_phone(phone)

        busy_hours: Dict[str, List[Dict[str, str]]] = {}
        if selection.busy_hours:
            busy_hours = self._extract_busy_hours(page)

        latitude = ""
        longitude = ""
        if selection.location:
            latitude, longitude = extract_lat_lng_from_link(page.url)

        # 3) Müşteri yorumları (önceki adımlar tamamlandıktan sonra)
        reviews: List[PlaceReview] = []
        if selection.wants_reviews():
            if self._select_tab(page, "reviews"):
                reviews = self._extract_reviews(page, rating_count)
            if not reviews:
                reviews = self._extract_reviews(page, rating_count)

        category_slug = slugify_category(business_type) if business_type else ""
        return selection.apply(
            PlaceResult(
                city_name=self.city_name,
                name=name,
                formatted_address=address,
                formatted_phone_number=phone,
                telephone_type=phone_type,
                business_type=business_type or None,
                category_slug=category_slug,
                business_image=business_image,
                latitude=latitude,
                longitude=longitude,
                website=website,
                opening_hours=opening_hours,
                rating=rating,
                user_ratings_total=rating_count,
                reviews=reviews,
                busy_hours=busy_hours,
            )
        )

    def _extract_business_type(self, page: Page) -> str:
        selectors = [
            'div[role="main"] button.DkEaL',
            'div[role="main"] a.DkEaL',
            'button.DkEaL',
        ]
        return self._first_text(page, selectors)

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
            'div[data-item-id^="address"] div.Io6YTe',
            'div[aria-label*="Adres"] div.Io6YTe',
            'div[aria-label*="Address"] div.Io6YTe',
            'div.Io6YTe.fontBodyMedium.kR99db',
        ]
        address = self._first_text(page, selectors)
        if address:
            return address
        meta_items = self._extract_meta_items(page)
        for key, value in meta_items.items():
            if "address" in key.lower():
                return value
        return ""

    def _extract_phone(self, page: Page) -> str:
        selectors = [
            'button[data-item-id*="phone"] div.Io6YTe',
            'div[data-item-id*="phone"] div.Io6YTe',
            'div.AeaXub div.Io6YTe',
        ]
        phone = self._first_text(page, selectors)
        if phone:
            return phone
        meta_items = self._extract_meta_items(page)
        for key, value in meta_items.items():
            if "phone" in key.lower():
                return value
        # As a last resort, pick the first Io6YTe block that looks like a phone number
        locator = page.locator('div.AeaXub div.Io6YTe, div.Io6YTe')
        count = locator.count()
        for idx in range(count):
            text = self._safe_inner_text(locator.nth(idx))
            digits = "".join(ch for ch in text if ch.isdigit())
            if len(digits) >= 8:
                return text
        return ""

    def _extract_website(self, page: Page) -> Optional[str]:
        selectors = [
            'a.CsEnBe[data-item-id="authority"]',
            'a.CsEnBe',
        ]
        for selector in selectors:
            locator = page.locator(selector)
            if locator.count():
                href = self._safe_get_attribute(locator.first, "href")
                if href:
                    return href
        return None

    def _extract_default_image(self, page: Page) -> str:
        selectors = [
            "div.ZKCDEc div.RZ66Rb img",
            "div.RZ66Rb.FgCUCc img",
            "div.SpFAAb div.FQ2IWe img",
            "button.aoRNLd img",
        ]

        end_time = time.time() + 6
        while time.time() < end_time:
            for selector in selectors:
                locator = page.locator(selector)
                if not locator.count():
                    continue

                src = self._safe_get_attribute(locator.first, "src")
                if not src:
                    with suppress(PlaywrightError):
                        src = locator.first.evaluate("el => el.currentSrc || el.src || ''")
                if src:
                    return upscale_img(src)

            page.wait_for_timeout(200)

        return ""
    def _extract_meta_items(self, page: Page) -> Dict[str, str]:
        script = """
            () => {
                const data = {};
                document.querySelectorAll('[data-item-id]').forEach((el) => {
                    const key = el.getAttribute('data-item-id');
                    const valueNode = el.querySelector('.Io6YTe');
                    if (key && valueNode) {
                        const text = valueNode.innerText.trim();
                        if (text) {
                            data[key] = text;
                        }
                    }
                });
                return data;
            }
        """
        try:
            meta = page.evaluate(script)
            if isinstance(meta, dict):
                return {str(k): str(v) for k, v in meta.items()}
        except PlaywrightError:
            return {}
        return {}

    def _extract_rating_info(self, page: Page) -> tuple[Optional[float], Optional[int]]:
        rating_text = self._first_text(
            page, ['div.F7nice span[aria-hidden="true"]', 'div.F7nice span[role="img"]']
        )
        rating: Optional[float] = None
        if rating_text:
            rating_text = rating_text.replace(",", ".").split()[0]
            with suppress(ValueError):
                rating = float(rating_text)

        rating_count = self._extract_rating_count(page)
        return rating, rating_count

    def _extract_rating_count(self, page: Page) -> Optional[int]:
        keywords = (
            "yorum",
            "yorumlar",
            "review",
            "reviews",
            "değerlendirme",
            "degerlendirme",
            "ratings",
        )
        selectors = [
            'div.F7nice span[aria-label]',
            'div.F7nice span[role="img"]',
            'div.F7nice span',
        ]

        for selector in selectors:
            locator = page.locator(selector)
            total = min(locator.count(), 8)
            for idx in range(total):
                node = locator.nth(idx)
                values = [self._safe_inner_text(node), self._safe_get_attribute(node, "aria-label")]
                for value in values:
                    if not value:
                        continue
                    normalized = self._normalize_text(value)
                    if not any(keyword in normalized for keyword in keywords):
                        continue
                    digits = "".join(ch for ch in value if ch.isdigit())
                    if digits:
                        return int(digits)
        return None

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

    def _extract_busy_hours(self, page: Page) -> Dict[str, List[Dict[str, str]]]:
        container = page.locator('div.C7xf8b').first
        if not container.count():
            return {}
        day_blocks = container.locator('div.g2BVhd')
        day_order = [
            "Pazar",
            "Pazartesi",
            "Salı",
            "Çarşamba",
            "Perşembe",
            "Cuma",
            "Cumartesi",
        ]
        busy_hours: Dict[str, List[Dict[str, str]]] = {}
        total_days = min(day_blocks.count(), len(day_order))
        for idx in range(total_days):
            day_label = day_order[idx]
            block = day_blocks.nth(idx)
            slots: List[Dict[str, str]] = []
            slots_locator = block.locator('div[role="img"][aria-label*="saatinde"]')
            for slot_idx in range(slots_locator.count()):
                slot = slots_locator.nth(slot_idx)
                aria = self._safe_get_attribute(slot, "aria-label")
                if not aria:
                    continue
                match = re.search(r"(\d{2}:\d{2}).*?(%\d+)", aria)
                if not match:
                    continue
                slots.append({"time": match.group(1), "busy": match.group(2)})
            if slots:
                busy_hours[day_label] = slots
        return busy_hours

    def _extract_reviews(self, page: Page, rating_count: Optional[int] = None) -> List[PlaceReview]:
        if not self.field_selection.wants_reviews():
            return []
        target = max(0, self.max_reviews)
        fast_target = False
        if rating_count is not None:
            if target > rating_count:
                fast_target = True
            target = min(target, rating_count)
        if target == 0:
            return []

        try:
            page.wait_for_selector('div[data-review-id]', timeout=4000)
        except PlaywrightError:
            self._scroll_reviews_pane(page)

        reviews_locator = page.locator('div[data-review-id]')
        if reviews_locator.count() == 0:
            return []
        self._ensure_reviews_loaded(page, reviews_locator, target, fast=fast_target)
        reviews: List[PlaceReview] = []
        seen_ids: set[str] = set()
        idx = 0
        stalled = 0
        while len(reviews) < target:
            total = reviews_locator.count()
            if idx >= total:
                previous_total = total
                self._ensure_reviews_loaded(page, reviews_locator, target)
                total = reviews_locator.count()
                if total <= previous_total:
                    stalled += 1
                    if stalled > 2:
                        break
                else:
                    stalled = 0
                continue
            stalled = 0
            review = reviews_locator.nth(idx)
            with suppress(PlaywrightError):
                review.scroll_into_view_if_needed(timeout=1500)
            self._expand_review_content(review)
            content, extras = self._review_text_and_extras(review)
            media_urls = self._extract_review_media(review)
            author = self._safe_inner_text(review.locator('div.d4r55, button.al6Kxe div.d4r55').first)
            rating_text = self._safe_get_attribute(review.locator('span.kvMYJc').first, "aria-label")
            rating: Optional[float] = None
            if rating_text:
                digits = "".join(ch for ch in rating_text if ch.isdigit() or ch in {",", "."})
                digits = digits.replace(",", ".")
                with suppress(ValueError):
                    rating = float(digits)
            relative = self._safe_inner_text(review.locator('span.rsqaWe').first)
            photo = self._safe_get_attribute(review.locator('img.NBa7we').first, "src") or None
            photo = upscale_img(photo)
            photo = photo or None
            signature_source = self._safe_get_attribute(review, "data-review-id")
            if not signature_source:
                signature_source = self._normalize_text("||".join(filter(None, [author, relative or "", content])))
            if signature_source in seen_ids:
                idx += 1
                continue
            seen_ids.add(signature_source)
            reviews.append(
                PlaceReview(
                    author_name=author,
                    rating=rating,
                    relative_time=relative or None,
                    text=content,
                    profile_photo_url=photo or None,
                    text_extra=extras,
                    review_photo_urls=media_urls,
                )
            )
            idx += 1
        return reviews

    def _review_text_and_extras(self, review: Locator) -> tuple[str, Dict[str, str]]:
        """Extract the main review text and DOM-based metadata."""

        container = review.locator('div.MyEned').first
        if not container.count():
            container = review
        text = self._safe_inner_text(container.locator('span.wiI7pd').first).strip()
        extras: Dict[str, str] = {}
        if self.field_selection.text_extra:
            extras = self._extract_review_metadata(container)
        return text, extras

    def _ensure_reviews_loaded(
        self, page: Page, reviews_locator: Locator, target: int, fast: bool = False
    ) -> None:
        if target <= 0:
            return
        deadline = time.time() + (8 if fast else 16)
        attempts = 0
        while time.time() < deadline:
            count = reviews_locator.count()
            if count >= target:
                return
            if count > 0:
                last = reviews_locator.nth(count - 1)
                with suppress(PlaywrightError):
                    last.scroll_into_view_if_needed(timeout=1200)
            scrolled = self._scroll_reviews_pane(page)
            self._wait_for_review_loader(page)
            attempts += 1
            if not scrolled and attempts > 6:
                break
            page.wait_for_timeout(220 if fast else 400)
        # allow best-effort even if we exit the loop

    def _expand_review_content(self, review: Locator) -> None:
        """Click the review's "more" control so the full text is visible."""
        selectors = [
            'button.w8nwRe',
            'button[jsaction*="expandReview" i]',
            'button:has-text("Daha fazla")',
            'button:has-text("More")',
        ]
        for selector in selectors:
            try:
                button = review.locator(selector)
                if button.count() == 0:
                    continue
                btn = button.first
                btn.scroll_into_view_if_needed(timeout=800)
                expanded = self._safe_get_attribute(btn, "aria-expanded")
                if expanded and expanded.lower() == "true":
                    return
                btn.click(timeout=800)
                with suppress(PlaywrightError, AttributeError):
                    review.page.wait_for_timeout(150)  # type: ignore[attr-defined]
                return
            except PlaywrightError:
                continue

    def _extract_review_media(self, review: Locator) -> List[str]:
        if not self.field_selection.review_photo_urls:
            return []
        media_urls: List[str] = []
        buttons = review.locator('div.KtCyie button.Tya61d')
        count = buttons.count()
        for idx in range(count):
            button = buttons.nth(idx)
            url = self._background_image_url(button)
            if not url:
                continue
            scaled_url = upscale_img(url)
            if scaled_url and scaled_url not in media_urls:
                media_urls.append(scaled_url)
        return media_urls

    def _background_image_url(self, locator: Locator) -> Optional[str]:
        style = self._safe_get_attribute(locator, "style")
        parsed = self._parse_background_url(style)
        if parsed:
            return parsed
        handle = locator.element_handle()
        if not handle:
            return None
        with suppress(PlaywrightError):
            computed = handle.evaluate("el => getComputedStyle(el).backgroundImage")
            return self._parse_background_url(computed)
        return None

    @staticmethod
    def _parse_background_url(value: Optional[str]) -> Optional[str]:
        if not value:
            return None
        start = value.find("url(")
        if start == -1:
            return None
        start += 4
        end = value.find(")", start)
        if end == -1:
            return None
        candidate = value[start:end].strip().strip(" \"'")
        if not candidate:
            return None
        return html.unescape(candidate)

    def _extract_review_metadata(self, context: Locator) -> Dict[str, str]:
        extras: Dict[str, str] = {}
        blocks = context.locator('div.PBK6be')
        count = blocks.count()
        for idx in range(count):
            block = blocks.nth(idx)
            block_text = self._safe_inner_text(block).strip()
            if not block_text:
                continue
            lines = [line.strip() for line in block_text.splitlines() if line.strip()]
            label = self._clean_label(
                self._safe_inner_text(
                    block.locator('span[style*="font-weight"], span b, span strong').first
                )
            )
            value = ""
            if lines:
                first = lines[0]
                if ":" in first:
                    candidate_label, remainder = first.split(":", 1)
                    candidate_label = self._clean_label(candidate_label)
                    if candidate_label:
                        if not label:
                            label = candidate_label
                        value = remainder.strip()
                    if not value and len(lines) > 1:
                        value = lines[1]
                elif len(lines) > 1:
                    if not label:
                        label = self._clean_label(first)
                    value = lines[1]
            if not value:
                rows = block.locator(':scope > div')
                if rows.count() >= 2:
                    if not label:
                        label = self._clean_label(self._safe_inner_text(rows.nth(0)))
                    value = self._safe_inner_text(rows.nth(1)).strip()
                    if not value:
                        aria_node = rows.nth(1).locator('[aria-label]').first
                        aria_value = (
                            self._safe_get_attribute(aria_node, "aria-label")
                            if aria_node.count()
                            else None
                        )
                        if aria_value:
                            value = aria_value.strip()
            if label and value:
                extras[label] = value
        return extras

    @staticmethod
    def _clean_label(value: Optional[str]) -> str:
        if not value:
            return ""
        return value.strip().rstrip(":").strip()

    def _select_tab(self, page: Page, key: str) -> bool:
        candidates = self._tab_label_candidates(key)
        if not candidates:
            return False
        tab_locators = [page.locator('[role="tab"]'), page.locator('div.Gpq6kf')]
        for tabs in tab_locators:
            count = tabs.count()
            for idx in range(count):
                tab = tabs.nth(idx)
                text_parts = [
                    self._normalize_text(self._safe_inner_text(tab)),
                    self._normalize_text(self._safe_get_attribute(tab, "aria-label")),
                ]
                combined = " ".join(part for part in text_parts if part)
                if not combined:
                    continue
                for candidate in candidates:
                    if self._normalize_text(candidate) in combined:
                        with suppress(PlaywrightError):
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

    def _ensure_fake_cursor(self, page: Page) -> None:
        script = """
            (() => {
                if (window.__botCursor) {
                    return;
                }
                const cursor = document.createElement('div');
                cursor.id = '__botCursor';
                cursor.style.position = 'fixed';
                cursor.style.width = '20px';
                cursor.style.height = '20px';
                cursor.style.pointerEvents = 'none';
                cursor.style.top = '0';
                cursor.style.left = '0';
                cursor.style.borderRadius = '50%';
                cursor.style.border = '2px solid #0b6fa4';
                cursor.style.zIndex = '2147483647';
                cursor.style.boxShadow = '0 0 8px rgba(11, 111, 164, 0.4)';
                cursor.style.background = 'rgba(255,255,255,0.4)';
                cursor.style.transition = 'transform 120ms ease-out, opacity 200ms ease-out';
                cursor.classList.add('bot-cursor');
                const style = document.createElement('style');
                style.textContent = `
                    #__botCursor.bot-cursor--click {
                        transform: scale(0.85);
                        background: rgba(11, 111, 164, 0.2);
                    }
                `;
                document.head.appendChild(style);
                document.body.appendChild(cursor);
                window.__botCursor = cursor;
            })();
        """
        with suppress(PlaywrightError):
            page.evaluate(script)

    def _move_fake_cursor_to_locator(
        self, page: Page, locator: Locator
    ) -> Optional[tuple[float, float]]:
        try:
            target = locator.first
            target.scroll_into_view_if_needed(timeout=1500)
            box = target.bounding_box()
        except PlaywrightError:
            return None
        if not box:
            return None
        width = max(1.0, box.get("width", 1.0))
        height = max(1.0, box.get("height", 1.0))
        offset_x = min(width - 6, max(18.0, width * 0.65))
        offset_y = min(height - 6, max(12.0, height * 0.55))
        x = box.get("x", 0.0) + max(4.0, offset_x)
        y = box.get("y", 0.0) + max(4.0, offset_y)
        self._ensure_fake_cursor(page)
        with suppress(PlaywrightError):
            page.mouse.move(x, y, steps=10)
            self._set_fake_cursor_position(page, x, y, clicked=False)
        return x, y

    def _animate_fake_click(self, page: Page, position: tuple[float, float]) -> None:
        self._set_fake_cursor_position(page, position[0], position[1], clicked=True)

    def _set_fake_cursor_position(self, page: Page, x: float, y: float, clicked: bool) -> None:
        script = """
            (data) => {
                const cursor = window.__botCursor;
                if (!cursor) {
                    return;
                }
                cursor.style.transform = `translate(${data.x}px, ${data.y}px)`;
                if (data.clicked) {
                    cursor.classList.add('bot-cursor--click');
                    setTimeout(() => cursor.classList.remove('bot-cursor--click'), 200);
                }
            }
        """
        with suppress(PlaywrightError):
            page.evaluate(script, {"x": x, "y": y, "clicked": clicked})

    def _emit_event(
        self,
        callback: Optional[Callable[[str, Dict[str, str]], None]],
        event: str,
        payload: Dict[str, str],
    ) -> None:
        if not callback:
            return
        with suppress(Exception):
            callback(event, payload)
class Application(tk.Tk):
    def __init__(self) -> None:
        super().__init__()
        self.selected_language = tk.StringVar(value="tr")
        self.license_manager = LicenseManager()
        self.license_key_var = tk.StringVar()
        self.license_message_var = tk.StringVar()
        self.license_machine_var = tk.StringVar(value=self.license_manager.machine_id())
        self.license_status_var = tk.StringVar()
        self.license_days_var = tk.StringVar()
        self.settings_search_limit_var = tk.IntVar(value=9999)
        self.settings_review_limit_var = tk.IntVar(value=9999)
        self.settings_message_var = tk.StringVar()
        self.settings_api_url_var = tk.StringVar(value="https://maps.noasoft.org/api/ingest.php")
        self.settings_api_token_var = tk.StringVar(value=os.getenv("MAPS_API_TOKEN", "maps-default-token"))
        self.field_option_vars: Dict[str, tk.BooleanVar] = {}
        self._field_option_checkbuttons: Dict[str, List[ttk.Checkbutton]] = {
            key: [] for key in FIELD_OPTION_KEYS
        }
        self._field_option_controls: List[tuple[ttk.Checkbutton, str]] = []
        default_selected = {
            "name",
            "formatted_address",
            "formatted_phone_number",
            "business_type",
            "rating",
            "user_ratings_total",
            "location",
            "business_image",
        }
        for key in FIELD_OPTION_KEYS:
            default = key in default_selected
            self.field_option_vars[key] = tk.BooleanVar(value=default)
        self._load_persisted_settings()
        self.city_lookup: Dict[str, tuple[str, str]] = self._load_cities()
        self.license_expiry_var = tk.StringVar()
        self.license_duration_var = tk.StringVar()
        self.title(self._("app_title"))
        self.geometry("1280x860")
        self.minsize(1180, 760)
        self.configure(bg="#f4f6fb")
        self.columnconfigure(0, weight=1)
        self.rowconfigure(1, weight=1)
        self._tree_sort_states: Dict[ttk.Treeview, Dict[str, bool]] = {}

        self._api_results: List[PlaceResult] = []
        self._bot_results: List[PlaceResult] = []
        self._bot_map_photo: Optional[ImageTk.PhotoImage] = None
        self._bot_active_limit: int = 0
        self._bot_attempted: int = 0
        self._bot_log_lines: List[tuple[str, Dict[str, str]]] = []
        self._bot_selected_index: Optional[int] = None
        self._map_preview_size = (780, 480)
        self._map_placeholder_active = True

        self._configure_style()
        self._create_widgets()
        self._layout_widgets()
        self._bind_events()
        self._update_translations()

    def _configure_style(self) -> None:
        style = ttk.Style(self)
        with suppress(tk.TclError):
            if "clam" in style.theme_names():
                style.theme_use("clam")
        style.configure("Accent.TButton", padding=6, font=("Segoe UI", 10, "bold"))
        style.configure("Status.TLabel", foreground="#0b6fa4", font=("Segoe UI", 10, "bold"))
        style.configure("Treeview", font=("Segoe UI", 10))
        style.configure("Treeview.Heading", font=("Segoe UI", 10, "bold"))
        style.configure("Body.TLabelframe", padding=12, background="#ffffff")
        style.configure("Body.TLabelframe.Label", font=("Segoe UI", 11, "bold"))
        style.configure("Card.TFrame", background="#ffffff")
        style.configure("TNotebook", background="#f4f6fb")
        style.configure("TNotebook.Tab", padding=(18, 8))
        self.style = style

    def _build_logo_image(self) -> ImageTk.PhotoImage:
        size = 96
        image = Image.new("RGBA", (size, size), (0, 0, 0, 0))
        draw = ImageDraw.Draw(image)
        body_box = (18, 4, 78, 64)
        draw.ellipse(body_box, fill="#4285F4")
        draw.pieslice(body_box, 45, 180, fill="#34A853")
        draw.pieslice(body_box, 180, 270, fill="#FBBC05")
        draw.pieslice(body_box, 270, 360, fill="#EA4335")
        draw.ellipse((32, 18, 64, 50), fill="#ffffff")
        draw.polygon([(48, 58), (70, 90), (26, 90)], fill="#EA4335")
        draw.ellipse((38, 60, 58, 80), fill="#4285F4")
        return ImageTk.PhotoImage(image)

    def _create_widgets(self) -> None:
        self.header_frame = tk.Frame(self, bg="#0b6fa4", padx=16, pady=12)
        self.logo_photo = self._build_logo_image()
        self.logo_label = tk.Label(self.header_frame, image=self.logo_photo, bg="#0b6fa4")
        self.header_text_frame = tk.Frame(self.header_frame, bg="#0b6fa4")
        self.app_title_label = tk.Label(
            self.header_text_frame,
            text="",
            font=("Segoe UI", 18, "bold"),
            fg="#ffffff",
            bg="#0b6fa4",
        )
        self.app_tagline_label = tk.Label(
            self.header_text_frame,
            text="",
            font=("Segoe UI", 11),
            fg="#e1f3ff",
            bg="#0b6fa4",
        )

        self.separator = ttk.Separator(self, orient=tk.HORIZONTAL)
        self.content_frame = ttk.Frame(self, padding=10, style="Card.TFrame")
        self.notebook = ttk.Notebook(self.content_frame)

        # API tab widgets
        self.api_tab = ttk.Frame(self.notebook)
        self.api_form_frame = ttk.LabelFrame(self.api_tab, text="", padding=10, style="Body.TLabelframe")
        self.api_results_frame = ttk.LabelFrame(self.api_tab, text="", padding=5, style="Body.TLabelframe")
        self.api_details_frame = ttk.LabelFrame(self.api_tab, text="", padding=5, style="Body.TLabelframe")
        self.api_button_frame = ttk.Frame(self.api_tab)

        self.api_key_label = ttk.Label(self.api_form_frame, text="")
        self.api_key_entry = ttk.Entry(self.api_form_frame, show="*")

        self.api_query_label = ttk.Label(self.api_form_frame, text="")
        self.api_query_entry = ttk.Entry(self.api_form_frame)

        self.language_label = ttk.Label(self.api_form_frame, text="")
        self.language_combo = ttk.Combobox(
            self.api_form_frame,
            textvariable=self.selected_language,
            values=["tr", "en"],
            state="readonly",
        )

        self.api_limit_label = ttk.Label(self.api_form_frame, text="")
        self.api_limit_spin = ttk.Spinbox(
            self.api_form_frame,
            from_=1,
            to=9999,
            width=12,
            justify=tk.CENTER,
        )
        self._set_spin_value(self.api_limit_spin, "5")

        self.api_field_options_frame = self._build_field_option_section(self.api_form_frame)

        self.api_search_button = ttk.Button(
            self.api_form_frame, text="", style="Accent.TButton", command=self._on_api_search
        )

        self.api_status_var = tk.StringVar(value=self._("status_ready"))
        self.api_status_label = ttk.Label(
            self.api_button_frame, textvariable=self.api_status_var, style="Status.TLabel"
        )

        self.api_results_tree = ttk.Treeview(
            self.api_results_frame,
            columns=("name", "phone", "rating", "category"),
            show="headings",
            height=12,
        )
        self.api_results_tree.heading("name", text="")
        self.api_results_tree.heading("phone", text="")
        self.api_results_tree.heading("rating", text="")
        self.api_results_tree.heading("category", text="")
        self.api_results_tree.column("name", width=240)
        self.api_results_tree.column("phone", width=160)
        self.api_results_tree.column("rating", width=140, anchor=tk.CENTER)
        self.api_results_tree.column("category", width=160)

        self.api_details_text = tk.Text(
            self.api_details_frame,
            wrap=tk.WORD,
            state=tk.DISABLED,
            height=18,
            relief=tk.GROOVE,
            borderwidth=2,
            background="#fcfcfc",
        )
        self.api_details_scroll = ttk.Scrollbar(
            self.api_details_frame, orient=tk.VERTICAL, command=self.api_details_text.yview
        )
        self.api_details_text.configure(yscrollcommand=self.api_details_scroll.set)

        self.api_save_json_button = ttk.Button(
            self.api_button_frame,
            text="",
            command=lambda: self._save_results(self._api_results, "json"),
        )
        self.api_save_csv_button = ttk.Button(
            self.api_button_frame,
            text="",
            command=lambda: self._save_results(self._api_results, "csv"),
        )
        self.api_save_pdf_button = ttk.Button(
            self.api_button_frame,
            text="",
            command=lambda: self._save_results(self._api_results, "pdf"),
        )
        self.api_save_xlsx_button = ttk.Button(
            self.api_button_frame,
            text="",
            command=lambda: self._save_results(self._api_results, "xlsx"),
        )
        self.api_transfer_button = ttk.Button(
            self.api_button_frame,
            text="",
            command=lambda: self._transfer_results(self._api_results, "api"),
            style="Accent.TButton",
        )

        # Bot tab widgets
        self.bot_tab = ttk.Frame(self.notebook)
        self.bot_form_frame = ttk.LabelFrame(self.bot_tab, text="", padding=10, style="Body.TLabelframe")
        self.bot_map_frame = ttk.LabelFrame(self.bot_tab, text="", padding=5, style="Body.TLabelframe")
        self.bot_results_frame = ttk.LabelFrame(self.bot_tab, text="", padding=5, style="Body.TLabelframe")
        self.bot_details_frame = ttk.LabelFrame(self.bot_tab, text="", padding=5, style="Body.TLabelframe")
        self.bot_button_frame = ttk.Frame(self.bot_tab)

        self.bot_query_label = ttk.Label(self.bot_form_frame, text="")
        self.bot_query_entry = ttk.Entry(self.bot_form_frame)

        self.bot_language_label = ttk.Label(self.bot_form_frame, text="")
        self.bot_language_combo = ttk.Combobox(
            self.bot_form_frame,
            values=["tr", "en"],
            state="readonly",
        )
        self.bot_language_combo.set("tr")

        self.bot_city_label = ttk.Label(self.bot_form_frame, text="")
        self.bot_city_combo = ttk.Combobox(
            self.bot_form_frame,
            values=[],
            state="readonly",
        )
        self._populate_city_combo()

        self.bot_limit_label = ttk.Label(self.bot_form_frame, text="")
        self.bot_limit_spin = ttk.Spinbox(
            self.bot_form_frame,
            from_=1,
            to=9999,
            width=12,
            justify=tk.CENTER,
        )
        self._set_spin_value(self.bot_limit_spin, "5")

        self.bot_reviews_label = ttk.Label(self.bot_form_frame, text="")
        self.bot_reviews_spin = ttk.Spinbox(
            self.bot_form_frame,
            from_=0,
            to=9999,
            width=10,
            justify=tk.CENTER,
        )
        self._set_spin_value(self.bot_reviews_spin, "5")

        self.bot_field_options_frame = self._build_field_option_section(self.bot_form_frame)

        self.bot_search_button = ttk.Button(
            self.bot_form_frame, text="", style="Accent.TButton", command=self._on_bot_search
        )
        self.bot_status_var = tk.StringVar(value=self._("status_ready"))
        self.bot_status_label = ttk.Label(
            self.bot_button_frame, textvariable=self.bot_status_var, style="Status.TLabel"
        )

        self.bot_results_tree = ttk.Treeview(
            self.bot_results_frame,
            columns=("name", "phone", "rating", "category"),
            show="headings",
            height=12,
        )
        self.bot_results_tree.heading("name", text="")
        self.bot_results_tree.heading("phone", text="")
        self.bot_results_tree.heading("rating", text="")
        self.bot_results_tree.heading("category", text="")
        self.bot_results_tree.column("name", width=240)
        self.bot_results_tree.column("phone", width=160)
        self.bot_results_tree.column("rating", width=140, anchor=tk.CENTER)
        self.bot_results_tree.column("category", width=160)

        self.bot_details_text = tk.Text(
            self.bot_details_frame,
            wrap=tk.WORD,
            state=tk.DISABLED,
            height=14,
            relief=tk.GROOVE,
            borderwidth=2,
            background="#fcfcfc",
        )
        self.bot_details_scroll = ttk.Scrollbar(
            self.bot_details_frame, orient=tk.VERTICAL, command=self.bot_details_text.yview
        )
        self.bot_details_text.configure(yscrollcommand=self.bot_details_scroll.set)

        self.bot_map_frame.configure(
            width=self._map_preview_size[0] + 30, height=self._map_preview_size[1] + 30
        )
        with suppress(Exception):
            self.bot_map_frame.pack_propagate(False)
        self.bot_map_canvas = tk.Label(
            self.bot_map_frame,
            text="",
            anchor=tk.CENTER,
            relief=tk.SUNKEN,
            borderwidth=1,
            bg="#f8fafc",
        )
        self.bot_map_canvas.configure(
            width=self._map_preview_size[0], height=self._map_preview_size[1]
        )

        self.bot_save_json_button = ttk.Button(
            self.bot_button_frame,
            text="",
            command=lambda: self._save_results(self._bot_results, "json"),
        )
        self.bot_save_csv_button = ttk.Button(
            self.bot_button_frame,
            text="",
            command=lambda: self._save_results(self._bot_results, "csv"),
        )
        self.bot_save_pdf_button = ttk.Button(
            self.bot_button_frame,
            text="",
            command=lambda: self._save_results(self._bot_results, "pdf"),
        )
        self.bot_save_xlsx_button = ttk.Button(
            self.bot_button_frame,
            text="",
            command=lambda: self._save_results(self._bot_results, "xlsx"),
        )
        self.bot_transfer_button = ttk.Button(
            self.bot_button_frame,
            text="",
            command=lambda: self._transfer_results(self._bot_results, "bot"),
            style="Accent.TButton",
        )

        self._set_bot_map_placeholder()

        # Settings tab widgets
        self.settings_tab = ttk.Frame(self.notebook)
        self.settings_frame = ttk.LabelFrame(self.settings_tab, text="", padding=10, style="Body.TLabelframe")
        self.settings_search_limit_label = ttk.Label(self.settings_frame, text="")
        self.settings_search_limit_spin = ttk.Spinbox(
            self.settings_frame,
            from_=1,
            to=9999,
            width=10,
            justify=tk.CENTER,
            textvariable=self.settings_search_limit_var,
        )
        self.settings_review_limit_label = ttk.Label(self.settings_frame, text="")
        self.settings_review_limit_spin = ttk.Spinbox(
            self.settings_frame,
            from_=0,
            to=9999,
            width=10,
            justify=tk.CENTER,
            textvariable=self.settings_review_limit_var,
        )
        self.settings_api_url_label = ttk.Label(self.settings_frame, text="")
        self.settings_api_url_entry = ttk.Entry(
            self.settings_frame,
            textvariable=self.settings_api_url_var,
        )
        self.settings_api_token_label = ttk.Label(self.settings_frame, text="")
        self.settings_api_token_entry = ttk.Entry(
            self.settings_frame,
            textvariable=self.settings_api_token_var,
            show="*",
        )
        self.settings_apply_button = ttk.Button(
            self.settings_frame, text="", style="Accent.TButton", command=self._on_settings_apply
        )
        self.settings_message_label = ttk.Label(
            self.settings_frame, textvariable=self.settings_message_var, foreground="#0b6fa4"
        )

        # License tab widgets
        self.license_tab = ttk.Frame(self.notebook)
        self.license_info_frame = ttk.LabelFrame(self.license_tab, text="", padding=10)
        self.license_activation_frame = ttk.LabelFrame(self.license_tab, text="", padding=10)

        self.license_machine_label = ttk.Label(self.license_info_frame, text="")
        self.license_machine_entry = ttk.Entry(
            self.license_info_frame,
            textvariable=self.license_machine_var,
            state="readonly",
            width=32,
        )
        self.license_copy_button = ttk.Button(
            self.license_info_frame, text="", command=self._copy_machine_id
        )

        self.license_status_text = ttk.Label(
            self.license_info_frame, textvariable=self.license_status_var, style="Status.TLabel"
        )
        self.license_days_text = ttk.Label(
            self.license_info_frame, textvariable=self.license_days_var
        )
        self.license_expiry_text = ttk.Label(
            self.license_info_frame, textvariable=self.license_expiry_var
        )
        self.license_duration_text = ttk.Label(
            self.license_info_frame, textvariable=self.license_duration_var
        )

        self.license_key_label = ttk.Label(self.license_activation_frame, text="")
        self.license_key_entry = ttk.Entry(
            self.license_activation_frame,
            textvariable=self.license_key_var,
            width=36,
        )
        self.license_activate_button = ttk.Button(
            self.license_activation_frame, text="", style="Accent.TButton", command=self._on_activate_license
        )
        self.license_message_label = ttk.Label(
            self.license_activation_frame, textvariable=self.license_message_var, foreground="#b91c1c"
        )

        self.notebook.add(self.api_tab, text="")
        self.notebook.add(self.bot_tab, text="")
        self.notebook.add(self.settings_tab, text="")
        self.notebook.add(self.license_tab, text="")

        self._enable_tree_sorting(self.api_results_tree)
        self._enable_tree_sorting(self.bot_results_tree)

        self._apply_settings_limits()
        self._on_field_option_toggle()

    def _build_field_option_section(self, parent: tk.Widget) -> ttk.LabelFrame:
        frame = ttk.LabelFrame(parent, text="", padding=8, style="Body.TLabelframe")
        columns = 3
        for col in range(columns):
            frame.columnconfigure(col, weight=1)
        for index, key in enumerate(FIELD_OPTION_KEYS):
            var = self.field_option_vars[key]
            check = ttk.Checkbutton(
                frame,
                text=self._(f"field_{key}"),
                variable=var,
                command=self._on_field_option_toggle,
            )
            row, column = divmod(index, columns)
            check.grid(row=row, column=column, sticky=tk.W, padx=4, pady=2)
            self._field_option_checkbuttons[key].append(check)
            self._field_option_controls.append((check, key))
        return frame

    def _on_field_option_toggle(self) -> None:
        reviews_enabled = bool(self.field_option_vars.get("reviews", tk.BooleanVar(value=False)).get())
        for key in ("review_photo_urls", "text_extra"):
            for control in self._field_option_checkbuttons.get(key, []):
                control_state = tk.NORMAL if reviews_enabled else tk.DISABLED
                control.config(state=control_state)
            if not reviews_enabled:
                self.field_option_vars[key].set(False)
        if hasattr(self, "bot_reviews_spin"):
            state = tk.NORMAL if reviews_enabled else tk.DISABLED
            self.bot_reviews_spin.config(state=state)
            if not reviews_enabled:
                self._set_spin_value(self.bot_reviews_spin, "0")
        self._update_field_option_labels()
        self._persist_settings()

    def _update_field_option_labels(self) -> None:
        label = self._("field_section")
        if hasattr(self, "api_field_options_frame"):
            self.api_field_options_frame.config(text=label)
        if hasattr(self, "bot_field_options_frame"):
            self.bot_field_options_frame.config(text=label)
        for check, key in self._field_option_controls:
            check.config(text=self._(f"field_{key}"))

    def _safe_spin_int(
        self,
        spinbox: ttk.Spinbox,
        default: int,
        minimum: int,
        maximum: Optional[int] = None,
    ) -> int:
        try:
            value = int(float(spinbox.get()))
        except (ValueError, tk.TclError):
            value = default
        if maximum is not None:
            value = min(value, maximum)
        value = max(minimum, value)
        self._set_spin_value(spinbox, str(value))
        return value

    def _load_persisted_settings(self) -> None:
        if not SETTINGS_PATH.exists():
            return
        try:
            data = json.loads(SETTINGS_PATH.read_text(encoding="utf-8"))
        except (OSError, json.JSONDecodeError):
            LOGGER.warning("Could not read settings from %s", SETTINGS_PATH)
            return

        self.selected_language.set(data.get("language", self.selected_language.get()))
        for key, target in (
            ("search_limit", self.settings_search_limit_var),
            ("review_limit", self.settings_review_limit_var),
        ):
            with suppress(Exception):
                target.set(int(data.get(key, target.get())))

        if "api_url" in data:
            self.settings_api_url_var.set(str(data.get("api_url") or ""))
        if "api_token" in data:
            self.settings_api_token_var.set(str(data.get("api_token") or ""))

        saved_fields = data.get("field_selection", {})
        if isinstance(saved_fields, dict):
            for key, var in self.field_option_vars.items():
                if key in saved_fields:
                    var.set(bool(saved_fields.get(key)))

    def _persist_settings(self) -> None:
        payload = {
            "language": self.selected_language.get(),
            "search_limit": self._get_settings_search_limit(),
            "review_limit": self._get_settings_review_limit(),
            "api_url": self.settings_api_url_var.get(),
            "api_token": self.settings_api_token_var.get(),
            "field_selection": {k: bool(v.get()) for k, v in self.field_option_vars.items()},
        }
        try:
            SETTINGS_PATH.write_text(
                json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8"
            )
        except OSError as exc:
            LOGGER.warning("Unable to persist settings to %s: %s", SETTINGS_PATH, exc)

    def _load_cities(self) -> Dict[str, tuple[str, str]]:
        if not CITIES_PATH.exists():
            return {}
        try:
            data = json.loads(CITIES_PATH.read_text(encoding="utf-8"))
        except (OSError, json.JSONDecodeError):
            LOGGER.warning("Could not read cities.json from %s", CITIES_PATH)
            return {}
        lookup: Dict[str, tuple[str, str]] = {}
        for entry in data:
            name = str(entry.get("name", "")).strip()
            lat = str(entry.get("latitude", "")).strip()
            lng = str(entry.get("longitude", "")).strip()
            if name and lat and lng:
                lookup[name.lower()] = (lat, lng)
        return lookup

    def _populate_city_combo(self) -> None:
        if not hasattr(self, "bot_city_combo"):
            return
        names = sorted({name.title() for name in self.city_lookup.keys()})
        self.bot_city_combo["values"] = names
        if names:
            self.bot_city_combo.set(names[0])

    def _get_settings_search_limit(self) -> int:
        try:
            value = int(self.settings_search_limit_var.get())
        except (ValueError, tk.TclError):
            value = 9999
        value = max(1, min(9999, value))
        self.settings_search_limit_var.set(value)
        return value

    def _get_settings_review_limit(self) -> int:
        try:
            value = int(self.settings_review_limit_var.get())
        except (ValueError, tk.TclError):
            value = 9999
        value = max(0, min(9999, value))
        self.settings_review_limit_var.set(value)
        return value

    def _apply_settings_limits(self) -> None:
        search_limit = self._get_settings_search_limit()
        review_limit = self._get_settings_review_limit()

        for spin in (self.api_limit_spin, self.bot_limit_spin):
            spin.config(to=search_limit)
            self._safe_spin_int(spin, default=min(search_limit, 5), minimum=1, maximum=search_limit)

        self.bot_reviews_spin.config(to=review_limit)
        bot_current_default = 0
        with suppress(ValueError, tk.TclError):
            bot_current_default = int(float(self.bot_reviews_spin.get()))
        self._safe_spin_int(
        self.bot_reviews_spin,
        default=min(review_limit, max(0, bot_current_default)),
        minimum=0,
        maximum=review_limit,
    )

        self._set_spin_value(self.settings_search_limit_spin, str(search_limit))
        self._set_spin_value(self.settings_review_limit_spin, str(review_limit))

    def _current_field_selection(self) -> FieldSelection:
        return FieldSelection(
            **{key: bool(var.get()) for key, var in self.field_option_vars.items()}
        )

    def _on_settings_apply(self) -> None:
        search_limit = self._safe_spin_int(
            self.settings_search_limit_spin,
            default=self._get_settings_search_limit(),
            minimum=1,
            maximum=9999,
        )
        review_limit = self._safe_spin_int(
            self.settings_review_limit_spin,
            default=self._get_settings_review_limit(),
            minimum=0,
            maximum=9999,
        )
        self.settings_search_limit_var.set(search_limit)
        self.settings_review_limit_var.set(review_limit)
        self._apply_settings_limits()
        self._persist_settings()
        self.settings_message_var.set(self._("settings_saved"))
        self.after(3500, lambda: self.settings_message_var.set(""))

    def _layout_widgets(self) -> None:
        self.logo_label.pack(side=tk.LEFT)
        self.header_text_frame.pack(side=tk.LEFT, fill=tk.BOTH, expand=True)
        self.app_title_label.pack(anchor=tk.W)
        self.app_tagline_label.pack(anchor=tk.W)
        self.header_frame.pack(fill=tk.X)
        self.separator.pack(fill=tk.X)
        self.content_frame.pack(fill=tk.BOTH, expand=True)
        self.notebook.pack(fill=tk.BOTH, expand=True, padx=10, pady=10)

        # API tab layout
        self.api_tab.columnconfigure(0, weight=1)
        self.api_tab.columnconfigure(1, weight=1)
        self.api_tab.rowconfigure(1, weight=1)

        self.api_form_frame.grid(row=0, column=0, columnspan=2, sticky=tk.EW, padx=10, pady=(10, 5))
        form_pad = {"padx": 5, "pady": 5}
        for idx in range(5):
            weight = 1 if idx in {1, 3} else 0
            self.api_form_frame.columnconfigure(idx, weight=weight)
        self.api_key_label.grid(row=0, column=0, sticky=tk.W, **form_pad)
        self.api_key_entry.grid(row=0, column=1, columnspan=4, sticky=tk.EW, **form_pad)
        self.api_query_label.grid(row=1, column=0, sticky=tk.W, **form_pad)
        self.api_query_entry.grid(row=1, column=1, columnspan=4, sticky=tk.EW, **form_pad)
        self.language_label.grid(row=2, column=0, sticky=tk.W, **form_pad)
        self.language_combo.grid(row=2, column=1, sticky=tk.W, **form_pad)
        self.api_limit_label.grid(row=2, column=2, sticky=tk.W, **form_pad)
        self.api_limit_spin.grid(row=2, column=3, sticky=tk.W, **form_pad)
        self.api_search_button.grid(row=2, column=4, sticky=tk.E, **form_pad)
        self.api_field_options_frame.grid(row=3, column=0, columnspan=5, sticky=tk.EW, padx=5, pady=(0, 5))

        self.api_results_frame.grid(row=1, column=0, sticky=tk.NSEW, padx=(10, 5), pady=5)
        self.api_details_frame.grid(row=1, column=1, sticky=tk.NSEW, padx=(5, 10), pady=5)
        self.api_results_tree.pack(fill=tk.BOTH, expand=True)
        self.api_details_text.pack(side=tk.LEFT, fill=tk.BOTH, expand=True)
        self.api_details_scroll.pack(side=tk.RIGHT, fill=tk.Y)

        self.api_button_frame.grid(row=2, column=0, columnspan=2, sticky=tk.EW, padx=10, pady=(0, 10))
        self.api_save_json_button.pack(side=tk.LEFT, padx=5)
        self.api_save_csv_button.pack(side=tk.LEFT, padx=5)
        self.api_save_pdf_button.pack(side=tk.LEFT, padx=5)
        self.api_save_xlsx_button.pack(side=tk.LEFT, padx=5)
        self.api_transfer_button.pack(side=tk.LEFT, padx=5)
        self.api_status_label.pack(side=tk.RIGHT)

        # Bot tab layout
        self.bot_tab.columnconfigure(0, weight=3, minsize=560)
        self.bot_tab.columnconfigure(1, weight=3, minsize=520)
        self.bot_tab.rowconfigure(0, weight=0)
        self.bot_tab.rowconfigure(1, weight=3)
        self.bot_tab.rowconfigure(2, weight=2)
        self.bot_tab.rowconfigure(3, weight=0)

        self.bot_form_frame.grid(row=0, column=0, columnspan=2, sticky=tk.NSEW, padx=10, pady=(10, 5))
        for idx in range(6):
            weight = 1 if idx in {1, 3} else 0
            self.bot_form_frame.columnconfigure(idx, weight=weight)
        bot_pad = {"padx": 5, "pady": 5}
        self.bot_query_label.grid(row=0, column=0, sticky=tk.W, **bot_pad)
        self.bot_query_entry.grid(row=0, column=1, columnspan=3, sticky=tk.EW, **bot_pad)
        self.bot_search_button.grid(row=0, column=5, sticky=tk.E, **bot_pad)
        self.bot_language_label.grid(row=1, column=0, sticky=tk.W, **bot_pad)
        self.bot_language_combo.grid(row=1, column=1, sticky=tk.W, **bot_pad)
        self.bot_city_label.grid(row=1, column=2, sticky=tk.W, **bot_pad)
        self.bot_city_combo.grid(row=1, column=3, sticky=tk.W, **bot_pad)
        self.bot_limit_label.grid(row=1, column=4, sticky=tk.W, **bot_pad)
        self.bot_limit_spin.grid(row=1, column=5, sticky=tk.W, **bot_pad)
        self.bot_reviews_label.grid(row=2, column=0, sticky=tk.W, **bot_pad)
        self.bot_reviews_spin.grid(row=2, column=1, sticky=tk.W, **bot_pad)
        self.bot_field_options_frame.grid(row=3, column=0, columnspan=5, sticky=tk.EW, padx=5, pady=(0, 5))

        self.bot_map_canvas.pack(fill=tk.BOTH, expand=True, padx=5, pady=5)

        self.bot_results_frame.grid(row=1, column=0, sticky=tk.NSEW, padx=(10, 5), pady=5)
        self.bot_map_frame.grid(row=1, column=1, sticky=tk.NSEW, padx=(5, 10), pady=5)
        self.bot_details_frame.grid(row=2, column=0, columnspan=2, sticky=tk.NSEW, padx=10, pady=5)
        self.bot_results_tree.pack(fill=tk.BOTH, expand=True)
        self.bot_details_text.pack(side=tk.LEFT, fill=tk.BOTH, expand=True)
        self.bot_details_scroll.pack(side=tk.RIGHT, fill=tk.Y)

        self.bot_button_frame.grid(row=3, column=0, columnspan=2, sticky=tk.EW, padx=10, pady=(0, 10))
        self.bot_save_json_button.pack(side=tk.LEFT, padx=5)
        self.bot_save_csv_button.pack(side=tk.LEFT, padx=5)
        self.bot_save_pdf_button.pack(side=tk.LEFT, padx=5)
        self.bot_save_xlsx_button.pack(side=tk.LEFT, padx=5)
        self.bot_transfer_button.pack(side=tk.LEFT, padx=5)
        self.bot_status_label.pack(side=tk.RIGHT)

        # Settings tab layout
        self.settings_tab.columnconfigure(0, weight=1)
        self.settings_frame.grid(row=0, column=0, sticky=tk.NSEW, padx=10, pady=10)
        self.settings_frame.columnconfigure(1, weight=1)
        settings_pad = {"padx": 5, "pady": 5}
        self.settings_search_limit_label.grid(row=0, column=0, sticky=tk.W, **settings_pad)
        self.settings_search_limit_spin.grid(row=0, column=1, sticky=tk.W, **settings_pad)
        self.settings_review_limit_label.grid(row=1, column=0, sticky=tk.W, **settings_pad)
        self.settings_review_limit_spin.grid(row=1, column=1, sticky=tk.W, **settings_pad)
        self.settings_api_url_label.grid(row=2, column=0, sticky=tk.W, **settings_pad)
        self.settings_api_url_entry.grid(row=2, column=1, sticky=tk.EW, **settings_pad)
        self.settings_api_token_label.grid(row=3, column=0, sticky=tk.W, **settings_pad)
        self.settings_api_token_entry.grid(row=3, column=1, sticky=tk.EW, **settings_pad)
        self.settings_apply_button.grid(row=4, column=0, columnspan=2, sticky=tk.E, **settings_pad)
        self.settings_message_label.grid(row=5, column=0, columnspan=2, sticky=tk.W, **settings_pad)

        # License tab layout
        self.license_tab.columnconfigure(0, weight=1)
        self.license_info_frame.grid(row=0, column=0, sticky=tk.EW, padx=10, pady=(10, 5))
        self.license_activation_frame.grid(row=1, column=0, sticky=tk.NSEW, padx=10, pady=(5, 10))
        info_pad = {"padx": 5, "pady": 5}
        self.license_machine_label.grid(row=0, column=0, sticky=tk.W, **info_pad)
        self.license_machine_entry.grid(row=0, column=1, sticky=tk.W, **info_pad)
        self.license_copy_button.grid(row=0, column=2, sticky=tk.W, **info_pad)
        self.license_status_text.grid(row=1, column=0, columnspan=3, sticky=tk.W, **info_pad)
        self.license_days_text.grid(row=2, column=0, columnspan=3, sticky=tk.W, **info_pad)
        self.license_expiry_text.grid(row=3, column=0, columnspan=3, sticky=tk.W, **info_pad)
        self.license_duration_text.grid(row=4, column=0, columnspan=3, sticky=tk.W, **info_pad)

        act_pad = {"padx": 5, "pady": 5}
        self.license_key_label.grid(row=0, column=0, sticky=tk.W, **act_pad)
        self.license_key_entry.grid(row=0, column=1, sticky=tk.EW, **act_pad)
        self.license_activation_frame.columnconfigure(1, weight=1)
        self.license_activate_button.grid(row=1, column=0, columnspan=2, sticky=tk.E, **act_pad)
        self.license_message_label.grid(row=2, column=0, columnspan=2, sticky=tk.W, **act_pad)

    def _copy_machine_id(self) -> None:
        machine_id = self.license_machine_var.get()
        try:
            self.clipboard_clear()
            self.clipboard_append(machine_id)
        except tk.TclError:
            pass
        self.license_message_var.set(self._("license_machine_copied"))

    def _on_activate_license(self) -> None:
        key = (self.license_key_var.get() or "").strip()
        if not key:
            self.license_message_var.set(self._("license_missing_key"))
            return
        try:
            self.license_manager.activate(key)
        except LicenseError:
            self.license_message_var.set(self._("license_invalid_key"))
            return
        self.license_message_var.set(self._("license_success"))
        self.license_key_var.set("")
        self._refresh_license_state()

    def _refresh_license_state(self) -> None:
        self.license_machine_var.set(self.license_manager.machine_id())
        self._update_license_view()
        self._apply_license_state()

    def _update_license_view(self) -> None:
        if self.license_manager.is_valid():
            self.license_status_var.set(self._("license_status_valid"))
            days = self.license_manager.remaining_days()
            expiry = self.license_manager.expires_at()
            days_text = days if days is not None else "-"
            expiry_text = expiry or "-"
        else:
            self.license_status_var.set(self._("license_status_invalid"))
            days_text = "-"
            expiry_text = "-"
        plan = self.license_manager.plan_components()
        if plan:
            parts: List[str] = []
            if plan["years"]:
                parts.append(f"{plan['years']} {self._('duration_years')}")
            if plan["months"]:
                parts.append(f"{plan['months']} {self._('duration_months')}")
            if plan["days"]:
                parts.append(f"{plan['days']} {self._('duration_days')}")
            duration_text = ", ".join(parts) if parts else "-"
        else:
            duration_text = "-"
        self.license_days_var.set(f"{self._('license_days_left')}: {days_text}")
        self.license_expiry_var.set(f"{self._('license_expiry')}: {expiry_text}")
        self.license_duration_var.set(f"{self._('license_duration')}: {duration_text}")

    def _apply_license_state(self) -> None:
        valid = self.license_manager.is_valid()
        state = tk.NORMAL if valid else tk.DISABLED
        self.api_search_button.config(state=state)
        self.bot_search_button.config(state=state)
        if not valid:
            self.notebook.select(self.license_tab)

    def _ensure_license_valid(self) -> bool:
        if self.license_manager.is_valid():
            return True
        messagebox.showerror(self._("error_title"), self._("license_required"))
        self.notebook.select(self.license_tab)
        return False

    def _bind_events(self) -> None:
        self.language_combo.bind("<<ComboboxSelected>>", self._on_language_change)
        self.api_results_tree.bind("<<TreeviewSelect>>", lambda _: self._on_select_api_result())
        self.bot_results_tree.bind("<<TreeviewSelect>>", lambda _: self._on_select_bot_result())

    def _on_language_change(self, _event=None) -> None:
        self._update_translations()
        self._persist_settings()

    def _on_api_search(self) -> None:
        if not self._ensure_license_valid():
            return
        api_key = self.api_key_entry.get().strip()
        query = self.api_query_entry.get().strip()
        if not api_key:
            messagebox.showerror(self._("error_title"), self._("error_missing_key"))
            return
        if not query:
            messagebox.showerror(self._("error_title"), self._("error_missing_query"))
            return

        language = self.selected_language.get()
        max_search_limit = self._get_settings_search_limit()
        limit = self._safe_spin_int(
            self.api_limit_spin,
            default=min(5, max_search_limit),
            minimum=1,
            maximum=max_search_limit,
        )
        self.api_status_var.set(self._("status_searching"))
        self.api_search_button.config(state=tk.DISABLED)
        selection = self._current_field_selection()
        selection_payload = FieldSelection(**vars(selection))
        review_limit_setting = self._get_settings_review_limit()
        review_limit = review_limit_setting if selection_payload.wants_reviews() else 0

        def worker() -> None:
            try:
                client = GoogleMapsClient(api_key)
                results = client.search_places(
                    query,
                    language=language,
                    limit=limit,
                    field_selection=selection_payload,
                    review_limit=review_limit,
                )
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
        if not self._ensure_license_valid():
            return
        query = self.bot_query_entry.get().strip()
        if not query:
            messagebox.showerror(self._("error_title"), self._("error_missing_query"))
            return

        language = self.bot_language_combo.get() or "tr"
        max_search_limit = self._get_settings_search_limit()
        limit = self._safe_spin_int(
            self.bot_limit_spin,
            default=min(5, max_search_limit),
            minimum=1,
            maximum=max_search_limit,
        )

        settings_review_limit = self._get_settings_review_limit()
        review_limit = self._safe_spin_int(
            self.bot_reviews_spin,
            default=min(5, settings_review_limit),
            minimum=0,
            maximum=settings_review_limit,
        )
        city_name = (self.bot_city_combo.get() or "").strip()
        city_center = None
        if city_name:
            city_center = self.city_lookup.get(city_name.lower())
        selection = self._current_field_selection()
        selection_payload = FieldSelection(**vars(selection))
        if not selection_payload.wants_reviews():
            review_limit = 0
        self._bot_active_limit = limit
        self._bot_attempted = 0
        self.bot_status_var.set(self._("status_scraping_progress").format(0, limit))
        self.bot_search_button.config(state=tk.DISABLED)
        self._set_bot_map_placeholder()
        self._bot_log_lines.clear()
        self._bot_selected_index = None
        self._refresh_bot_details_view(None)
        for item in self.bot_results_tree.get_children():
            self.bot_results_tree.delete(item)
        self._bot_results.clear()

        def worker() -> None:
            try:
                scraper = GoogleMapsPlaywrightScraper(
                    language=language,
                    limit=limit,
                    max_reviews=review_limit,
                    field_selection=selection_payload,
                    city_center=city_center,
                    city_name=city_name,
                )
                results = scraper.search(
                    query,
                    progress_callback=self._queue_bot_map_update,
                    result_callback=self._queue_bot_result_append,
                    attempt_callback=self._queue_bot_attempt_update,
                    event_callback=self._queue_bot_event,
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
            category = result.business_type or "-"
            rating_display = "-"
            if result.rating is not None:
                rating_display = f"{result.rating:.1f}"
                if result.user_ratings_total is not None:
                    rating_display = f"{rating_display} ({result.user_ratings_total})"
            self.api_results_tree.insert(
                "",
                tk.END,
                iid=str(index),
                values=(result.name, phone, rating_display, category),
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
            self._refresh_bot_details_view(None)
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
                        result.business_type or "-",
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
                        result.business_type or "-",
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

    def _queue_bot_map_update(self, image_bytes: bytes) -> None:
        self.after(0, lambda: self._update_bot_map_image(image_bytes))

    def _queue_bot_result_append(self, result: PlaceResult) -> None:
        self.after(0, lambda: self._append_bot_result(result))

    def _queue_bot_attempt_update(self, count: int, name: Optional[str]) -> None:
        self.after(0, lambda: self._update_bot_attempt_status(count))

    def _queue_bot_event(self, event: str, payload: Dict[str, str]) -> None:
        self.after(0, lambda: self._handle_bot_event(event, payload))

    def _append_bot_result(self, result: PlaceResult) -> None:
        index = len(self._bot_results)
        self._bot_results.append(result)
        phone = result.formatted_phone_number or "-"
        category = result.business_type or "-"
        rating_display = "-"
        if result.rating is not None:
            rating_display = f"{result.rating:.1f}"
            if result.user_ratings_total is not None:
                rating_display = f"{rating_display} ({result.user_ratings_total})"
        self.bot_results_tree.insert(
            "",
            tk.END,
            iid=str(index),
            values=(result.name, phone, rating_display, category),
        )
        self._update_bot_status_label()
        self.bot_results_tree.selection_set(str(index))
        self.bot_results_tree.focus(str(index))
        self._show_bot_details(index)

    def _update_bot_attempt_status(self, count: int) -> None:
        self._bot_attempted = max(self._bot_attempted, count)
        self._update_bot_status_label()

    def _update_bot_status_label(self) -> None:
        limit = self._bot_active_limit or max(len(self._bot_results), 1)
        limit = max(limit, self._bot_attempted, 1)
        self.bot_status_var.set(
            self._("status_scraping_progress").format(self._bot_attempted, limit)
        )

    def _handle_bot_event(self, event: str, payload: Dict[str, str]) -> None:
        event_map = {
            "search_started": "log_search_started",
            "click_card": "log_click_card",
            "panel_opened": "log_panel_opened",
            "panel_failed": "log_panel_failed",
            "result_captured": "log_result_captured",
            "search_finished": "log_search_finished",
            "search_failed": "log_search_failed",
        }
        key = event_map.get(event)
        if not key:
            return
        self._append_bot_log_from_key(key, **payload)

    def _append_bot_log_from_key(self, key: str, **kwargs: str) -> None:
        payload = {k: str(v) for k, v in kwargs.items()}
        self._bot_log_lines.append((key, payload))
        if len(self._bot_log_lines) > 200:
            self._bot_log_lines = self._bot_log_lines[-200:]
        self._refresh_bot_details_view(None)

    def _get_localized_bot_logs(self) -> List[str]:
        language = self.bot_language_combo.get() or self.selected_language.get()
        catalog = TRANSLATIONS.get(language, TRANSLATIONS["tr"])
        localized: List[str] = []
        for key, payload in self._bot_log_lines:
            template = catalog.get(key, key)
            try:
                localized.append(template.format(**payload))
            except Exception:
                localized.append(template)
        return localized

    def _update_bot_map_image(self, image_bytes: bytes) -> None:
        if not image_bytes:
            self._set_bot_map_placeholder()
            return
        try:
            screenshot = Image.open(io.BytesIO(image_bytes))
        except (UnidentifiedImageError, OSError):
            self._set_bot_map_placeholder()
            return
        screenshot.thumbnail(self._map_preview_size, Image.LANCZOS)
        self._bot_map_photo = ImageTk.PhotoImage(screenshot)
        self._map_placeholder_active = False
        self.bot_map_canvas.configure(image=self._bot_map_photo, text="")

    def _set_bot_map_placeholder(self) -> None:
        placeholder = Image.new("RGB", self._map_preview_size, "#f3f6fb")
        draw = ImageDraw.Draw(placeholder)
        text = self._("map_preview_placeholder")
        try:
            bbox = draw.textbbox((0, 0), text)
            text_width = bbox[2] - bbox[0]
            text_height = bbox[3] - bbox[1]
        except AttributeError:
            text_width, text_height = draw.textsize(text)
        x = (self._map_preview_size[0] - text_width) / 2
        y = (self._map_preview_size[1] - text_height) / 2
        draw.text((x, y), text, fill="#6b7a90")
        self._bot_map_photo = ImageTk.PhotoImage(placeholder)
        self._map_placeholder_active = True
        self.bot_map_canvas.configure(image=self._bot_map_photo, text="")

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
        self._refresh_bot_details_view(index)

    def _refresh_bot_details_view(self, index: Optional[int]) -> None:
        if index is not None:
            self._bot_selected_index = index
        elif self.bot_results_tree.selection():
            try:
                self._bot_selected_index = int(self.bot_results_tree.selection()[0])
            except (ValueError, IndexError):
                self._bot_selected_index = None
        lines: List[str] = []
        if self._bot_log_lines:
            lines.append("== LOG ==")
            lines.extend(self._get_localized_bot_logs())
        selected = self._bot_selected_index
        if selected is not None and selected < len(self._bot_results):
            detail_text = self._format_result_details(self._bot_results[selected])
            if detail_text:
                if lines:
                    lines.append("")
                lines.append(f"== {self._('details')} ==")
                lines.append(detail_text)
        if not lines:
            lines.append(self._("no_results"))
        self.bot_details_text.config(state=tk.NORMAL)
        self.bot_details_text.delete("1.0", tk.END)
        self.bot_details_text.insert(tk.END, "\n".join(lines))
        self.bot_details_text.config(state=tk.DISABLED)

    def _render_details(self, text_widget: tk.Text, result: PlaceResult) -> None:
        lines = self._format_result_details(result).split("\n")
        text_widget.config(state=tk.NORMAL)
        text_widget.delete("1.0", tk.END)
        text_widget.insert(tk.END, "\n".join(lines))
        text_widget.config(state=tk.DISABLED)

    def _format_result_details(self, result: PlaceResult) -> str:
        lines = [
            f"{self._('column_name')}: {result.name}",
            f"{self._('column_phone')}: {result.formatted_phone_number or '-'}",
            f"{self._('column_phone_type')}: {result.telephone_type or '-'}",
            f"{self._('column_rating')}: {result.rating or '-'}",
            f"{self._('column_category')}: {result.business_type or '-'}",
        ]
        if result.user_ratings_total is not None:
            lines.append(f"{self._('ratings_total')}: {result.user_ratings_total}")
        lines.append(f"{self._('address')}: {result.formatted_address or '-'}")
        if result.website:
            lines.append(f"{self._('column_website')}: {result.website}")
        if result.business_image:
            lines.append(f"{self._('column_image')}: {result.business_image}")
        if result.latitude or result.longitude:
            lines.append(
                f"{self._('column_lat')}/{self._('column_lng')}: {result.latitude or '-'}, {result.longitude or '-'}"
            )
        lines.append("")
        lines.append(f"{self._('opening_hours')}:")
        if result.opening_hours:
            lines.extend(f"  - {item}" for item in result.opening_hours)
        else:
            lines.append("  - -")
        if result.busy_hours:
            lines.append("")
            lines.append(f"{self._('busy_hours')}:")
            for day, slots in result.busy_hours.items():
                lines.append(f"  {day}:")
                for slot in slots:
                    lines.append(f"    {slot.get('time','')}: {slot.get('busy','')}")
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
                if review.profile_photo_url:
                    review_lines.append(
                        f"  {self._('review_profile')}: {review.profile_photo_url}"
                    )
                if review.review_photo_urls:
                    review_lines.append(
                        f"  {self._('review_media')}: {' | '.join(review.review_photo_urls)}"
                    )
                review_lines.append(f"  {self._('review_text')}: {review.text or '-'}")
                if review.text_extra:
                    for extra_key, extra_value in review.text_extra.items():
                        review_lines.append(f"  {extra_key}: {extra_value}")
                lines.extend(review_lines)
                lines.append("")
        else:
            lines.append("  - -")
        return "\n".join(lines)

    def _enable_tree_sorting(self, tree: ttk.Treeview) -> None:
        columns = tree["columns"]
        self._tree_sort_states[tree] = {col: False for col in columns}
        for col in columns:
            tree.heading(col, command=lambda c=col, t=tree: self._sort_treeview(t, c))

    def _set_tree_heading(self, tree: ttk.Treeview, column: str, text: str) -> None:
        tree.heading(column, text=text, command=lambda c=column, t=tree: self._sort_treeview(t, c))

    def _sort_treeview(self, tree: ttk.Treeview, column: str) -> None:
        state = self._tree_sort_states.get(tree)
        if state is None:
            return
        reverse = state.get(column, False)
        numeric_columns = {"rating"}
        numeric = column in numeric_columns
        rows = []
        for iid in tree.get_children(""):
            value = tree.set(iid, column)
            rows.append((self._coerce_sort_value(value, numeric), iid))
        rows.sort(reverse=reverse)
        for index, (_, iid) in enumerate(rows):
            tree.move(iid, "", index)
        state[column] = not reverse

    def _coerce_sort_value(self, value: str, numeric: bool):
        text = (value or "").strip()
        if numeric:
            if not text:
                return float("-inf")
            cleaned = text.replace(",", ".")
            match = re.search(r"-?\d+(?:\.\d+)?", cleaned)
            if match:
                with suppress(ValueError):
                    return float(match.group())
            return float("-inf")
        return text.lower()

    def _set_spin_value(self, spinbox: ttk.Spinbox, value: str) -> None:
        original_state = spinbox.cget("state") if hasattr(spinbox, "cget") else "normal"
        if original_state == "disabled":
            spinbox.config(state=tk.NORMAL)
        try:
            spinbox.set(value)
        except (AttributeError, tk.TclError):
            spinbox.delete(0, tk.END)
            spinbox.insert(0, value)
        finally:
            if original_state == "disabled":
                spinbox.config(state=tk.DISABLED)

    def _save_results(self, results: List[PlaceResult], file_format: str) -> None:
        if not results:
            messagebox.showinfo(self._("info_title"), self._("error_no_results_to_save"))
            return
        fieldnames = [
            "name",
            "address",
            "phone",
            "telephone_type",
            "category",
            "business_image",
            "latitude",
            "longitude",
            "website",
            "opening_hours",
            "rating",
            "rating_count",
            "reviews",
            "busy_hours",
        ]

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
        elif file_format == "pdf":
            path = filedialog.asksaveasfilename(
                defaultextension=".pdf",
                filetypes=[("PDF", "*.pdf")],
            )
            if not path:
                return
            try:
                exporter = ResultPdfExporter(PDF_FONT_PATH)
                exporter.export(results, path, self._)
            except FileNotFoundError:
                messagebox.showerror(self._("error_title"), self._("save_error"))
                return
            except OSError:
                messagebox.showerror(self._("error_title"), self._("save_error"))
                return
        elif file_format == "xlsx":
            path = filedialog.asksaveasfilename(
                defaultextension=".xlsx",
                filetypes=[("Excel", "*.xlsx")],
            )
            if not path:
                return
            try:
                wb = Workbook()
                ws = wb.active
                ws.append(fieldnames)
                for result in results:
                    row = result.to_csv_row()
                    ws.append([row.get(field, "") for field in fieldnames])
                wb.save(path)
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
                with open(path, "w", encoding="utf-8-sig", newline="") as output:
                    writer = csv.DictWriter(output, fieldnames=fieldnames)
                    writer.writeheader()
                    for result in results:
                        writer.writerow(result.to_csv_row())
            except OSError:
                messagebox.showerror(self._("error_title"), self._("save_error"))
                return
        messagebox.showinfo(self._("info_title"), self._("save_success"))

    def _transfer_results(self, results: List[PlaceResult], source: str) -> None:
        if not results:
            messagebox.showinfo(self._("info_title"), self._("error_no_results_to_save"))
            return
        endpoint = (self.settings_api_url_var.get() or "").strip()
        if not endpoint:
            messagebox.showerror(self._("error_title"), self._("error_missing_api_url"))
            return
        token = (self.settings_api_token_var.get() or "").strip()
        city_name = ""
        if source == "bot":
            city_name = (self.bot_city_combo.get() or "").strip()
        if not city_name and results:
            city_name = results[0].city_name or ""
        payload = {
            "source": source,
            "generated_at": datetime.utcnow().isoformat() + "Z",
            "city_name": city_name,
            "city": city_name,
            "results": [result.to_dict() for result in results],
        }
        try:
            response = requests.post(
                endpoint,
                json={"token": token, "payload": payload},
                timeout=30,
            )
            response.raise_for_status()
        except requests.RequestException as exc:
            LOGGER.exception("API transfer failed: %s", exc)
            messagebox.showerror(self._("error_title"), self._("transfer_failed"))
            return
        messagebox.showinfo(self._("info_title"), self._("transfer_success"))

    def _update_translations(self) -> None:
        self.title(self._("app_title"))
        self.notebook.tab(self.api_tab, text=self._("tab_api"))
        self.notebook.tab(self.bot_tab, text=self._("tab_bot"))
        self.notebook.tab(self.settings_tab, text=self._("tab_settings"))
        self.notebook.tab(self.license_tab, text=self._("tab_license"))

        self.app_title_label.config(text=self._("app_title"))
        self.app_tagline_label.config(text=self._("app_tagline"))

        self.api_form_frame.config(text=self._("search_panel"))
        self.api_results_frame.config(text=self._("results"))
        self.api_details_frame.config(text=self._("details"))
        self.api_key_label.config(text=self._("api_key"))
        self.api_query_label.config(text=self._("query"))
        self.language_label.config(text=self._("language"))
        self.api_search_button.config(text=self._("search"))
        self.api_limit_label.config(text=self._("result_limit"))
        self._set_tree_heading(self.api_results_tree, "name", self._("column_name"))
        self._set_tree_heading(self.api_results_tree, "phone", self._("column_phone"))
        self._set_tree_heading(self.api_results_tree, "rating", self._("column_rating"))
        self._set_tree_heading(self.api_results_tree, "category", self._("column_category"))
        self.api_save_json_button.config(text=self._("save_json"))
        self.api_save_csv_button.config(text=self._("save_csv"))
        self.api_save_pdf_button.config(text=self._("save_pdf"))
        self.api_save_xlsx_button.config(text=self._("save_xlsx"))
        self.api_transfer_button.config(text=self._("transfer_data"))

        self.bot_form_frame.config(text=self._("search_panel"))
        self.bot_results_frame.config(text=self._("results"))
        self.bot_details_frame.config(text=self._("details"))
        self.bot_map_frame.config(text=self._("map_preview"))
        self.bot_query_label.config(text=self._("query"))
        self.bot_language_label.config(text=self._("language"))
        self.bot_city_label.config(text=self._("city"))
        self.bot_search_button.config(text=self._("search"))
        self.bot_limit_label.config(text=self._("result_limit"))
        self.bot_reviews_label.config(text=self._("review_limit"))
        if self._map_placeholder_active or self._bot_map_photo is None:
            self._set_bot_map_placeholder()
        self._set_tree_heading(self.bot_results_tree, "name", self._("column_name"))
        self._set_tree_heading(self.bot_results_tree, "phone", self._("column_phone"))
        self._set_tree_heading(self.bot_results_tree, "rating", self._("column_rating"))
        self._set_tree_heading(self.bot_results_tree, "category", self._("column_category"))
        self.bot_save_json_button.config(text=self._("save_json"))
        self.bot_save_csv_button.config(text=self._("save_csv"))
        self.bot_save_pdf_button.config(text=self._("save_pdf"))
        self.bot_save_xlsx_button.config(text=self._("save_xlsx"))
        self.bot_transfer_button.config(text=self._("transfer_data"))

        self.settings_frame.config(text=self._("settings_panel"))
        self.settings_search_limit_label.config(text=self._("settings_search_limit"))
        self.settings_review_limit_label.config(text=self._("settings_review_limit"))
        self.settings_api_url_label.config(text=self._("settings_api_url"))
        self.settings_api_token_label.config(text=self._("settings_api_token"))
        self.settings_apply_button.config(text=self._("settings_apply"))

        self.license_info_frame.config(text=self._("license_info_group"))
        self.license_activation_frame.config(text=self._("license_activation_group"))
        self.license_machine_label.config(text=self._("license_machine_id"))
        self.license_copy_button.config(text=self._("license_copy_id"))
        self.license_key_label.config(text=self._("license_key"))
        self.license_activate_button.config(text=self._("license_activate"))

        self._update_field_option_labels()
        self._refresh_license_state()
        self.license_message_var.set("")
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
