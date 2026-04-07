# Geopolitical MMO – Sistem Detay Dokümanı

Bu doküman, proje içinde şimdiye kadar uygulanan tüm ana mimari kararları, veri modelini, API uçlarını, oyun mekaniklerini ve ekran davranışlarını Türkçe olarak özetlemek için hazırlanmıştır.

---

## 1) Genel Mimari

Proje 3 ana parçadan oluşur:

1. **PHP 8.3 Backend (MVC + basit çekirdek)**
   - `app/Core`: Request/Response/Router/Database
   - `app/Controllers`: API endpoint katmanı
   - `app/Services`: oyun iş kuralları
   - `app/Models`: SQL erişim katmanı
   - `app/Middlewares`: auth kontrolü

2. **Frontend (public/ altında basit sayfalar + JS)**
   - Harita: `public/map.php`
   - Dashboard: `public/dashboard.php`
   - Profil: `public/profile.php`
   - Auth sayfaları: login/register

3. **Realtime Node Socket Server**
   - `realtime/server.js`
   - Yolculuk ilerleme ve bitiş event’leri için kullanılır.

---

## 2) Veritabanı Tasarımı (Normalize Yapı)

Güncel şema tek migration dosyasında (`database/migrations/001_schema.sql`) tutulur.

### 2.1 Temel Tablolar

- `users`
- `api_tokens`
- `player_profiles`
- `citizenships`
- `travel_logs`
- `player_travel`

### 2.2 Dünya/Harita Tabloları

- `countries`
  - seed kaynak ülke referansı

- `regions`
  - temel bölge kimlik/konum/owner bilgisi
  - `owner_region_id` ile bölgenin bağlı olduğu ülke-region (başkent) tutulur

- `region_infra`
  - bina count + bina level alanları
  - default: count = 100, level = 1

- `region_profile`
  - `capital_region_id`, `is_coastal`

- `country_economy`
  - ülke hazinesi, ülke vergi oranları, resource_type
  - default:
    - devlet parası: 250.000.000
    - altın: 250.000.000
    - uranyum/maden/petrol/elmas: 1.000.000
    - genel vergi: %10
    - satış/fabrika vergileri: %0

- `region_taxes`
  - bölgesel fabrika vergileri
  - default: tümü %0

---

## 3) Seed Mekanizması (`database/seed_world.php`)

Seed akışı:

1. FK check kapatılır, ilgili tablolar temizlenir.
2. `countries` verisi world json’dan yüklenir.
3. `regions` verisi yüklenir.
   - Her ülke için ilk bölge başkent/ülke region olarak işaretlenir (`region_type='country'`).
   - Diğerleri `region` olur.
4. `owner_region_id`, ülkenin capital region’ına bağlanır.
5. Her region için:
   - `region_infra` (count/level default)
   - `region_profile` (capital/coastal)
   - `region_taxes` (default 0)
6. Sadece `region_type='country'` olan kayıtlar için `country_economy` oluşturulur.

Not: `regions.population` seed sırasında **0** atanır. Population tamamen oyuncu hareketlerinden üretilir.

---

## 4) Auth ve Oyuncu Yaşam Döngüsü

### 4.1 Kayıt / Giriş

- Register ile kullanıcı oluşturulur.
- Konum ataması `LocationService` ile yapılır:
  - Önce GeoIP/Mindmind country denemesi
  - Bulunamazsa koordinata en yakın region fallback
- Player profile açılır ve başlangıç değerleri atanır.

### 4.2 Başlangıç Ekonomisi

- Oyuncu başlangıç coin’i: **100.000.000**
- Gold ve enerji alanları profile tablosunda tutulur.

### 4.3 Population Davranışı (Kullanıcı Bazlı)

- Yeni kullanıcı bir bölgeye yerleştiğinde population +1
- Yolculuk tamamlandığında:
  - çıktığı bölge population -1
  - vardığı bölge population +1
- Aynı bölgeye dönülüyorsa net değişim yok

