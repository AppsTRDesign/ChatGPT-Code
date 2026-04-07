# Grand Strategy Political Simulation — Phase 5 (Map System)

## 1) Bu fazda ne inşa edildi?

Phase 5 kapsamında backend + frontend tarafında çalışan ilk etkileşimli dünya haritası sistemi kuruldu.

### Backend
- `GET /api/v1/map/world`:
  - ülke özetleri + şehir marker verileri döner.
- `GET /api/v1/map/countries/:countryCode`:
  - ülke info card verisini döner.
- `GET /api/v1/map/cities?countryCode=XX&cityName=YYY`:
  - şehir info card verisini döner.

Kaynak: `backend/database/world-data/map-ready.json`

### Frontend
- React + React Query + Leaflet ile dünya haritası ekranı oluşturuldu.
- Ülke markerları (playable highlight) ve şehir markerları etkileşimli hale getirildi.
- Ülke/şehir seçimi sonrası sağ panelde info card gösterimi eklendi.
- `Fly to Capital` / `Fly Here` aksiyonları ile focus zoom + rota çizgisi (polyline) eklendi.
- Dil değiştirici (EN/TR) ve localStorage tabanlı anlık dil değişimi eklendi.

---

## 2) Tasarım kararları

- Şimdilik polygon tabanlı ülke sınırları yerine marker/centroid yaklaşımı kullanıldı (hızlı ve ölçeklenebilir başlangıç).
- `map-ready.json` tek kaynak olarak backend map servisinde normalize edilip frontend'e sunuluyor.
- Kart verileri backend tarafından verildiği için ileride economy/politics/war modülleriyle kolay entegre edilir.

---

## 3) Phase 6 için hazır noktalar

- Map tıklamalarından country/city detay endpointlerine geçiş altyapısı hazır.
- Sağ panel kartları, bir sonraki fazdaki detay sayfalarına route bağlayacak şekilde ayrıştırılabilir.
- Travel engine geldiğinde polyline/animation mantığı doğrudan gerçek süre hesaplarıyla bağlanabilir.
