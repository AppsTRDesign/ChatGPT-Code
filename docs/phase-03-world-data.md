# Grand Strategy Political Simulation — Phase 3 (World Data)

## 1) Bu fazda ne inşa edildi?

Bu fazda, ücretsiz coğrafi veri kaynaklarına (Natural Earth / GADM / GeoNames yaklaşımı) uyumlu bir **world bootstrap pipeline** oluşturuldu.

Eklenen ana parçalar:
- Kaynak veri dosyaları:
  - `backend/database/world-data/countries.json`
  - `backend/database/world-data/regions.json`
  - `backend/database/world-data/cities.json`
- Üretim scripti:
  - `scripts/world/build-world-seed.js`
- Script çıktıları:
  - SQL seed: `backend/database/seeds/generated/002_world_bootstrap.sql`
  - Map-ready JSON: `backend/database/world-data/map-ready.json`
- Veri sözleşmesi:
  - `shared/contracts/world-data.schema.json`

---

## 2) Pipeline akışı

`node scripts/world/build-world-seed.js` çalıştığında:
1. Ülke/region/şehir JSON dosyaları okunur.
2. `countries` için idempotent insert SQL üretilir.
3. `regions` ve `cities` tablolarına, ülke/region kodları ile bağlı insert SQL üretilir.
4. `capital_city_id` alanı ülkelerin başkentlerine göre güncellenir.
5. Ülke ve region için temel aggregate alanlar (`total_population`, `city_count`) güncellenir.
6. Frontend map katmanı için hiyerarşik `map-ready.json` üretilir.

---

## 3) Neden bu yaklaşım?

- **Admin panelden ülke ekleme yok** kuralını korur; dünya verisi dataset/pipeline üzerinden gelir.
- Import süreci tekrar çalıştırılabilir ve idempotent şekilde tasarlanmıştır.
- `map-ready.json`, Leaflet/SVG harita katmanına doğrudan verilebilecek sade bir format sunar.
- Phase 4+ için onboarding/GeoIP atama altyapısına hazır referans şehir/ülke yapısı sağlar.

---

## 4) Phase 4 ön hazırlık çıktısı

Bu faz sonrasında:
- Auth + onboarding katmanı, GeoIP sonucu ile `countries.code` ve `cities.name` üzerinden eşleştirme yapabilecek durumda.
- Fallback ülke seçimi için `countries.is_playable` ve şehir varlıkları hazır.
- Map sistemi için ülke->region->city hiyerarşisi tek JSON çıktıda kullanılabilir.
