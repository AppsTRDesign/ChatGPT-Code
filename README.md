Bu proje, Google Haritalar'dan işletme bilgilerini (isim, adres, telefon, çalışma saatleri ve müşteri yorumları) almak için iki
dilli (Türkçe/İngilizce) bir masaüstü arayüzü sunar. Uygulama Windows üzerinde Python 3.11 kullanılarak çalışacak şekilde
tasarlandı ve hem Google Places API'yi hem de Playwright tabanlı yerleşik bir bot tarayıcısını destekler. Taramaları başlatmadan
önce Lisans sekmesinden bilgisayarınıza özel, gün/ay/yıl kombinasyonuyla üretilmiş lisans anahtarını girmeniz gerekir.

## Özellikler

- Türkçe ve İngilizce arayüz desteği
- Google Places API Text Search ve Details uç noktaları ile veri çekme
- Playwright + Windows modunda görünen Chromium ile Google Haritalar üzerinden bot ile tarama
- Google Maps esintili başlık, SVG pin logosu ve sabit panellerle profesyonel masaüstü görünümü
- Bot taraması sırasında Google Haritalar üzerindeki sonuç kartlarını taklit edilmiş fare hareketleriyle seçme
- Bot taramasında sol menüdeki işletme kartlarını seçerek puan, adres, telefon, çalışma saatleri, yorumlar ve "Hakkında" sekmesindeki olanakları toplama
- API ve bot sonuçlarında yinelenen müşteri yorumlarını otomatik olarak temizleme
- Yorum içeriklerinin sonunda yer alan "Yiyecek / Hizmet / Atmosfer" gibi satırları JSON çıktısında `text_extra` alanında ayrı saklama
- Bot sekmesinde toplanacak yorum sayısını belirleyip (örn. 10 yorum) Google Haritalar'daki kaydırma alanından otomatik olarak ilgili sayıda yorumu profilleriyle birlikte indirme
- Karttaki kategori bilgisini (ör. "Güzellik Salonu") de dahil ederek her işletmeyi sınıflandırma
- İşletme kartlarının kapak görsellerini JSON'a ekleme ve isteğe bağlı olarak galeri fotoğraflarını (1-50 arası) kaydetme
- Google Haritalar'ın bildirdiği ücret aralığını ve "Paylaş" penceresindeki kısa konum bağlantısını sonuçlara ekleme (bilgi mevcutsa)
- Harita ekran görüntülerini canlı olarak gösteren ve Playwright tarafından güncellenen yerleşik önizleme paneli
- Her imleç hareketi ve tıklamada harita önizlemesini yenileyen canlı ilerleme akışı
- İşletmelerin adı, adresi, telefonu, çalışma saatleri ve müşteri yorumlarını görüntüleme
- Bot sekmesindeki "Detaylar" panelinde seçili arayüz dilinde günlük satırlarını ve seçilen işletmenin özetini eş zamanlı görüntüleme
- Bot günlüklerinin dili, bot taraması için seçtiğiniz dil ile otomatik olarak senkronize olur
- API veya bot ile alınan verileri JSON ya da CSV formatında dışa aktarma
- Sonuç tablolarında sütun başlıklarına tıklayarak alfabetik veya puan bazlı sıralama yapabilme
- İnternet ve API hataları için kullanıcı dostu uyarılar
- Her iki sekmede de taranacak işletme sayısını belirleyebilme
- Bot durum çubuğunda 0/N biçiminde kaç kart tıklandığını gösteren gerçek zamanlı sayaç
- Olası hataları `google_maps_gui.log` dosyasına kaydetme
- Makine kimliğine bağlı esnek gün/ay/yıl lisans planlarını yöneten yerleşik Lisans sekmesi ve kalan gün göstergesi

## Gereksinimler

