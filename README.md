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
- Oturum/geçiş + vatandaşlık sistemi: izin talebi, onay/reddetme, ihlal takibi, vatandaşlık başvurusu ve şehir/ülke taşınma akışı.
- Harita modülü genişletme: katman (layer) ve şehir POI tabanlı world builder desteği.
- Dünya haritası (jsVectorMap) interaktif: katman seçimi, ülke detay paneli, şehir/POI filtreleri.
- Admin panelden world builder: ülke/şehir/kaynak/layer/POI CRUD + JSON import/export.
- Global sıralamalar: oyuncu/şehir/ülke leaderboard ekranı.
- Günlük görevler + başarımlar + ödül claim akışı.
- Bildirim merkezi: toast + inbox + event feed.
- API v1 route standardizasyonu (`/api/v1/...`) + legacy `/api/...` uyumluluğu.
- Mobil odaklı responsive iyileştirmeler (küçük ekran tablo/form/harita optimizasyonu).
- Ekonomi anti-cheat katmanı: aksiyon cooldown guard + şüpheli işlem logları.
- Test altyapısı: unit/integration/load scriptleri ve tek komut test suite.
- Gezgin tüccar + realtime savaş altyapısı için Node/socket hazır veri modeli.
- Gelecekteki genişleme için seçim/parti/yönetim tabloları (schema hazır).

## Endpointler
- Web: `/`, `/login`, `/register`, `/forgot-password`, `/reset-password`, `/admin`
- API:
  - `GET /api/v1/state` (önerilen sürüm)
  - `POST /api/v1/...` (önerilen sürüm)
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
  - `POST /api/travel/request-citizenship`
  - `POST /api/travel/citizenship-decision`
  - `POST /api/quest/claim`
  - `POST /api/notification/read`
  - `POST /api/coup/start`
  - `POST /api/province/donate`
  - `POST /api/province/update-identity`
  - `GET /health` (operasyonel sağlık endpointi)

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
   - `db/migrations/20260406_000013_map_worldbuilder.sql`
   - `db/migrations/20260406_000015_border_citizenship_queue.sql`
   - `db/migrations/20260406_000016_rankings_quests_notifications.sql`
   - `db/migrations/20260406_000017_anticheat_balance_testinfra.sql`
   - `db/migrations/20260406_000018_realtime_coup_statecraft.sql`
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

- Task-15 (oturum+vatandaşlık+sınır geçiş) notu: `docs/BORDER_CITIZENSHIP_TASK15.md`

- Harita/world builder notu: `docs/MAP_WORLDBUILDER_TASK14.md`

- İnteraktif dünya haritası notu: `docs/WORLD_MAP_INTERACTIVE_TASK16.md`

- Admin world builder genişletme notu: `docs/ADMIN_WORLDBUILDER_TASK17.md`

- Sıralamalar notu: `docs/RANKINGS_TASK18.md`

- Görev/başarım notu: `docs/QUESTS_ACHIEVEMENTS_TASK19.md`

- Bildirim sistemi notu: `docs/NOTIFICATIONS_TASK20.md`

- API v1 standardizasyon notu: `docs/API_V1_TASK21.md`

- Mobil UI/UX notu: `docs/MOBILE_UI_TASK22.md`

- Ekonomi anti-cheat notu: `docs/ECONOMY_ANTICHEAT_TASK23.md`

- Test altyapısı notu: `docs/TEST_INFRA_TASK24.md`

- Kalite kapıları notu: `docs/QUALITY_GATES_TASK25.md`

- Gezgin tüccar + socket realtime notu: `docs/TRAVELER_SOCKET_TASK26_27.md`

- Canlıya çıkış planı notu: `docs/OPS_RELEASE_TASK28.md`

- Tam sürüm kurulum adımları: `docs/FULL_INSTALL_GUIDE.md`

- Test/izleme/canlıya çıkış notu (ileri faz): `docs/RELEASE_MONITORING_TASK15.md`

## Operasyonel Kontroller
- Hızlı release kontrol scripti: `scripts/release_checks.sh`
- Test suite scripti: `scripts/test_suite.sh`
