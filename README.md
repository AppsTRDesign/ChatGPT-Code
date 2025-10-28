# NoaSoft File Upload Platform

A PHP 8 file upload and storage platform tailored for AlmaLinux/Plesk deployments. The system provides SEO-friendly file sharing links, modern Bootstrap 5 UI enhanced with Dropzone drag & drop uploads, and AJAX-driven admin/client dashboards.

## Özellikler
- **Modern Arayüz:** Bootstrap 5 tabanlı responsive tasarım, özel renk paleti, Dropzone teması ve SweetAlert bildirimleri.
- **Gelişmiş Dosya Yöneticisi:** Çoklu seçim, CTRL+A ile tümünü seçme, Delete kısayolu, klasör oluşturma, ad değiştirme, taşıma, aynı klasördeki dosyalardan zip üretme ve sağ tık menüsünden paylaş/şifrele aksiyonları.
- **Video/Ses Önizlemeleri:** Desteklenen MIME türleri için HTML5 video ve ses oynatıcıları, görseller/PDF'ler için yerleşik önizleme.
- **Dosya Tipi İkonları & Sıralama:** MIME tipine göre otomatik ikon ataması, ada/boyuta/tarihe göre sıralama, sayfalama ve modern grid görünümü.
- **Drag & Drop Yükleme:** Paket limitlerine göre otomatik ayarlanan paralel yükleme, MIME kontrolü ve klasör bazlı sürükle-bırak deneyimi.
- **Yükleme Kuyrukları:** Manuel başlatılan Dropzone kuyruğu, iptal edilebilir görevler, detaylı ilerleme çubuğu ve toplu başarı bildirimleri.
- **Paylaşım ve Güvenli İndirme:** Herkese açık paylaşım bağlantıları, admin tanımlı geri sayım ile indirme butonunu aktifleştirme, gizli indirme linkleri (`/s/{token}` ve `/d/{token}`) ve paylaşım sayfasında reklam alanları.
- **Paket ve Limit Yönetimi:** Admin panelinden izin verilen MIME türlerini paket bazında tanımlayın, maksimum eş zamanlı yüklemeleri, paylaşım süresini ve klasör şifreleme/publik paylaşım ayarlarını belirleyin.
- **SEO Dostu Rotalar:** `.htaccess` ile `/file/{id}-{slug}` formatında temiz URL yönlendirmeleri.
- **SEO Kontrolü:** Admin panelinden meta başlık/açıklama/anahtar kelime, sosyal paylaşım başlığı ve açıklaması ile banner/JSON-LD snippet yapılandırması.
- **Admin Paneli:** Dosya ve kullanıcı yönetimi, paket tanımlama, paket bazlı MIME listesi, paylaşım süresi ve indirme gecikmesi, klasör şifreleme, reklam alanları ve genel meta/HTML/mail/analytics ayarları.
- **Client Paneli:** Dosya yönetimi, paket satın alma, kullanım istatistikleri, profil düzenleme ve ajax tabanlı bildirimler.
- **Ana Sayfa Vitrini:** Koyu temaya uyumlu yeni hero, özellik kartları, zaman çizelgesi ve paket vitrinleri ile satış odaklı sunum.
- **Ödeme Otomasyonu:** Iyzico, Stripe ve Havale/EFT seçenekleri; aktif/pasif kontrolü, otomatik ödeme sayfası oluşturma ve webhook/callback ile paket ataması.
- **Satın Alma & Dekont Yönetimi:** Banka transferleri için Dropzone destekli dekont yükleme, admin panelinde bekleyen/onaylanan/eksik ödeme takip kartları.
- **Paylaşım Analitiği:** Client panelinde günlük/haftalık/aylık/yıllık grafikler, coğrafi ve cihaz kırılımları ile CSV/PDF dışa aktarma.
- **Gerçek Zamanlı Uyarılar:** Ratchet tabanlı WebSocket sunucusu sayesinde dosya yüklemeleri, ödeme bildirimleri ve paket güncellemeleri canlı olarak yönetim ekranlarına yansır.
- **Bildirim & Entegrasyonlar:** PHPMailer tabanlı SMTP/PHP mail seçimi, GeoIP2 ve DeviceDetector ile indirme istatistikleri, Stripe webhook ve Iyzico callback uç noktaları, Plesk API senkronizasyonu.
- **Saklama Politikaları:** Belirli gün sonunda arşivleme veya otomatik silme için zamanlayıcı fonksiyonları.
- **Güvenlik:** CSRF koruması, MIME tipi doğrulaması, 50 MB varsayılan sınır, `uploads/.htaccess` ile doğrudan erişim kısıtlama.
- **Veritabanı Otomasyonu:** PDO ile bağlantı, ilk kurulumda tablo ve örnek verilerin (admin hesabı, paketler, varsayılan ayarlar) oluşturulması.
- **AJAX Tabanlı İş Akışı:** Tüm form ve veri işlemleri `api/` uç noktaları üzerinden JSON cevapları ile çalışır.

