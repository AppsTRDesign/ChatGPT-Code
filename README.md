# QR Menü Sistemi

Plesk / AlmaLinux 8 ortamında PHP 8 ile uyumlu çalışan, mobil uyumlu QR menü ve yönetim paneli örneği.

## Özellikler

### Müşteri Arayüzü
- 📱 Masaya özel QR kodu ile temassız menü erişimi
- 🧭 Kategori kısayolları ve modern, responsive tasarım
- ⚡ 5 dakikalık akıllı cache ile hızlı menü yükleme
- 🍽️ Ürün görselleri, açıklamalar ve fiyatlar
- 🛒 Sepet yönetimi ve masaya özel anlık sipariş verme
- 🔔 Garson çağırma butonu (yönetim paneline anlık bildirim)
- 📡 Sipariş durumu ekranı ile canlı takip (otomatik yenileme)

### Yönetim Paneli
- 📊 Dashboard: toplam/bekleyen/hazırlanan/tamamlanan sipariş istatistikleri
- 📂 Sürükle-bırak kategori sıralama ve düzenleme
- 🧾 Ürün ekleme, düzenleme ve silme araçları
- 🖼️ Logo, banner ve hoş geldiniz mesajı özelleştirmesi
- 🪑 Masa yönetimi, tek tıkla yeni QR kod üretimi
- 📦 Gerçek zamanlı sipariş listesi ve durum güncelleme
- 🔔 Garson çağrılarını anlık görme ve kapatma (sesli uyarı)

## Kurulum

1. Depoyu sunucunuza kopyalayın ve web sunucusunun kök dizinine yerleştirin.
2. PHP 8 ile uyumlu olduğundan emin olun. Gerekli dizinlerin yazma izni olduğundan emin olmak için:

   ```bash
   chmod -R 775 storage cache
   ```

3. `config.php` dosyasında yönetici şifresini güncelleyin.
4. Plesk üzerinde iki sanal dizin tanımlayarak müşteri arayüzünü `/public`, yönetim panelini `/admin` üzerinden yayınlayabilirsiniz.
5. Her masa için yönetim panelindeki QR kodları yazdırın veya dijital olarak paylaşın.

## Veri Yapısı

- `storage/menu.json`: Kategoriler, ürünler ve marka ayarları.
- `storage/tables.json`: Masalar ve QR token bilgileri.
- `storage/orders.json`: Anlık siparişler.
- `storage/waiter_calls.json`: Garson çağrı kayıtları.

Tüm veriler JSON dosyalarında saklandığı için Plesk ortamında ek veritabanı gerektirmez. İhtiyaç halinde farklı bir veri kaynağına uyarlamak için `lib` içerisindeki servis sınıfları düzenlenebilir.

## Geliştirme

- PHP kodları için sözdizimi kontrolü: `find . -name "*.php" -print -exec php -l {} \;`
- Ön yüzde Bootstrap 5 ve Bootstrap Icons CDN üzerinden kullanılmaktadır.
- Yönetim panelinde sürükle-bırak işlemleri için `SortableJS` kütüphanesi kullanılmaktadır.

## Güvenlik Notları

- Varsayılan yönetici şifresini üretim öncesi değiştirin.
- Plesk üzerinden HTTPS kullanarak müşteri ve yönetici trafiğini şifreleyin.
- Panelde oturum açıldıktan sonra `Logout` butonu ile oturumu kapatın.
