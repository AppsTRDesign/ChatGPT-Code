# NoaSoft File Upload Platform

A PHP 8 file upload and storage platform tailored for AlmaLinux/Plesk deployments. The system provides SEO-friendly file sharing links, modern Bootstrap 5 UI enhanced with Dropzone drag & drop uploads, and AJAX-driven admin/client dashboards.

## Özellikler
- **Modern Arayüz:** Bootstrap 5 tabanlı responsive tasarım, özel renk paleti, Dropzone teması ve SweetAlert bildirimleri.
- **Dosya Yönetimi:** Drag & drop yükleme, MIME/limit kontrolleri, önizleme ve güvenli indirme bağlantıları.
- **SEO Dostu Rotalar:** `.htaccess` ile `/file/{id}-{slug}` formatında temiz URL yönlendirmeleri.
- **Admin Paneli:** Dosya ve kullanıcı yönetimi, paket tanımlama, genel ayarlar (meta, header/footer HTML, logo/favicon, e-posta ve Google Analytics konfigürasyonu).
- **Client Paneli:** Kullanıcı dosyaları, paket satın alma, kullanım istatistikleri ve profil düzenleme.
- **Güvenlik:** CSRF koruması, MIME tipi doğrulaması, 50 MB varsayılan sınır, `uploads/.htaccess` ile doğrudan erişim kısıtlama.
- **Veritabanı Otomasyonu:** PDO ile bağlantı, ilk kurulumda tablo ve örnek verilerin (admin hesabı, paketler, varsayılan ayarlar) oluşturulması.
- **AJAX Tabanlı İş Akışı:** Tüm form ve veri işlemleri `api/` uç noktaları üzerinden JSON cevapları ile çalışır.

## Kurulum
1. **Depoyu Kopyala:**
   ```bash
   git clone https://example.com/your-fork.git fileupload
   cd fileupload
   ```
2. **PHP Bağımlılıkları:** Gerekirse `composer install` (şu an için zorunlu bağımlılık bulunmuyor).
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
7. **Dosya İzinleri:** `uploads/` klasörünün web sunucusu tarafından yazılabilir olduğundan emin olun.

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
- **Önizleme Geliştirmeleri:** Video/ses türleri için entegre önizleyiciler ekleyin.
- **Saklama Politikaları:** Dosyalar için otomatik arşivleme veya süre sonu silme kuralları tanımlayın.
- **Paket Otomasyonu:** Plesk API ile paket limitlerini sunucu kaynaklarıyla eşleştirin.
- **Bildirimler:** E-posta/SMS entegrasyonu ile yükleme ve paket süresi bildirimleri gönderin.
- **İki Aşamalı Doğrulama:** Admin ve kullanıcı hesaplarına MFA desteği ekleyin.
- **Gelişmiş Raporlama:** Dosya erişim logları ve indirme istatistikleri için ayrı analitik paneller hazırlayın.

## Testler
Temel sözdizimi doğrulaması için:
```bash
find . -name '*.php' -print0 | xargs -0 -n1 php -l
```

## Lisans
Bu proje örnek amaçlıdır. Üretim ortamında kullanmadan önce güvenlik ve performans testlerinden geçiriniz.
