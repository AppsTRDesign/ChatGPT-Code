# Siyah & Beyaz Muhasebe Scripti

Bu depo, PHP 8 ile uyumlu, Plesk/AlmaLinux 8 ortamlarında kolaylıkla yayınlanabilecek, modern ve mobil uyumlu bir muhasebe otomasyonunu içerir. Uygulama; cari hesap, stok, fatura, ödeme, raporlama, finansal yönetim ve kullanıcı loglama işlevlerini tek bir arayüzde birleştiren sofistike bir siyah-beyaz temaya sahiptir.

## Özellik Özeti
- **Cari Hesaplar:** Müşteri/tedarikçi kaydı, gruplama ve anlık bakiye takibi.
- **Stok Yönetimi:** Ürün tanımlama, kategorileme, kritik stok uyarıları.
- **Fatura & Fiş:** Satış/alış faturaları, kalem bazlı giriş, PDF/Excel/Word çıktı alma.
- **Ödeme & Tahsilat:** Tahsilat/tediye işlemleri, vade takibi.
- **Raporlama:** Satış, alış, kâr/zarar, stok ve cari özetleri; çoklu formatta dışa aktarma.
- **Finansal Yönetim:** Banka hesapları, kasa hareketleri ve nakit akışı.
- **Kullanıcı Logları ve Giriş:** Yönetici oturum açma, işlem kayıtları ve denetim izi.
- **Ayarlar:** Firma bilgileri, fatura şablonu tercihi, tek tıkla veritabanı yedeği.
- **Anlık Uyarılar:** Tüm form işlemleri AJAX üzerinden yürütülür ve başarı/hata mesajları SweetAlert2 ile gösterilir.

## Gereksinimler
- PHP 8.0 veya üzeri (PDO MySQL uzantısı etkin).
- MySQL 5.7+/MariaDB 10.2+ veritabanı sunucusu.
- Web sunucusu (Apache/Nginx) veya `php -S` yerleşik sunucusu.

## Kurulum
1. Depoyu sunucunuza kopyalayın veya `git clone` ile alın.
2. Plesk üzerinde `muhasebe.noasoft.org` alan adının web kökünü bu deponun ana dizinine (`index.php` ile aynı konum) yönlendirin.
3. `config.php` dosyasındaki veritabanı kullanıcı adı, parola ve sunucu bilgilerini MySQL ortamınıza göre düzenleyin. (Dilerseniz ortam değişkenleri ile de sağlayabilirsiniz.)
4. Uygulama ilk çalıştığında veritabanını otomatik oluşturur ve gerekli tabloları kurar.
5. Uygulamayı yerelde test etmek isterseniz şu komutu çalıştırabilirsiniz:
   ```bash
   php -S 0.0.0.0:8000 -t .
   ```
6. Tarayıcıdan `http://localhost:8000/admin/login` adresine giderek yönetici giriş ekranına ulaşın ve varsayılan `admin` / `admin` bilgileriyle oturum açın. (Giriş yaptıktan sonra şifrenizi değiştirmeniz tavsiye edilir.)

İlk kurulumda MySQL veritabanı bağlantısı sağlandığında tüm tablolar ile varsayılan yönetici hesabı ve ayarlar otomatik oluşturulur.

## Dosya Yapısı
- `index.php` – Tüm trafiği yöneten ön denetleyici.
- `src/` – Veritabanı ve yardımcı sınıflar.
- `templates/` – Modüler sayfa şablonları.
- `assets/` – Siyah/beyaz temalı stil (`style.css`) ve SweetAlert2 destekli etkileşimleri yöneten `app.js`.
- `config.php` – `muhasebe.noasoft.org` alan adına uygun varsayılan ayarları barındırır, MySQL bilgilerinizle güncelleyin.

## Dışa Aktarım Notları
PDF, Excel ve Word seçenekleri temel raporlama için hızla kullanılabilir çıktılar üretir. Daha gelişmiş şablon ihtiyacı olması durumunda, üçüncü parti PDF/Office kitaplıkları kolaylıkla entegre edilebilir.

## AJAX & Bildirim Akışı
- Tüm veri giriş ve silme formları `form[data-ajax="true"]` yapısı üzerinden `assets/app.js` tarafından yakalanır ve `fetch` ile arka uca gönderilir.
- İşlemler tamamlandığında yanıt mesajları SweetAlert2 modalları ile kullanıcıya aktarılır; yanıt bir yönlendirme içeriyorsa otomatik olarak ilgili sayfaya geçilir.
- Silme aksiyonları için SweetAlert2 tabanlı onay diyaloğu devreye girer. Ek onay metinleri için ilgili butonlara `data-confirm-title`/`data-confirm-text` nitelikleri ekleyebilirsiniz.

## Güvenlik ve Geliştirme İpuçları
- Üretime almadan önce oturum yönetimi ve kullanıcı yetkilendirmesini genişletin.
- HTTPS ve güçlü parola politikaları önerilir.
- Üretim ortamında MySQL kullanıcı yetkilerini en aza indirin ve düzenli yedek alın.

Keyifli kullanımlar!
