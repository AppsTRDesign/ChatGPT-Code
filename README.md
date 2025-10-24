# NoaSoft QR Menu Platformu

NoaSoft QR Menu; PHP 8 ve MySQL üzerinde çalışan, paket tabanlı lisanslama, gelişmiş QR kod üretimi ve çok kanallı bildirim destekli bir SaaS altyapısıdır. Sistem; Plesk AlmaLinux 8 ortamlarında kök dizine kurulmak üzere hazırlanmış, mavi-siyah temalı ve tamamen mobil uyumlu müşteri/admin panelleri içerir.

## İçindekiler
- [Genel Bakış](#genel-bakış)
- [Özellik Matrisi](#özellik-matrisi)
- [Detaylı Modüller](#detaylı-modüller)
- [Paneller ve İş Akışları](#paneller-ve-iş-akışları)
- [Entegrasyonlar](#entegrasyonlar)
- [Gereksinimler](#gereksinimler)
- [Kurulum](#kurulum)
- [Konfigürasyon](#konfigürasyon)
- [API Kılavuzu](#api-kılavuzu)
- [Dosya Yapısı](#dosya-yapısı)
- [Geliştirme İpuçları](#geliştirme-ipuçları)

## Genel Bakış
Platform; üyelik, paket satın alma ve API üzerinden QR üretimi süreçlerini tek çatı altında toplar. Dropzone ile yönetilen dosya yüklemeleri, SweetAlert geri bildirimleri, bootstrap-table tabanlı veri tabloları ve Chart.js grafikleri varsayılan olarak dahildir. Sistem hem frontend hem backend tarafında çoklu dil hazırlıklı JSON tabanlı bir yapı kullanır.

## Özellik Matrisi
| Alan | İçerik |
| --- | --- |
| QR Kod Üretimi | chillerlan/php-qrcode tabanlı üretim, PNG/JPG/SVG çıktıları, transparan arka plan, en-boy oranı, çoklu format indirme, ortalanmış logo (PNG alpha korunumu). |
| İçerik Şablonları | URL, metin, e-posta, SMS, telefon, Wi-Fi, konum, etkinlik, WhatsApp, sosyal medya (Facebook/Instagram/YouTube), kripto cüzdanları ve özel JSON/payload sekmeleri. |
| QR Geçmişi | Hem müşteri hem admin panelinde oluşturulan QR kayıtlarını saklama, indirme, silme; API çağrılarından gelen kayıtlar dahil. |
| Abonelik & Paketler | Ücretsiz başlangıç paketi (aylık 100 istek), süre/limit tanımlı paketler, admin tarafında ekleme-düzenleme-silme, kullanıcıya paket atama ve durdurma. |
| Ödeme Süreçleri | İyzico (test) kredi kartı ödemeleri, banka havalesi/IBAN bilgisi ve bildirim akışı, satın alma onay/red/eksik ödeme iş akışları, gerçek zamanlı kullanım güncellemesi. |
| API Güvenliği | JSON tabanlı token üretimi, token pasifleştirme/silme, tek cihaz kayıt kontrolü, GET/POST desteği, `<img>` ile gömülebilir uç nokta, kullanım eşik e-postaları (%50/%25/%5). |
| Raporlama | Günlük/haftalık/aylık/yıllık API kullanım grafikleri, bootstrap-table tablolar, PDF/Excel dışa aktarma (Türkçe karakter desteği). |
| Bildirimler | SweetAlert bildirimleri, e-posta doğrulama ve şifre sıfırlama, yapılandırılabilir PHP mail/SMTP katmanı, OneSignal push bildirimleri (toplu/tek kullanıcı, görsel destekli) ve dahili web push kampanyaları. |
| Web Push Analitiği | Dropzone ile görsel yüklemeli kampanyalar, <code>&lt;img&gt;</code> gömme linkleri, teslim/görüntülenme/tıklama kayıtları, Chart.js grafikleri ve PDF/Excel dışa aktarma. |
| Canlı Ziyaretçiler | Oturum kalp atışı (AJAX ping), ülke/şehir/platform/referer & arama sorgusu istatistikleri, gerçek zamanlı tablo ve grafikler, PDF/Excel export. |
| Sosyal Giriş | Firebase destekli Google, Facebook, Twitter, GitHub, Microsoft, Apple, Yahoo ile tek tıkla kayıt/giriş; admin ayarlarında etkinleştirilebilir. |
| Marka Yönetimi | Dropzone ile logo/favicon yükleme, marka öğeleri kartları, logo yoksa site adı gösterimi, footer/header içeriklerini ve meta verilerini canlı güncelleme. |
| Analitik & Entegrasyon | Google Analytics ölçüm kimliği, OneSignal, Firebase yapılandırması, API dokümantasyonunu iç siteye gömme ve dil dosyalarıyla özelleştirme. |

## Detaylı Modüller
### QR Servisi
- `includes/QrService.php` QR üretim motoru; içerik tipine göre payload oluşturur, logoyu yeniden boyutlandırıp ortalar ve transparan PNG desteği sunar.
- `client/qr-builder.php` sekmeli arayüzle içerik alanlarını yönetir; Dropzone ile logo yükler, renk/arka plan/format/ebat seçeneklerini gerçek zamanlı uygular.
- `client/generate-qr.php` ve `api/qr.php` hem panel hem API üzerinden gelen istekleri işler, limit düşümlerini anlık günceller ve `includes/QrHistory.php` aracılığıyla kayıt tutar.

### Abonelik & Kullanım
- `includes/Subscription.php` paket süreleri, limitler ve durum değişikliklerini izler; admin tarafında kullanıcıya paket atamayı destekler.
- `includes/UsageLogger.php` kullanım sayaçlarını takip eder, eşik bildirim e-postalarını tetikler.
- `admin/packages.php` paket yönetimi, `admin/data/packages.php` AJAX veri kaynağı sağlar.

### Ödeme Yönetimi
- `includes/IyzicoService.php` ile İyzico test ortamına bağlanır; `client/iyzico-pay.php` ve `client/iyzico-callback.php` ödeme sürecini yönetir.
- Banka havalesi bildirimleri `client/payment-notify.php` üzerinden alınır, durumlar admin `admin/purchases.php` ekranından yönetilir.

### Kullanıcı & Kimlik Doğrulama
- `includes/Auth.php` oturum açma, tek cihaz kayıt kontrolü, e-posta doğrulama ve şifre sıfırlama süreçlerini uygular.
- `firebase-auth.php` ile sosyal giriş JSON Web Token doğrulaması yapılır.
- `forgot-password.php` ve `reset-password.php` süreçleri PHPMailer veya PHP mail() üzerinden çalışacak şekilde yapılandırılır.

### Bildirimler
- `includes/Mailer.php` ve `includes/MailSettings.php` mail altyapısını yönetir; admin panelinde SMTP/PHPMailer aktif/pasif seçilebilir.
- `includes/Notifications.php` OneSignal REST çağrılarını yönetir; `admin/push.php` toplu veya seçili üyeye görselli push gönderir.
- `admin/push.php` aynı form üzerinden dahili web push kampanyalarını kaydeder, OneSignal gönderimini tetikler ve kampanya geçmişini Chart.js grafikleriyle sunar.
- `admin/data/web-push-campaigns.php`, `admin/data/web-push-events.php`, `admin/data/web-push-metrics.php` kampanya tabloları ile teslim/görüntülenme/tıklama metriklerini servis eder.
- `client/data/push-poll.php` ve `client/push-event.php` tarayıcıda gösterilen kampanyaları JSON olarak iletir ve görüntülenme/tıklama olaylarını kaydeder.

### Canlı Ziyaretçi İzleme
- `includes/Activity.php` oturum anahtarlarını yönetir, IP/platform/referer/arama verilerini saklar ve heartbeat güncellemelerini işler.
- `client/ping.php` ve `admin/ping.php` AJAX kalp atışı isteğiyle `session_activity` tablosunun güncel kalmasını sağlar.
- `admin/online.php` Chart.js grafikleri, bootstrap-table listesi ve PDF/Excel dışa aktarma butonlarıyla canlı oturumları raporlar.
- `admin/data/online.php` ve `admin/data/online-metrics.php` aktif kullanıcı tablolarının ve zaman serilerinin JSON veri kaynaklarını sağlar.
- `client/onesignal-register.php` ile kullanıcı cihazları kaydedilir.

### Raporlama & Analitik
- `admin/dashboard.php` gelir/usage grafikleri, bekleyen & onaylı satın alım metrikleri içerir.
- `admin/usage.php` ve `client/dashboard.php` Chart.js grafikleri, bootstrap-table tabloları ve PDF/Excel ihracını destekler (`assets/js/app.js`).
- `admin/data/usage-metrics.php`, `client/data/usage-metrics.php` JSON veri kaynakları sunar.

### Marka & Ayarlar
- `includes/Settings.php` site adı, logo, favicon, meta, sosyal giriş, OneSignal, Google Analytics gibi tüm konfigürasyonları saklar.
- `admin/settings.php` dropzone tabanlı logo/favicon yönetimi, site metaları, footer/header içerikleri ve entegrasyon anahtarlarının tamamını tek sayfadan düzenler.

## Paneller ve İş Akışları
### Müşteri Paneli (`/client`)
- **Dashboard**: Kullanım grafikleri, kalan limit, aktif paket bilgisi, son satın alımlar.
- **QR Oluşturucu**: Sekmeli içerik şablonları, logo yükleme, renk/format ayarları, transparan arka plan seçimi.
- **QR Geçmişi**: Oluşturulan QR kayıtları için indirme (PNG/JPG/SVG) ve silme.
- **Paket Satın Alma**: Paket kartları, İyzico & banka havalesi akışları, ödeme durum takipleri.
- **API Tokenları**: Token üretme, pasifleştirme, silme; kalan kullanım bilgileri.
- **Profil & Ayarlar**: Parola değişimi, sosyal giriş bağlantıları, e-posta doğrulama durumu.
- **Dil & Bildirimler**: Firebase aktif ise sosyal giriş butonları otomatik görünür; OneSignal aktifse tarayıcı izinleri alınır.

### Admin Paneli (`/admin`)
- **Dashboard**: Bekleyen/onaylı satın alımlar, gelir grafikleri, API kullanım grafikleri, hızlı linkler.
- **Paketler**: Paket oluşturma/düzenleme/silme, aktif/pasif toggles, paketleri kullanıcıya atama.
- **Satın Alımlar & Ödemeler**: Banka/Iyzico işlemleri, durum yönetimi, hata kayıtları.
- **Kullanıcılar**: Düzenleme (paket değiştirme dahil), silme, OneSignal push gönderme butonu.
- **QR Geçmişi**: Tüm üyelerin QR kayıtları, indirme/silme, kaynak (API/panel) bilgisi.
- **API Kullanımı**: Filtrelenebilir tablolar, PDF/Excel ihracı.
- **Push Bildirimleri**: Dropzone ile görsel eklenebilen, tüm üyelere veya seçili kullanıcıya anlık push gönderme.
- **Ayarlar**: Site marka öğeleri, mail ayarları, Firebase, OneSignal, Google Analytics, dil dosyaları, API dokümantasyonu yönetimi.

## Entegrasyonlar
- **chillerlan/php-qrcode** – QR üretim kütüphanesi.
- **PHPMailer** – SMTP tabanlı e-posta gönderimi (istenirse PHP mail()).
- **Iyzico** – Test ödeme altyapısı (API anahtarları admin ayarlarında). 
- **Firebase Authentication** – Sosyal giriş ve token doğrulama.
- **OneSignal** – Push bildirimleri ve cihaz kaydı.
- **Google Analytics** – Ölçüm kimliği admin ayarlarından yönetilir.
- **bootstrap-table, Chart.js, Dropzone, SweetAlert2** – Ön uç bileşenleri.

## Gereksinimler
- PHP 8.1+
- MySQL 5.7+/MariaDB 10+
- Composer
- cURL, GD, OpenSSL, PDO, mbstring eklentileri
- Plesk AlmaLinux 8 üzerinde root dizine kurulum (public alt klasörü olmadan)

## Kurulum
1. Projeyi sunucunuzun kök dizinine kopyalayın.
2. Bağımlılıkları yükleyin:
   ```bash
   composer install
   ```
3. `config/config.php` içerisinde veritabanı bilgilerinizi ve temel ayarları yapın.
4. Veritabanına `sql/schema.sql` dosyasını uygulayın. Varsayılan yönetici bilgileri:
   - Kullanıcı adı: `admin`
   - Şifre: `admin`
5. `uploads/` altındaki klasörlerin yazılabilir olduğundan emin olun (`logos`, `brand`, `push`, `qr`, `temp`).
6. Web sunucusunda `.htaccess` dosyasının rewrite kurallarını desteklemesini sağlayın (Apache mod_rewrite).

## Konfigürasyon
- **Admin > Ayarlar** sayfasından;
  - Site adı, açıklama, meta etiketleri, footer içerikleri
  - Logo & favicon yükleme/silme (Dropzone)
  - Mail ayarları (SMTP host, port, kullanıcı, TLS/SSL, gönderen adı, aktif/pasif)
  - İyzico API anahtarları ve mod durumu
  - Banka havalesi hesap/IBAN bilgileri
  - Firebase proje kimliği, API anahtarları ve aktif/pasif durumu
  - OneSignal App ID & REST anahtarı, varsayılan ikon/görsel
  - Google Analytics ölçüm kimliği
  - API dokümantasyonu ve çoklu dil JSON dosyalarının yönetimi

## API Kılavuzu
- **Temel uç nokta**: `https://qrmenu.noasoft.org/api/v1/qr`
- **Kimlik doğrulama**: `Authorization: Bearer <TOKEN>` başlığı veya `token` parametresi (GET/POST).
- **İstek türleri**:
  - `GET` – `<img>` etiketi veya hızlı entegrasyon için (örn. `<img src="https://.../api/v1/qr?token=...&type=url&url=https%3A%2F%2Fsite.com">`).
  - `POST` – JSON gövdesi; detaylı ayarları destekler.
- **Örnek JSON**:
  ```json
  {
    "token": "TOKEN",
    "type": "event",
    "event": {
      "name": "Tanıtım Lansmanı",
      "start": "2025-05-12T19:00:00+03:00",
      "end": "2025-05-12T22:00:00+03:00",
      "location": "NoaSoft HQ"
    },
    "color": "#00b4ff",
    "background": "transparent",
    "logo_url": "https://site.com/logo.png",
    "aspect_ratio": "1:1",
    "formats": ["png", "svg"],
    "width": 512
  }
  ```
- **Yanıt**: JSON içerisinde üretim özeti, kalan limit, QR geçmiş kaydı ve talep edilen formatların Base64/URL bilgileri. `format=png` parametresiyle doğrudan `image/png` çıktısı alınabilir.
- Ayrıntılı şema, hata kodları ve örnekler için site içindeki `/api-docs` sayfasına bakın.

## Dosya Yapısı
- `admin/` – Yönetici paneli sayfaları, AJAX uç noktaları ve iş akışları.
- `client/` – Müşteri paneli sayfaları, QR oluşturucu ve geçmiş modülleri.
- `api/` – Dış API uç noktaları (`qr.php`).
- `assets/` – Tema CSS/JS dosyaları (Chart.js, bootstrap-table, Dropzone entegrasyonları dahil).
- `includes/` – Çekirdek PHP sınıfları (Auth, Settings, Subscription, QrService, Notifications vb.).
- `templates/` – Ortak header/footer şablonları.
- `uploads/` – Logo, marka görselleri, push medya, QR çıktıları ve geçici dosyalar.
- `sql/schema.sql` – Veritabanı şeması.

## Geliştirme İpuçları
- PHP dosyaları için `php -l` ile sözdizimi kontrolü yapın.
- JavaScript ve CSS derlemeleri `assets/js/app.js` ve `assets/css/style.css` içerisinden yönetilir.
- Bootstrap Table veri kaynakları AJAX ile JSON döner; doğrudan tarayıcıdan erişim için CSRF/AJAX kontrolleri bulunur.
- QR üretimi veya API testi yaparken kullanım limitleri anlık düşer; test sırasında ücretsiz paketi güncellemeniz gerekebilir.

Bu README, uygulamanın tüm modüllerine genel bir bakış sunar. Ayrıntılı kullanım için ilgili panel ekranlarına ve API dokümantasyonuna başvurabilirsiniz.
