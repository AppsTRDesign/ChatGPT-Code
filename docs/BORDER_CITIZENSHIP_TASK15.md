# Task-15: Oturum İzni / Vatandaşlık / Sınır Geçiş Sistemi

## Tamamlananlar

- Oturum izni akışı genişletildi:
  - başvuru
  - onay / red
  - geçerlilik süresi (`valid_until`)
  - ihlal alanları (`violated_at`, `violation_reason`)
- Vatandaşlık akışı eklendi:
  - vatandaşlık başvurusu
  - yetkili karar (onay/red)
- Sınır geçiş kuralları:
  - onaylı izin ve süre kontrolü
  - süresi dolmuş izinle geçiş denemesinde ihlal kaydı
- Otomatik dönüş:
  - izin süresi dolunca queue event ile otomatik ülkeye dönüş
- Event/queue altyapısı:
  - `border_event_queue`
  - queue batch işlem akışı

## Yeni API Endpointleri

- `POST /api/travel/request-citizenship`
- `POST /api/travel/citizenship-decision`

## Yeni Migration

- `20260406_000015_border_citizenship_queue.sql`
