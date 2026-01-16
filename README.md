# NoaSoft Çiçek Sipariş Scripti

Bu proje, PHP 8 + PDO uyumlu, AlmaLinux 8 ve Plesk üzerinde çalışacak şekilde tasarlanmış örnek bir çiçek sipariş sistemidir.

## Özellikler
- **Admin Paneli**: site ayarları, ürün yönetimi, sipariş yönetimi, kullanıcı yönetimi, dinamik sayfa yönetimi, PayTR ayarları.
- **Ön Yüz**: ana sayfa vitrinleri, ürün detayları, üyelik paneli, sipariş geçmişi, iletişim ve SSS sayfaları.
- **Ajax & Toastr**: tüm formlar AJAX gönderimi ve Toastr bildirimleri içerir.
- **Tema Rengi**: ayarlardan değiştirilebilir.

## Kurulum
1. Dosyaları web sunucusunun kök dizinine kopyalayın.
2. `includes/config.php` içinden MariaDB bağlantı ayarlarını güncelleyin.
3. MariaDB/MySQL için `database/schema.mysql.sql` dosyasını çalıştırın.
4. Apache için `.htaccess` dosyasını aktif edin (Plesk panelinde AllowOverride açık olmalı).
5. Tarayıcıda ana sayfayı açın.

> Varsayılan admin hesabı: `admin@noasoft.org` / `admin123`

## PayTR
PayTR entegrasyonunu composer ile kurmanız beklenir. Örnek kurulum:

```bash
cd /var/www/vhosts/noasoft.org/cicek.noasoft.org/includes
composer require paytr/merchant
```

Ardından `admin/paytr.php` sayfasından bilgileri girin. Ödeme iframe çıktısı `includes/paytr_gateway.php` içinde hazırlanır.

## Dizin Yapısı
- `admin/` Admin paneli sayfaları
- `api/` AJAX endpointleri
- `assets/` CSS/JS ve görseller
- `database/` SQL şemaları
- `includes/` yapılandırma ve yardımcı fonksiyonlar
- `storage/` yüklenen dosyalar ve SQLite

## SEO URL Yapısı
`/urun/slug`, `/kategori/slug`, `/sayfa/slug` adresleri `.htaccess` ile yönlendirilir.
