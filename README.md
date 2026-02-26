# CargoAfrik Kurumsal Kargo Platformu (PHP 8 + MariaDB)

Plesk uyumlu, kök dizine kurulan ve `/admin` paneli ile yönetilen prodüksiyon odaklı kurumsal kargo altyapısı.

## Öne Çıkanlar
- **Pro UI/UX**: modern, mobil uyumlu, premium görünüm.
- **Dinamik Admin**: sayfa, menü, kargo, event, fiyatlama, dil, ayar ve admin şifre yönetimi.
- **Çoklu dil sistemi**: tr/en/de/fr aktif; adminden yeni dil eklenebilir.
- **Tam yönetilebilir metinler**: tekil çeviri + JSON import/export.
- **SEO URL**: `.htaccess` ile temiz linkler ve dinamik sayfalarda `/page/{id}/{slug}`.
- **Kargo takip**: captcha + ajax + toastr + Yandex harita güzergah çizimi.
- **Fiyat hesaplama**: ülke + kategori + ücret; ülke/kategori çevirileri dahil.
- **Yandex Maps entegrasyonu**:
  - Site iletişim haritası
  - Admin konum seçiciler (site ve kargo)
  - İngilizce `active-shipments` canlı pin haritası (gemi ikonu SVG)
- **Site ayarları**: logo/favicon dosya yükleme + kurumsal ve harita ayarları.

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
