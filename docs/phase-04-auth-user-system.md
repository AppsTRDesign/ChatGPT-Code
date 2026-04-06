# Grand Strategy Political Simulation — Phase 4 (Auth + User System)

## 1) Bu fazda ne inşa edildi?

Bu fazda backend tarafında çalışan bir **JWT access + refresh** kimlik altyapısı ve **GeoIP onboarding atama akışı** kuruldu.

Eklenenler:
- Express uygulama çekirdeği: `backend/src/app.js`, `backend/src/server.js`
- Auth modülü:
  - `backend/modules/auth/auth.routes.js`
  - `backend/modules/auth/auth.controller.js`
  - `backend/modules/auth/auth.service.js`
  - `backend/modules/auth/onboarding.service.js`
  - `backend/modules/auth/geoip.provider.js`
  - `backend/modules/auth/token.service.js`
- User modülü:
  - `backend/modules/users/users.routes.js`
  - `backend/modules/users/users.controller.js`
  - `backend/modules/users/users.service.js`
- i18n çözümleme servisi: `backend/modules/i18n/i18n.service.js`
- Core servisleri: db, jwt, password hash, auth middleware, error sınıfı
- Refresh token migration: `backend/database/migrations/003_phase4_auth_refresh_tokens.sql`

---

## 2) API endpointleri

- `POST /api/v1/auth/register`
  - kullanıcı oluşturur
  - GeoIP sonucu ile ülke/şehir ataması yapar
  - `user_profiles` tablosuna atama nedenini kaydeder
  - varsayılan envanteri oluşturur

- `POST /api/v1/auth/login`
  - email/username + password doğrular
  - access token + refresh token üretir
  - refresh token hash olarak DB'ye kaydeder

- `POST /api/v1/auth/refresh`
  - refresh token doğrular
  - eski refresh token'ı revoke eder
  - yeni access+refresh token üretir

- `POST /api/v1/auth/logout`
  - refresh token revoke eder

- `GET /api/v1/users/me` (auth gerekli)
  - oyuncu profilini + onboarding atama verisini döner

- `PATCH /api/v1/users/me/language` (auth gerekli)
  - oyuncunun dil tercihini günceller
  - sadece enabled diller kabul edilir

---

## 3) GeoIP onboarding atama mantığı

Sıra:
1. GeoIP provider ülke + şehir üretir (şimdilik mock provider, genişletilebilir abstraction).
2. Ülke kodu oyunda varsa aynı ülkede şehir aranır.
3. Şehir eşleşmezse aynı ülkedeki en kalabalık fallback şehir atanır.
4. Ülke yoksa playable+active ülkeler arasında dengeli (aktif oyuncusu az) ülke seçilir.
5. Tüm sonuçlar `user_profiles` içinde saklanır (`detected_country`, `detected_city`, `assigned_country_id`, `assigned_city_id`, `assignment_reason`).

---

## 4) Dil tercihi ve fallback

- Header ve kullanıcı tercihine göre dil çözümlemesi middleware'de yapılır.
- Öncelik:
  1. kullanıcı tercihi (`x-user-language`)
  2. guest override (`x-guest-language`)
  3. `Accept-Language`
  4. fallback `en`
- Kullanıcı manuel dil değiştirirse `/users/me/language` ile profile persist edilir.

---

## 5) Node 16 uyumluluğu

- Kod tamamen CommonJS ile yazıldı.
- Node 18+ gerektiren API kullanılmadı.
- Bağımlılıklar Node 16.20.2 ile uyumlu sürümlerden seçildi.
