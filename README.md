# CargoAfrik Kurumsal Kargo Sitesi (PHP 8 + MariaDB)

Bu repo, **Plesk uyumlu**, kök dizine kurulabilen ve yönetim paneli `/admin` altında çalışan bir kurumsal kargo platformu iskeleti içerir.

## Özellikler
- SEO URL altyapısı (`.htaccess`), dinamik sayfalar için `/sayfa/{id}/{slug}`
- Browser diline göre otomatik dil seçimi (tr/en/de/fr, yoksa en)
- Kargo takip (captcha + ajax + toastr)
- Fiyat hesaplama (ülke + kategori bazlı)
- İletişim + OSM embed
- Admin panel:
  - Sayfa yönetimi (çok dilli)
  - Menü yönetimi
  - Kargo oluşturma + durum/event güncelleme
  - Fiyatlandırma yönetimi (ülke, kategori, ücret)
  - Site ayarları (logo, favicon, meta, şirket bilgileri)
  - Çeviri yönetimi

## Kurulum
1. MariaDB üzerinde bir veritabanı oluşturun.
2. `sql/schema.sql` dosyasını içeri aktarın.
3. `config/config.php` dosyasında DB bilgilerini güncelleyin.
4. Doküman kökünü bu proje olarak ayarlayın (Plesk).
5. Apache mod_rewrite aktif olmalı.

## Varsayılan Admin
- URL: `/admin/login.php`
- E-posta: `admin@cargoafrik.org`
- Şifre: `Admin123!`

> Şifre hash'i ilk kurulum için seedlenmiştir; canlıya almadan hemen değiştirin.
