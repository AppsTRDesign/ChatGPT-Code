# ChatGPT-Code

Browser tabanlı grand strategy / politik simülasyon oyunu için üretim odaklı temel.

## Mevcut Durum

- ✅ Phase 1 (Architecture) tamamlandı.
- ✅ Phase 2 (Database) tamamlandı.
- ✅ Phase 3 (World Data) tamamlandı.
- ✅ Phase 4 (Auth + User System) tamamlandı.
- ✅ Phase 5 (Map System) tamamlandı.
- ✅ Phase 6 (Country + City Modules) tamamlandı.
- ✅ Phase 7 (Travel Engine) tamamlandı.
- ✅ Phase 8 (Governor / City Development) tamamlandı.
- ✅ Phase 9 (Politics) tamamlandı.
- ✅ Phase 10 (Economy) tamamlandı.
- Belgeler:
  - `docs/phase-01-architecture.md`
  - `docs/phase-02-database.md`
  - `docs/phase-03-world-data.md`
  - `docs/phase-04-auth-user-system.md`
  - `docs/phase-05-map-system.md`
  - `docs/phase-06-country-city-modules.md`
  - `docs/phase-07-travel-engine.md`
  - `docs/phase-08-governor-city-development.md`
  - `docs/phase-09-politics.md`
  - `docs/phase-10-economy.md`

## Klasörler

- `backend/`: Node.js 16.20.2 uyumlu Express + Socket.io backend modülleri
- `frontend/`: React + Leaflet tabanlı istemci
- `shared/`: paylaşılan sözleşmeler/DTO'lar
- `lang/`: kanonik i18n dil dosyaları (`en.json`, `tr.json`)
- `backend/database/`: MariaDB şema, migration, seed ve world-data dosyaları

## Sonraki Adım

- Phase 11: War (declaration, fronts, battles, region transfer)
