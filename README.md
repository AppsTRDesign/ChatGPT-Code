# ChatGPT-Code

Browser tabanlı grand strategy / politik simülasyon oyunu için üretim odaklı temel.

## Mevcut Durum

- ✅ Phase 1 (Architecture) tamamlandı.
- ✅ Phase 2 (Database) tamamlandı.
- Belgeler:
  - `docs/phase-01-architecture.md`
  - `docs/phase-02-database.md`

## Klasörler

- `backend/`: Node.js 16.20.2 uyumlu Express + Socket.io backend modülleri
- `frontend/`: React tabanlı istemci, map ve oyun arayüzü
- `shared/`: paylaşılan sözleşmeler/DTO'lar
- `lang/`: kanonik i18n dil dosyaları (`en.json`, `tr.json`)
- `backend/database/`: MariaDB şema ve seed dosyaları

## Sonraki Adım

- Phase 3: World dataset import + GeoJSON tabanlı dünya bootstrap pipeline
