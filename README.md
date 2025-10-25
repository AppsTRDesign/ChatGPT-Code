# NoaSoft Web Push Platformu Taslağı

Bu depo, Plesk + AlmaLinux 8 üzerinde PHP 8 uyumlu, OneSignal tarzında web push bildirim sistemi geliştirmesi için oluşturulan başlangıç taslağını içerir. Taslak; admin ve client panelleri, API uçları, bildirim şablonları ve temel servis katmanlarını barındıran modüler bir mimari sunar.

## Ana Özellikler

- **Base URL:** `https://webpush.noasoft.org`
- **Tema:** Koyu mavi / turkuaz kombinasyonlu responsive arayüz
- **Ajax Tabanlı Yapı:** Tablolar, grafikler ve formlar JSON kaynaklı Ajax çağrıları ile beslenmeye hazırdır.
- **Gelişmiş API Kurgusu:** Token bazlı bildirim gönderimi için `ApiController` ve `NotificationService` taslakları.
- **Bildirim Şablonları:** 10 adet responsive HTML şablonu (`app/Views/client/templates`) ile OneSignal benzeri bildirimler.
- **Entegrasyon Hazırlıkları:** GeoIP2, DeviceDetector, PHPMailer ve iyzico entegrasyonları için servis katmanları ve Composer bağımlılıkları.
- **Veritabanı Yapısı:** MySQL üzerinde tüm ayarların tutulduğu kapsamlı şema (`database/migrations/0001_initial_schema.sql`).
- **Güvenlik ve Erişim:** `.htaccess` ile SEO uyumlu URL yönlendirmeleri, `/login` üzerinden ortak giriş ekranı ve JSON uçları için koruma altyapısı.

## Dizim Yapısı

```
├── app
│   ├── Controllers
│   ├── Core
│   ├── Models
│   ├── Services
│   └── Views
├── assets
│   ├── css
│   └── js
├── bootstrap
├── config
├── database
│   └── migrations
├── public
│   └── js
└── index.php
```

## Kurulum

1. Bağımlılıkları kurun:
   ```bash
   composer install
   ```
2. `config/database.php` dosyasını sunucu bilgilerinize göre güncelleyin.
3. `database/migrations/0001_initial_schema.sql` dosyasını MySQL üzerinde çalıştırarak şemayı oluşturun. Varsayılan admin hesabı kullanıcı adı **admin**, şifre **admin** olarak atanır.
4. Web sunucunuzu projenin kök dizinine yönlendirin ve `.htaccess` kuralının aktif olduğundan emin olun.

## Sonraki Adımlar

- Admin ve client panelleri için detaylı CRUD ve raporlama ekranları
- GeoIP2, DeviceDetector, PHPMailer ve iyzico entegrasyonlarının tamamlanması
- Bildirim servis worker dosyası ve tarayıcı token yönetim süreçlerinin geliştirilmesi

Bu taslak üzerine ek geliştirme adımları, kullanıcı talimatlarına göre ilerleyecektir.
