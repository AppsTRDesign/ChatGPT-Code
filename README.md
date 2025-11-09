# Telegram Otomasyon Paketi

Bu proje PySide6 ve Telethon kullanılarak geliştirilmiş, Türkçe ve İngilizce arasında anlık geçiş yapabilen bir masaüstü uygulamasıdır. Windows 11 üzerinde Python **3.13** ile çalışacak şekilde hazırlanmıştır ve aşağıdaki otomasyon araçlarını içerir:

- Birden fazla numara için OTP ile oturum açma, `session/` klasöründe oturum dosyalarının saklanması ve aktif oturum listesinin canlı olarak güncellenmesi.
- Kayıtlı oturumlar için ban kontrolü yapma ve banlı oturumları otomatik silme.
- Anahtar kelimeler, görünürlük filtresi ve sayfalama desteğiyle gelişmiş grup taraması. Sonuçlar tabloda sıralanabilir, kopyalanabilir ve `groups/` klasörüne kaydedilebilir.
- Anahtar kelimelerle genel üye araması. Kullanıcı adı olan/olmayan filtreleri, sayfalama ve kopyalanabilir tablo desteği bulunur; sonuçlar `users/` klasörüne kaydedilir.
- Hedef gruptaki üyeleri; dakika/saat/gün bazında aktiflik koşullarına göre tarama. Sonuçlar ad/kullanıcı adı/durum kolonlarıyla tabloda listelenir, oturumlar hedefe gerekirse otomatik katılır ve flood beklemeleri sayaçla gösterilir.
- Hedef grupta mesaj atan üyeleri toplama. Oturum bazlı yük dağıtımı, sıralanabilir tablo, ilerleme çubukları ve flood bekleme yönetimi dahildir.
- Toplu üye daveti. Başarılı eklenen üyeler `users/` dosyasından silinir; “zaten ekli” kullanıcılar da otomatik temizlenir.
- `users/` dosyalarındaki üyelere toplu DM gönderme. Görev, seçilen oturumlara eşit bölünür ve her oturum için progress bar ile flood geri sayımı gösterilir.
- `groups/` dosyalarındaki gruplara mesaj yayını. İşlem başlamadan önce her oturum ilgili gruplara katılır, katılma ve gönderim aşamaları ayrı ayrı izlenir.
- Flood ve kısıtlamaları en aza indirmek için DM, grup mesajı ve gruba katılım gecikmeleri varsayılan olarak ayarlanır; bu değerler ayarlar sekmesinden değiştirilebilir.
- Sistem saat dilimi otomatik algılanır; 30 popüler saat dilimi arasından seçim yaparak tüm süre hesaplarını özelleştirebilirsiniz.
- Telethon tarafından döndürülen hata mesajları, seçili dile göre günlüklerde ve uyarılarda otomatik çevrilir.

## Proje Dizini

```
app/
  gui.py              # PySide6 arayüz bileşenleri
  telethon_manager.py # Telethon işlemlerini yöneten yardımcılar
  translations.py     # TR/EN metinleri ve çeviri yardımcıları
main.py               # Uygulamanın giriş noktası
requirements.txt      # Bağımlılıklar
session/              # OTP ile alınan .session dosyaları
users/                # Tarama sonuçları ve DM listeleri
groups/               # Grup tarama sonuçları
config.json           # API bilgileri, hız limitleri ve seçili saat dilimi (çalışma anında oluşur)
```

## Gerekli Kurulumlar

