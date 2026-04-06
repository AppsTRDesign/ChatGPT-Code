# Task-15: Test, İzleme, Canlıya Çıkış Planı

## Tamamlananlar

- Sağlık gözlemi:
  - `GET /health` endpointi eklendi.
  - Health çağrıları `app_heartbeat_logs` tablosuna yazılır.
  - Hata senaryoları `app_error_events` tablosuna kayıtlanır.
- Release izleme migration’ı:
  - `20260406_000014_observability_release.sql`
- Operasyonel script:
  - `scripts/release_checks.sh`
  - PHP syntax + frontend syntax + migration order kontrolü.

## Canlıya Çıkış Adımları (Özet)

1. `database.sql` migration sırasını canlı DB’de çalıştır.
2. `.env` üretim değerlerini doğrula (DB, cookie secure, app env).
3. `scripts/release_checks.sh` çalıştır.
4. `/health` endpointini 200/`ok` ile doğrula.
5. Admin panelden kritik world-builder verilerini hızlı kontrol et.
6. İlk 24 saat `app_heartbeat_logs` ve `app_error_events` tablolarını takip et.
