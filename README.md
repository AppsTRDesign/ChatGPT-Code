# Telegram Yönetim Aracı

Bu proje, Windows 11 üzerinde Python 3.13 ile uyumlu, Telethon tabanlı çok oturumlu bir Telegram yönetim aracıdır. Uygulama PySide6 ile geliştirilmiş bir masaüstü arayüzü sağlar ve hem Türkçe hem de İngilizce dil desteği sunar.

## Özellikler

- **Çoklu OTP Girişi:** Birden fazla telefon numarası için oturum açma ve oturum dosyalarını `session/` klasöründe saklama.
- **Ban Kontrolü:** Kayıtlı oturumların ban durumunu kontrol etme ve banlı oturumları temizleme.
- **Hedef Gruptan Üye Taraması:** Zaman filtresi ve kota desteği ile çoklu oturuma düşen üye sayısını otomatik bölerek tekrar etmeyen kayıtlar toplama.
- **Hedef Gruba Üye Ekleme:** Kayıtlı kullanıcı listesinden hedef gruba üyeleri eşit bölerek ekleme, ilerleme çubukları ve flood wait yönetimi.
- **Aktif Mesaj Atanları Bulma:** Belirlenen zaman aralığında mesaj atan kullanıcıları, ayarlardaki maksimum mesaj tarama sınırını oturumlara bölerek ve isteğe bağlı kullanıcı adı filtresiyle kayıt altına alma.
- **Esnek İlerleme ve Kayıt Kontrolleri:** Kullanıcı adı olmayanları hariç tutma, kayıtlı listeleri temizleme ve işlemleri iptal ederek baştan başlatma seçenekleri.
- **Rate Limit Yönetimi:** Flood hatalarını azaltmak için ayarlanabilir süreler.
- **Dil ve Zaman Dilimi Ayarları:** 30 popüler zaman dilimi ve anlık TR/EN dil değişimi.
- **Kullanıcı Kaydı:** Taranan üyeler `users/scanned_users.json`, aktif mesaj atanlar `users/active_users.json` dosyasında saklanır ve arayüzde tablo olarak gösterilir.

## Kurulum Adımları (Windows 11)

1. **Python 3.13 Kurulumu**
   - [python.org](https://www.python.org/downloads/) adresinden Windows için Python 3.13 indirin ve kurulum sırasında "Add Python to PATH" seçeneğini işaretleyin.

2. **Depoyu Klonlayın veya İndirin**
   ```powershell
   git clone https://github.com/<kullanici>/telegram-yonetim-araci.git
   cd telegram-yonetim-araci
   ```
   Git kullanmak istemiyorsanız proje dosyalarını ZIP olarak indirip açabilirsiniz.

3. **Sanal Ortam Oluşturun**
   ```powershell
   py -m venv .venv
   .\.venv\Scripts\activate
   ```

4. **Bağımlılıkları Yükleyin**
   ```powershell
   py -m pip install -r requirements.txt
   ```

5. **Uygulamayı Başlatın**
   ```powershell
   py -m app.main
   ```

> Alternatif olarak `install.bat` ve `run.bat` dosyalarını çalıştırarak kurulum ve başlatma adımlarını otomatikleştirebilirsiniz.

## İlk Çalıştırma

1. Uygulama açıldığında varsayılan dil Türkçe olacaktır. Ayarlar sekmesinden dil ve zaman dilimi değiştirilebilir.
2. `API ID` ve `API Hash` değerlerinizi [my.telegram.org](https://my.telegram.org) üzerinden alın ve Oturumlar sekmesinde ilgili alanlara girin.
3. Telefon numaranızı yazıp "OTP Girişi" butonuna basın. Telegram'dan gelen kodu girerek oturumu onaylayın. Oturum dosyaları `session/` klasörüne kaydedilir.
4. Ban kontrolü, üye tarama, üye ekleme ve aktif mesaj tarama işlemleri için ilgili sekmelerde oturum seçip hedef grup bilgilerini girin.

## Veri Kayıt Yapısı

- `session/` klasörü Telethon oturum dosyalarını tutar.
- `users/` klasöründeki `scanned_users.json` ve `active_users.json` dosyaları ilgili sekmelerdeki kullanıcı kayıtlarını saklar. İşlenen kullanıcılar otomatik olarak silinir veya güncellenir.
- `config/settings.json` uygulama ayarlarını barındırır.
- `logs/application.log` dosyası hata ve işlem günlüklerini içerir.

## Önemli Notlar

- Telegram API limitleri gereği eş zamanlı işlemler sırasında flood wait (süre bekleme) hataları alınabilir. Rate limit sekmesinden gecikmeleri artırarak bu hataları azaltabilirsiniz.
- Telethon işlemleri için internet bağlantısı gereklidir. Proxy veya VPN kullanıyorsanız Telethon istemcisine uygun ayarları ekleyin.
- Uygulama PySide6 üzerine kurulu olduğundan görsel arayüz, ekran çözünürlüğüne göre ölçeklenir.

## Lisans

Bu proje MIT Lisansı ile lisanslanmıştır.
