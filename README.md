# Telegram Automation Admin Panel

Bu proje, Telegram hesap yönetimi, üye keşfi ve mesajlaşma operasyonlarını tek panelden yönetebilmeniz için hazırlanmış bir PHP 8 tabanlı yönetim panelidir. Admin arayüzü Bootstrap 5 ile hazırlanmış, tamamen AJAX destekli ve Plesk / AlmaLinux 8 ortamıyla uyumlu olacak şekilde tasarlanmıştır.

## Özellikler

- Çoklu Telegram hesabı yönetimi, ban ve oturum durumlarının takibi
- Üye keşfi için detaylı günlükleme ve CSV içe/dışa aktarma desteği
- MadelineProto ile gerçek MTProto oturumu, üye keşfi ve mesaj gönderimi
- Mesaj şablonu oluşturma, medya yükleme (Dropzone destekli) ve gönderim planlama
- Servis durum izleme, servis başlatma/durdurma ve sağlık kontrolleri
- Telegram API kimlik bilgileri, mail ayarları ve rate-limit yapılandırması
- Tamamen AJAX tabanlı formlar, Bootstrap 5 toasts ile durum bildirimleri
- SEO uyumlu `.htaccess` yönlendirmesi ve mobil uyumlu üst menü
- SVG formatında özel logo ve favicon

## Kurulum

1. Depoyu sunucunuza klonlayın ve kök dizine yerleştirin.
2. PHP 8 ve PDO MySQL eklentilerinin etkin olduğundan emin olun. Geliştirme için SQLite varsayılan olarak kullanılır.
3. Proje, Composer ile yönetilen bağımlılıklar kullanır. Aşağıdaki komutla gerekli paketleri (MadelineProto dahil) kurun:

   ```bash
   composer install
   ```

4. `.env.example` dosyasını `.env` olarak kopyalayın ve veritabanı ile diğer ayarları güncelleyin.
5. Veritabanı tabloları ilk açılışta otomatik olarak oluşturulur ve `admin / admin` bilgileriyle varsayılan bir kullanıcı eklenir.
6. Plesk üzerinde `https://telegrambot.noasoft.org` alan adını projeye yönlendirin ve `.htaccess` dosyasının çalıştığından emin olun.

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
