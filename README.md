# NoaSoft QR Menu Platformu

Plesk AlmaLinux 8 ve PHP 8 uyumlu bu proje; logolu, renk seçenekli QR kod üretimi ile paket tabanlı API yönetimini bir araya getirir. Sistem tamamen MySQL üzerinde çalışır, modern mavi-siyah arayüz kullanır ve SweetAlert + Dropzone ile kullanıcı deneyimini zenginleştirir.

## Özellikler
- chillerlan/php-qrcode ile renk ve logo destekli QR kod üretimi
- JSON tabanlı token yapısı ile API paylaşımı
- Admin paneli (/admin) üzerinden üyeler, paketler, ödeme ayarları ve raporların yönetimi
- Dropzone ile logo yükleme ve QR üzerinde otomatik merkezleme
- SweetAlert ile bildirimler, AJAX tabanlı işlemler
- Paket bazlı kullanım limitleri, banka havalesi ve İyzico destekli ödeme kurgusu
- Banka havalesi bildirimleri ve admin onaylı aktivasyon
- Mobil uyumlu kartlar, tablolar, formlar ve navigasyon

## Kurulum
1. Gerekli bağımlılıkları yükleyin:
   ```bash
   composer install
   ```
2. `config/config.php` dosyasındaki veritabanı ayarlarını güncelleyin.
3. MySQL veritabanınıza `sql/schema.sql` dosyasını import edin. Varsayılan admin bilgileri:
   - Kullanıcı adı: `admin`
   - Şifre: `admin`
4. `uploads/logos` ve `uploads/temp` dizinlerinin web sunucusu tarafından yazılabilir olduğundan emin olun.
5. Plesk üzerinde site kök dizini olarak bu klasörü tanımlayın (public alt dizini yoktur).

## API Kullanımı
- Endpoint: `POST https://qrmenu.noasoft.org/api/qr.php`
- Yetkilendirme: `Authorization: Bearer <TOKEN>` veya JSON gövdesinde `token`
- Parametreler: `data`, `color`, `background`, `logo_url` veya `logo_upload` (base64)
- Yanıt: Base64 kodlu PNG içeren JSON

Detaylı dokümantasyon için `docs/api.html` dosyasına göz atabilirsiniz.

## Geliştirme
- CSS: `assets/css/style.css`
- JavaScript: `assets/js/app.js`
- PHP sınıfları: `includes/`
- Admin paneli: `/admin`
- Müşteri paneli: `/client`

SweetAlert mesajlarının çalışması için `assets/js/app.js` dosyasını özelleştirebilirsiniz. Dropzone ile logo yükleme uç noktası `client/upload-logo.php` dosyasında yer alır.
