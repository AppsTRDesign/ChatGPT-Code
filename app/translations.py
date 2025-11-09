"""Translation utilities for the Telegram automation GUI."""
from __future__ import annotations

from dataclasses import dataclass
from typing import Dict


@dataclass(frozen=True)
class Translation:
    """Represents a single translation catalogue."""

    strings: Dict[str, str]

    def get(self, key: str) -> str:
        return self.strings.get(key, key)


TRANSLATIONS: Dict[str, Translation] = {
    "en": Translation(
        strings={
            "app_title": "Telegram Automation Suite",
            "tab_sessions": "Sessions",
            "tab_ban": "Ban Check",
            "tab_scan": "Scan Members",
            "tab_add": "Add Members",
            "tab_settings": "Settings",
            "language_label": "Language",
            "rate_limits": "Rate Limits",
            "delay_between_actions": "Delay between actions (seconds)",
            "delay_between_sessions": "Delay between sessions (seconds)",
            "save_settings": "Save Settings",
            "login_phone": "Phone Number",
            "login_send_code": "Send Code",
            "login_code_prompt": "Enter the login code for {phone}",
            "login_password_prompt": "Enter the two-factor password for {phone}",
            "login_success": "Login successful for {phone}",
            "login_failure": "Login failed for {phone}: {error}",
            "sessions_active": "Active Sessions",
            "refresh": "Refresh",
            "ban_check": "Check Bans",
            "ban_removed": "Removed banned session: {phone}",
            "ban_ok": "Session OK: {phone}",
            "ban_results": "Ban Check Results",
            "target_group": "Target Group or Channel",
            "timeframe": "Activity Timeframe",
            "minutes": "Minutes",
            "hours": "Hours",
            "days": "Days",
            "activity_value": "Value",
            "member_limit": "Member Limit",
            "start": "Start",
            "stop": "Stop",
            "status_ready": "Ready",
            "status_running": "Running",
            "status_completed": "Completed",
            "status_stopped": "Stopped",
            "status_error": "Error",
            "select_sessions": "Select Sessions",
            "scan_log": "Scan Log",
            "add_log": "Add Log",
            "users_file": "Users File",
            "choose_file": "Choose File",
            "no_sessions_selected": "Please select at least one session.",
            "session_progress": "Session {phone}",
            "flood_wait": "Flood wait for {seconds}s",
            "members_saved": "Saved {count} members to {path}",
            "adding_done": "Finished processing users.",
            "file_not_found": "Users file not found.",
            "invalid_limit": "Please provide a positive member limit or leave blank.",
            "group_info": "Group: {title} | Members: {count}",
            "otp_sessions": "Logged-in Sessions",
            "api_id_prompt": "Enter your Telegram API ID",
            "api_hash_prompt": "Enter your Telegram API Hash",
            "api_error": "API credentials are required to continue.",
        }
    ),
    "tr": Translation(
        strings={
            "app_title": "Telegram Otomasyon Paketi",
            "tab_sessions": "Oturumlar",
            "tab_ban": "Ban Kontrol",
            "tab_scan": "Üye Tara",
            "tab_add": "Üye Ekle",
            "tab_settings": "Ayarlar",
            "language_label": "Dil",
            "rate_limits": "Hız Sınırları",
            "delay_between_actions": "İşlem arası gecikme (saniye)",
            "delay_between_sessions": "Oturumlar arası gecikme (saniye)",
            "save_settings": "Ayarları Kaydet",
            "login_phone": "Telefon Numarası",
            "login_send_code": "Kod Gönder",
            "login_code_prompt": "{phone} için giriş kodunu girin",
            "login_password_prompt": "{phone} için iki faktörlü şifreyi girin",
            "login_success": "{phone} için giriş başarılı",
            "login_failure": "{phone} giriş başarısız: {error}",
            "sessions_active": "Aktif Oturumlar",
            "refresh": "Yenile",
            "ban_check": "Ban Kontrolü",
            "ban_removed": "Banlı oturum kaldırıldı: {phone}",
            "ban_ok": "Oturum uygun: {phone}",
            "ban_results": "Ban Kontrol Sonuçları",
            "target_group": "Hedef Grup veya Kanal",
            "timeframe": "Aktiflik Zamanı",
            "minutes": "Dakika",
            "hours": "Saat",
            "days": "Gün",
            "activity_value": "Değer",
            "member_limit": "Üye Limiti",
            "start": "Başlat",
            "stop": "Durdur",
            "status_ready": "Hazır",
            "status_running": "Çalışıyor",
            "status_completed": "Tamamlandı",
            "status_stopped": "Durduruldu",
            "status_error": "Hata",
            "select_sessions": "Oturumları Seç",
            "scan_log": "Tarama Günlüğü",
            "add_log": "Ekleme Günlüğü",
            "users_file": "Kullanıcı Dosyası",
            "choose_file": "Dosya Seç",
            "no_sessions_selected": "Lütfen en az bir oturum seçin.",
            "session_progress": "Oturum {phone}",
            "flood_wait": "Flood bekleme {seconds}s",
            "members_saved": "{count} üye {path} konumuna kaydedildi",
            "adding_done": "Kullanıcı işlemleri tamamlandı.",
            "file_not_found": "Kullanıcı dosyası bulunamadı.",
            "invalid_limit": "Lütfen pozitif bir üye limiti girin veya boş bırakın.",
            "group_info": "Grup: {title} | Üye: {count}",
            "otp_sessions": "Giriş Yapılan Oturumlar",
            "api_id_prompt": "Telegram API ID'nizi girin",
            "api_hash_prompt": "Telegram API Hash'inizi girin",
            "api_error": "Devam etmek için API bilgileri gerekli.",
        }
    ),
}


class Translator:
    """Simple runtime translator for GUI labels."""

    def __init__(self, language: str = "en") -> None:
        self.language = language if language in TRANSLATIONS else "en"

    def set_language(self, language: str) -> None:
        if language in TRANSLATIONS:
            self.language = language

    def tr(self, key: str, **kwargs) -> str:
        text = TRANSLATIONS[self.language].get(key)
        if kwargs:
            try:
                return text.format(**kwargs)
            except KeyError:
                return text
        return text

    def available_languages(self) -> Dict[str, str]:
        return {code: code.upper() for code in TRANSLATIONS}

    def current_language(self) -> str:
        return self.language
