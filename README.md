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
- Bot taramasında sol menüdeki işletme kartlarını seçerek puan, adres, telefon, çalışma saatleri ve müşteri yorumlarını toplama
- API ve bot sekmelerinde "Veri Alanları" kutucukları ile isim, adres, telefon, kategori, çalışma saatleri, puan, toplam değerlendirme, paylaşım bağlantısı, web sitesi, varsayılan işletme görseli, kart görseli, galeri fotoğrafları ve videolar gibi alanları isteğe bağlı olarak açıp kapatma (varsayılan olarak yalnızca temel kimlik bilgileri seçilidir)
- "Yorum Fotoğrafları" ve "Yorum Ek Bilgileri" seçeneklerinin yalnızca müşteri yorumları aktifken kullanılabilmesi sayesinde gereksiz verilerin otomatik olarak devre dışı bırakılması
- Ayarlar sekmesinden azami işletme sayısı (varsayılan 9999) ve yorum limiti (varsayılan 9999) değerlerini global olarak değiştirme; API ve bot sekmeleri bu üst değerleri otomatik olarak uygular
- API ve bot sonuçlarında yinelenen müşteri yorumlarını otomatik olarak temizleme
- Yorum kartlarının içindeki "PBK6be" bloklarından gelen "Yiyecek / Hizmet / Atmosfer", "Kişi başı fiyat", "Grup büyüklüğü", "Rezervasyon", "Gürültü seviyesi", "Park yeri" vb. satırları JSON çıktısında `text_extra` alanında ayrı saklama
- Yorum kartlarında yer alan küçük fotoğraf/video kutucuklarının `review_photo_urls` alanında JSON'a eklenmesi
- Bot sekmesinde toplanacak yorum sayısını belirleyip (örn. 10 yorum) Google Haritalar'daki kaydırma alanından otomatik olarak ilgili sayıda yorumu profilleriyle birlikte indirme
- Karttaki kategori bilgisini (ör. "Güzellik Salonu") de dahil ederek her işletmeyi sınıflandırma
- Dil seçiminin yanında `assets/cities.json` dosyasından gelen "Şehir" açılır menüsü ile arama merkezini belirleme; seçilen şehirden alınan enlem/boylam Playwright URL'sine otomatik olarak uygulanır
- "Paylaş" penceresindeki kısa konum bağlantısını sonuçlara ekleme (bilgi mevcutsa)
- "Paylaş" penceresini açıp URL girişini tıklayan, bağlantıyı kopyalayan ve modalı sağ üstteki çarpıdan kapatan otomatik kopyalama rutini
- Harita ekran görüntülerini canlı olarak gösteren ve Playwright tarafından güncellenen yerleşik önizleme paneli
- Her imleç hareketi ve tıklamada harita önizlemesini yenileyen canlı ilerleme akışı
- İşletmelerin adı, adresi, telefonu, çalışma saatleri ve müşteri yorumlarını görüntüleme
- Bot sekmesindeki "Detaylar" panelinde seçili arayüz dilinde günlük satırlarını ve seçilen işletmenin özetini eş zamanlı görüntüleme
- Bot günlüklerinin dili, bot taraması için seçtiğiniz dil ile otomatik olarak senkronize olur
- API veya bot ile alınan verileri JSON, UTF-8 BOM'lu CSV, XLSX (openpyxl) ya da Türkçe karakter uyumlu PDF formatında dışa aktarma
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
- XLSX dışa aktarımı için `openpyxl` (install.bat otomatik yükler)

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
4. "İşletme Sayısı" alanından kaç sonucun getirileceğini belirleyin (varsayılan 5). Bu değer Ayarlar sekmesinde belirlediğiniz üst limitten büyük olamaz.
5. "Veri Alanları" bölümündeki kutucuklarla yalnızca ihtiyaç duyduğunuz alanları seçin. Örneğin sadece isim/adres/telefon yeterliyse puan veya yorum kutularını pasif bırakabilirsiniz. Yorum verisini kapatırsanız ilgili ek seçenekler (fotoğraflar ve ek bilgiler) otomatik olarak devre dışı kalır.
6. "Ara" butonuna tıklayın. Sonuçlar sol taraftaki listede görüntülenecektir. Sütun başlıklarına tıklayarak adı, telefon numarası, puan veya kategoriye göre sıralama yapabilirsiniz.
7. Listeden bir işletme seçtiğinizde, sağ tarafta işletmeye ait ayrıntılar (adres, telefon, çalışma saatleri ve müşteri yorumları) gösterilir.
8. Sonuçları kaydetmek için "JSON Kaydet", "CSV Kaydet", "XLSX Kaydet" veya "PDF Kaydet" butonlarından birine basın. CSV dosyaları Excel ile uyumlu olacak şekilde `UTF-8-SIG` kodlamasıyla, XLSX dosyaları ise `openpyxl` ile oluşturulur; PDF raporları gömülü DejaVu Sans yazı tipi sayesinde Türkçe karakterleri sorunsuz gösterir.

### Bot ile Tara

