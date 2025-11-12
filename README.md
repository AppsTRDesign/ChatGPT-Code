Bu proje, Google Haritalar'dan işletme bilgilerini (isim, adres, telefon, çalışma saatleri ve müşteri yorumları) almak için iki
dilli (Türkçe/İngilizce) bir masaüstü arayüzü sunar. Uygulama Windows üzerinde Python 3.11 kullanılarak çalışacak şekilde
tasarlandı ve hem Google Places API'yi hem de Selenium tabanlı bir bot tarayıcısını destekler.

## Özellikler

- Türkçe ve İngilizce arayüz desteği
- Google Places API Text Search ve Details uç noktaları ile veri çekme
- Selenium + Google Chrome kullanarak canlı Google Haritalar üzerinden bot ile tarama
- İşletmelerin adı, adresi, telefonu, çalışma saatleri ve müşteri yorumlarını görüntüleme
- API veya bot ile alınan verileri JSON ya da CSV formatında dışa aktarma
- İnternet ve API hataları için kullanıcı dostu uyarılar

## Gereksinimler

- Windows 10/11
- [Python 3.11](https://www.python.org/downloads/windows/)
- [Google Chrome](https://www.google.com/chrome/) tarayıcısı (bot taraması için)
- Aktif bir Google Cloud hesabı ve **Places API** etkinleştirilmiş bir proje
- Google Cloud Console üzerinden oluşturulmuş bir API anahtarı

> Not: Selenium botu varsayılan olarak `webdriver-manager` aracılığıyla ChromeDriver indirir. İnternet erişiminiz yoksa
> sürücüyü manuel indirip `PATH` değişkenine eklemeniz gerekir.

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

## Kullanım

Uygulama iki sekmeden oluşur: **API ile Tara** ve **Bot ile Tara**.

### API ile Tara

1. "API Anahtarı" alanına Google Cloud'dan aldığınız anahtarı girin.
2. "Arama Sorgusu" alanına aramak istediğiniz işletme türünü (ör. "kafe", "diş hekimi" vb.) yazın.
3. Sonuçları belirli bir bölgeye daraltmak isterseniz "Konum" alanına şehir/bölge bilgisini ekleyin.
4. Sağdaki açılır menüden arayüz dilini (Türkçe veya İngilizce) seçin. Dil seçimi aynı zamanda API'den dönen verilerin dilini de etkiler.
5. "Ara" butonuna tıklayın. Sonuçlar sol taraftaki listede görüntülenecektir.
6. Listeden bir işletme seçtiğinizde, sağ tarafta işletmeye ait ayrıntılar (adres, telefon, çalışma saatleri ve müşteri yorumları) gösterilir.
7. Sonuçları kaydetmek için "JSON Kaydet" veya "CSV Kaydet" butonlarından birine basın.

### Bot ile Tara

1. "Arama Sorgusu" alanına taramak istediğiniz anahtar kelimeyi (ör. "İstanbul restoran") yazın.
2. İsteğe bağlı olarak "Konum" alanına ek bir bölge veya şehir bilgisi girin. Sorgu, Google Haritalar arama çubuğunda birleştirilerek kullanılır.
3. "Dil" açılır menüsünden botun açacağı Google Haritalar sayfasının dilini seçin (varsayılan Türkçe).
4. "Ara" butonuna bastığınızda Google Chrome açılır ve bot aramayı gerçek zamanlı olarak gerçekleştirir. Lütfen tarama sırasında tarayıcıyı kapatmayın.
5. Bulunan işletmeler sol taraftaki listede, ayrıntıları ise sağ panelde görüntülenir.
6. Bot sonuçlarını JSON veya CSV olarak kaydetmek için ilgili butonları kullanın.

> Bot sekmesinde Selenium taraması sırasında tarayıcı açık kalır. İşlem tamamlandıktan sonra otomatik olarak kapatılır.

## Çıktı Biçimleri

- **JSON:** Her işletme için telefon, adres, çalışma saatleri ve müşteri yorumları ayrıntılı şekilde saklanır.
- **CSV:** İşletme başına tek satır olacak şekilde temel bilgiler ile yorumların özet hali saklanır.

## Sık Karşılaşılan Sorular

### Sonuç alamıyorum, neden?
- API anahtarınızın yetkileri doğru ayarlanmış mı kontrol edin.
- Günlük kota sınırlarınızı aşmadığınızdan emin olun.
- Arama sorgunuz çok dar veya yanlış olabilir; konum bilgisini genişletmeyi deneyin.
- Bot taraması için Chrome'un güncel olduğundan ve internet erişiminizin bulunduğundan emin olun.

### Müşteri yorumları eksik görünüyor, normal mi?
- Google Places API, her istekte en fazla 5 yorumu döndürür.
- Bot taraması sırasında Google Haritalar sayfası yeterli yorumu göstermeyebilir; daha fazla yorum için tarayıcıdaki "Daha fazla yorum" bağlantısını manuel açmayı deneyebilirsiniz.

### Bot çalışmıyor, ne yapmalıyım?
- Chrome tarayıcısının kurulu olduğundan emin olun.
- Güvenlik yazılımları otomatik tarayıcı açılmasını engelleyebilir; gerekirse geçici olarak izin verin.
- İnternet bağlantınızı ve Google'ın otomasyon kısıtlamalarını kontrol edin.

### API kullanım maliyeti var mı?
- Google, Places API için sınırlı ücretsiz kota sunsa da yoğun kullanım ücretlendirmeye tabidir. Ayrıntılar için [fiyatlandırma sayfasına](https://developers.google.com/maps/billing) göz atın.

## Geliştirme

Sanal ortamı manuel olarak etkinleştirmek isterseniz:

```bat
venv\Scripts\activate
python -m google_maps_gui.app
```

Kod katkısında bulunmadan önce `requirements.txt` dosyasındaki paketlerin güncel olduğundan emin olun ve yeni özellikler eklerken hem Türkçe hem de İngilizce çevirilerini eklemeyi unutmayın.

## Lisans

Bu proje MIT lisansı ile lisanslanmıştır. Ayrıntılar için `LICENSE` dosyasına göz atın (varsa) veya proje sahibiyle iletişime geçin.
