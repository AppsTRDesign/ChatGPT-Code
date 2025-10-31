# NoaSoft QR Menü Platformu

Modern restoranlar için tasarlanmış ajax tabanlı QR menü ve restoran yönetim platformu. Sistem PHP 8 + PDO, Node.js 16.20 ve Socket.IO kullanarak gerçek zamanlı sipariş, garson çağrısı ve bildirim altyapısı sunar.

## Özellikler

- Yönetim paneli: Sipariş durumu, masa yönetimi, garson çağrıları ve gelişmiş raporlama ekranları.
- Müşteri menüsü: Mobil uyumlu kart tasarımları, kategori bazlı ürün gösterimi, sepet sistemi ve anlık bildirimler.
- QR kod yönetimi: qrcode.noasoft.org API entegrasyonu ile masa bazlı QR üretimi ve Dropzone tabanlı logo yükleme.
- Çoklu dil ve para birimi: JSON tabanlı dil paketleri ve canlı para birimi dönüştürme yardımcıları.
- Doküman çıktıları: Dompdf ve mPDF ile Türkçe karakter desteği olan PDF/adisyon çıktıları.
- Gerçek zamanlı katman: HTTPS + Socket.IO sunucusu (port 4000) ile garson çağrısı ve sipariş durum güncellemeleri.

## Kurulum

```bash
composer install
npm install
```

### PHP Uygulaması

1. `config/config.php` dosyasında veritabanı bilgilerini güncelleyin.
2. `database/schema.sql` dosyasındaki tabloları MySQL sunucunuza uygulayın.
3. Web sunucunuzu projenin kök dizinine yönlendirin (`public` alt dizini yoktur).

### Node.js Socket Sunucusu

```bash
npm run start
```

Sunucu varsayılan olarak 4000 portunda TLS ile çalışır. Sertifika yollarını `node/socket/server.js` dosyasından güncelleyin.

## Geliştirme Notları

- Tüm AJAX istekleri `/api` altındaki uç noktalara yönlendirilmiştir.
- SweetAlert2 bildirimleri için `assets/js/admin.js` ve `assets/js/menu.js` dosyaları örnek kullanım içerir.
- PDF/adisyon çıktıları için `App\Services\ReportExportService` sınıfı kullanılabilir.
- Dil paketleri `languages/` klasöründe JSON formatındadır; yeni dosyalar otomatik olarak ayarlar ekranına eklenir.
- Dropzone bileşenleri `data-dropzone` özelliği üzerinden otomatik başlatılır.
