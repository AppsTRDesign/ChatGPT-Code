# NoaSoft WebPush Platformu

Plesk ve AlmaLinux 8 üzerinde PHP 8 ile çalışacak şekilde tasarlanan AJAX tabanlı web bildirim yönetim paneli.

## Başlangıç

1. Depoyu sunucunuza kopyalayın ve kök dizine kurun.
2. PHP 8, MySQL 8 ve Composer kurulu olmalıdır.
3. `composer dump-autoload` komutu ile sınıf yükleyicisini oluşturun.
4. MySQL veritabanınızı oluşturun ve `database/schema.sql` dosyasını uygulayın.
5. Sunucu yapılandırmasında depo kök dizinini (`webpush.noasoft.org`) web kökü olarak tanımlayın.
6. `config/database.php` dosyasında veritabanı bilgilerini düzenleyin.

## Özellikler

- Yönetici ve müşteri panelleri
- Token tabanlı API ile bildirim gönderimi
- Bootstrap, DataTables, Dropzone ve SweetAlert ile modern arayüz
- 10 adet responsive bildirim şablonu
- Grafik bileşenleri Chart.js ile desteklenmiştir

## Varsayılan Yönetici Bilgileri

- E-posta: `admin@noasoft.org`
- Şifre: `admin`
