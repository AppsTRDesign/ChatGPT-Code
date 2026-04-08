# Geopolitical MMO – Güncel Sistem ve Oyun Özellikleri (Detaylı)

Bu doküman projedeki **güncel** mimariyi ve oyun mekaniklerini tek yerden anlatır.

## 1) Genel Mimari

Sistem 3 ana parçadan oluşur:

1. **PHP Backend (MVC + servis katmanı)**
   - `app/Core`: Router, Request, Response, Database
   - `app/Controllers`: HTTP endpoint katmanı
   - `app/Services`: iş kuralları
   - `app/Models`: SQL erişimi
   - `app/Middlewares`: auth kontrolü

2. **Frontend (public/)**
   - Harita (`map.php`), Dashboard (`dashboard.php`), Profil (`profile.php`), Login/Register
   - Vanilla JS + API çağrıları

3. **Realtime Sunucu (Node + Socket.IO)**
   - `realtime/server.js`
   - yolculuk ilerleme/bitiş event’leri

---

## 2) Veritabanı Tasarımı (Normalize)

Tek migration: `database/migrations/001_schema.sql`

### 2.1 Ana tablolar
- `users`, `api_tokens`
- `player_profiles`, `citizenships`
- `travel_logs`, `player_travel`

### 2.2 Dünya ve yönetim tabloları
- `countries`
  - ülke adı, slug, `iso_code`, bayrak, **color**
- `regions`
  - temel region kimliği + konum + owner_country_region bağlantısı (`owner_region_id`)
- `country_visuals`
  - ülke-region bazında **renk + bayrak** (harita/detay görselliği)
- `region_infra`
  - bina count + level alanları
- `region_profile`
  - `capital_region_id`, `is_coastal`
- `country_economy`
  - resource_type, hazine kalemleri, ülke vergi oranları
- `region_taxes`
  - bölgesel fabrika vergileri

---

## 3) Seed Akışı

`database/seed_world.php` adımları:
1. FK check kapat, tüm oyun tablolarını temizle
2. `countries` yükle (bayrak + renk dahil)
3. `regions` yükle
4. Her ülkenin ilk region’ını country/capital kabul edip owner ilişkisini kur
5. Her region için `region_infra`, `region_profile`, `region_taxes` oluştur
6. Sadece country region’lar için `country_economy` + `country_visuals` oluştur

### Population başlangıcı
- `regions.population` seed sırasında **0** atanır.
- Population tamamen oyuncu hareketlerinden oluşur.

---

## 4) Kayıt, Konum ve Ulus Sistemi

### 4.1 Kayıt sırasında konum atama
- IP API / MaxMind çözümleme
- country bulunamazsa nearest region fallback
- profile + citizenship açılır
- yerleşilen region popülasyonu +1

### 4.2 Ulus (Nation)
- `player_profiles.nation_country_id` ile tutulur (countries FK)
- ilk atama: kullanıcının yerleştiği region’un `country_id`
- `nation_changed_at` ile cooldown takibi

### 4.3 Ulus değiştirme
- Maliyet: **1000 gold**
- Endpoint: `POST /api/player/change-nation`
- Cooldown: **30 gün**
- Cooldown bitmeden ulus değişimi engellenir

---

## 5) Yolculuk Mekaniği

- Mesafe: Haversine (km)
- Hız: airport level etkili
  - `effective_speed = base_speed * (1 + log(airport_level + 1) * 0.25)`
- Maliyet: distance tabanlı yüksek çarpan (coins)
- Vergi: ülke `general_tax_rate` oranı kadar kesinti `country_economy.treasury_state_money`’a yazılır
- Travel sonunda population transfer:
  - eski region -1
  - yeni region +1
- Travel’den XP kapalıdır (gelecek sistem için planlı)

---

## 6) Harita (Map) Özellikleri

Region sheet açıldığında kartta:
- owner
- resource
- population
- **mesafe (km)**
- travel cost
- ortalama uçuş süresi

gösterilir.

Polygon renkleri ülke-region görsellerinden (`country_visuals`) türetilerek API ile gelir.

---

## 7) Dashboard Özellikleri

`/api/stats/dashboard` çoklu liste döndürür:
- Top Regions (count toplam skor)
- Top Countries (bağlı region count toplam skor)
- Top Airports / Armies / Hospitals / Educations / Ports (level bazlı)
- Top Country Population
- Top Region Population

Country detail kartında hazine kalemleri görünür:
- devlet parası, altın, elmas, petrol, maden, uranyum

---

## 8) Profil Özellikleri

Profil ekranında:
- oyuncu coin/gold/enerji/xp bilgileri
- aktif travel durumu
- gold ile enerji satın alma (kullanıcı miktarı girer)
- ulus adı + ulus bayrağı
- ulus değiştirme select + buton
- ulus değişim geri sayımı (gün/saat/dakika)
- ulus cooldown progress bar

---

## 9) API Uçları (Özet)

### Auth
- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/logout`

### Player
- `GET /api/player/me`
- `POST /api/player/travel`
- `GET /api/player/travel-history`
- `POST /api/player/cancel-travel`
- `POST /api/player/buy-energy`
- `POST /api/player/change-nation`

### Map/Stats
- `GET /api/map/regions`
- `GET /api/map/countries`
- `GET /api/map/region-detail?id=...`
- `GET /api/map/country-detail?id=...`
- `GET /api/stats/dashboard`

### Action
- `POST /api/region/action`

---

## 10) Realtime

Socket server travel progress/complete event’leri üretir.
Frontend harita bu event’lerle uçuş progress görselliğini günceller.

---

## 11) Kısa Özet

Güncel sistem:
- normalize tablo modeli,
- kullanıcı bazlı population,
- ülke hazinesi + vergi altyapısı,
- mesafe/süre/maliyet odaklı travel,
- ulus değiştirme + 30 gün cooldown,
- harita sheet’te km bilgisi,
- dashboard’da çoklu ranking

özelliklerini birlikte çalıştırır.