1. "Arama Sorgusu" alanına taramak istediğiniz anahtar kelimeyi yazın (örn. "kuaför" veya "restoran").
2. "Dil" açılır menüsünden botun açacağı Google Haritalar sayfasının dilini seçin (varsayılan Türkçe).
3. Dil seçiminin yanındaki "Şehir" açılır menüsünden `google_maps_gui/assets/cities.json` dosyasına eklediğiniz şehirlerden birini seçin. Şehir seçerseniz Playwright doğrudan `https://www.google.com/maps/search/<sorgu>/@<lat>,<lng>` adresine gider; seçim yapılmazsa standart arama kutusu kullanılır. Dosya yoksa menü boş kalır.
4. "İşletme Sayısı" alanından kaç sonuç alınacağını belirleyin. Ayarlar sekmesinde belirlediğiniz üst limit, bu değerin maksimumunu belirler.
5. "Yorum Sayısı" alanına her işletme için toplanacak maksimum yorum adedini yazın. Bot, Google Haritalar'daki toplam değerlendirme sayısını aşmadan otomatik olarak kaydırma yapıp bu kadar yorumu çeker. (0 değeri yorum toplamayı devre dışı bırakır.) Yorum veri alanı pasif bırakılırsa bu alan otomatik olarak devre dışı kalır.
6. Veri alanı kutucuklarından (ör. web sitesi, varsayılan işletme görseli, kart görseli, galeri fotoğrafları, videolar, paylaşım bağlantısı) ihtiyacınız olanları açık bırakın; kapalı olan alanlar hem taranmaz hem de çıktı dosyalarına eklenmez. Galeri fotoğrafı/video kutucuklarını seçerseniz varsayılan 5 fotoğraf/5 video sınırı spinbox'lar ile güncellenebilir.
7. "Ara" butonuna bastığınızda Playwright görünür (headful) Chromium penceresini açar; bot yalnızca sol menüdeki `div.Nv2PK THOPZb CpccDe` sınıfı ile başlayan işletme kartlarını yapay bir fare imleciyle izleyip tıklar ve ayrıntı panelinin açılmasını bekler. Chromium penceresi Windows üzerinde ayrı bir uygulama olarak çalışır, ancak ekran görüntüleri uygulama penceresindeki önizleme paneline aktarılır.
8. Bot çalışırken her imleç hareketinde ve kart seçildiğinde harita ekran görüntüleri sekmenin sağ üstündeki "Harita Önizleme" panelinde otomatik olarak yenilenir; alt kısımdaki durum etiketi 0/N biçiminde kaç kartın tıklandığını gösterir.
9. Her işletme açılır açılmaz isim, kategori, adres, telefon, puan, çalışma saatleri, "Paylaş" penceresindeki kısa konum bağlantısı (bot, bağlantı kutusuna tıklayıp "Bağlantıyı kopyala" düğmesini tetikledikten sonra modalı kapatır) ve müşteri yorumları (profil fotoğraflarıyla birlikte) eşzamanlı olarak sonuç listesine eklenir. "Detaylar" paneli, seçtiğiniz arayüz dilinde canlı log satırlarını ve seçili işletmenin özetini göstererek hangi adımda olduğunuzu hissettirir.
10. Bot sonuçlarını JSON, CSV, XLSX veya PDF olarak kaydetmek için ilgili butonları kullanın.

> Bot sekmesinde Playwright tarafından yönetilen Chromium hem ayrı bir pencerede çalışır hem de ekran görüntüleri ile uygulamaya akış sağlar. Tarama tamamlandığında pencere otomatik olarak kapanır; tarama esnasında manuel olarak kapatmayın. Bot yalnızca listede yer alan metin bölgesine tıklar.

> Bot başlatıldığında otomasyon insan benzeri davranmak için Windows 10/Chrome 124 kullanıcı aracısı ve gerçek fare tıklamaları kullanır. Google'ın çerez/onay pencereleri otomatik kapatılamazsa Chromium'da "Kabul et" veya "Accept all" düğmesine manuel olarak basabilirsiniz.


> Hata mesajı aldığınızda ayrıntıları `google_maps_gui.log` dosyasında bulabilirsiniz.

### Ayarlar sekmesi

"Ayarlar" sekmesi, API ve bot sekmelerindeki spinbox'ların uyması gereken üst sınırları tanımlar:

- **Maksimum İşletme Sayısı:** Her iki sekmede de girebileceğiniz en yüksek kart sayısını belirler.
- **Maksimum Yorum Sayısı:** Bot sekmesindeki "Yorum Sayısı" alanının üst limitidir. Yorum veri alanını devre dışı bırakırsanız bu değer otomatik olarak sıfırlanır.

"Ayarları Uygula" düğmesi yeni sınırları kaydeder, spinbox'ların `to` değerlerini günceller ve kısa süreli bilgilendirme mesajı gösterir.

## Çıktı Biçimleri

- **JSON:** Her işletme için telefon, telefon tipi, adres, kategori, çalışma saatleri, puan, "Paylaş" konum bağlantısı, (isteğe bağlı) web sitesi ve işletme görseli ile müşteri yorumları (yorumcunun adı, puanı, zaman damgası, profil fotoğrafı URL'si, varsa `review_photo_urls` listesi ve `text_extra` alanı; örn. "Kişi başı fiyat", "Grup büyüklüğü", "Rezervasyon", "Park yeri" vb.) ayrıntılı şekilde saklanır.
- **CSV:** İşletme başına tek satır olacak şekilde temel bilgiler, kategori, paylaşım bağlantısı ve yorumların özet hâli saklanır. Dosyalar `UTF-8-SIG` kodlamasıyla oluşturulduğu için Excel gibi programlarda Türkçe karakterler bozulmadan görüntülenir.
- **PDF:** "PDF Kaydet" düğmesi `assets/fonts/DejaVuSans.ttf` yazı tipini kullanarak Türkçe karakter destekli rapor üretir; her işletme için özet bilgiler, çalışma saatleri ve en fazla 5 müşteri yorumu sayfaya eklenir.

> Not: Lisans kısıtları nedeniyle `DejaVuSans.ttf` dosyası depoya dahil edilmez. PDF oluşturmak için [DejaVu Sans](https://dejavu-fonts.github.io/) yazı tipini indirip `google_maps_gui/assets/fonts/DejaVuSans.ttf` yoluna yerleştirin.

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