## Kurulum
1. **Depoyu Kopyala:**
   ```bash
   git clone https://example.com/your-fork.git fileupload
   cd fileupload
   ```
2. **PHP Bağımlılıkları:** Proje şu Composer paketlerine dayanır: `piwik/device-detector`, `geoip2/geoip2`, `phpmailer/phpmailer`, `iyzico/iyzipay-php`, `stripe/stripe-php`, `cboden/ratchet`. Yüklemek için `composer install` komutunu çalıştırın.
3. **Veritabanı Oluştur:**
   ```sql
   CREATE DATABASE fileupload CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
4. **Şema ve Örnek Veriyi Yükle:**
   ```bash
   mysql -u fileupload_user -p fileupload < database.sql
   ```
5. **`config.php` Güncelle:** Veritabanı bilgilerini, `BASE_URL` değerini ve e-posta/analitik ayarlarını ihtiyaçlarınıza göre düzenleyin.
6. **Sunucu Yapılandırması:**
   - Projeyi kök dizine yerleştirin (`public` alt klasörü kullanılmıyor).
   - Apache için `.htaccess` dosyasını etkinleştirin; Nginx kullanıyorsanız eşdeğer yönlendirme kurallarını ekleyin.
7. **Gerçek Zamanlı Sunucu:** `composer install` sonrası `php bin/realtime-server.php [port]` komutu ile WebSocket sunucusunu başlatın (varsayılan port 6001).
8. **Dosya İzinleri:** `uploads/` klasörünün web sunucusu tarafından yazılabilir olduğundan emin olun.

### Varsayılan Yönetici Bilgileri
- E-posta: `admin@noasoft.org`
- Parola: `admin`

Kurulumdan sonra kontrol paneline erişip güçlü bir parola belirlemeniz önerilir.

## Ödeme Entegrasyonları
- **Iyzico:** Admin panelinden API anahtarlarını tanımlayıp modu aktifleştirin. Ödemeler `api/payment.php?provider=iyzico` uç noktasına geri döner ve başarılı işlemler otomatik olarak paketi atar.
- **Stripe:** Webhook gizli anahtarını girin ve Stripe yönetim panelinde `https://fileupload.noasoft.org/api/payment.php?provider=stripe` adresini webhook olarak ekleyin. Checkout oturumları tamamlandığında paketler otomatik tanımlanır.
- **Havale/EFT:** Banka talimatlarını girin; kullanıcılar paket seçimi sırasında ödeme yöntemini belirler, Dropzone ile dekont yükler ve yönetici panelinden onay bekler. Onaylanan işlemler otomatik olarak ilgili paketi atar.
- **Plesk Senkronizasyonu:** Plesk API bilgileri ayarlandığında paket atamaları sonrasında limitler otomatik güncellenir ve isteğe bağlı servis planı kimliği paketlere eşlenebilir.

### Plesk API Kullanımı
Plesk REST API çağrıları panelinizin 8443 portu üzerinden yapılır. Örnek taban URL formatı:

```
https://<plesk-host>:8443/api/v2
```

