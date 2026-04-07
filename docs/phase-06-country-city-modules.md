# Grand Strategy Political Simulation — Phase 6 (Country + City Modules)

## 1) Bu fazda ne inşa edildi?

Phase 6 kapsamında country/city detay API'leri, ranking endpointleri ve trend history endpointleri eklendi.

### Backend endpointleri
- `GET /api/v1/countries/:countryCode`
- `GET /api/v1/countries/rankings?limit=20`
- `GET /api/v1/cities/:countryCode/:cityName`
- `GET /api/v1/cities/rankings?limit=20`
- `GET /api/v1/stats/countries/:countryCode/history?days=30`
- `GET /api/v1/stats/cities/:cityId/history?days=30`

### Frontend ekranları
- `DashboardPage`: country/city ranking listeleri
- `CountryPage`: ülke detay + trend görünümü
- `CityPage`: şehir detay + trend görünümü
- Map kartlarından detay sayfalarına yönlendirme (`View Details`) eklendi.

---

## 2) Teknik notlar

- Country detayında region/city sayıları aggregate SQL ile dönülür.
- Ranking endpointleri limit parametresini 1..100 aralığında normalize eder.
- Stats endpointleri günlük snapshot tablolarından (`country_stats_daily`, `city_stats_daily`) trend döner.
- Frontend React Query ile cache edilen API state kullanır.

---

## 3) Phase 7 hazırlığı

- City ve country detay route altyapısı tamamlandığı için Travel Engine ekranları doğrudan bu sayfalara entegre edilebilir.
- Ranking ekranı, ileride savaş/ekonomi/politik skorlarla genişletilebilir.
