# Grand Strategy Political Simulation — Phase 8 (Governor / City Development)

## 1) Bu fazda ne inşa edildi?

Phase 8 kapsamında governor yetkileri ve şehir gelişim mekanikleri eklendi.

### Backend endpointleri
- `GET /api/v1/governors/cities/:cityId`
- `GET /api/v1/governors/cities/:cityId/projects`
- `POST /api/v1/governors/cities/:cityId/projects` (auth)
- `POST /api/v1/governors/cities/:cityId/projects/:projectId/effort` (auth)

### Backend iş kuralları
- Sadece aktif governor ilgili şehirde proje başlatabilir/ilerletebilir.
- Proje başlatımı şehir hazinesinden bütçe düşer.
- Proje türüne göre farklı city stat etkileri uygulanır.
- Effort ile progress artar; %100 olduğunda proje completed olur.
- Proje tamamlandığında governor experience/reputation artışı uygulanır.

---

## 2) Frontend entegrasyonu

`CityPage` ekranı governor/city development paneliyle genişletildi:
- aktif governor bilgisi
- yeni proje başlatma formu
- proje listesi ve `+ Effort` aksiyonu
- proje ilerleme yüzdesi ve durum takibi

---

## 3) Phase 9 hazırlığı

Governor/city development altyapısı politics fazına hazırdır:
- governor seçimi/ataması politik mekaniklere bağlanabilir.
- budget/proposal akışları city project sistemiyle doğrudan entegre edilebilir.