1. Plesk panelinden **Araçlar & Ayarlar → API Erişimi** bölümüne gidin ve REST API'yi etkinleştirin.
2. Bir API kullanıcı hesabı oluşturun veya mevcut yönetici hesabınız için parola kullanın.
3. Yönetici panelindeki **Genel Ayarlar → Plesk API** kartına aşağıdaki alanları girin:
   - **API URL:** Örn. `https://example.com:8443/api/v2`
   - **API Kullanıcı Adı:** Plesk kullanıcı adı veya özel API hesabı.
   - **API Parolası:** Kullanıcı parolası veya API anahtarı.
4. Paket eşitlemesi tetiklendiğinde uygulama `POST /servers/{id}/subscriptions` ve benzeri uç noktalara çağrı yapabilmek için HTTP Basic kimlik doğrulaması kullanır. Plesk tarafında IP kısıtlaması varsa uygulama sunucusunu yetkilendirin.

> **Not:** Plesk API'si varsayılan olarak self-signed sertifika ile gelir. Üretim ortamında geçerli bir TLS sertifikası kullanarak API bağlantısının kesilmesini önleyin.

### WebSocket Sunucusu

Gerçek zamanlı bildirimler için `bin/realtime-server.php` betiği Ratchet tabanlı bir WebSocket sunucusu sağlar. Sunucuyu kalıcı olarak çalıştırmak için bir servis yöneticisi (systemd, supervisor) kullanabilir veya geçici olarak aşağıdaki komutla başlatabilirsiniz:

```bash
php bin/realtime-server.php 6001
```

Admin veya client panelleri bağlantı kurduğunda otomatik olarak `files` kanalına abone olur; ihtiyaç halinde `document.dispatchEvent(new CustomEvent('realtime:subscribe', { detail: { channel: 'transactions' } }));` kodu ile farklı kanallara geçiş yapabilirsiniz.

## Dizinyapısı
```
├── admin/           # Yönetim paneli sayfaları
├── api/             # AJAX uç noktaları
├── assets/          # CSS, JS, medya
├── client/          # Kullanıcı paneli
├── templates/       # Paylaşılan header/footer
├── uploads/         # Yüklenen dosyalar (HTTP erişimine kapalı)
├── config.php       # PDO yapılandırması
├── functions.php    # Yardımcı fonksiyonlar ve şema oluşturma
├── .htaccess        # SEO yönlendirmeleri ve güvenlik kontrolleri
└── database.sql     # Şema ve örnek veri
```

## Geliştirme İpuçları
- **PDF/Excel Raporlama:** Admin ve client panellerindeki grafikler için çok formatlı dışa aktarma seçenekleri ekleyin.
- **SMS Entegrasyonu:** Paket sonlanması ve ödeme bildirimleri için SMS sağlayıcılarıyla entegrasyon kurun.
- **MFA & IP Kısıtlama:** Yönetici oturumlarını iki faktörlü doğrulama ve IP beyaz listeleme ile güçlendirin.
- **Gelişmiş Analitik:** `file_access_logs` tablosunu kullanarak coğrafi/cihaz bazlı grafikler, PDF/Excel raporları ve ısı haritaları üretin.
- **Gerçek Zamanlı Bildirimler:** Ratchet tabanlı WebSocket sunucusunu devreye alarak yükleme/paket kullanım uyarılarını canlı iletin.
- **Harici Depo Desteği:** Dosya arşivlerini S3, Backblaze veya benzeri bulut depolara gönderecek adaptörler ekleyin.
- **Planlı Görevler:** Saklama politikası işlemlerini cron veya queue altyapısı ile yöneterek performansı artırın.
- **Gelişmiş Yetkilendirme:** Takım bazlı roller, dosya paylaşım izinleri ve audit log ekranları tasarlayın.
- **Tematik Özelleştirme:** Tema değiştirici, çoklu dil desteği ve kullanıcı başına koyu/açık tema tercihi ekleyin.

## Testler
Temel sözdizimi doğrulaması için:
```bash
find . -name '*.php' -print0 | xargs -0 -n1 php -l
```

## Lisans
Bu proje örnek amaçlıdır. Üretim ortamında kullanmadan önce güvenlik ve performans testlerinden geçiriniz.
