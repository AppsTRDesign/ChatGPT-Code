# Task-2 Çıktısı: Production Veritabanı Mimarisi Revizyonu

Bu doküman, 2. görevin tamamlandığını ve alınan kararları kayıt altına alır.

## 1) Migration yaklaşımı
- Tek parça `database.sql` yerine versiyonlu migration dosyaları kullanılır.
- Uygulama sırası:
  1. `db/migrations/20260405_000001_core_schema.sql`
  2. `db/migrations/20260405_000002_seed_core_data.sql`
- `migration_history` tablosu eklendi (ileride migration runner için hazır).

## 2) Şema normalizasyonu
- Domain tabanlı ayrım korundu:
  - `world`: countries, cities
  - `economy`: resources, country_resources, user_resources
  - `identity`: users
  - `market`: market_offers
  - `politics`: parties, party_members, elections, election_votes, country_government_roles
  - `ops`: settings, audit_logs, migration_history

## 3) Performans / index stratejisi
- Sık sorgulanan kolonlara index eklendi:
  - Kullanıcı ülke/şehir, last_energy_at, created_at
  - Market status + created_at, seller/status, resource/status
  - Election country/status + dates
  - Audit entity/event + time

## 4) Veri bütünlüğü
- Tüm ilişkiler için FK tanımları korunup genişletildi.
- Critical tablolarda unique key’ler netleştirildi:
  - `users.username`, `users.email`
  - `countries.code`
  - `resources.resource_key`
  - `country_resources(country_id,resource_id)`
  - `country_government_roles(country_id, role_key)`

## 5) Operasyonel eklemeler
- `updated_at` kolonları yaygınlaştırıldı.
- `is_active`, `is_banned` gibi operasyon bayrakları eklendi.
- `audit_logs` ile kritik event kayıt altyapısı eklendi.
- Kaynaklara `volatility_percent`, ülke-kaynak dağılımına `extraction_cost` eklendi.

## 6) Seed verisi
- TR/DE/RU/US + şehirleri + kaynak dağılımları sürdürülerek yeni şemaya taşındı.
- Sistem ayarlarının çekirdek değerleri `settings` tablosuna işlendi.

## 7) Geriye uyumluluk notu
- Uygulama kodundaki mevcut sorgular bozulmayacak şekilde ana alanlar korunmuştur.
- Yeni kolonlar opsiyonel genişleme ve performans amaçlı eklenmiştir.
