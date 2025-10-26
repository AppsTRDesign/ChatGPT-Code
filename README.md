# WebPush Bildirim Platformu

Plesk + AlmaLinux 8 + PHP 8 ortamlarında çalışan, MySQL veritabanı kullanan modern bir web push yönetim paneli. Script ana dizine kurulacak şekilde tasarlanmıştır (public klasörü gerektirmez) ve admin/client tarafı içerir.

## Özellikler
- ✅ Yönetici girişi ve oturum yönetimi
- ✅ Abone kaydı, segment oluşturma ve kampanya gönderimi
- ✅ OneSignal benzeri arayüz ve turkuaz/mavi renk paleti
- ✅ JSON API ile abone kaydı (client/register)
- ✅ Responsive tasarım ve modern tipografi

## Kurulum
1. Proje dosyalarını `https://webpush.noasoft.org` temel URL'sine karşılık gelen ana dizine yerleştirin.
2. `sql/schema.sql` dosyasını MySQL sunucunuza uygulayın.
3. `config.php` içindeki veritabanı kullanıcı adı/parolasını kendi bilgilerinizle güncelleyin.
4. Web sunucusunda `index.php` dosyasını varsayılan giriş noktası olarak işaretleyin.

### Varsayılan Yönetici Bilgileri
- E-posta: `admin@noasoft.org`
- Şifre: `admin123`

## Kullanım
- Ziyaretçiler ana sayfada demo abonelik oluşturabilir.
- Yönetici paneli üzerinden segment ve kampanyalar yönetilir.
- Kampanya gönderimi simüle edilerek bildirim log tablosuna kaydedilir.

## Gereksinimler
- PHP 8.0+
- MySQL 5.7+ veya MariaDB uyumlu sürüm
- cURL ve JSON uzantıları (varsayılan olarak Plesk/AlmaLinux kurulumlarında bulunur)

## Lisans
Bu proje örnek amaçlıdır. Dilediğiniz gibi özelleştirebilirsiniz.
