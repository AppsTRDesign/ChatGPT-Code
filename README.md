# NoaSoft QR Menü Sistemi

Modern web teknolojileriyle geliştirilmiş, PHP 8 ve MySQL üzerinde çalışan üretim seviyesinde QR menü ve restoran yönetim platformu.

## Özellikler
- SPA mantığıyla çalışan Bootstrap 5 tabanlı admin ve restoran panelleri
- Ajax destekli giriş, kayıt, şifre yönetimi ve oturum açma/kapama
- Restoranlara ve masalara özel dinamik QR kod üretimi (NoaSoft QR API)
- Masa bazlı sipariş akışı, sepet yönetimi ve çok dillilik
- Socket.io ile gerçek zamanlı sipariş, garson çağrısı ve durum bildirimleri (masa ve restoran bazlı)
- Dropzone tarzı yerel dosya yükleme (kategori, ürün, logo, favicon)
- Restoran ayarlarında NoaSoft QR API token, renk/boyut/format ve QR logo yönetimi; masalara otomatik parametreli QR linkleri
- Chart.js ile gruplanmış yığılmış grafikler, MPDF/Dompdf + Anvilco HTML PDF Invoice Template ile kurumsal PDF çıktıları ve Excel (CSV) dışa aktarma
- Garson çağrı yönetimi, masa doluluk takibi ve yazar kasa uyumlu adisyon şablonları
- Restoran bazlı API anahtarı yönetimi ve masa linkleri için hazır entegrasyon dokümantasyonu
- API anahtarı ile korunan REST servisleri, XSS/SQL Injection önlemleri
- Müşteri tarafında anlık döviz kuru desteği ve çoklu para birimi görüntüleme
- Kategori ve menü başlıkları için Bootstrap Icons tabanlı ikon desteği, görsel dropzone alanları
- Masa kartlarında aktif sipariş tutarı, ödeme/hesap kapatma ve detay butonlarıyla canlı masa yönetimi
- Simple-DataTables destekli sipariş geçmişi: arama, sayfalama, tarih filtresi, PDF / CSV dışa aktarma
- Mobilde açılıp kapanabilir admin/restoran yan menüleri ve geliştirilmiş responsive tasarım
- Symfony VarDumper ile debug modunda detaylı hata çıktıları
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
- Composer 2+
- MySQL 8+
- Node.js 16.20.2, npm 8.19.4 (Socket.IO sunucusu için)
- Apache üzerinde `mod_rewrite` desteği

### Adımlar
1. Depoyu sunucunuzun `https://qrmenu.noasoft.org` kök dizinine klonlayın.
2. `sql/schema.sql` dosyasını MySQL veritabanınıza uygulayın.
3. PHP bağımlılıklarını yüklemek için proje kökünde `composer install` çalıştırın. Bu adım Anvilco HTML PDF Invoice Template, MPDF, Dompdf ve Symfony VarDumper kütüphanelerini sisteme kazandırır.
4. `app/config/config.php` içerisinde veritabanı erişim bilgileri, yükleme dizini ve API uçları tanımlıdır. Gerektiğinde güncelleyin.
5. `public/uploads` klasörünün yazılabilir olduğundan emin olun (dropzone yüklemeleri bu dizine yapılır).
6. Node tarafında Socket.IO sunucusunu kurmak için:
   ```bash
   npm install
   npm run start
   ```
   Sertifikalar varsayılan olarak `/usr/local/psa/var/certificates/scfgYrZUm` yolundan okunur. Farklı bir sertifika kullanacaksanız `socket-server.js` içindeki yol değerlerini güncelleyin.
7. Yeni restoran kullanıcısı oluşturulduğunda ilgili restoran, plan ve API anahtarı otomatik atanır.

### Hazır Örnek Veriler ve Giriş Bilgileri
`sql/schema.sql` dosyası aşağıdaki örnek verileri içerir:

| Rol | E-posta | Parola | Açıklama |
| --- | --- | --- | --- |
| Admin | `admin@noasoft.org` | `Admin123!` | Sistem yöneticisi hesabı |
| Restoran Sahibi | `owner@efonur.com` | `Restaurant123!` | "Ef Onur" restoranı ile ilişkilidir |

- Restoran slug: `ef-onur`
- Masa bağlantısı örneği: `https://qrmenu.noasoft.org/menu/ef-onur/table/masa-1?token=efonur-table-1`
- Restoran API anahtarı: `rk_live_demo_f1d4596a6c2c48cbb3c1b7ef3dd7ad68`
- Varsayılan QR ayarları ve logo yolları restoran kaydında hazırdır; dropzone alanlarından güncelleyebilirsiniz.

Ek kullanıcılar veya restoranlar oluşturmak için panel üzerindeki kayıt akışını ya da manuel SQL eklemelerini kullanabilirsiniz.

### Çalıştırma
- Uygulama URL'leri:
  - Giriş: `/`
  - Admin paneli: `/admin`
  - Restoran paneli: `/dashboard`
  - Müşteri menüsü: `/menu/{restoran-slug}` veya masa bazlı `/menu/{restoran-slug}/table/{masa-slug}?token={masa-token}`
- Socket.io istemcisi ve sunucusu `https://qrmenu.noasoft.org:4000` adresini kullanır.
- Restoran panelinden MPDF tabanlı PDF ve Excel (CSV) raporları üretebilir, masa bazlı QR kodları indirebilir, garson çağrılarını yönetebilirsiniz.

