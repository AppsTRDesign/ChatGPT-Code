# Noa Political Wars - Tam Sürüm Kurulum Rehberi

## 1) Sistem Gereksinimleri
- PHP 8.3+
- MariaDB 10.6+
- Apache + mod_rewrite
- Node.js 16.20.2 (socket servisi için)

## 2) Uygulama Dosyalarını Yerleştirme
1. Repo içeriğini web root'a kopyalayın.
2. `.env.example` dosyasını `.env` olarak kopyalayıp DB bilgilerini düzenleyin.

## 3) Veritabanı Kurulumu
1. MariaDB'de hedef veritabanını oluşturun.
2. `database.sql` dosyasını sırasıyla çalıştırın (içinde migration SOURCE sırası tanımlı).

## 4) PHP Uygulama Kontrolleri
- `./scripts/release_checks.sh`
- `./scripts/test_suite.sh`

## 5) Socket Servisini Kurma
1. `cd realtime-socket`
2. `npm install`
3. `npm start`
4. Sağlık kontrolü: `http://127.0.0.1:3001/health`

## 6) Web Sunucu Yönlendirmeleri
- `.htaccess` aktif olmalı ve tüm route istekleri `index.php`'ye düşmeli.

## 7) Opsiyonel Load Test
- `RUN_LOAD_TEST=1 TARGET_URL=http://127.0.0.1/health ./scripts/test_suite.sh`

## 8) Üretim Önerileri
- PHP-FPM process manager ayarlarını canlı yüke göre ölçekleyin.
- Socket servisini systemd/supervisor ile daemon olarak çalıştırın.
- DB backup + migration rollback planı hazırlayın.
