# Task-7 Çıktısı: Kaynak Sistemi Tam Sürüm + Dağılım Balansı

## Yapılanlar
1. Kaynak balans migration'ı eklendi (`20260405_000006_resource_balance.sql`)
   - `country_resources` için `regeneration_rate` ve `quality_index` alanları eklendi.
   - `resource_market_prices` tablosu eklendi.
2. Üretim sırasında ülke stok tüketimi aktif edildi.
   - Work aksiyonu artık sadece kullanıcı envanterini değil, ülke kaynağının global stoğunu da etkiliyor.
   - Stok tükenirse çalışma aksiyonu engelleniyor.
3. Kaynak fiyatlandırma modeli eklendi.
   - `resourceMarketSnapshot()` ile stok/kıtlık/kaliteye göre dinamik fiyat üretiliyor.
   - Fiyatlar `resource_market_prices` tablosuna yazılıyor.
4. Dashboard'a kaynak piyasa dengesi tablosu eklendi.
   - fiyat, kıtlık faktörü, toplam stok, günlük üretim canlı gösteriliyor.
5. API state ile resource market verisi döndürülüyor ve frontend periodic refresh ile güncelleniyor.

## Not
- Bu adım ekonomik dengeyi global stok üzerinden etkileyebilir hale getirdi.
- Sonraki adımda fabrika zinciri ile kaynak tüketim/işleme ilişkisi genişletilmeli.
