# Task-8 Çıktısı: Fabrika Sistemi (Kurulum/Seviye/İşçi)

## Yapılanlar
1. Fabrika veri modeli eklendi (`20260405_000007_factory_system.sql`)
   - `factory_types`
   - `user_factories`
   - `factory_production_logs`
2. Fabrika kurulum akışı eklendi
   - Oyuncu 50 gold ile fabrika kurabilir.
   - Fabrika oyuncunun ülke/şehir konumuna bağlanır.
3. Fabrika üretim akışı eklendi
   - Enerji tüketimi ile üretim başlar.
   - Girdi kaynağı olan fabrikalarda hammadde kontrolü yapılır.
   - Çıktı kaynak kullanıcı envanterine eklenir.
   - Üretim logu tutulur.
4. Dashboard ve API entegrasyonu
   - API: `/api/factory/create`, `/api/factory/produce`
   - Dashboard'da fabrika kurma ve üretim tetikleme arayüzü eklendi.
5. Frontend canlı güncelleme
   - state poll ile fabrika listesi otomatik yenileniyor.

## Not
- Bu adım fabrika çekirdeğini kurar; sonraki adımda seviye upgrade, worker pazarı ve üretim kuyruk sistemi genişletilecektir.
