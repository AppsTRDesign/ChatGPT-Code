# NoaSoft QR Menü Sistemi

Modern web teknolojileriyle geliştirilmiş, PHP 8 ve MySQL üzerinde çalışan uçtan uca QR menü ve restoran yönetim platformu.

## Özellikler
- SPA mantığıyla çalışan Bootstrap 5 tabanlı yönetim panelleri
- Ajax üzerinden giriş, kayıt, şifre sıfırlama (şu an login/register) işlemleri
- Restoranlara özel dinamik QR kod üretimi (NoaSoft QR API kullanımı)
- SweetAlert bildirimleri, Chart.js istatistik grafikleri
- Socket.io ile gerçek zamanlı sipariş bildirimleri
- Dropzone tarzı yerel depolama kullanan resim yükleme servisi
- Çoklu dil desteği (TR, EN, AR, FR)
- XSS ve SQL Injection'a karşı korumalı prepared statement yapısı
- MVC yapısı (controllers, models, routes, middlewares, utils, views, public)
- Sef URL desteği (.htaccess) ve API key korumalı REST servisleri

## Kurulum

### Gereksinimler
- PHP 8+
- MySQL 8+
- Composer (opsiyonel)
- Node.js 18+

### Adımlar
1. Depoyu sunucunuzun `https://qrmenu.noasoft.org` kök dizinine klonlayın.
2. `sql/schema.sql` dosyasındaki tabloları MySQL veritabanınıza uygulayın.
3. Sunucu ortam değişkenlerini ayarlayın:
   ```bash
   export DB_HOST=localhost
   export DB_NAME=qrmenu
   export DB_USER=db_user
   export DB_PASS=db_pass
   ```
4. `app/config/config.php` dosyasındaki `jwt_secret` değerini güncelleyin ve `upload` bölümünde yerel klasör yolunu ihtiyaçlarınıza göre düzenleyin.
5. `public/uploads` klasörünün yazılabilir olduğundan emin olun (varsayılan yapılandırma bu dizini kullanır).
6. Socket sunucusunu ayağa kaldırmak için:
   ```bash
   npm install
   npm run start
   ```
7. Apache üzerinde `mod_rewrite` aktif olmalı ve `.htaccess` dosyası kullanılmalıdır.
8. Admin kullanıcısı oluşturmak için veritabanına manuel kayıt ekleyin:
   ```sql
   INSERT INTO users (name, email, password, role, status) VALUES ('Admin', 'admin@qrmenu.com', '$2y$10$hashedpassword', 'admin', 'active');
   ```
   `password_hash('admin123', PASSWORD_BCRYPT)` çıktısını kullanabilirsiniz.
9. Restoran kullanıcısı kaydı sonrası otomatik olarak restoran ve plan bilgisi oluşturulur.
10. REST API erişimi için kullanıcıya `api_key` üretmek üzere `ApiKey` modelindeki `generateForUser` metodu kullanılmalıdır.

### Çalıştırma
- Ana uygulama PHP-FPM/Apache üzerinden hizmet verir; giriş sayfası `/`, admin paneli `/admin`, restoran paneli `/dashboard`, müşteri menüsü `/menu/{restoran-slug}` adreslerindedir.
- Socket.io sunucusu sipariş bildirimleri için `http://127.0.0.1:4000` adresini kullanır.

### Güvenlik
- Tüm girişler `Security::sanitize` ile temizlenir ve veritabanı işlemlerinde prepared statement kullanılır.
- REST API uçları `X-API-KEY` başlığı ile korunur.
- Session tabanlı yetkilendirme kullanılır.

### Test ve Geliştirme
- Frontend Ajax çağrıları JSON yanıtları bekler; hata durumlarında SweetAlert bilgilendirmesi gösterilir.
- Chart.js ve Socket.io gibi bağımlılıklar CDN üzerinden sağlanır.

## Dizin Yapısı
```
app/
  config/
  controllers/
  middlewares/
  models/
  routes/
  utils/
public/
  css/
  js/
sql/
views/
```

## Lisans
MIT
