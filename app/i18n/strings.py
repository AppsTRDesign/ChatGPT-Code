from __future__ import annotations

from dataclasses import dataclass
from typing import Dict


@dataclass(frozen=True)
class TranslationCatalog:
    messages: Dict[str, str]

    def get(self, key: str) -> str:
        return self.messages.get(key, key)


TRANSLATIONS: Dict[str, TranslationCatalog] = {
    "tr": TranslationCatalog(
        messages={
            "app.title": "Telegram Yönetim Aracı",
            "tab.sessions": "Oturumlar",
            "tab.ban_check": "Ban Kontrolü",
            "tab.scan": "Üye Tarama",
            "tab.add_members": "Üye Ekle",
            "tab.active_senders": "Aktif Mesaj Atanlar",
            "tab.rate_limit": "Rate Limit",
            "tab.settings": "Ayarlar",
            "label.api_id": "API ID",
            "label.api_hash": "API Hash",
            "label.phone": "Telefon Numarası",
            "label.session_name": "Oturum Adı",
            "label.code": "Doğrulama Kodu",
            "label.password": "Şifre",
            "label.target_group": "Hedef Grup Bağlantısı veya ID",
            "label.limit": "Tarama Limiti",
            "label.interval": "Zaman Aralığı",
            "label.interval_minutes": "Dakika",
            "label.interval_hours": "Saat",
            "label.interval_days": "Gün",
            "label.language": "Dil",
            "label.timezone": "Zaman Dilimi",
            "label.session_count": "Toplam Oturum",
            "label.rate_join": "Üye Ekleme Bekleme (sn)",
            "label.rate_message": "Mesaj Gönderme Bekleme (sn)",
            "label.rate_scan": "Tarama Bekleme (sn)",
            "label.flood_wait": "Flood bekleme yönetimi",
            "label.login_sessions": "Aktif Oturumlar",
            "label.user_table": "Kayıtlı Kullanıcılar",
            "label.progress": "İlerleme",
            "label.completed": "Tamamlandı",
            "label.status": "Durum",
            "label.members": "Üyeler",
            "button.start": "Başlat",
            "button.stop": "Durdur",
            "button.login": "OTP Girişi",
            "button.confirm_code": "Kodu Onayla",
            "button.check_ban": "Ban Kontrolü Yap",
            "button.refresh_sessions": "Oturumları Yenile",
            "button.export": "Dışa Aktar",
            "button.save": "Kaydet",
            "status.idle": "Bekliyor",
            "status.running": "Çalışıyor",
            "status.stopped": "Durduruldu",
            "status.waiting_flood": "Flood bekleniyor",
            "status.error": "Hata",
            "dialog.enter_password": "Hesap 2FA şifresini girin",
            "dialog.enter_code": "Telegram'dan gelen kodu girin",
            "dialog.success": "İşlem tamamlandı",
            "dialog.failure": "İşlem hatası",
            "log.login_success": "Oturum başarıyla oluşturuldu",
            "log.login_failure": "Oturum oluşturulamadı",
            "log.session_removed": "Oturum kaldırıldı",
            "log.ban_detected": "Hesap banlanmış",
            "log.flood_wait": "Flood bekleme süresi",
            "log.scan_finished": "Tarama tamamlandı",
            "log.add_finished": "Üye ekleme tamamlandı",
            "log.active_finished": "Aktif kullanıcı taraması tamamlandı",
            "table.column.username": "Kullanıcı Adı",
            "table.column.phone": "Telefon",
            "table.column.last_seen": "Son Görülme",
            "table.column.status": "Durum",
        }
    ),
    "en": TranslationCatalog(
        messages={
            "app.title": "Telegram Management Suite",
            "tab.sessions": "Sessions",
            "tab.ban_check": "Ban Check",
            "tab.scan": "Member Scanner",
            "tab.add_members": "Add Members",
            "tab.active_senders": "Active Senders",
            "tab.rate_limit": "Rate Limit",
            "tab.settings": "Settings",
            "label.api_id": "API ID",
            "label.api_hash": "API Hash",
            "label.phone": "Phone Number",
            "label.session_name": "Session Name",
            "label.code": "Verification Code",
            "label.password": "Password",
            "label.target_group": "Target Group Link or ID",
            "label.limit": "Scan Limit",
            "label.interval": "Time Interval",
            "label.interval_minutes": "Minutes",
            "label.interval_hours": "Hours",
            "label.interval_days": "Days",
            "label.language": "Language",
            "label.timezone": "Time Zone",
            "label.session_count": "Session Count",
            "label.rate_join": "Join Interval (s)",
            "label.rate_message": "Message Interval (s)",
            "label.rate_scan": "Scan Interval (s)",
            "label.flood_wait": "Handle flood waits",
            "label.login_sessions": "Active Sessions",
            "label.user_table": "Stored Users",
            "label.progress": "Progress",
            "label.completed": "Completed",
            "label.status": "Status",
            "label.members": "Members",
            "button.start": "Start",
            "button.stop": "Stop",
            "button.login": "OTP Login",
            "button.confirm_code": "Confirm Code",
            "button.check_ban": "Run Ban Check",
            "button.refresh_sessions": "Refresh Sessions",
            "button.export": "Export",
            "button.save": "Save",
            "status.idle": "Idle",
            "status.running": "Running",
            "status.stopped": "Stopped",
            "status.waiting_flood": "Waiting flood",
            "status.error": "Error",
            "dialog.enter_password": "Enter account 2FA password",
            "dialog.enter_code": "Enter the code received from Telegram",
            "dialog.success": "Operation completed",
            "dialog.failure": "Operation failed",
            "log.login_success": "Session created successfully",
            "log.login_failure": "Failed to create session",
            "log.session_removed": "Session removed",
            "log.ban_detected": "Account is banned",
            "log.flood_wait": "Flood wait",
            "log.scan_finished": "Scan finished",
            "log.add_finished": "Member addition completed",
            "log.active_finished": "Active sender scan completed",
            "table.column.username": "Username",
            "table.column.phone": "Phone",
            "table.column.last_seen": "Last seen",
            "table.column.status": "Status",
        }
    ),
}


class Translator:
    def __init__(self, language: str = "tr") -> None:
        self.language = language

    def set_language(self, language: str) -> None:
        if language in TRANSLATIONS:
            self.language = language

    def translate(self, key: str) -> str:
        catalog = TRANSLATIONS.get(self.language) or TRANSLATIONS["tr"]
        return catalog.get(key)


translator = Translator()
