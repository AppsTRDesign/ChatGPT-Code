# Task-14: Harita Modülü Genişletme + World Builder

## Tamamlananlar

- Yeni harita domain tabloları:
  - `world_map_layers`
  - `city_points_of_interest`
- Oyun harita payload’ı genişletildi:
  - Ülke bazlı katman verisi (`layers`)
  - Şehir POI marker verisi (`pois`)
- Admin world builder geliştirildi:
  - Harita katmanı ekleme/güncelleme formu
  - Şehir POI ekleme formu
  - Katman ve POI liste ekranları
- Harita render davranışı güncellendi:
  - Marker listesine POI’ler dahil edildi
  - Katman verileri ile bölge renk serisi (`regions series`) üretildi

## Yeni Admin Endpointleri

- `POST /admin/world/map-layer`
- `POST /admin/world/city-poi`
