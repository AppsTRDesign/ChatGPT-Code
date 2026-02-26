# CargoAfrik Kurumsal Kargo Platformu (PHP 8 + MariaDB)

Plesk uyumlu, kök dizine kurulan ve `/admin` paneli ile yönetilen prodüksiyon odaklı kurumsal kargo altyapısı.

## Öne Çıkanlar
- **Pro UI/UX**: modern, mobil uyumlu, premium görünüm.
- **Çoklu dil sistemi**: tr/en/de/fr aktif; adminden yeni dil eklenebilir.
- **Tam yönetilebilir metinler**: tüm kullanıcı metinleri `translations` tablosu üzerinden yönetilir.
- **SEO URL**: `.htaccess` ile temiz linkler ve dinamik sayfalarda `/page/{id}/{slug}`.
- **Kargo takip**: captcha + ajax + toastr + event zaman çizelgesi.
- **Fiyat hesaplama**: ülke + kategori + ücret; ülke/kategori çevirileri dahil.
- **Admin yönetimi**:
  - Sayfa ve menü yönetimi
  - Kargo ve durum güncellemeleri
  - Fiyatlandırma + çoklu dil çeviri yönetimi
  - Site ayarları
  - Dil ve metin yönetimi

## Kurulum
1. Veritabanı oluşturun.
2. `sql/schema.sql` dosyasını içeri aktarın.
3. `config/config.php` DB bilgilerini düzenleyin.
4. Plesk document root olarak proje kökünü gösterin.
5. Apache `mod_rewrite` aktif olsun.

## Varsayılan Admin
- URL: `/admin/login.php`
- E-posta: `admin@cargoafrik.org`
- Şifre: `Admin123!`

> Canlıya geçmeden önce şifreyi ve güvenlik ayarlarını değiştirin.
