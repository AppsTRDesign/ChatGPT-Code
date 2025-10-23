# NoaSoft QR Menu Platformu

NoaSoft QR Menu; PHP 8 / MySQL üzerinde çalışan, paket bazlı lisanslama ve gelişmiş QR üretim özelliklerini tek çatı altında toplayan çok kiracılı bir SaaS altyapısıdır. Proje Plesk AlmaLinux 8 ortamlarına kurulmaya hazırdır ve hem müşteri hem admin panelleri mavi-siyah, modern ve tamamen mobil uyumlu olarak tasarlanmıştır.

## Öne Çıkan Özellikler
- **QR üretimi**: chillerlan/php-qrcode entegrasyonu ile PNG/JPG/SVG çıktıları, renk & arka plan seçimi, şeffaf logoları otomatik hizalama, isteğe bağlı transparan arka plan ve en-boy oranı kontrolü.
- **Hazır içerik şablonları**: URL, metin, e-posta, telefon, SMS, Wi-Fi, konum, etkinlik, sosyal medya, WhatsApp ve Bitcoin/Ethereum gibi içerikler için sekmeli QR şablonları; özel formatlar için ham veri desteği.
- **Paket ve abonelik yönetimi**: Admin panelinden sınırlı veya sınırsız paketler oluşturma, süre & limit tanımlama, ücretsiz aylık 100 istekli başlangıç paketi.
- **Ödeme altyapısı**: İyzico test modunda kredi kartı ödemeleri, banka havalesi IBAN bilgisi, ödeme bildirim takibi, satın alımlar için onay/red/eksik ödeme iş akışları.
- **API güvenliği**: JSON tabanlı token üretimi, token pasifleştirme/silme, kullanım limitlerinin anlık güncellenmesi ve %50/%25/%5 eşiklerinde otomatik e-posta uyarıları.
- **Raporlama & tablolar**: Admin panelinde günlük/haftalık/aylık/yıllık API raporları, grafikler ve PDF/Excel dışa aktarma; müşteri panelinde kişisel API grafikleri. Tüm tablolar Bootstrap Table bileşeniyle arama, sayfalama ve kart görünümü sunar.
- **Marka yönetimi**: Dropzone ile logo & favicon yükleme, site başlığı/meta/HTML header-footer düzenleme, tema ile uyumlu ve okunabilir e-posta şablonları, varsayılan durumda logo yerine site adı gösterimi.
- **Bildirimler**: SweetAlert tabanlı geri bildirimler, AJAX işlemleri, e-posta doğrulama/şifre sıfırlama akışları ve yapılandırılabilir PHP mailer/PHPMailer katmanı.

## Kurulum Adımları
1. Bağımlılıkları yükleyin:
   ```bash
   composer install
   ```
2. `config/config.php` içerisindeki veritabanı ve temel ayarları güncelleyin.
3. MySQL veritabanınıza `sql/schema.sql` dosyasını import edin. Varsayılan yönetici bilgileri:
   - Kullanıcı adı: `admin`
   - Şifre: `admin`
4. `uploads/logos`, `uploads/temp` gibi dizinlerin web sunucusu tarafından yazılabilir olduğundan emin olun.
5. Projeyi Plesk üzerinde doğrudan domain köküne (public alt klasörü olmadan) yerleştirin.

## API Kullanımı
- **Temel uç nokta**: `https://qrmenu.noasoft.org/api/v1/qr`
- **Kimlik doğrulama**: `Authorization: Bearer <TOKEN>` başlığı veya JSON/GET parametresi olarak `token`.
- **Desteklenen yöntemler**: `POST` (JSON body) ve `GET` (img etiketleri için hızlı kullanım). Örnek GET kullanımı:
  ```html
  <img src="https://qrmenu.noasoft.org/api/v1/qr?token=TOKEN&type=url&url=https%3A%2F%2Fsite.com" alt="QR Kod">
  ```
- **Parametreler**: `type` (örn. `url`, `wifi`, `event`, `custom`), ilgili içerik alanları (`url`, `text_content`, `wifi_ssid` vb.), `color`, `background`, `logo_url` veya `logo_upload` (Base64), `aspect_ratio`, `width`/`height`, `formats` gibi gelişmiş ayarlar.
- **Yanıtlar**: JSON çıktıları veya doğrudan `image/png` yanıtı; müşteri panelinden indirilebilen PNG/JPG/SVG dosyaları.

Tüm parametreler ve örnekler için `/api-docs` (site içine gömülü) sayfasını veya `docs/api.html` dosyasını inceleyin.

## Geliştirme Notları
- Stil: `assets/css/style.css`
- JavaScript & grafikler: `assets/js/app.js`
- Sunucu tarafı sınıflar: `includes/`
- Yönetim paneli: `/admin`
- Müşteri paneli: `/client`
- API uç noktaları: `/api` ve `/api/v1/qr`

SweetAlert tabanlı uyarılar ve Dropzone yüklemeleri `assets/js/app.js` üzerinden yönetilir. Logolu QR oluşturma isteği `client/generate-qr.php`, marka dosyası yükleme ise `client/upload-logo.php` dosyaları tarafından karşılanır.