1. [python.org](https://www.python.org/downloads/windows/) adresinden **Python 3.13** kurun ve kurulum sırasında “Add Python to PATH” seçeneğini işaretleyin.
2. PySide6 için gerekli olan Microsoft Visual C++ Redistributable paketini yükleyin: [aka.ms/vs/17/release/vc_redist.x64.exe](https://aka.ms/vs/17/release/vc_redist.x64.exe).
3. (Opsiyonel) Git kurmak için [git-scm.com](https://git-scm.com/downloads) adresini kullanın.
4. <https://my.telegram.org> üzerinden bir Telegram uygulaması oluşturup **API ID** ve **API Hash** değerlerini alın.

## Kurulum Adımları

PowerShell’i açıp şu komutları çalıştırın:

```powershell
git clone https://github.com/kullanici/telegram-otomasyon.git
cd telegram-otomasyon
py -3.13 -m venv .venv
.\.venv\Scripts\Activate.ps1
pip install --upgrade pip
pip install -r requirements.txt
```

Gerekirse depo adresini kendi ortamınıza göre değiştirin.

## Uygulamayı Çalıştırma

```powershell
py -3.13 main.py
```

İlk açılışta Telegram API kimlik bilgileri istenir ve `config.json` dosyasına kaydedilir. Aynı dosyada hız limitleri de saklanır.

## Sekmeler ve Özellikler

### Oturumlar
- Telefon numarasını girip **Kod Gönder** düğmesiyle OTP isteyin.
- Kod (ve gerekirse 2FA şifresi) girildiğinde `.session` dosyası oluşturulur ve liste anında yenilenir.

### Ban Kontrolü
- Kayıtlı tüm oturumları taramak için **Ban Kontrolü** düğmesine basın.
- Banlı oturumlar silinir ve sonuçlar günlük bölümüne yazılır.

### Grup Taraması
- Bir veya daha fazla anahtar kelime girin, görünürlük filtresini seçin (**Hepsi / Public / Private**).
- Birden fazla oturum seçebilirsiniz; her oturum için ayrı ilerleme çubuğu bulunur.
- Tablo sonuçları sıralanabilir ve seçili satırlar kopyalanabilir (Ctrl+C).
- **Kaydet** düğmesiyle sonuçlar `groups/` klasörüne JSON olarak yazılır.

### Üye Taraması
- Anahtar kelimeler ile global üye araması yapın.
- Kullanıcı adı olan/olmayan filtreleri, limit ve sayfalama desteği mevcuttur.
- Sonuçlar tabloya akar, kopyalanabilir ve `users/` klasörüne kaydedilebilir.

### Gruptan Üye Tara
- Hedef grup/kanal bağlantısını girin, dakika/saat/gün bazında aktiflik filtresi belirleyin.
- Oturumlar gruba katılmamışsa otomatik katılır, durum çubuğu “Katılıyor” olarak güncellenir.
- Bot hesaplar ve yönetici/kurucu roller otomatik olarak hariç tutulur.
- Taranan üyeler tabloya düşer; ad, kullanıcı adı/ID ve durum sütunlarına göre sıralama ve kopyalama desteklenir.
- Flood bekleme süreleri sayaç ile gösterilir.

### Aktif Mesajcılar
- Belirli bir zaman aralığında mesaj atan üyeleri toplar.
- Sonuçlar kullanıcı adı/ID, durum ve son mesaj tarihi içeren tabloda gösterilir; sütunlar ad, kullanıcı adı ve duruma göre sıralanabilir.
- Otomatik katılım, flood yönetimi ve sonuçların `users/` klasörüne kaydedilmesi desteklenir.
- Mesaj tarihleri seçtiğiniz saat dilimine göre yorumlanır; UTC ve yerel saat farkları otomatik dengelenir.

### Gruba Üye Ekle
- `users/` klasöründen bir liste seçin, hedef grubu belirtin.
- Başarılı eklenen ve zaten grupta olan kullanıcılar JSON’dan silinir.

### DM Gönder
- `users/` dosyalarındaki üyeleri seçilen oturumlara eşit paylaştırarak DM gönderir.
- Mesaj gövdesi, bağlantı ön izlemesi ve opsiyonel medya desteği vardır.
- Her oturum için ilerleme çubuğu ve flood geri sayımı gösterilir; “Too many requests” uyarıları otomatik olarak beklemeye alınır.

### Grup Mesajı
- `groups/` dosyalarındaki gruplar oturumlara bölünür.
- Mesaj göndermeden önce oturumlar gruplara katılır; katılma aşaması ve mesaj gönderme aşaması ayrı olarak takip edilir.
- Flood beklemeleri sayaç olarak gösterilir; günlük panelinde oturum bazlı durumlar kaydedilir.

### Ayarlar
- İşlem, oturum, DM, grup mesajı ve katılım isteği gecikmelerini düzenleyin.
- Türkçe ve İngilizce arayüz arasında anında geçiş yapın.
- Otomatik algılanan saat dilimini görüntüleyip 30 popüler seçenekten birini seçerek tüm zaman hesaplamalarını güncelleyin.

## Çalışma Sırasında Oluşan Klasörler

- `session/` – OTP ile alınan `.session` dosyaları.
- `users/` – Üye taramaları ve DM listeleri.
- `groups/` – Grup tarama sonuçları ve grup yayınına temel oluşturan JSON dosyaları.
- `config.json` – API bilgileri, hız limitleri ve seçilen saat dilimi.

## Flood Beklemeleri

Telethon `FloodWaitError` döndürdüğünde ilgili oturumun altında geri sayım başlatılır. Sayaç sıfırlandığında görev otomatik devam eder.

## Sorun Giderme

- API ID / Hash bilgileri hatalıysa uygulama açılmaz; değerleri `config.json` üzerinden güncelleyebilirsiniz.
- Windows Defender uyarı verirse “More info” → “Run anyway” adımlarını izleyin.
- Sürekli flood uyarısı alıyorsanız **Ayarlar** sekmesindeki gecikmeleri artırın.

Bu proje eğitim amaçlıdır ve tüm sorumluluk kullanıcıya aittir.