## REST API Kullanımı
- Her restoran kullanıcısı için benzersiz bir API anahtarı otomatik üretilir. `/dashboard > Restoran Ayarları > REST API Anahtarı` kartından anahtarı görüntüleyebilir, kopyalayabilir veya yenileyebilirsiniz.
- Tüm HTTP isteklerinde `X-API-KEY` başlığına bu anahtarı ekleyin.
- Masa bağlantıları aynı kartta listelenir. Örnek masa linki: `https://qrmenu.noasoft.org/menu/{slug}/table/{masa-slug}?token={masa-token}`
- Desteklenen uç noktalar:
  - `GET /api/menu?slug={slug}&token={masa-token}` → Menü ve kategoriler
  - `POST /api/menu/order` → Sipariş oluşturma
  - `POST /api/menu/waiter-call` → Garson çağırma
  - `GET /api/menu/order-status?slug={slug}&order_number={sipariş-no}` → Sipariş durumu
  - `GET /api/menu/currency?slug={slug}&to={para-birimi}` → Restoranın varsayılan para birimini hedef kura çevirir
  - `POST /api/menu/receipt?slug={slug}` → `order_number` ve `table_token` ile PDF adisyon indirir
- Örnek cURL istekleri:
  ```bash
  curl -H "X-API-KEY: <ANAHTAR>" "https://qrmenu.noasoft.org/api/menu?slug=williams-cafe&token=<masa-token>"

  curl -X POST -H "Content-Type: application/json" -H "X-API-KEY: <ANAHTAR>" \
    -d '{"slug":"williams-cafe","table_token":"<masa-token>","total_amount":149.50,"items":[{"id":12,"name":"Latte","price":49.83,"quantity":3}]}' \
    https://qrmenu.noasoft.org/api/menu/order

  curl -H "X-API-KEY: <ANAHTAR>" "https://qrmenu.noasoft.org/api/menu/currency?slug=williams-cafe&to=EUR"

  curl -X POST -H "Content-Type: application/json" -H "X-API-KEY: <ANAHTAR>" \
    -d '{"order_number":"123-240101-AB12CD","table_token":"<masa-token>"}' \
    https://qrmenu.noasoft.org/api/menu/receipt?slug=williams-cafe
  ```

## Socket.io Olayları
- `order:new` → Restoran paneli yeni siparişi bildirir, ilgili masa da sesli uyarı alır.
- `order:status` → Sipariş durum değişikliğini masa ve restoran tarafına iletir.
- `table:status` → Masa doluluk değişikliklerini (sipariş, ödeme, manuel işaretleme) yayar.
- `waiter:call` → Masadan gelen garson çağrısını restoran paneline iletir.
- `waiter:update` → Garson çağrı güncellemelerini (tamamlandı vb.) senkronize eder.

## Para Birimi Yönetimi
- Admin panelindeki **Genel Ayarlar** ekranından (`/admin > Ayarlar`) `Desteklenen Para Birimleri` alanına virgülle ayrılmış şekilde
  kodlar (örn. `TRY, USD, EUR`) girin. Bu liste restoranların seçim yapabileceği resmi para birimlerini belirler.
- Restoran panelinde **Ayarlar > Genel Bilgiler** formundaki `Para Birimi` açılır menüsü, admin tarafından tanımlanan bu değerlerle
  doldurulur ve restoranın varsayılan fiyatlandırma para birimini belirler.
- Müşteri menüsündeki para birimi rozeti, admin/restoran kombinasyonundan gelen tüm kodları listeler; seçilen kod için
  `/api/menu/currency` uç noktası tetiklenir ve fiyatlar `app/utils/Currency.php` sınıfının Google / Forbes kaynaklı dönüşüm
  oranlarına göre anlık olarak yeniden hesaplanır.
- Kur bilgisi alınamazsa sistem uyarı verir ve fiyatlar varsayılan para biriminde görüntülenmeye devam eder.


## Gelişmiş Özellikler
- **Masa Yönetimi:** Her masa için benzersiz link ve QR kod; dolu/boş takibi.
- **Sipariş Akışı:** Sipariş durumu (beklemede, hazırlanıyor, hazır, tamamlandı, iptal) ve ödeme yönetimi.
- **Garson Çağrısı:** Masadan tek tıkla garson çağırma ve panelde bildirim.
- **Adisyon PDF'leri:** Hem müşteriler hem de restoranlar için Anvilco HTML PDF Invoice Template + MPDF/Dompdf ile sunucu tarafında üretilen, logo destekli adisyonlar.
- **Çok Dillilik:** TR, EN, AR, FR desteği ve yeni diller için JSON dosyaları.
- **Döviz Desteği:** Admin panelinde tanımlanan tüm para birimlerini müşterilere sunma ve Google/Forbes kaynaklı anlık kur hesaplama.
- **Dropzone Yükleme:** Kategori görselleri, ürün resimleri, logo ve favicon dosyaları için lokal dropzone alanları.

## Geliştirme İpuçları
- Yeni çeviri anahtarları için `public/lang` altındaki JSON dosyalarını güncelleyin.
- Ek API uçları `app/routes/api.php`, panel uçları `app/routes/web.php` içerisinde tanımlanmalıdır.
- `RestaurantController` içerisinde masa, sipariş, rapor ve ayarlar ile ilgili REST işlemleri bulunur.
- PDF çıktıları MPDF öncelikli, Dompdf yedekli şekilde sunucu tarafında üretilir; Excel için CSV tabanlı dışa aktarma kullanılır.

## Test
- PHP sözdizimi kontrolü: `find app -name '*.php' -print0 | xargs -0 -n1 php -l`
- Ana giriş noktası: `php -l index.php`

## Geliştirme Önerileri
- Müşteri tarafında temaya göre özelleştirilebilir renk paletleri eklemek.
- Ürün varyasyonları ve stok takibi gibi gelişmiş POS özellikleri.
- Restoranlar için kampanya ve sadakat modüllerinin entegrasyonu.
- API anahtarıyla üçüncü parti POS sistemleri arasında çift yönlü senkron senaryolarını desteklemek.