---

## 5) Yolculuk Mekaniği

### 5.1 Maliyet ve Süre

- Mesafe Haversine ile hesaplanır.
- Uçuş maliyeti yüksek çarpanlıdır (distance bazlı, min eşik var).
- Hız formülü havaalanı level’ına bağlıdır:

`effective_speed = base_speed * (1 + log(airport_level + 1) * 0.25)`

### 5.2 Enerji Tüketimi

- Travel başlatırken instant/total energy ve coin kontrol edilir.
- Yetersizse işlem reddedilir.

### 5.3 Vergi Kesintisi ve Hazine

- Yolculuk coin maliyetinden ülkenin `general_tax_rate` oranında vergi hesaplanır.
- Bu değer `country_economy.treasury_state_money` üzerine eklenir.

### 5.4 XP Davranışı

- Travel tamamlanınca XP kasma kapatılmıştır.
- Gelecekte savaş/çalışma ile yeni XP sistemi planlıdır.

---

## 6) Dashboard Özellikleri

Dashboard API (`/api/stats/dashboard`) ile çoklu sıralama döndürülür:

1. **Top Regions**
   - region total score = bina count toplamı
2. **Top Countries**
   - ülkeye bağlı region bina count toplamı
3. **Top Airports / Armies / Hospitals / Educations / Ports**
   - doğrudan level alanlarına göre sıralama
4. **Top Country Population**
   - player_profiles üzerinden ülke bazlı kullanıcı sayısı
5. **Top Region Population**
   - player_profiles üzerinden region bazlı kullanıcı sayısı

Country detail ekranında hazine kalemleri gösterilir:
- devlet parası, altın, elmas, petrol, maden, uranyum

---

## 7) Harita Özellikleri

- Region polygon renkleri region verisinden gelir.
- Region seçince:
  - owner
  - kaynak
  - population
  - tahmini uçuş süresi
  - maliyet bilgisi gösterilir.
- Travel aktifse durum paneli güncellenir.

---

## 8) Profil Özellikleri

- Oyuncu level/xp/enerji/coin/gold bilgileri görünür.
- Aktif travel varsa süre/progress gösterilir.
- Travel iptal butonu vardır (returning geçişi).
- **Gold ile enerji alma formu**:
  - kullanıcı istediği enerji miktarını girer
  - sistem oransal gold maliyeti hesaplar
  - yeterli gold yoksa buton pasif olur

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

### Map/Stats
- `GET /api/map/regions`
- `GET /api/map/countries`
- `GET /api/map/region-detail?id=...`
- `GET /api/map/country-detail?id=...`
- `GET /api/stats/dashboard`

### Actions
- `POST /api/region/action` (travel vb)

---

## 10) Realtime Server

- Socket.IO ile travel progress ve complete event’leri yayınlanır.
- Frontend map tarafı bu event’lerle oyuncunun uçuş animasyonu/progress bilgisini tazeler.

---

## 11) Dağıtım ve Çalıştırma

1. `.env` hazırlığı
2. `001_schema.sql` çalıştırma
3. `php database/seed_world.php`
4. PHP app + web server
5. İsteğe bağlı realtime server başlatma

---

## 12) Bilinen Kapsam ve Yol Haritası Notları

- XP sistemi travel’den ayrıldı; savaş/çalışma ile yeni formül planlı.
- Vergi ve hazine altyapısı normalize edildi; ekonomik mekanikler genişletilebilir.
- Region/country ownership ve istatistik sistemi kullanıcı odaklı population’a taşındı.

---

## 13) Özet

Bu noktada sistem:
- normalize DB tasarımı,
- kullanıcı bazlı population,
- level + count ayrımı,
- ülke hazinesi ve vergi altyapısı,
- dinamik travel ekonomi/süre hesapları,
- dashboard sıralamaları,
- profile üzerinde esnek enerji satın alma

özelliklerini birlikte çalıştıran bir çekirdeğe sahiptir.
