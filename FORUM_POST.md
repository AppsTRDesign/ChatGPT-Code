[center][size=18][b]NoaSoft Dosya Deposu - PHP 8 Dosya Yükleme & Paylaşım Scripti[/b][/size][/center]
[hr]
[b]Öne Çıkan Özellikler[/b]
[list]
[*]Bootstrap 5 tabanlı koyu temalı, Dropzone destekli modern arayüz (tüm dropzone alanları tıklanabilir ve özel tema ile gelir).
[*]Dosya yöneticisinde çoklu seçim, CTRL+A, sürükle-bırak, zip oluşturma, zip/rar içeriği izinli uzantılara göre ayıklama, Ace tabanlı metin düzenleme.
[*]Paket bazlı limitler: depolama, tek dosya boyutu, eşzamanlı yükleme, izinli uzantılar ve paylaşım analitiği yetkisi.
[*]Paketinde analitik aktif olmayan kullanıcılar için client panelinde grafikler ve menüler otomatik gizlenir.
[*]Gelişmiş paylaşım analitiği (günlük/haftalık/aylık/yıllık grafikler, lokasyon & cihaz kırılımı, PDF/CSV dışa aktarma).
[*]Iyzico, Stripe ve Havale/EFT ödeme kanalları; başarılı işlemlerde paketler otomatik atanır, havale bildirimleri Dropzone ile dekont yükleyebilir.
[*]Admin panelinde ilerleme çubuklu GeoIP Dropzone: `.mmdb` / `.mmdb.gz` dosyalarını `uploads/geo` dizinine yükler ve yol alanını otomatik günceller.
[*]SEO dostu SEF URL'ler (`/file/{id}-{slug}`), sosyal meta & JSON-LD ayarları, reklam alanları ve özelleştirilebilir header/footer blokları.
[*]PDO tabanlı veritabanı katmanı, otomatik şema + örnek veri kurulumu ve `uploads/.htaccess` ile doğrudan erişim engeli.
[/list]

[b]Kurulum Adımları[/b]
[list=1]
[*][b]Kodları indir:[/b]
[code]git clone https://example.com/your-fork.git fileupload
cd fileupload[/code]
[*][b]Composer bağımlılıklarını kur:[/b]
[code]composer install[/code]
[*][b]Veritabanını hazırla:[/b] MySQL/MariaDB üzerinde UTF-8 uyumlu bir veritabanı oluştur.
[code]CREATE DATABASE fileupload CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;[/code]
[*][b]Şema + örnek veriyi içe aktar:[/b]
[code]mysql -u fileupload_user -p fileupload < database.sql[/code]
[*][b]config.php düzenle:[/b] Veritabanı erişim bilgileri, BASE_URL ve e-posta/analitik seçeneklerini kendi sunucuna göre güncelle.
[*][b]Dosya izinleri:[/b] `uploads/` (ve oluşturulacak `uploads/geo`) klasörlerinin web sunucusu tarafından yazılabilir olduğundan emin ol.
[*][b]Web sunucusu ayarları:[/b] Script kök dizinde çalışacak şekilde sanal host'u yönlendir, Apache kullanıyorsan `.htaccess` dosyasını aktifleştir.
[*][b]GeoIP (opsiyonel ama önerilir):[/b] Admin &rarr; Genel Ayarlar &rarr; Analitik & Gerçek Zamanlı Takip kartındaki Dropzone üzerinden MaxMind GeoLite2 veritabanını yükle; yükleme bittiğinde yol alanı otomatik dolar.
[/list]

[b]Varsayılan Yönetici Bilgileri[/b]
[quote]
E-posta: admin@noasoft.org
Şifre : admin
[/quote]
Kurulum sonrası admin şifresini mutlaka değiştirin.

[b]Ek Notlar[/b]
[list]
[*]Paylaşım analitiği, genel ayarlarda ve kullanıcının paketinde aktif değilse API tarafında 403 döner ve client panelinde kartlar gizlenir.
[*]Dropzone teması tüm formlarda ilerleme çubuğu, iptal butonu ve SweetAlert geri bildirimleriyle birlikte gelir.
[*]Zip/Rar ayıklama işlemleri yalnızca izinli uzantıları çıkarır; limit aşımlarında kullanıcı bilgilendirilir.
[*]Stripe/Iyzico başarıyla döndüğünde paketler otomatik atanır; Havale bildirimleri admin panelinden onaylanabilir.
[/list]

Sorularınız olursa konu altından yazabilirsiniz. İyi çalışmalar!
