# Noa Political Wars

PHP 8.3 + MariaDB PDO uyumlu, Rival Regions tarzı siyaset/savaş/strateji oyun başlangıç sürümü.

## Özellikler
- Ana oyun paneli (`/`): canlı ekonomi, nüfus, asker, nüfuz kartları.
- Yönetim paneli (`/admin`): giriş + oyun ayarları yönetimi.
- JSON API endpointleri (`/api/state`, `/api/action/train`, `/api/action/collect`) mobil client hazırlığı için.
- Mobil uyumlu Bootstrap 5 arayüz.
- Font Awesome + Bootstrap Icons entegrasyonu.
- AJAX otomatik canlı veri yenileme (15 saniye poll).
- CSRF koruması ve temel admin session auth.

## Kurulum
1. Dosyaları web root'a yerleştirin: `/var/www/vhosts/noasoft.org/game.noasoft.org`
2. `.env.example` dosyasını `.env` olarak kopyalayın ve DB bilgilerini girin.
3. `database.sql` dosyasını MariaDB'de çalıştırın.
4. Apache için `.htaccess` aktif olmalı (`mod_rewrite`).
5. Admin giriş bilgileri (varsayılan):
   - kullanıcı: `admin`
   - şifre: `admin123`
   - production'da `.env` içinde `ADMIN_USER`, `ADMIN_PASS` tanımlayın.

## Not
Bu sürüm production-ready temeli sağlar. Sonraki sprintte çok oyunculu diplomasi/savaş sistemi, queue, battle simulator, cron tick ve websocket katmanı eklenebilir.
