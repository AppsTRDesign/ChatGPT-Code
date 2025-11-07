# Telegram Automation Admin Panel

Bu proje, Telegram hesap yönetimi, üye keşfi ve mesajlaşma operasyonlarını tek panelden yönetebilmeniz için hazırlanmış bir PHP 8 tabanlı yönetim panelidir. Admin arayüzü Bootstrap 5 ile hazırlanmış, tamamen AJAX destekli ve Plesk / AlmaLinux 8 ortamıyla uyumlu olacak şekilde tasarlanmıştır.

## Özellikler

- Çoklu Telegram hesabı yönetimi, ban ve oturum durumlarının takibi
- Üye keşfi için detaylı günlükleme ve CSV içe/dışa aktarma desteği
- MadelineProto ile gerçek MTProto oturumu, üye keşfi ve mesaj gönderimi
- Mesaj şablonu oluşturma, medya yükleme (Dropzone destekli) ve gönderim planlama
- Servis durum izleme, servis başlatma/durdurma ve sağlık kontrolleri
- phpseclib tabanlı uzaktan servis kontrolü ve arka plan süreçlerinin yönetimi
- Telegram API kimlik bilgileri, mail ayarları ve rate-limit yapılandırması
- Tamamen AJAX tabanlı formlar, Bootstrap 5 toasts ile durum bildirimleri
- Başarılı işlemler sonrası tabloları otomatik yenileyen AJAX yanıtları ve ayrıntılı hata mesajları
- SEO uyumlu `.htaccess` yönlendirmesi ve mobil uyumlu üst menü
- SVG formatında özel logo ve favicon

## Kurulum

1. Depoyu sunucunuza klonlayın ve kök dizine yerleştirin.
2. PHP 8 ve PDO MySQL eklentilerinin etkin olduğundan emin olun. Geliştirme için SQLite varsayılan olarak kullanılır.
3. Proje, Composer ile yönetilen bağımlılıklar kullanır. Aşağıdaki komutla gerekli paketleri (MadelineProto ve phpseclib dahil) kurun:

   ```bash
   composer install
   ```

4. `config.example.php` dosyasını `config.php` olarak kopyalayın ve veritabanı, base URL, mail, rate-limit ve `remote` bölümündeki SSH bilgilerini (sunucu adı/IP, port, kullanıcı adı ve parola) güncelleyin. Ayrıca Telegram MTProto oturumları için `telegram.api_id` ve `telegram.api_hash` değerlerini Telegram geliştirici panelinden alarak girin. Panelde görüntülenen servis komutları da bu dosyada `services` anahtarında tanımlanır:

   ```bash
   cp config.example.php config.php
   ```

5. Veritabanı tabloları ilk açılışta otomatik olarak oluşturulur ve `admin / admin` bilgileriyle varsayılan bir kullanıcı eklenir.
6. Plesk üzerinde `https://telegrambot.noasoft.org` alan adını projeye yönlendirin ve `.htaccess` dosyasının çalıştığından emin olun.

Varsayılan olarak `services` bölümünde "Telegram Kuyruk İşleyici" kaydı bulunur. Bu servis `php /path/to/project/bin/telegram_worker.php` komutunu çalıştırarak mesaj gönderim kuyruğunu ve davet işlemlerini yürütür. Yönetim panelindeki Servisler tablosu, durum (yeşil = başladı, sarı = uyarı, kırmızı = hata/durdu) ve son heartbeat bilgisini gösterir; ihtiyaç halinde yeni servis tanımları ekleyebilir, komut ve açıklamaları güncelleyebilirsiniz. Panelde "Başlat" düğmesine bastığınızda servis durumu `running` olarak güncellenir ve son heartbeat otomatik işlenir; gerçek servis sürecini kalıcı olarak çalıştırmak için komutu Plesk üzerinden bir arka plan görevi ya da systemd servisi olarak eklemeyi unutmayın.

### Uzaktaki Servis Yönetimi

- `remote_host`, `remote_port`, `remote_username` ve `remote_password` değerlerini hem `config.php` dosyasından hem de yönetim panelindeki **Ayarlar → Sunucu Kontrolü** bölümünden güncelleyebilirsiniz. Panelden yapılan değişiklikler `settings` tablosuna kaydedilir ve phpseclib oturumları için otomatik olarak kullanılır.
- Servisler sayfasındaki **Başlat** düğmesi ilgili komutu `nohup` ile arka plana alır ve PID bilgisini toast mesajında gösterir. **Durdur** düğmesi aynı komutu `pkill -f` ile sonlandırır. Komut alanını güncel tutarak gerekli script veya worker'ları uzaktan yönetebilirsiniz.
- Bağlantı problemi yaşanması hâlinde panel ayrıntılı hata mesajını gösterir; SSH oturumunu doğrulamak için aynı bilgilerle manuel giriş yapmayı deneyebilirsiniz.

## Giriş Bilgileri

- Yönetici: `admin`
- Şifre: `admin`
- Giriş Adresi: `https://telegrambot.noasoft.org/admin/login`

## Geliştirme

- Tüm kaynak kodu `app/` dizini altında PSR-4 standardına göre düzenlenmiştir.
- Yeni bir servis ya da özellik eklerken ilgili Controller, Model ve View dosyalarını oluşturmanız yeterlidir.
- Dropzone entegrasyonu sayesinde mesaj şablonlarına medya ekleyebilirsiniz; yüklenen dosyalar `storage/uploads` altında saklanır.

## Telegram MTProto Kullanımı

- Telegram API işlemleri `danog/madelineproto` paketi üzerinden yürütülür ve `app/Services/TelegramService.php` sınıfında oturum, üye keşfi ve mesaj gönderimi için hazır entegrasyon bulunur.
- Yönetim panelinden bir telefon numarası ekledikten sonra "Kod Gönder" ve "Doğrula" aksiyonlarıyla oturum açabilir, gerekirse 2FA şifresini yine aynı formdan tanımlayabilirsiniz.
- Üye keşfi için "Üyeleri Tara" formundan MTProto oturumu hazır olan hesabı ve taramak istediğiniz kanal/grup kullanıcı adını seçin. Sistem üyeleri doğrudan veritabanına kaydeder.
- Mesaj şablonları ve gönderim planları oluşturulduktan sonra kuyruktaki işler `bin/telegram_worker.php` komut satırı aracı ile (örn. cron üzerinden) işlenir ve hedeflere gerçek Telegram mesajları gönderilir.
- Rate-limit değerleri yönetim panelindeki ayarlardan güncellenebilir; MTProto servisleri varsayılan olarak keşif için 1500ms, mesaj gönderimi için 1200ms gecikme uygular.

## Notlar

- Rate limit değerleri yönetim panelinden güncellenebilir ve sistem genelinde kullanılmak üzere `settings` tablosunda saklanır.
- Mail gönderimleri için PHPMailer kullanılacak şekilde altyapı hazırdır; SMTP bilgilerinizi ayarladıktan sonra `AuthController` içerisindeki ilgili alanı genişletebilirsiniz.

## Lisans

Bu proje ticari kullanım için tasarlanmıştır. NoaSoft ihtiyaçlarına göre özelleştirilebilir.
