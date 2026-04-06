# Grand Strategy Political Simulation — Phase 9 (Politics)

## 1) Bu fazda ne inşa edildi?

Phase 9 kapsamında seçim, adaylık, oylama, yasa önerisi ve politika güncelleme akışları eklendi.

### Backend endpointleri
- `GET /api/v1/politics/elections`
- `POST /api/v1/politics/elections` (auth)
- `POST /api/v1/politics/elections/:electionId/candidate` (auth)
- `POST /api/v1/politics/elections/:electionId/vote` (auth)
- `GET /api/v1/politics/elections/:electionId/results`
- `POST /api/v1/politics/elections/:electionId/finalize` (auth)
- `POST /api/v1/politics/laws` (auth)
- `POST /api/v1/politics/laws/:lawId/vote` (auth)
- `POST /api/v1/politics/laws/:lawId/finalize` (auth)

### Politics mantığı
- election scope (country/city) bazlı adaylık ve oy yetkisi doğrulaması
- seçim finalizasyonu sonrası
  - presidency election -> `presidents` + `countries.active_president_user_id`
  - governor election -> `governors` + `cities.active_governor_user_id`
- yasa oylama (yes/no) ve sonuçlarına göre `country_policies` güncellemesi

---

## 2) Frontend

Yeni sayfa: `ElectionsPage`
- aktif seçimleri listeler
- aday olma / oy verme / sonuç görüntüleme
- seçim finalizasyonu
- law demo akışı (öneri, oy, finalize)

---

## 3) Phase 10 hazırlığı

Politics modülü economy fazına hazırdır:
- tax_rate ve military_spending_ratio gibi policy parametreleri economy hesaplarına bağlanabilir.
