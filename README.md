# Dosya Deposu Uygulaması

PHP 8 ile uyumlu bu proje, AlmaLinux 8 / Plesk ortamında çalışacak şekilde tasarlanmış şifre korumalı bir dosya yükleme ve yönetim sistemi sağlar. Sistem kök dizine kurulmak üzere hazırlanmıştır ve tüm URL yönlendirmeleri `.htaccess` ile SEO uyumlu hale getirilmiştir (ör. `https://fileupload.noasoft.org/file/1-ornek-dosya.pdf`).

## Özellikler

- Modern, responsive arayüz (Bootstrap 5).
- Drag & drop destekli dosya yükleme deneyimi.
- Dosyaları `/uploads` dizinine güvenli bir şekilde kaydeder, doğrudan erişim `.htaccess` ile engellenir.
- Yüklenen her dosya için MySQL veritabanında meta bilgiler saklanır.
- Görsel ve PDF dosyaları için tarayıcı içinde önizleme imkanı.
- Yönetim paneli üzerinden dosya listeleme, düzenleme (isim güncelleme) ve silme işlemleri.
- Toplam dosya sayısı ve kullanılan depolama alanı bilgileri.
- CSRF koruması, MIME türü doğrulaması ve 50 MB dosya boyutu sınırı.
- Tüm sistem UTF-8 uyumlu olup PDO ile veritabanına bağlanır.

## Kurulum

1. Depodaki dosyaları sunucunun kök dizinine kopyalayın.
2. `config.php` dosyasındaki veritabanı ve kimlik doğrulama ayarlarını ihtiyacınıza göre güncelleyin.
3. `database.sql` dosyasını MySQL sunucunuza uygulayarak `files` tablosunu oluşturun.
4. `/uploads` dizininin web sunucusu kullanıcısı tarafından yazılabilir olduğundan emin olun.
5. Gerekirse Plesk üzerinden Apache mod_rewrite özelliğinin etkin olduğundan emin olun.

Varsayılan yönetici kullanıcı adı `admin` olarak tanımlanmıştır. Parola hash'i `config.php` içinde yer alır; kendi parolanızı belirleyip `password_hash()` ile oluşturduğunuz değeri güncellemeniz önerilir.

## Önemli Notlar

- Uygulama yalnızca giriş yapmış yöneticilere açıktır; herhangi bir halka açık sayfa bulunmaz.
- İndirme ve önizleme bağlantıları PHP üzerinden servis edilir, böylece erişim kontrolü korunur.
- MIME türü beyaz listesi gerektiğinde `config.php` üzerinden genişletilebilir.

## Geliştirme

- Tüm PHP dosyaları PHP 8 sözdizimine uygundur (ör. `str_starts_with`).
- Ek güvenlik ihtiyaçlarına göre IP kayıtları veya denetimler genişletilebilir.
