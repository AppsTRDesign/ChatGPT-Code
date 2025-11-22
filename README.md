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
  - `POST /surf/start` — başka kullanıcılara ait bir site seçer, davranış planı döndürür ve sahipten puan düşer.
  - `POST /surf/complete` — surf tamamlanınca puan kazandırır.
  - `GET /dashboard` — günlük/haftalık kazanç, kalan süre ve puan özeti.
  - `GET /dashboard/history` — günlük/haftalık puan geçmişi.
  - `GET /mail/settings` ve `POST /mail/send` — info@noasoft.org’a iletilecek mesajları API ayarlarından alır ve gönderir.

## Python GUI (PyQt6 + Playwright)
- **Konum:** `client/surf_gui.py`
- **Özellikler:**
  - NoaSoft logolu başlık kartı ve sağ üstte Discord kartı tarzı puan alanı; giriş yapılmadan kayıt/giriş/şifre sıfırla sekmeleri, girişten sonra ana sekmeler (Puan & Özet, Siteler, Surf + Puan, İletişim).
  - Dashboard sekmesinde animasyonlu sayaç, kalan/ toplam süre barı, puan yetersizliğinde kırmızı uyarı bandı, günlük/haftalık kazanç grafikleri (PyQt6-Charts).
  - Site ekleme/güncelleme formu: süre, mobil/realistik/mouse/tıklama/scroll/form/media bayrakları, detaylı davranış önizlemesi, sayfalama ve silme butonları.
  - Surf sekmesi: Playwright Chromium ile gerçekçi gezinme (mobil UA, scroll, mouse hareketi, link tıklama, form doldurma, medya kontrolleri), ilerleme çubuğu, kalan/ toplam süre gösterimi ve kazanç bildirimi; canlı log sekmesiyle kopyalanabilir hata/eylem çıktısı.
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
