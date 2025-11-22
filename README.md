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
- **Endpoint’ler:**
  - `POST /auth/register` — email/şifre/isim ile kayıt, başlangıç puanı verir.
  - `POST /auth/login` — token üretir.
  - `POST /auth/forgot` — şifre sıfırlama maili ister.
  - `GET /profile` — kullanıcı bilgisi.
  - `GET/POST /sites` — site listeleme/oluşturma (mobil, gerçekçi dolaşma, mouse/scroll/link/form/media bayrakları, süre başına puan düşümü).
  - `PATCH/DELETE /sites/{id}` — site güncelle/sil.
  - `POST /surf/start` — başka kullanıcılara ait bir site seçer, davranış planı döndürür ve sahipten puan düşer (aynı site aynı
    kullanıcıya gün içinde `config.php`'deki `max_daily_site_visits` değerinden fazla gösterilmez).
  - `POST /surf/complete` — surf tamamlanınca puan kazandırır; günlük/haftalık/aylık kazanım limitleri `config.php` (max_daily/
    weekly/monthly_reward) ile kısıtlanır.
  - `GET /dashboard` — günlük/haftalık kazanç, kalan süre, puan ve limit özetleri.
  - `GET /dashboard/history` — günlük/haftalık puan geçmişi.
  - `GET /mail/settings` ve `POST /mail/send` — info@noasoft.org’a iletilecek mesajları API ayarlarından alır; `contact_email` değeri PHP `mail()` ile alıcı ve From olarak kullanılır.

## Python GUI (PyQt6 + Playwright)
- **Konum:** `client/surf_gui.py`
- **Özellikler:**
  - NoaSoft logolu başlık kartı ve sağ üstte Discord kartı tarzı puan alanı; giriş yapılmadan kayıt/giriş/şifre sıfırla sekmeleri, girişten sonra ana sekmeler (Puan & Özet, Siteler, Surf + Puan, Google Görevi, YouTube Görevi, Puan Sistemi/Özellikler, İletişim, Log).
  - Dashboard sekmesinde animasyonlu sayaç ve uyarı bandı; Chart.js hissi veren line + bar PyQt6-Charts grafikleriyle günlük/haftalık kazançlar.
  - Site ekleme/güncelleme formu: süre, mobil/realistik/mouse/tıklama/scroll/form/media bayrakları, medya aksiyon checkbox’ları, detaylı davranış önizlemesi, sayfalama ve silme butonları.
  - Surf sekmesi: Playwright Chromium ile gerçekçi gezinme (mobil UA, scroll, mouse hareketi, link tıklama, metin seçip çizme/kopyalama, form doldurup temizleme, medya kontrolleri), ilerleme çubuğu ve canlı önizleme (tarayıcı üzerinde yapay mouse overlay) ile puan kazanımı; loglar ayrı sekmede kopyalanabilir.
  - Google araması sekmesi: ülke seçimiyle Google üzerinden arama yapıp hedef siteyi bulduğunda görev planını aynı aksiyonlarla çalıştırır, puanı `50 + süre + 10*sayfa` formülüyle hesaplar.
  - YouTube sekmesi: arama + hedef video linki veya direkt linkle video açıp işaretli medya/görev aksiyonlarını çalıştırır, puanı `50 + süre (+10*sayfa aramadaysa)` formülüyle hesaplar.
  - Puan sistemi sekmesi: harcama/kazanç kalemleri ve günlük/haftalık/aylık limitleri tablo halinde gösterir.
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
