# QR Menü Sistemi (MySQL + PHP 8)

Plesk / AlmaLinux 8 üzerinde PHP 8 ile uyumlu çalışan, mobil dostu QR menü ve yönetim paneli. Sistem tamamen MySQL tabanlıdır ve müşteri ile yönetici tarafındaki tüm aksiyonlarda SweetAlert bildirimleri kullanır.

## Özellikler

### Müşteri Arayüzü (qrmenu.noasoft.org)
- 📱 Masaya özel QR token ile anında menü erişimi
- 🧾 Ürün görselleri, açıklamalar ve fiyatlarla modern kart tasarımı
- 🛒 Sepet yönetimi, SweetAlert onayları ile sipariş verme
- 🔔 Garson çağırma butonu (yönetim paneline anlık bildirim ve sesli uyarı)
- 📡 Sipariş durumunu 15 saniyede bir yenileyen canlı durum paneli

### Yönetim Paneli (qrmenu.noasoft.org/admin)
- 🔐 MySQL tabanlı oturum (varsayılan kullanıcı: `admin` / `admin`)
- 📊 Dashboard: toplam sipariş, bekleyen sipariş, aktif masa ve çağrı istatistikleri
- 🍽️ Kategori ve ürün yönetimi (Dropzone ile görsel yükleme, aktif/pasif kontrolü)
- 🪑 Masa yönetimi: otomatik token üretimi ve QR linkleri
- 📦 Gerçek zamanlı sipariş listesi, durum güncelleme (hazırlanıyor, hazır, tamamlandı)
- 🔔 Garson çağrılarını anlık izleme ve tamamlama
- 🧾 Tek tuşla Türkçe karakter destekli PDF adisyon indirme
- ⚡ Panel içi tüm işlemler AJAX tabanlı SweetAlert bildirimleriyle desteklenir

## Kurulum

1. Dosyaları Plesk üzerindeki `qrmenu.noasoft.org` dizinine çıkarın.
2. `database/schema.sql` dosyasını MySQL sunucunuza uygulayın. Örnek bir veritabanı ve kullanıcı oluşturmak için:
   ```sql
   CREATE DATABASE qrmenu CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci;
   CREATE USER 'qrmenu'@'localhost' IDENTIFIED BY 'qrmenu_pass';
   GRANT ALL PRIVILEGES ON qrmenu.* TO 'qrmenu'@'localhost';
   FLUSH PRIVILEGES;
   ```
   Ardından `schema.sql` dosyasını içeri aktarın.
3. `config.php` dosyasında MySQL erişim bilgilerini, site adresini ve bildirim ses linklerini güncelleyin.
4. `uploads/`, `cache/` dizinlerinin web kullanıcısı tarafından yazılabildiğinden emin olun:
   ```bash
   chmod -R 775 uploads cache
   ```
5. Yönetim paneline ilk girişinizde varsayılan admin hesabıyla oturum açın ve parolanızı güncelleyin.

## Dosya Yapısı

```
admin/           Yönetim paneli ve AJAX endpointleri
api/             Müşteri tarafı JSON API uç noktaları
lib/             Servis sınıfları, veritabanı ve PDF üretim kütüphanesi
uploads/         Dropzone ile yüklenen ürün görselleri
database/        MySQL şema dosyası
index.php        Müşteri arayüzü (ana dizin)
```

## Teknik Notlar

- PDO kullanarak MySQL bağlantısı kurulmuştur. Tüm tablolar `utf8mb4_turkish_ci` ile oluşturulur.
- Cache sistemi, masaya özel HTML çıktısını `cache/` dizininde 5 dakika saklar.
- SweetAlert2, Bootstrap 5, Dropzone ve Bootstrap Icons CDN üzerinden yüklenir.
- PDF adisyonlar, Google Fonts üzerinden dinamik indirilen NotoSans TrueType fontu ile oluşturulur (Türkçe karakter desteği).
- İlk PDF üretiminde yazı tipi otomatik indirildikten sonra `cache/fonts/` dizininde saklanır.
- Depo içerisinde herhangi bir ikili (binary) dosya tutulmaz; fontlar çalışma anında Google Fonts'tan indirilir, Dropzone ile gelen medya ise `.gitignore` sayesinde sürüm kontrolü dışında kalır.
- `cache/` ve `uploads/` altındaki geçici JSON çıktıları işlemler tamamlandığında otomatik temizlenir; elle müdahale gerektirmez.
- Gerçek zamanlı sipariş ve çağrı kontrolleri için yönetim panelinde periyodik fetch istekleri ve sesli uyarılar bulunur.

## Geliştirme & Test

- PHP sözdizimi kontrolü:
  ```bash
  find . -name "*.php" -print -exec php -l {} \;
  ```
- Front-end değişikliklerinde Bootstrap 5 bileşenlerini kullanın, yeni bileşenlerde SweetAlert bildirimlerini unutmayın.

## Güvenlik Tavsiyeleri

- Varsayılan admin parolasını ilk girişte değiştirin.
- Plesk üzerinden HTTPS sertifikası tanımlayın.
- `uploads/` klasörü için maksimum dosya boyutu ve MIME türü kontrolleri uygulanmıştır; yine de WAF kurallarıyla destekleyin.
- Yönetim paneli oturumları tarayıcı kapandıktan sonra süresi dolacak şekilde PHP `session.gc_maxlifetime` değerini ayarlayın.

İhtiyaçlarınıza göre sipariş durum akışlarını veya bildirim ses dosyalarını `config.php` üzerinden özelleştirebilirsiniz.
