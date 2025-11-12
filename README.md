# Google Harita İşletme Arama Aracı

Bu proje, Google Haritalar'dan işletme bilgilerini (isim, adres, telefon, çalışma saatleri ve müşteri yorumları) almak için iki dilli (Türkçe/İngilizce) bir masaüstü arayüzü sunar. Uygulama Windows üzerinde Python 3.11 kullanılarak çalışacak şekilde tasarlanmıştır ve Google Places API'yi kullanır.

## Özellikler

- Türkçe ve İngilizce arayüz desteği
- Google Places API Text Search ve Details uç noktaları ile veri çekme
- İşletmelerin adı, adresi, telefonu, çalışma saatleri ve müşteri yorumlarını görüntüleme
- İnternet hataları ve API hataları için kullanıcı dostu uyarılar

## Gereksinimler

- Windows 10/11
- [Python 3.11](https://www.python.org/downloads/windows/)
- Aktif bir Google Cloud hesabı ve **Places API** etkinleştirilmiş bir proje
- Google Cloud Console üzerinden oluşturulmuş bir API anahtarı

## Google Places API Anahtarının Alınması

1. [Google Cloud Console](https://console.cloud.google.com/) adresine gidin ve hesabınızla giriş yapın.
2. Yeni bir proje oluşturun veya mevcut bir projeyi seçin.
3. "API ve Hizmetler" > "Kitaplık" menüsünden **Places API** ve **Maps JavaScript API** servislerini etkinleştirin.
4. "API ve Hizmetler" > "Kimlik Bilgileri" bölümünden **API anahtarı** oluşturun.
5. Güvenlik için anahtarınızı sadece gerekli domain/IP adreslerine kısıtlamayı unutmayın.

## Kurulum

Projeyi klonladıktan veya indirdikten sonra aşağıdaki adımları izleyin:

1. `install.bat` dosyasını çift tıklayarak veya komut satırından çalıştırın. Betik, `venv` klasöründe bir sanal ortam oluşturur ve gerekli paketleri yükler.

```bat
install.bat
```

2. Kurulum tamamlandığında `run.bat` dosyasını çalıştırarak uygulamayı başlatın.

```bat
run.bat
```

> Not: İlk çalıştırmada Windows SmartScreen tarafından uyarı alırsanız "Daha fazla bilgi" > "Yine de çalıştır" seçenekleri ile devam edebilirsiniz.

## Kullanım

1. Uygulama açıldığında "API Anahtarı" alanına Google Cloud'dan aldığınız anahtarı girin.
2. "Arama Sorgusu" alanına aramak istediğiniz işletme türünü (ör. "kafe", "diş hekimi" vb.) yazın.
3. Sonuçları belirli bir bölgeye daraltmak isterseniz "Konum" alanına şehir/bölge bilgisini ekleyin.
4. Sağ üstteki açılır menüden arayüz dilini (Türkçe veya İngilizce) seçin.
5. "Ara" butonuna tıklayın. Sonuçlar sol taraftaki listede görüntülenecektir.
6. Listeden bir işletme seçtiğinizde, sağ tarafta işletmeye ait ayrıntılar (adres, telefon, çalışma saatleri ve müşteri yorumları) gösterilir.

## Sık Karşılaşılan Sorular

### Sonuç alamıyorum, neden?
- API anahtarınızın yetkileri doğru ayarlanmış mı kontrol edin.
- Günlük kota sınırlarınızı aşmadığınızdan emin olun.
- Arama sorgunuz çok dar veya yanlış olabilir; konum bilgisini genişletmeyi deneyin.

### Müşteri yorumları eksik görünüyor, normal mi?
- Google Places API, her istekte en fazla 5 yorumu döndürür. Daha fazlası için işlevsel bir çözüm yoktur.

### API kullanım maliyeti var mı?
- Google, Places API için sınırlı ücretsiz kota sunsa da yoğun kullanım ücretlendirmeye tabidir. Ayrıntılar için [fiyatlandırma sayfasına](https://developers.google.com/maps/billing) göz atın.

## Geliştirme

Sanal ortamı manuel olarak etkinleştirmek isterseniz:

```bat
venv\Scripts\activate
python -m google_maps_gui.app
```

Kod katkısında bulunmadan önce `requests` paketinin güncel olduğundan emin olun ve yeni özellikler eklerken hem Türkçe hem de İngilizce çevirilerini eklemeyi unutmayın.

## Lisans

Bu proje MIT lisansı ile lisanslanmıştır. Ayrıntılar için `LICENSE` dosyasına göz atın (varsa) veya proje sahibiyle iletişime geçin.
