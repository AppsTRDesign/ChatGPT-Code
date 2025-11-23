# NoaSoft AutoSurf

PHP tabanlı API ve PyQt6 ile hazırlanmış masaüstü kontrol paneli aynı repoda toplandı. API, `https://autosurf.noasoft.org` ana dizinine (public olmayan klasör) kurulacak şekilde hazırlandı; GUI ise bu endpoint'lere bağlanıp üyelik, site ekleme ve surf puan akışını yönetir.

## API (PHP + MySQL)
- **Konum:** `api/index.php`
- **Kurulum:**
  1. `api/schema.sql` dosyasını MySQL’e uygulayın.
  2. `api/config.php` içindeki bağlantı ve `JWT_SECRET` ayarlarını güncelleyin.
  3. PHP 8 üstünde çalıştırın (plesk/ana dizin, public değil). Tüm istekler `index.php` üzerinden karşılanır.
  4. `api/.htaccess` dosyasını yayın klasörüne ekleyin; Authorization header’ını ve CORS’u korur, 404 yerine istekleri `index.php`
     üzerinden yönlendirir.
  5. Görev puanları için ekstra ayar gerekmiyor; Google/YouTube ödülleri arama süresi ve hedefin bulunmasına göre otomatik hesaplanır.
- **Endpoint’ler:**
  - `POST /auth/register` — email/şifre/isim ile kayıt, başlangıç puanı verir.
  - `POST /auth/login` — token üretir.
  - `POST /auth/forgot` — şifre sıfırlama maili ister.
  - `GET /profile` — kullanıcı bilgisi.
  - `GET/POST /sites` — site listeleme/oluşturma (mobil, gerçekçi dolaşma, mouse/scroll/link/form/media bayrakları, süre başına puan düşümü).
  - `PATCH/DELETE /sites/{id}` — site güncelle/sil.
  - `GET /sites/{id}/stats` — site bazında ülke/cihaz/IP/UA, puan kazanç-harcama toplamları, günlük/haftalık/aylık bar serileri ve sayfalanmış detay listesi döndürür.
  - `POST /surf/start` — başka kullanıcılara ait bir site seçer, davranış planı döndürür (aynı site aynı
    kullanıcıya gün içinde kullanıcının profilindeki `max_daily_site_visits` değeri kadar gösterilir; varsayılan sınırlar kullanıcı tablosundaki
    sütun varsayılanlarından gelir).
  - `POST /surf/complete` — surf tamamlanınca puan kazandırır; günlük/haftalık/aylık kazanım limitleri kullanıcının profilindeki
    max_daily/weekly/monthly_reward alanlarıyla kısıtlanır. İstemciden gelen IP/ülke/şehir/cihaz/aksiyon telemetrisi `site_stats` tablosuna kaydedilir.
  - `GET /dashboard` — günlük/haftalık kazanç, kalan süre, puan ve limit özetleri.
  - `GET /dashboard/history` — günlük/haftalık puan geçmişi.
  - `GET /mail/settings` ve `POST /mail/send` — info@noasoft.org’a iletilecek mesajları API ayarlarından alır; `contact_email` değeri PHP `mail()` ile alıcı ve From olarak kullanılır.
  - `GET /tasks/config` — Google/YouTube görev bilgilendirmesi (sabit 90 saniyelik arama tabanı) için istemciye bildirim.

