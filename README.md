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
- **Davet Kotası Uyarıları:** Davet sınırı aşıldığında kaç kullanıcı denendiğini ve tahmini bekleme süresini anlık olarak gösterir, yetki eksikliklerini açıkça bildirir.
- **Eklenen Üye Takibi:** Hem grup taramasından hem de aktif mesaj atanlar listesinden eklenen üyeler ayrı JSON dosyalarına kaydedilir ve tablolar üzerinden yönetilir.
- **Dil ve Zaman Dilimi Ayarları:** 30 popüler zaman dilimi ve anlık TR/EN dil değişimi.
- **Kullanıcı Kaydı:** Taranan üyeler `users/scanned_users.json`, aktif mesaj atanlar `users/active_users.json` dosyasında saklanır ve arayüzde tablo olarak gösterilir.
- **Makineye Özel Lisanslama:** Ayarlar sekmesinde cihaz kimliği ve kalan süre görülebilir, lisans anahtarları yalnızca hedef PC'de çalışır.

## Kurulum Adımları (Windows 11)

1. **Install.bat ile Otomatik Kurulum (Önerilir)**
   - Depoyu açtıktan sonra `install.bat` dosyasını çalıştırın. Betik `[1/4]` ile başlayan mesajlarla her adımı gösterir ve sırasıyla sanal ortamı (`venv/`) oluşturur, etkinleştirir, bağımlılıkları yükler ve Playwright Chromium bileşenini indirir.
   - Script `py -3.11 -m venv venv` komutunu çalıştırır. Sisteminizde 3.11 kuruluysa doğrudan kullanılacaktır; alternatif sürümlere ihtiyaç duyuyorsanız komutu düzenleyebilirsiniz.
   - Pip yükseltme ve `requirements.txt` yüklemesi, akabinde `python -m playwright install chromium` çalıştırılır. Tüm adımlar tamamlandığında "Kurulum tamamlandi" mesajı görünür ve pencere kapanmadan önce `pause` ile bekler.

2. **El ile Kurulum (Alternatif)**
   1. [python.org](https://www.python.org/downloads/) adresinden Python 3.13 kurun ve "Add Python to PATH" seçeneğini işaretleyin.
   2. Depoyu klonlayın veya ZIP olarak indirip açın:
      ```powershell
      git clone https://github.com/<kullanici>/telegram-yonetim-araci.git
      cd telegram-yonetim-araci
      ```
   3. Sanal ortam oluşturup etkinleştirin:
      ```powershell
      py -m venv venv
      .\venv\Scripts\activate
      ```
   4. Bağımlılıkları yükleyin:
      ```powershell
      py -m pip install -r requirements.txt
      ```
   5. Playwright Chromium bileşenini yükleyin:
      ```powershell
      python -m playwright install chromium
      ```

3. **Uygulamayı Başlatın**
   ```powershell
   py -m app.main
   ```

> `run.bat` dosyası `venv` klasörünü etkinleştirip aynı komutu otomatik olarak çalıştırır.

## İlk Çalıştırma

1. Uygulama açıldığında varsayılan dil Türkçe olacaktır. Ayarlar sekmesinden dil ve zaman dilimi değiştirilebilir.
2. `API ID` ve `API Hash` değerlerinizi [my.telegram.org](https://my.telegram.org) üzerinden alın ve Oturumlar sekmesinde ilgili alanlara girin.
3. Telefon numaranızı yazıp "OTP Girişi" butonuna basın. Telegram'dan gelen kodu girerek oturumu onaylayın. Oturum dosyaları `session/` klasörüne kaydedilir.
4. Ban kontrolü, üye tarama, üye ekleme ve aktif mesaj tarama işlemleri için ilgili sekmelerde oturum seçip hedef grup bilgilerini girin.

## Lisanslama Sistemi

Uygulama yalnızca lisanslandığı bilgisayarda çalışacak şekilde tasarlanmıştır. Lisans verisi `config/license.json` dosyasında saklanır ve Ayarlar ▸ "Lisans Bilgileri" bölümünde kalan süre, plan adı ve bitiş tarihiyle birlikte gösterilir.

1. **Makine Kimliğini Kopyalayın:** Ayarlar sekmesindeki "Makine Kimliği" alanını kopyalayarak lisans sağlayıcınıza gönderin.
2. **Anahtar Üretin:** Lisansı dağıtan kişi hazır planları veya özel süreleri kullanabilir:
   ```powershell
   # Hazır plan (1, 3 veya 6 ay)
   .\generate_license.bat --machine <MAKINE_ID> --plan 3m

   # Özel süre (örn. 1 yıl 2 ay 10 gün)
   .\generate_license.bat --machine <MAKINE_ID> --years 1 --months 2 --days 10 --label "Kurumsal 1Y2A10G"
   ```
   > `generate_license.bat` sanal ortamı (`venv`) kullanır; önce `install.bat` çalıştırılmış olmalıdır. Komutu hiçbir parametre olmadan çalıştırırsanız makine ID'si ve süre bilgisi etkileşimli olarak sorulur.

   Windows dışındaki ortamlarda sanal ortamı etkinleştirdikten sonra `python -m licence.license_tool ...` komutunu çalıştırabilirsiniz. Hazır plan kodları `1m`, `3m` ve `6m` olup, özel sürede yıl/ay/gün değerlerinden en az biri girilmelidir.
3. **Anahtarı Girin:** Kullanıcı, aldığı anahtarı Ayarlar ▸ Lisans Bilgileri alanına yapıştırıp "Lisansı Etkinleştir" butonuna basar. Doğrulama başarılı olursa kalan süre ve plan adı anında güncellenir.
4. **Makineye Kilitli Yapı:** Anahtarlar üretildiği makinenin kimliğiyle imzalandığı için başka bir bilgisayarda çalışmaz. `config/license.json` dosyasını kopyalamak lisansı taşımaya yetmez.

> Lisans süresi dolduğunda veya lisans hiç girilmemişse iş başlatma butonları devre dışı kalır ve kullanıcıya uyarı gösterilir.

## Veri Kayıt Yapısı

- `session/` klasörü Telethon oturum dosyalarını tutar.
- `users/` klasöründeki `scanned_users.json` ve `active_users.json` dosyaları ilgili sekmelerdeki kullanıcı kayıtlarını saklar. İşlenen kullanıcılar otomatik olarak silinir veya güncellenir.
- `users/scanned_added_users.json` ve `users/active_added_users.json` dosyaları hedef kanala başarıyla eklenen üyeleri kaydeder.
- `config/settings.json` uygulama ayarlarını barındırır.
- `config/license.json` doğrulanmış lisans kaydını tutar.
- `logs/application.log` dosyası hata ve işlem günlüklerini içerir.

## Önemli Notlar

- Telegram API limitleri gereği eş zamanlı işlemler sırasında flood wait (süre bekleme) hataları alınabilir. Rate limit sekmesinden gecikmeleri artırarak bu hataları azaltabilirsiniz.
- Telethon işlemleri için internet bağlantısı gereklidir. Proxy veya VPN kullanıyorsanız Telethon istemcisine uygun ayarları ekleyin.
- Uygulama PySide6 üzerine kurulu olduğundan görsel arayüz, ekran çözünürlüğüne göre ölçeklenir.

## Lisans

Bu proje MIT Lisansı ile lisanslanmıştır.
