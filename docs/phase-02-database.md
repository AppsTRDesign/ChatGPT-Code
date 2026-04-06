# Grand Strategy Political Simulation — Phase 2 (Database)

## 1) Bu fazda ne inşa edildi?

Bu fazda oyun için **MariaDB uyumlu, normalleştirilmiş ve ölçeklenebilir** temel şema oluşturuldu.

Teslim edilenler:
- Tam SQL şema dosyası: `backend/database/schema.sql`
- Başlangıç dil seed dosyası: `backend/database/seeds/001_base_languages.sql`

Bu şema aşağıdaki ana alanları kapsar:
- Kimlik ve profil: `users`, `user_profiles`
- Dünya modeli: `countries`, `regions`, `cities`
- Siyaset: `elections`, `election_candidates`, `votes`, `laws`, `country_policies`, `country_policy_rules`, `presidents`, `governors`
- Gelişim: `city_projects`, `country_stats_daily`, `city_stats_daily`
- Seyahat/izin: `travel_routes`, `travels`, `visas`, `work_permits`
- Savaş: `wars`, `battles`, `battle_logs`
- Ekonomi/envanter: `items`, `inventories`, `inventory_items`, `transactions`
- Sosyal: `notifications`, `chats`
- Çokdillilik: `languages`, `translation_keys`, `translation_values`

---

## 2) İlişkiler ve veri bütünlüğü

Temel ilişki kararları:
- `World -> Countries -> Regions -> Cities` zinciri FK ile güvence altına alındı.
- `users` hem bulunduğu şehir/ülke hem de “home” şehir/ülke bilgilerini taşır.
- `user_profiles` onboarding/GeoIP sonucu (`detected_country`, `detected_city`, `assigned_country_id`, `assignment_reason`) saklar.
- `votes` tablosunda `(election_id, voter_user_id)` unique constraint ile **tek seçimde tek oy** kuralı uygulanır.
- `travels` tablosu seyahat motoru için zorunlu alanları içerir:
  `departure_city_id`, `arrival_city_id`, `distance_km`, `duration_seconds`, `travel_status`,
  `travel_type`, `permit_status`, `start_time`, `end_time`, `ticket_cost`.
- `country_policy_rules` ile ülke bazlı vize/çalışma izni kuralları politikadan ayrıştırılarak ölçeklenebilir hale getirildi.

---

## 3) İndeks stratejisi (gereksinim karşılama)

İstenen indekslerin tamamı şemaya eklendi:
- `country_code`: `countries.code` üzerinde `uk_countries_code` + `idx_countries_code`
- `region_id`: `cities.region_id`, `battles.target_region_id`
- `city_id`: `users.current_city_id`, `work_permits.city_id`, `city_stats_daily.city_id`
- `user_id`: birden çok tabloda (`votes`, `travels`, `notifications`, `transactions`, `inventories` vb.)
- `travel status`: `travels.travel_status`, `travels(user_id, travel_status)`
- `election status`: `elections.status`
- `war status`: `wars.status`
- `language code`: `languages.code`, `users.preferred_language_code`

---

## 4) Faz-3 için hazır hale getirilen noktalar

Bu şema, bir sonraki fazdaki dünya veri importu için uygundur:
- `countries.source_external_id` ve `map_source` kolonları dataset eşlemesi için hazır.
- `regions.geometry_geojson` alanı ile region geometri verisi saklanabilir.
- `cities` tablosunda koordinatlar (`latitude`, `longitude`) ve map etkileşimi için gerekli metrikler tanımlıdır.

---

## 5) Uygulama notları

- SQL dosyası geliştirme sırasında tekrar çalıştırılabilir olması için başta kontrollü `DROP TABLE IF EXISTS` blokları içerir.
- MariaDB üzerinde çalışacak şekilde InnoDB + utf8mb4 kullanıldı.
- Node.js 16.20.2 tarafında query builder/ORM fark etmeksizin bu şema doğrudan kullanılabilir.
