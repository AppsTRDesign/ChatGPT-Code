# Noa Political Wars (Rival Regions tarzı genişletilmiş çekirdek)

Bu sürüm, önceki basit scaffold yerine daha kapsamlı bir oyun çekirdeği içerir.

## Aktif Sistemler
- Üye kayıt / giriş sistemi.
- IP bazlı ülke tespiti ve otomatik ulus+şehir ataması.
- Ulus sistemi + bayraklar (emoji flag).
- Oyuncu statları: enerji, seviye, tecrübe, kuvvet, eğitim, dayanıklılık.
- Çalışma ve savaş mekaniği (enerji tüketimi, XP kazanımı).
- Stat geliştirme (çalışma puanı + altın gerektirir).
- Kaynak ekonomisi: altın, petrol, elmas, nadir toprak elementleri, uranyum, demir, taş, tahta, bakır, silikon.
- Ülke bazlı farklı günlük kaynak üretim dağılımı.
- Global market: oyuncu ilan açma / satın alma, vergi+komisyon ve fiyat koruma kuralları.
- Ülke savaş çekirdeği: savaş başlatma, cephe saldırısı, skor ve savaş raporları.
- Parti/Seçim/Meclis: parti yönetimi, seçim oylaması ve kanun teklif/oylama çekirdeği.
- Bakanlık rolleri: rol atama, yetki matrisi ve bakanlık aksiyon logları.
- Oturum/geçiş izin sistemi: izin talebi, onay/reddetme ve şehir/ülke taşınma akışı.
- Dünya haritası (jsVectorMap) ve şehir bazlı oyuncu yoğunluğu işaretleme.
- Admin panelden ülke/şehir/kaynak dağılımı ekleme.
- Gelecekteki genişleme için seçim/parti/yönetim tabloları (schema hazır).

## Endpointler
- Web: `/`, `/login`, `/register`, `/forgot-password`, `/reset-password`, `/admin`
- API:
  - `GET /api/state`
  - `POST /api/action/work`
  - `POST /api/action/battle`
  - `POST /api/action/upgrade`
  - `POST /api/market/create`
  - `POST /api/market/buy`
  - `POST /api/war/start`
  - `POST /api/war/attack`
  - `POST /api/party/create`
  - `POST /api/party/join`
  - `POST /api/party/leave`
  - `POST /api/election/open`
  - `POST /api/election/vote`
  - `POST /api/law/propose`
  - `POST /api/law/vote`
  - `POST /api/gov/assign-role`
  - `POST /api/gov/action`
  - `POST /api/travel/request-permit`
  - `POST /api/travel/permit-decision`
  - `POST /api/travel/move`

## Kurulum
1. Dosyaları `/var/www/vhosts/noasoft.org/game.noasoft.org` içine koy.
2. `.env.example` -> `.env` yapıp DB bilgilerini gir.
3. Migration sırasını çalıştır:
   - `db/migrations/20260405_000001_core_schema.sql`
   - `db/migrations/20260405_000002_seed_core_data.sql`
   - `db/migrations/20260405_000003_auth_hardening.sql`
   - `db/migrations/20260405_000004_geoip_fallback_cache.sql`
   - `db/migrations/20260405_000005_balance_tuning.sql`
   - `db/migrations/20260405_000006_resource_balance.sql`
   - `db/migrations/20260405_000007_factory_system.sql`
   - `db/migrations/20260406_000008_market_hardening.sql`
   - `db/migrations/20260406_000009_war_core.sql`
   - `db/migrations/20260406_000010_politics_core.sql`
   - `db/migrations/20260406_000011_government_roles.sql`
   - `db/migrations/20260406_000012_travel_permits.sql`
   (veya `database.sql` içindeki SOURCE sırasını kullan.)
4. Apache `mod_rewrite` açık olmalı.
5. Admin varsayılan giriş:
   - kullanıcı: `admin`
   - şifre: `admin123`

## Not
Bu sürüm artık büyük oyun mekaniklerinin çekirdeğini taşır.
Bir sonraki adımda savaş yasası/meclis oylama, vizeler/oturum izinleri, fabrika zinciri, şehir bina geliştirme queue, aylık seçim cron süreçleri ve gerçek zamanlı savaş odaları eklenebilir.


## Plan
- Yol haritası ve görev takibi: `docs/RIVALREGIONS_TODO.md`

- DB mimarisi revizyon notu: `docs/DB_ARCHITECTURE_TASK2.md`

- Auth hardening notu: rate-limit + password reset + session security (Task-3 tamamlandı).

- GeoIP fallback/caching notu: `docs/GEOIP_FALLBACK_TASK4.md`

- Domain/stat formül notu: `docs/DOMAIN_RULES_TASK5.md`

- Balance tuning notu: `docs/BALANCE_TUNING_TASK6.md`

- Kaynak balans notu: `docs/RESOURCE_BALANCE_TASK7.md`

- Fabrika sistemi notu: `docs/FACTORY_SYSTEM_TASK8.md`

- Market tam sürüm notu: `docs/MARKET_FULL_TASK9.md`

- Savaş çekirdeği notu: `docs/WAR_CORE_TASK10.md`

- Siyaset sistemi notu: `docs/POLITICS_TASK11.md`

- Bakanlık rol sistemi notu: `docs/GOVERNMENT_ROLES_TASK12.md`

- Oturum/geçiş izin sistemi notu: `docs/TRAVEL_PERMIT_TASK13.md`
