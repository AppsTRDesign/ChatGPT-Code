# Task-17: Admin World Builder (CRUD + Import/Export)

## Tamamlananlar

- Admin world builder paneli genişletildi:
  - Ülke güncelleme (kod/ad/bayrak/aktiflik)
  - Şehir güncelleme (ülke/ad/koordinat/aktiflik)
- Silme aksiyonları eklendi:
  - Ülke-kaynak dağılımı silme
  - Harita katmanı silme
  - Şehir POI silme
- JSON tabanlı veri taşıma eklendi:
  - `GET /admin/world/export` ile world builder veri dışa aktarma
  - `POST /admin/world/import` ile world builder toplu içe aktarma
- Import akışı transaction + upsert yaklaşımıyla çalışır:
  - countries
  - cities
  - country_resources
  - world_map_layers
  - city_points_of_interest

## Notlar

- Import payload bozuk JSON ise işlem reddedilir.
- Şehir koordinatlarında aralık doğrulaması uygulanır.
- Harita katmanı renk formatı `#RRGGBB` doğrulamasından geçmek zorundadır.
