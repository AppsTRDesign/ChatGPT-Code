# Task-13: Oturum / Geçiş İzin Sistemi

## Tamamlananlar

- Yeni veri modeli:
  - `country_travel_policies`
  - `residence_permits`
  - `travel_logs`
- Oyuncu akışları:
  - Geçiş izni talebi oluşturma
  - Onaylı izin varsa ülke/şehir taşınma
  - Geçiş sırasında vize ücretinin tahsil edilmesi
- Yönetim akışları:
  - İçişleri Bakanı / Başkan tarafından izin onay-red kararı
  - Kararların bakanlık aksiyon loguna yazılması
- Dashboard:
  - İzin talebi oluşturma paneli
  - Permit ID ile onay/red paneli
  - Şehir ID ile taşınma paneli
  - Oyuncunun son izin kayıtları tablosu

## Yeni API Endpointleri

- `POST /api/travel/request-permit`
- `POST /api/travel/permit-decision`
- `POST /api/travel/move`
