# Grand Strategy Political Simulation — Phase 10 (Economy)

## 1) Bu fazda ne inşa edildi?

Phase 10 kapsamında work/salary/treasury/taxation döngüsü eklendi.

### Backend
- Yeni migration:
  - `005_phase10_jobs_work_sessions.sql`
  - `jobs` ve `work_sessions` tabloları
- Yeni economy modülü:
  - `GET /api/v1/economy/jobs`
  - `GET /api/v1/economy/overview` (auth)
  - `POST /api/v1/economy/work` (auth)
  - `POST /api/v1/economy/transfer-support` (auth)

### İş kuralları
- Work action:
  - job baz maaşı
  - city industry/employment çarpanları
  - country tax rate uygulaması
  - net gelir user profile’a eklenir
  - vergi city/country hazinesine paylaştırılır
  - work_session + transaction kaydı oluşturulur
- Transfer support:
  - sadece aktif president, kendi ülkesindeki şehre hazine transferi yapabilir

---

## 2) Frontend

Yeni sayfa: `EconomyPage`
- ekonomi overview kartları
- job listesi ve work butonu
- work sonucu (gross/tax/net)
- president transfer support aksiyonu
- recent work sessions listesi

---

## 3) Phase 11 hazırlığı

Economy döngüsü artık war fazına temel sağlar:
- vergi/hazine akışları savaş maliyetlerine bağlanabilir.
- work economy çıktıları askeri üretim ve lojistik maliyetlerine entegre edilebilir.
