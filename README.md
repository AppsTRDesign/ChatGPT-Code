[b]NoaSoft QR Menü Platformu[/b]

[quote]PHP 8 + PDO, Node.js 16.20, Socket.IO ve MySQL üzerinde çalışan modern, ajax tabanlı restoran yönetim ve QR menü çözümü.[/quote]

[b]Sunucu Gereksinimleri[/b]
[list]
[*]PHP 8 (pdo_mysql etkin)
[*]Node.js 16.20 ve npm 8
[*]MySQL 5.7/8.x
[*]cURL, mbstring ve GD kütüphaneleri
[/list]

[b]Öne Çıkan Özellikler[/b]
[list]
[*][b]Yönetim Paneli:[/b] Gerçek zamanlı sipariş/garson kartları, masa QR yönetimi, varyasyonlu ürün/kategori editörü, günlük menü slider yönetimi ve gelişmiş rapor dışa aktarmaları (PDF, Excel, yazar kasa fişi).
[*][b]QR & Marka Ayarları:[/b] qrcode.noasoft.org API ile logo_url destekli masa QR’ları, Dropzone önizlemeleri, otomatik QR yenileme ve tema bazlı mobil tasarım.
[*][b]Çoklu Dil & Para Birimi:[/b] JSON tabanlı dil CRUD, para birimi ekleme/silme/varsayılan yapma, anlık currencyConverter destekli fiyat çevirileri ve menüde simge bazlı para formatlama.
[*][b]Bildirimler & Sesler:[/b] HTTPS Socket.IO (4000) üzerinden anlık sipariş/garson push’ları, özelleştirilebilir bildirim ses dosyaları, mobil menüde canlı durum ve sesli uyarılar.
[*][b]İletişim & Mail:[/b] PHP mail() ayar paneli, iletişim formu üzerinden bildirim e-postası gönderimi, şifre sıfırlama maili ve yönetici hesap yönetimi.
[*][b]Müşteri Menüsü:[/b] Günün menüsü slider’ı, alt sekmeli navigasyon (Ana sayfa / Günün / Sipariş / Kategori / İletişim / Sepet), varyasyon modalları, mobil kart sepeti ve sipariş takibi.
[/list]

[b]Kurulum[/b]
[code]
composer install
npm install
[/code]

[b]Veritabanı[/b]
[list]
[*]`config/config.php` dosyasında bağlantı bilgilerini düzenleyin.
[*]`database/schema.sql` içeriğini MySQL sunucunuza uygulayın (tüm tablolar + demo veriler).
[*]Uygulamayı kök dizinden (public klasörü olmadan) yayınlayın.
[/list]

[b]Varsayılan Yönetici Girişi[/b]
[code]
E-posta : admin@noasoft.com
Şifre   : admin123
[/code]

[b]Socket.IO Sunucusu[/b]
[code]
npm run start
[/code]

[b]Önemli Notlar[/b]
[list]
[*]Tüm AJAX istekleri `/api` altındaki PHP uçlarından sağlanır; başarı/hata bildirimleri SweetAlert2 üzerinden yapılır.
[*]Dropzone bileşenleri `data-dropzone` özelliği ile otomatik yapılandırılır ve yüklenen dosyalar `storage/uploads/` altında tutulur.
[*]PDF ve yazar kasa fişleri için dompdf/dompdf ve mpdf/mpdf paketleri kurulu olup Türkçe karakter desteği sağlanmıştır.
[*]Gerçek zamanlı bildirimler için Node.js tarafında TLS sertifika yollarını `node/socket/server.js` içerisinden güncellemeyi unutmayın.
[/list]