## Python GUI (PyQt6 + Playwright)
- **Konum:** `client/surf_gui.py`
- **Özellikler:**
  - NoaSoft logolu başlık kartı ve sağ üstte Discord kartı tarzı puan alanı; giriş yapılmadan kayıt/giriş/şifre sıfırla sekmeleri, girişten sonra ana sekmeler (Puan & Özet, Siteler, Surf + Puan, Google Görevi, YouTube Görevi, Puan Sistemi/Özellikler, İletişim, Log).
  - Dashboard sekmesinde animasyonlu sayaç ve uyarı bandı; Chart.js hissi veren line + bar PyQt6-Charts grafikleriyle günlük/haftalık kazançlar.
  - Site ekleme/güncelleme formu: süre, mobil/realistik/mouse/tıklama/scroll/form/media bayrakları, medya aksiyon checkbox’ları, detaylı davranış önizlemesi, sayfalama ve silme butonları.
  - Site detayları: her kayıtlı site için “Detaylar” butonu; ülke/şehir/IP/platform/cihaz/UA ve aksiyon adetleri tablo halinde, puan kazanç/harcama netleri için ayrı bar+line grafikleri, günlük/haftalık/aylık seriler ve tek tıkla Türkçe karakter uyumlu PDF dışa aktarma.
  - Surf sekmesi: Playwright Chromium ile gerçekçi gezinme (mobil UA, scroll, mouse hareketi, link tıklama, metin seçip çizme/kopyalama, form doldurup temizleme, medya kontrolleri), ilerleme çubuğu ve canlı önizleme (tarayıcı üzerinde yapay mouse overlay) ile puan kazanımı; loglar ayrı sekmede kopyalanabilir.
  - Google araması sekmesi: ülke seçimiyle arama kutusuna kelime yazıp Enter’lar, sonuç sayfalarında 90 saniyeye kadar hedef URL’yi arar; bulunursa siteye girip aksiyonları çalıştırır. Puan formülü: bulunamazsa 90, bulunursa (arama süresi + süre + sayfa*10).
  - YouTube sekmesi: isteğe göre arama kutusuna yazarak 90 saniyeye kadar `/watch?v=` sonuçlarını tarar; hedef video bulunursa açıp medya/görev aksiyonlarını uygular. Puan formülü: bulunamazsa 90, bulunursa (arama süresi + izleme süresi).
  - Puan sistemi sekmesi: kart bazlı harcama/kazanç özetleri ve günlük/haftalık/aylık limitleri gösterir.
  - İletişim sekmesi: API’den çekilen mail ayarlarıyla info@noasoft.org’a mesaj iletimi.
- **Çalıştırma:**
  ```bash
  # Windows için
  install.bat
  run.bat

  # Manuel
  python -m venv .venv
  .venv\\Scripts\\activate
  pip install -r requirements.txt
  python -m playwright install chromium
  python client/surf_gui.py
  ```
  Varsayılan API adresi `https://autosurf.noasoft.org` olarak gelir; gerekirse üst alandan güncelleyin.

## Notlar
- Repo içerisindeki FastAPI tabanlı eski kod kaldırıldı; tek kaynak PHP API + PyQt6 panelidir.
- Endpoint’ler JWT Bearer token ile korunur; GUI otomatik olarak Authorization header’ını ayarlar.
- GeoIP raporları için `client/assets/GeoLite2-City.mmdb`, `GeoLite2-Country.mmdb` ve `GeoLite2-ASN.mmdb` dosya yollarını kullanır; lisans gereği repo içinde dosya yoktur, aynı konuma ekleyin. Çözümlenen IP, ülke kodu, kıta, koordinat, ASN/ISP/ağ bilgileri ve user-agent, site istatistiklerinde saklanır ve PDF çıktısına eklenir.
- `client/assets/personas.json` içine yeni persona profili eklendiğinde GUI yeniden başlatıldığında otomatik algılanır; mouse/scroll/tıklama/tartışma hızları persona profiline göre çeşitlenir.
- PDF çıktıları, eğer mevcutsa `client/assets/DejaVuSans.ttf` ve `DejaVuSans-Bold.ttf` fontlarını kullanarak Türkçe karakterleri tam destekleyecek şekilde üretilir; lisans gereği fontlar repo içinde yoktur, dosyaları aynı dizine manuel ekleyin. Rapor ayarlarından dikey/yatay sayfa yönünü ve isteğe bağlı alanları seçebilirsiniz.
- Playwright sayfalarına üstte kapatılabilir bir reklam bandı eklenir; varsayılan placeholder görseli `AD_BANNER_URL` ortam değişkeniyle değiştirebilirsiniz.