- Windows 10/11
- [Python 3.11](https://www.python.org/downloads/windows/)
- Aktif bir Google Cloud hesabı ve **Places API** etkinleştirilmiş bir proje
- Google Cloud Console üzerinden oluşturulmuş bir API anahtarı

> Not: Playwright taraması, gerekli Chromium sürümünü `playwright install chromium` komutu ile indirir. `install.bat` betiği bu adımı otomatik olarak gerçekleştirir; manuel kurulum yapıyorsanız aynı komutu çalıştırmayı unutmayın. Bot sekmesi gerçek bir Chromium penceresini Windows modunda açtığı için bu kurulum tamamlanmadan tarama başlatılamaz.

## Google Places API Anahtarının Alınması

1. [Google Cloud Console](https://console.cloud.google.com/) adresine gidin ve hesabınızla giriş yapın.
2. Yeni bir proje oluşturun veya mevcut bir projeyi seçin.
3. "API ve Hizmetler" > "Kitaplık" menüsünden **Places API** ve **Maps JavaScript API** servislerini etkinleştirin.
4. "API ve Hizmetler" > "Kimlik Bilgileri" bölümünden **API anahtarı** oluşturun.
5. Güvenlik için anahtarınızı sadece gerekli domain/IP adreslerine kısıtlamayı unutmayın.

## Kurulum

Projeyi klonladıktan veya indirdikten sonra aşağıdaki adımları izleyin:

1. `install.bat` dosyasını çift tıklayarak veya komut satırından çalıştırın. Betik, `venv` klasöründe bir sanal ortam oluşturur
   ve gerekli paketleri yükler.

   ```bat
   install.bat
   ```

2. Kurulum tamamlandığında `run.bat` dosyasını çalıştırarak uygulamayı başlatın.

   ```bat
   run.bat
   ```

> Not: İlk çalıştırmada Windows SmartScreen tarafından uyarı alırsanız "Daha fazla bilgi" > "Yine de çalıştır" seçenekleri ile
> devam edebilirsiniz.

## Lisans Aktivasyonu

Uygulama başlatıldığında önce **Lisans** sekmesi açılır ve geçerli lisans olmadan "Ara" butonları pasif kalır. Yetkilendirme adımları:


1. Lisans sekmesindeki **Makine Kimliği** alanı her bilgisayar için benzersizdir. `Kimliği Kopyala` düğmesi ile değeri panoya alıp lisans sağlayıcınıza iletin.
2. Sağlayıcı, `license_tool.bat` (veya `python -m licence.license_tool`) aracını kullanarak istediğiniz yıl/ay/gün kombinasyonunu girer ve `MAPS-<Y>Y-<M>M-<D>D-XXXXXXXXXXXX` biçiminde bir anahtar üretir. Anahtar yalnızca gönderdiğiniz makine kimliğiyle eşleşir.
3. **Lisans Anahtarı** alanına verilen değeri yapıştırıp **Lisansı Etkinleştir** düğmesine basın. Süre bilgisi anahtarın içinde taşındığı için ek bir menü seçimine gerek yoktur; Lisans sekmesi kalan günü, bitiş tarihini ve plan özetini otomatik olarak gösterir.
4. Başarılı aktivasyon sonrası `google_maps_gui\license.json` dosyası oluşturulur; dosya silinmediği sürece kalan gün ve bitiş tarihi Lisans sekmesinde görüntülenir.
5. Lisans makine kimliğine bağlı olduğu için farklı bir bilgisayarda kullanmak isterseniz yeni bir anahtar talep etmeniz gerekir.

> Lisans üretme aracı: Yetkili kişiler, depo kökündeki `license_tool.bat` dosyasını çalıştırarak makine kimliğine bağlı anahtarlar üretebilir. Örnek kullanım:

```bat
license_tool.bat --years 0 --months 3 --days 0 --machine-id "MAKINEID123456"
```

`--machine-id` parametresi girilmezse araç çalıştırıldığı bilgisayarın makine kimliğini kullanır. Aynı komut satırını `python -m licence.license_tool ...` şeklinde de çalıştırabilirsiniz.

> Not: Lisansınızın süresi dolduğunda bot ve API taraması yeniden pasif hâle gelir; yeni bir anahtar girdikten sonra aynı sekmeden hızlıca yeniden etkinleştirebilirsiniz.

## Kullanım

Uygulama iki sekmeden oluşur: **API ile Tara** ve **Bot ile Tara**.

### API ile Tara

1. "API Anahtarı" alanına Google Cloud'dan aldığınız anahtarı girin.
2. "Arama Sorgusu" alanına aramak istediğiniz işletme türünü (ör. "kafe", "diş hekimi" vb.) yazın. Belirli bir bölge hedefliyorsanız şehir veya semt adını sorguya ekleyebilirsiniz (ör. "Ankara kuaför").
3. Sağdaki açılır menüden arayüz dilini (Türkçe veya İngilizce) seçin. Dil seçimi aynı zamanda API'den dönen verilerin dilini de etkiler.
4. "İşletme Sayısı" alanından kaç sonucun getirileceğini belirleyin (varsayılan 5).
5. "Ara" butonuna tıklayın. Sonuçlar sol taraftaki listede görüntülenecektir. Sütun başlıklarına tıklayarak adı, telefon numarası, puan veya kategoriye göre sıralama yapabilirsiniz.
6. Listeden bir işletme seçtiğinizde, sağ tarafta işletmeye ait ayrıntılar (adres, telefon, çalışma saatleri ve müşteri yorumları) gösterilir.
7. Sonuçları kaydetmek için "JSON Kaydet" veya "CSV Kaydet" butonlarından birine basın.

### Bot ile Tara

1. "Arama Sorgusu" alanına taramak istediğiniz anahtar kelimeyi yazın (örn. "İstanbul kuaför"). Konum bilgisini sorgu metnine eklemek yeterlidir.
2. "Dil" açılır menüsünden botun açacağı Google Haritalar sayfasının dilini seçin (varsayılan Türkçe).
3. "İşletme Sayısı" alanından kaç sonuç alınacağını belirleyin.
4. "Yorum Sayısı" alanına her işletme için toplanacak maksimum yorum adedini yazın. Bot, Google Haritalar'daki toplam değerlendirme sayısını aşmadan otomatik olarak kaydırma yapıp bu kadar yorumu çeker. (0 değeri yorum toplamayı devre dışı bırakır.)
5. Galeri görsellerine ihtiyaç duyuyorsanız "Galeri Görsellerini Kaydet" kutusunu işaretleyin; açılır pencere, her işletmeden kaç fotoğraf alınacağını sorar (1-50). Bu seçenek devre dışıysa yalnızca kapak görseli kaydedilir.
6. "Ara" butonuna bastığınızda Playwright görünür (headful) Chromium penceresini açar; bot yalnızca sol menüdeki `div.Nv2PK THOPZb CpccDe` sınıfı ile başlayan işletme kartlarını yapay bir fare imleciyle izleyip tıklar ve ayrıntı panelinin açılmasını bekler. Chromium penceresi Windows üzerinde ayrı bir uygulama olarak çalışır, ancak ekran görüntüleri uygulama penceresindeki önizleme paneline aktarılır.
6. Bot çalışırken her imleç hareketinde ve kart seçildiğinde harita ekran görüntüleri sekmenin sağ üstündeki "Harita Önizleme" panelinde otomatik olarak yenilenir; alt kısımdaki durum etiketi 0/N biçiminde kaç kartın tıklandığını gösterir.
7. Her işletme açılır açılmaz isim, kategori, adres, telefon, puan, çalışma saatleri, ücret bilgisi (Google bu veriyi sağlıyorsa), "Paylaş" penceresindeki kısa konum bağlantısı, yorumlar (profil fotoğraflarıyla birlikte), kapak görseli, "Hakkında" verileri ve (aktif ise) galeri fotoğrafları eşzamanlı olarak sonuç listesine eklenir. "Detaylar" paneli, seçtiğiniz arayüz dilinde canlı log satırlarını ve seçili işletmenin özetini göstererek hangi adımda olduğunuzu hissettirir.
8. Bot sonuçlarını JSON veya CSV olarak kaydetmek için ilgili butonları kullanın.

> Bot sekmesinde Playwright tarafından yönetilen Chromium hem ayrı bir pencerede çalışır hem de ekran görüntüleri ile uygulamaya akış sağlar. Tarama tamamlandığında pencere otomatik olarak kapanır; tarama esnasında manuel olarak kapatmayın. Bot, karttaki fotoğraf galerisine girmemek için yalnızca listede yer alan metin bölgesine tıklar.

> Bot başlatıldığında otomasyon insan benzeri davranmak için Windows 10/Chrome 124 kullanıcı aracısı ve gerçek fare tıklamaları kullanır. Google'ın çerez/onay pencereleri otomatik kapatılamazsa Chromium'da "Kabul et" veya "Accept all" düğmesine manuel olarak basabilirsiniz.

> Bot, fotoğraf galerisi gibi açılır pencereleri otomatik kapatarak veri toplamaya devam eder.

> Hata mesajı aldığınızda ayrıntıları `google_maps_gui.log` dosyasında bulabilirsiniz.

## Çıktı Biçimleri

- **JSON:** Her işletme için telefon, adres, kategori, çalışma saatleri, puan, ücret bilgisi, "Paylaş" konum bağlantısı, "Hakkında" sekmesi verileri, müşteri yorumları (yorumcunun adı, puanı, zaman damgası, profil fotoğrafı URL'si ve varsa `text_extra` alanı) ile hem kapak görseli hem de isteğe bağlı galeri fotoğrafları ayrıntılı şekilde saklanır.
- **CSV:** İşletme başına tek satır olacak şekilde temel bilgiler, kategori, "Hakkında" sekmesi bilgileri, ücret bilgisi, paylaşım bağlantısı, kapak görseli/görsel listesi ve yorumların özet hali saklanır.

## Sık Karşılaşılan Sorular

### Sonuç alamıyorum, neden?
- API anahtarınızın yetkileri doğru ayarlanmış mı kontrol edin.
- Günlük kota sınırlarınızı aşmadığınızdan emin olun.
- Arama sorgunuz çok dar veya yanlış olabilir; konum bilgisini genişletmeyi deneyin.
- Bot taraması için Playwright'ın ihtiyaç duyduğu Chromium'un kurulu olduğundan (install.bat bunu otomatik yapar) ve internet erişiminizin bulunduğundan emin olun.

### Müşteri yorumları eksik görünüyor, normal mi?
- Google Places API, her istekte en fazla 5 yorumu döndürür.
- Bot sekmesindeki "Yorum Sayısı" alanına daha yüksek bir değer yazdığınızdan emin olun. Bot, Google Haritalar sonuç panelini otomatik kaydırarak belirtilen kadar yorumu toplar; ancak işletmenin toplam değerlendirme sayısı bu değerden düşükse mevcut yorum kadar veri gelir.

### Bot çalışmıyor, ne yapmalıyım?
- `install.bat` sonrasında `playwright install chromium` adımının başarıyla tamamlandığını ve güvenlik yazılımlarının botu engellemediğini kontrol edin.
- Güvenlik yazılımları otomatik tarayıcı açılmasını engelleyebilir; gerekirse geçici olarak izin verin.
- İnternet bağlantınızı ve Google'ın otomasyon kısıtlamalarını kontrol edin.

### API kullanım maliyeti var mı?
- Google, Places API için sınırlı ücretsiz kota sunsa da yoğun kullanım ücretlendirmeye tabidir. Ayrıntılar için [fiyatlandırma sayfasına](https://developers.google.com/maps/billing) göz atın.

## Geliştirme

Sanal ortamı manuel olarak etkinleştirmek isterseniz:

```bat
venv\Scripts\activate
python -m playwright install chromium
python -m google_maps_gui.app
```

### Seçici test modu

Google Haritalar arayüzünde yeni seçiciler denerken uygulamada kullanılan aynı kullanıcı aracısı ve yerel ayarlarla Chromium açmak için kök dizindeki `maps_dev_launcher.py` betiğini çalıştırabilirsiniz:

```bat
python maps_dev_launcher.py "istanbul kuaför" --language tr
```

Betik, üretim botuyla aynı komut satırı parametreleriyle görünür bir Chromium penceresi açar ve isteğe bağlı arama sorgusunu girdikten sonra pencereyi açık bırakır. İşiniz bittiğinde terminale dönüp Enter'a basmanız yeterlidir.

Kod katkısında bulunmadan önce `requirements.txt` dosyasındaki paketlerin güncel olduğundan emin olun ve yeni özellikler eklerken hem Türkçe hem de İngilizce çevirilerini eklemeyi unutmayın.

## Lisans

Bu proje MIT lisansı ile lisanslanmıştır. Ayrıntılar için `LICENSE` dosyasına göz atın (varsa) veya proje sahibiyle iletişime geçin.
