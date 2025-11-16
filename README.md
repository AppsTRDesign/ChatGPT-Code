# Telegram Yönetim Aracı

Bu proje, Windows 11 üzerinde Python 3.13 ile uyumlu, Telethon tabanlı çok oturumlu bir Telegram yönetim aracıdır. Uygulama PySide6 ile geliştirilmiş bir masaüstü arayüzü sağlar ve hem Türkçe hem de İngilizce dil desteği sunar.

## Özellikler

- **Çoklu OTP Girişi:** Birden fazla telefon numarası için oturum açma ve oturum dosyalarını `session/` klasöründe saklama.
- **Ban Kontrolü:** Kayıtlı oturumların ban durumunu kontrol etme ve banlı oturumları temizleme.
- **Hedef Gruptan Üye Taraması:** Zaman filtresi ve kota desteği ile çoklu oturuma düşen üye sayısını otomatik bölerek tekrar etmeyen kayıtlar toplama.
- **Hedef Gruba Üye Ekleme:** Kayıtlı kullanıcı listesinden hedef gruba üyeleri eşit bölerek ekleme, isteğe bağlı kullanıcı limiti belirleme ve flood wait yönetimi.
- **Üye Limiti Dağıtımı:** Örneğin 50 kişilik kota girildiğinde seçilen oturumlara otomatik olarak paylaştırılır ve her oturum yalnızca kendi payını işler.
- **Aktif Mesaj Atanları Bulma:** Belirlenen zaman aralığında mesaj atan kullanıcıları, ayarlardaki maksimum mesaj tarama sınırını oturumlara bölerek ve isteğe bağlı kullanıcı adı filtresiyle kayıt altına alma.
- **Üye Araması:** Virgülle ayrılmış birden fazla anahtar kelimeyle public kullanıcı araması yapar, DM skoru ve durumunu çıkartarak sonuçları "Taranan Üyeler" listesine kaydeder; sonuçlar oturumlara dağıtılır ve ilerleme çubukları ile gösterilir.
- **DM Durumu Analizi:** Üyeler taranırken veya aktif mesaj atanlar toplanırken her kullanıcının DM isteği kabul edip etmediği otomatik olarak test edilir; yeni tahmini "DM Skoru"/"DM Tahmini" sütunları ile birlikte CSV/JSON dışa aktarımlarına dahil edilir ve "DM Durumunu Yenile" butonu ile kayıtlı kullanıcılar için güncellenebilir.
- **Kayıtlı Kullanıcılara DM Gönderme:** Yeni DM sekmesi üzerinden her oturum için şablon seçerek veya manuel mesaj/medya girerek kayıtlı üyelere mesaj yollama, {user_name}/{first_name}/{last_name} değişkenlerini otomatik doldurma ve mesaj oranını rate-limit ayarlarına göre yönetme.
- **Şablon Yönetimi Sekmesi:** Ayrı bir "Şablon Yönetimi" sekmesinde oturum bazlı şablonları listeleme, düzenleme, kopyalanabilir {user_name}/{first_name}/{last_name} kısayollarını tek tıkla gövdeye ekleme ve emoji seçicisi ile mesajları zenginleştirme; her oturuma atanmış şablonlar DM sekmesinde özet olarak listelenir.
- **Mesaj Şablonları:** Her oturum için sınırsız şablon tutma, şablonlara medya dosyası/URL iliştirme ve seçilen şablonu ilgili oturuma atama; DM işlemi sırasında atanmış şablon yoksa manuel alanlar kullanılır.
- **Grup/Kanal Taraması ve Kaydı:** "Grup/Kanal Taraması" sekmesi virgülle ayrılmış çoklu anahtar kelimeyi oturumlara paylaştırır, kanal/süper grup/normal grup ve admin filtreleri uygular, tabloya tür (kanal/süper grup/grup) ve çevrim içi sayısı kolonlarını ekler. Sonuçlar `users/groups.json` dosyasında saklanır, tablo sıralanabilir ve içe/dışa aktarılabilir.
- **Grup Görünürlük Analizi:** Taranan her kayıt için herkese açık/özel durumu, mesaj gönderme izni ve üyelerin gizli olup olmadığı raporlanır; tablodaki yeni "Üyeler gizli mi" kolonu bu bilgiyi gösterir ve JSON/CSV dışa aktarımlarına dahil edilir.
- **Grup Katılımı:** Taramadan seçtiğiniz gruplar için "Seçili gruplara katıl" butonu, seçili oturumlara kayıtları eşit paylaştırarak Telegram'a katılma isteği gönderir; rate limit sekmesindeki "Gruba Katılma Bekleme" değeri her denemenin arasındaki beklemeyi yönetir ve başarılı/başarısız sonuçlar tabloda işaretlenir.
- **Grup Mesaj Şablonları:** DM şablonlarına benzer şekilde, her oturum için grup mesaj şablonları oluşturulur; kısayol ve emoji butonları gövdeye içerik ekler, medya URL/dosyaları iliştirilebilir ve şablonlar oturumlara atanabilir.
- **Gruba Mesaj Gönderimi:** Kayıtlı ya da manuel yazılan gruplara seçilen şablon veya manuel gövde/medya ile mesaj gönderilir. Sistem gerekirse gruba katılır, mesaj izni yoksa grubu listeden çıkarır ve rate limit ayarındaki bekleme süresini uygular.
- **Esnek İlerleme ve Kayıt Kontrolleri:** Kullanıcı adı olmayanları hariç tutma, kayıtlı listeleri temizleme ve işlemleri iptal ederek baştan başlatma seçenekleri.
- **CSV Dışa Aktarımı:** Kullanıcı tablolarını UTF-8 BOM'lu, sıralanabilir başlıklara sahip CSV dosyalarına aktararak Türkçe karakter sorunlarını ortadan kaldırır.
- **Seç-Sil/Dışa Aktar:** Kayıtlı kullanıcı ve grup tablolarında satırların başına gelen onay kutuları sayesinde tek tıkla hepsini seçebilir, yalnızca işaretlediğiniz kayıtları JSON/CSV'ye aktarabilir veya kalıcı olarak silebilirsiniz.
- **Rate Limit Yönetimi:** Üye daveti, DM/grup mesajı ve grup katılımı için ayrı gecikme alanlarıyla flood hatalarını azaltabilirsiniz.
- **Maksimum Aktif Mesaj Kotası:** Rate Limit sekmesindeki "Maksimum aktif mesaj taraması" alanı artık milyonlarca değere (2.147.483.647'e kadar) izin verir; aktif mesaj tarama limitini ihtiyaçlarınıza göre yükseltebilirsiniz.
- **Davet Kotası Uyarıları:** Davet sınırı aşıldığında kaç kullanıcı denendiğini ve tahmini bekleme süresini anlık olarak gösterir, yetki eksikliklerini açıkça bildirir.
- **Eklenen Üye Takibi:** Hem grup taramasından hem de aktif mesaj atanlar listesinden eklenen üyeler ayrı JSON dosyalarına kaydedilir ve tablolar üzerinden yönetilir.
- **Dil ve Zaman Dilimi Ayarları:** 30 popüler zaman dilimi ve anlık TR/EN dil değişimi.
- **Kullanıcı Kaydı:** Taranan üyeler `users/scanned_users.json`, aktif mesaj atanlar `users/active_users.json` dosyasında saklanır ve arayüzde tablo olarak gösterilir.
- **Makineye Özel Lisanslama:** Ayarlar sekmesinde cihaz kimliği ve kalan süre görülebilir, lisans anahtarları yalnızca hedef PC'de çalışır.
- **Modern Üst Başlık:** Uygulama penceresinin üst tarafında Telegram benzeri SVG ikon, başlık ve açıklama yer alır ve seçilen dile göre dinamik güncellenir.

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

   Windows dışındaki ortamlarda sanal ortamı etkinleştirdikten sonra `python -m licence.generate_license ...` komutunu çalıştırabilirsiniz. Hazır plan kodları `1m`, `3m` ve `6m` olup, özel sürede yıl/ay/gün değerlerinden en az biri girilmelidir.
3. **Anahtarı Girin:** Kullanıcı, aldığı anahtarı Ayarlar ▸ Lisans Bilgileri alanına yapıştırıp "Lisansı Etkinleştir" butonuna basar. Doğrulama başarılı olursa kalan süre ve plan adı anında güncellenir.
4. **Makineye Kilitli Yapı:** Anahtarlar üretildiği makinenin kimliğiyle imzalandığı için başka bir bilgisayarda çalışmaz. `config/license.json` dosyasını kopyalamak lisansı taşımaya yetmez.

> Lisans süresi dolduğunda veya lisans hiç girilmemişse iş başlatma butonları devre dışı kalır ve kullanıcıya uyarı gösterilir.

## Grup Araçları Kullanımı

1. **Grup/Kanal Taraması:** "Grup/Kanal Taraması" sekmesinde oturum(lar)ı işaretleyin, virgülle ayrılmış anahtar kelimeleri ve her kelime için kaç sonuç alınacağını belirtin. Kanal/süper grup/grup ve "admin olduğum" filtrelerini kullanabilir, sonuçları doğrudan grup/kanal listesine kaydedebilirsiniz.
2. **Grup Şablonları:** "Grup Şablonları" sekmesinde bir oturum seçip şablon adı/gövde/medya alanlarını doldurun. {user_name}, {first_name}, {last_name} kısayol butonları ve emoji seçici gövdeye metin ekler; kaydedilen şablonlar hemen düzenlenebilir veya oturuma varsayılan olarak atanabilir.
3. **Gruba Mesaj Gönderimi:** "Grup Mesaj Gönderimi" sekmesinde oturumları, kayıtlı grupları ve/veya manuel girilen linkleri seçin. Kota girerseniz toplam gruplar eşit bölünür. Mesaj gövdesi/medyası veya atanmış şablonlar kullanılarak gönderim yapılır; istemci gruba katılımı dener, mesaj izni yoksa ilgili kayıt tabloda işaretlenir ve ilerleme barı rate limit ayarlarına göre güncellenir.
4. **Grup Katılımı:** Grup taraması tablosundan istediğiniz satırları işaretleyip "Seçili gruplara katıl" tuşuna bastığınızda seçili oturumlar, ayarlardaki "Gruba Katılma Bekleme" süresine göre katılım isteği gönderir ve başarılı/başarısız durumlar ilerleme alanında gösterilir.

## Veri Kayıt Yapısı

- `session/` klasörü Telethon oturum dosyalarını tutar.
- `users/` klasöründeki `scanned_users.json` ve `active_users.json` dosyaları ilgili sekmelerdeki kullanıcı kayıtlarını saklar. İşlenen kullanıcılar otomatik olarak silinir veya güncellenir.
- `users/scanned_added_users.json` ve `users/active_added_users.json` dosyaları hedef kanala başarıyla eklenen üyeleri kaydeder.
- `users/groups.json` grup taraması sonuçlarını, kaynak bilgisini ve mesaj izin durumunu saklar.
- `config/message_templates.json` her oturuma ait DM şablonlarını ve atanmış varsayılanları saklar.
- `config/group_templates.json` grup mesaj şablonlarını ve oturum bazlı varsayılan atamalarını saklar.
- `config/settings.json` uygulama ayarlarını barındırır.
- `config/license.json` doğrulanmış lisans kaydını tutar.
- `logs/application.log` dosyası hata ve işlem günlüklerini içerir.

## Önemli Notlar

- Telegram API limitleri gereği eş zamanlı işlemler sırasında flood wait (süre bekleme) hataları alınabilir. Rate limit sekmesinden gecikmeleri artırarak bu hataları azaltabilirsiniz.
- Telethon işlemleri için internet bağlantısı gereklidir. Proxy veya VPN kullanıyorsanız Telethon istemcisine uygun ayarları ekleyin.
- Uygulama PySide6 üzerine kurulu olduğundan görsel arayüz, ekran çözünürlüğüne göre ölçeklenir.

## Lisans

Bu proje MIT Lisansı ile lisanslanmıştır.
