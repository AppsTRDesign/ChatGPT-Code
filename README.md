# Siyah & Beyaz Muhasebe Scripti

Bu depo, PHP 8 ile uyumlu, Plesk/AlmaLinux 8 ortamlarında kolaylıkla yayınlanabilecek, modern ve mobil uyumlu bir muhasebe otomasyonunu içerir. Uygulama; cari hesap, stok, fatura, ödeme, raporlama, finansal yönetim ve kullanıcı loglama işlevlerini tek bir arayüzde birleştiren sofistike bir siyah-beyaz temaya sahiptir.

## Özellik Özeti
- **Cari Hesaplar:** Müşteri/tedarikçi kaydı, gruplama ve anlık bakiye takibi.
- **Stok Yönetimi:** Ürün tanımlama, kategorileme, kritik stok uyarıları.
- **Fatura & Fiş:** Satış/alış faturaları, kalem bazlı giriş, PDF/Excel/Word çıktı alma.
- **Ödeme & Tahsilat:** Tahsilat/tediye işlemleri, vade takibi.
- **Raporlama:** Satış, alış, kâr/zarar, stok ve cari özetleri; çoklu formatta dışa aktarma.
- **Finansal Yönetim:** Banka hesapları, kasa hareketleri ve nakit akışı.
- **Kullanıcı Logları:** Tüm işlemlerin denetim izi.
- **Ayarlar:** Firma bilgileri, fatura şablonu tercihi, tek tıkla veritabanı yedeği.

## Gereksinimler
- PHP 8.0 veya üzeri (PDO SQLite uzantısı etkin).
- Web sunucusu (Apache/Nginx) veya `php -S` yerleşik sunucusu.
- Dosya sistemi üzerinde yazma izni (`data/` klasörü).

## Kurulum
1. Depoyu sunucunuza kopyalayın veya `git clone` ile alın.
2. Belgelenen dizinde PHP'nin `public/` klasörünü web kökü olacak şekilde yapılandırın.
3. Gerekirse veritabanı klasörünün yazma iznini ayarlayın: `chmod -R 775 data`.
4. Uygulamayı test etmek için yerel sunucuda çalıştırabilirsiniz:
   ```bash
   php -S 0.0.0.0:8000 -t public
   ```
5. Tarayıcıdan `http://localhost:8000` adresine giderek uygulamayı kullanmaya başlayın.

İlk girişte varsayılan yönetici oturumu otomatik açılır. Uygulama SQLite veritabanını ilk çalıştırmada `data/app.db` altında oluşturur.

## Dosya Yapısı
- `public/` – Giriş noktası (`index.php`).
- `src/` – Veritabanı ve yardımcı sınıflar.
- `templates/` – Modüler sayfa şablonları.
- `public/assets/` – Siyah/beyaz temalı stil dosyaları.
- `data/` – SQLite veritabanı (varsayılan olarak boş `.gitkeep`).

## Dışa Aktarım Notları
PDF, Excel ve Word seçenekleri temel raporlama için hızla kullanılabilir çıktılar üretir. Daha gelişmiş şablon ihtiyacı olması durumunda, üçüncü parti PDF/Office kitaplıkları kolaylıkla entegre edilebilir.

## Güvenlik ve Geliştirme İpuçları
- Üretime almadan önce oturum yönetimi ve kullanıcı yetkilendirmesini genişletin.
- HTTPS ve güçlü parola politikaları önerilir.
- SQLite yerine MySQL/PostgreSQL kullanmak isterseniz `src/Database.php` içindeki bağlantı mantığını güncelleyebilirsiniz.

Keyifli kullanımlar!
