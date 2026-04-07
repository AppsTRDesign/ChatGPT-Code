# Grand Strategy Political Simulation — Phase 7 (Travel Engine)

## 1) Bu fazda ne inşa edildi?

Phase 7 kapsamında server-side travel engine eklendi.

### Backend endpointleri
- `POST /api/v1/travel/quote` (auth)
- `POST /api/v1/travel/start` (auth)
- `GET /api/v1/travel/active` (auth)

### Engine yetenekleri
- Haversine formülü ile gerçek mesafe hesabı
- Travel type belirleme:
  - same_city
  - local_travel
  - domestic_flight
  - international_flight
- Süre hesabı:
  - mesafe
  - travel type
  - departure airport level
- Ticket cost hesabı
- Permit kontrolü:
  - international uçuşlarda visa/work permit kontrolü
  - `country_policies` + `country_policy_rules` uyumluluğu
  - aktif/valid `visas` ve `work_permits` doğrulaması
- Travel progress:
  - `travels` tablosuna `in_progress` kayıt
  - `active` sorgusunda süre dolmuşsa otomatik completion
  - completion sonrası kullanıcının current city/country güncellemesi

---

## 2) Frontend Travel Center

- Yeni sayfa: `frontend/src/pages/travel/TravelPage.jsx`
- City listesi ranking endpointinden alınır.
- Quote ve Start aksiyonları UI’dan tetiklenir.
- Active travel her 5 saniyede polling ile yenilenir.

---

## 3) Phase 8 hazırlığı

Travel engine artık governor/city development fazına temel sağlar:
- airport seviyesinin doğrudan travel süresine etkisi var.
- şehir gelişimi arttıkça gelecekte travel efficiency bonusları kolayca genişletilebilir.
