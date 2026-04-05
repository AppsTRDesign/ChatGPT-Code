# Task-4 Çıktısı: IP Ülke Atama + Fallback/Caching Stratejisi

## Yapılanlar
1. `geoip_cache` tablosu eklendi.
   - IP bazlı ülke kodu cache.
   - source/confidence ve TTL alanları var.
2. `GeoService` geliştirildi.
   - Çok sağlayıcı denemesi: `ip-api` -> `ipwho.is`.
   - Cache varsa doğrudan cache döner.
   - Provider hatasında `Accept-Language` fallback çalışır.
3. Private/reserved IP davranışı netleştirildi.
   - Lokal ağ veya reserved IP tespit edilirse dış API çağrısı yapılmaz.
4. Ülke içi şehir dağıtım kuralı geliştirildi.
   - Kayıtta aynı ülkenin şehirleri arasında en az oyunculu şehre öncelik verilir.
   - Eşitlikte nüfusu yüksek şehir tercih edilir.

## Dosyalar
- `db/migrations/20260405_000004_geoip_fallback_cache.sql`
- `src/Services/GeoService.php`
- `src/Services/GameService.php`
- `database.sql`
- `docs/RIVALREGIONS_TODO.md`

## Not
- Şu an dış sağlayıcılar HTTP tabanlı kullanılıyor; production'da provider SLA ve timeout izleme önerilir.
- Gerekirse sonraki adımda ASN/VPN/Proxy tespiti için ek filtre katmanı eklenebilir.
