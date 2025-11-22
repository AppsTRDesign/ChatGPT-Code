# NoaSoft AutoSurf

PHP tabanlı API ve PyQt5 ile hazırlanmış masaüstü kontrol paneli aynı repoda toplandı. API, `https://autosurf.noasoft.org` ana dizinine (public olmayan klasör) kurulacak şekilde hazırlandı; GUI ise bu endpoint'lere bağlanıp üyelik, site ekleme ve surf puan akışını yönetir.

## API (PHP + MySQL)
- **Konum:** `api/index.php`
- **Kurulum:**
  1. `api/schema.sql` dosyasını MySQL’e uygulayın.
  2. `api/config.php` içindeki bağlantı ve `JWT_SECRET` ayarlarını güncelleyin.
  3. PHP 8 üstünde çalıştırın (plesk/ana dizin, public değil). Tüm istekler `index.php` üzerinden karşılanır.
- **Endpoint’ler:**
  - `POST /auth/register` — email/şifre/isim ile kayıt, başlangıç puanı verir.
  - `POST /auth/login` — token üretir.
  - `GET /profile` — kullanıcı bilgisi.
  - `GET/POST /sites` — site listeleme/oluşturma (mobil, gerçekçi dolaşma, mouse/scroll/link/form/media bayrakları, süre başına puan düşümü).
  - `PATCH/DELETE /sites/{id}` — site güncelle/sil.
  - `POST /surf/start` — başka kullanıcılara ait bir site seçer, davranış planı döndürür ve sahipten puan düşer.
  - `POST /surf/complete` — surf tamamlanınca puan kazandırır.
  - `GET /dashboard` — günlük/haftalık kazanç, kalan süre ve puan özeti.
  - `POST /contact` — info@noasoft.org adresine iletilecek mesajları sıraya alır (DB’ye kaydeder).

## Python GUI (PyQt5)
- **Konum:** `client/surf_gui.py`
- **Özellikler:**
  - NoaSoft logolu başlık kartı, puan kartı, animasyonlu ilerleme çubuğu, aksiyon listesi.
  - API URL + giriş/kayıt formu, günlük/haftalık kazanç ve kalan süre özetleri.
  - Site ekleme formu (mobil/realistik/mouse/tıklama/scroll/form/media seçenekleri) ve tablo görünümü.
  - Surf başlat/iptal, plan adımlarını ve kazanılan puanları gerçek zamanlı gösterme.
- **Çalıştırma:**
  ```bash
  pip install -r requirements.txt
  python client/surf_gui.py
  ```
  Varsayılan API adresi `https://autosurf.noasoft.org` olarak gelir; gerekirse üst alandan güncelleyin.

## Notlar
- Repo içerisindeki FastAPI tabanlı eski kod kaldırıldı; tek kaynak PHP API + PyQt5 panelidir.
- Endpoint’ler JWT Bearer token ile korunur; GUI otomatik olarak Authorization header’ını ayarlar.
