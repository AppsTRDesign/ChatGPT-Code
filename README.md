# NoaSoft QR Menü Sistemi

Modern web teknolojileriyle geliştirilmiş, PHP 8 ve MySQL üzerinde çalışan üretim seviyesinde QR menü ve restoran yönetim platformu.

## Özellikler
- SPA mantığıyla çalışan Bootstrap 5 tabanlı admin ve restoran panelleri
- Ajax destekli giriş, kayıt, şifre yönetimi ve oturum açma/kapama
- Restoranlara ve masalara özel dinamik QR kod üretimi (NoaSoft QR API)
- Masa bazlı sipariş akışı, sepet yönetimi ve çok dillilik
- Socket.io ile gerçek zamanlı sipariş, garson çağrısı ve durum bildirimleri (masa ve restoran bazlı)
- Dropzone tarzı yerel dosya yükleme (kategori, ürün, logo, favicon)
- Chart.js raporları, jsPDF ile PDF çıktıları ve Excel (CSV) dışa aktarma
- Garson çağrı yönetimi, masa doluluk takibi ve yazar kasa uyumlu adisyon şablonları
- Restoran bazlı API anahtarı yönetimi ve masa linkleri için hazır entegrasyon dokümantasyonu
- API anahtarı ile korunan REST servisleri, XSS/SQL Injection önlemleri
- MVC dosya yapısı (controllers, models, routes, middlewares, utils, views, public)
- .htaccess ile sef link yapısı ve `https://qrmenu.noasoft.org` kök dizinine kurulum

## Veritabanı Şeması
`sql/schema.sql` dosyasında aşağıdaki tablolar tanımlıdır:
- `users`, `restaurants`, `plans`
- `restaurant_tables` (masa yönetimi)
- `categories`, `products`
- `orders`, `order_status_logs`
- `waiter_calls`
- `settings`

## Kurulum

### Gereksinimler
- PHP 8+
- MySQL 8+
- Node.js 16.20.2, npm 8.19.4 (Socket.IO sunucusu için)
- Apache üzerinde `mod_rewrite` desteği

### Adımlar
1. Depoyu sunucunuzun `https://qrmenu.noasoft.org` kök dizinine klonlayın.
2. `sql/schema.sql` dosyasını MySQL veritabanınıza uygulayın.
3. `app/config/config.php` içerisinde veritabanı erişim bilgileri, yükleme dizini ve API uçları tanımlıdır. Gerektiğinde güncelleyin.
4. `public/uploads` klasörünün yazılabilir olduğundan emin olun (dropzone yüklemeleri bu dizine yapılır).
5. Node tarafında Socket.IO sunucusunu kurmak için:
   ```bash
   npm install
   npm run start
   ```
   Sertifikalar varsayılan olarak `/usr/local/psa/var/certificates/scfgYrZUm` yolundan okunur. Farklı bir sertifika kullanacaksanız `socket-server.js` içindeki yol değerlerini güncelleyin.
6. Admin kullanıcısı oluşturmak için veritabanına bir kayıt ekleyin:
   ```sql
   INSERT INTO users (name, email, password, role, status)
   VALUES ('Admin', 'admin@qrmenu.com', '$2y$10$hashedpassword', 'admin', 'active');
   ```
   `hashedpassword` için `password_hash('admin123', PASSWORD_BCRYPT)` çıktısını kullanabilirsiniz.
7. Yeni restoran kullanıcısı oluşturulduğunda ilgili restoran, plan ve API anahtarı otomatik atanır.

### Çalıştırma
- Uygulama URL'leri:
  - Giriş: `/`
  - Admin paneli: `/admin`
  - Restoran paneli: `/dashboard`
  - Müşteri menüsü: `/menu/{restoran-slug}` veya masa bazlı `/menu/{restoran-slug}/table/{masa-slug}?token={masa-token}`
- Socket.io istemcisi ve sunucusu `https://qrmenu.noasoft.org:4000` adresini kullanır.
- Restoran panelinden PDF (jsPDF) ve Excel (CSV) raporları üretebilir, masa bazlı QR kodları indirebilir, garson çağrılarını yönetebilirsiniz.

## REST API Kullanımı
- Her restoran kullanıcısı için benzersiz bir API anahtarı otomatik üretilir. `/dashboard > Restoran Ayarları > REST API Anahtarı` kartından anahtarı görüntüleyebilir, kopyalayabilir veya yenileyebilirsiniz.
- Tüm HTTP isteklerinde `X-API-KEY` başlığına bu anahtarı ekleyin.
- Masa bağlantıları aynı kartta listelenir. Örnek masa linki: `https://qrmenu.noasoft.org/menu/{slug}/table/{masa-slug}?token={masa-token}`
- Desteklenen uç noktalar:
  - `GET /api/menu?slug={slug}&token={masa-token}` → Menü ve kategoriler
  - `POST /api/menu/order` → Sipariş oluşturma
  - `POST /api/menu/waiter-call` → Garson çağırma
  - `GET /api/menu/order-status?slug={slug}&order_number={sipariş-no}` → Sipariş durumu
- Örnek cURL istekleri:
  ```bash
  curl -H "X-API-KEY: <ANAHTAR>" "https://qrmenu.noasoft.org/api/menu?slug=williams-cafe&token=<masa-token>"

  curl -X POST -H "Content-Type: application/json" -H "X-API-KEY: <ANAHTAR>" \
    -d '{"slug":"williams-cafe","table_token":"<masa-token>","total_amount":149.50,"items":[{"id":12,"name":"Latte","price":49.83,"quantity":3}]}' \
    https://qrmenu.noasoft.org/api/menu/order
  ```


## Gelişmiş Özellikler
- **Masa Yönetimi:** Her masa için benzersiz link ve QR kod; dolu/boş takibi.
- **Sipariş Akışı:** Sipariş durumu (beklemede, hazırlanıyor, hazır, tamamlandı, iptal) ve ödeme yönetimi.
- **Garson Çağrısı:** Masadan tek tıkla garson çağırma ve panelde bildirim.
- **Adisyon PDF'leri:** Hem müşteriler hem de restoranlar için jsPDF ile yazdırılabilir adisyonlar.
- **Çok Dillilik:** TR, EN, AR, FR desteği ve yeni diller için JSON dosyaları.
- **Dropzone Yükleme:** Kategori görselleri, ürün resimleri, logo ve favicon dosyaları için lokal dropzone alanları.

## Geliştirme İpuçları
- Yeni çeviri anahtarları için `public/lang` altındaki JSON dosyalarını güncelleyin.
- Ek API uçları `app/routes/api.php`, panel uçları `app/routes/web.php` içerisinde tanımlanmalıdır.
- `RestaurantController` içerisinde masa, sipariş, rapor ve ayarlar ile ilgili REST işlemleri bulunur.
- PDF çıktıları jsPDF kütüphanesiyle tarayıcı tarafında üretilir, Excel için CSV tabanlı dışa aktarma kullanılır.

## Test
- PHP sözdizimi kontrolü: `find app -name '*.php' -print0 | xargs -0 -n1 php -l`
- Ana giriş noktası: `php -l index.php`

## Geliştirme Önerileri
- Müşteri tarafında temaya göre özelleştirilebilir renk paletleri eklemek.
- Ürün varyasyonları ve stok takibi gibi gelişmiş POS özellikleri.
- Restoranlar için kampanya ve sadakat modüllerinin entegrasyonu.
- API anahtarıyla üçüncü parti POS sistemleri arasında çift yönlü senkron senaryolarını desteklemek.
