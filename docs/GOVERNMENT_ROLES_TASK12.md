# Task-12: Bakanlık Rolleri ve Yetki Akışları

## Tamamlananlar

- Yetki matrisi için `government_role_permissions` tablosu eklendi.
- Bakanlık aksiyonlarının izlenmesi için `ministry_action_logs` tablosu eklendi.
- Rol atama akışı:
  - Başkan yetkisiyle bakanlık rolü atama (`assignGovernmentRole`)
  - API: `POST /api/gov/assign-role`
- Yetkili bakan aksiyonları:
  - Ekonomi: market alıcı vergi / satıcı komisyon ayarı
  - Savunma: savaş skor hedefi ayarı
  - API: `POST /api/gov/action`
- Dashboard güncellemesi:
  - Bakanlık rol listesi
  - Son bakanlık aksiyon logları
  - Rol atama ve bakanlık aksiyon formları

## Seedlenen Rol Yetkileri

- `president`
  - `gov.assign_roles`
  - `gov.market.adjust_tax`
  - `gov.war.adjust_score_to_win`
- `minister_economy`
  - `gov.market.adjust_tax`
- `minister_defense`
  - `gov.war.adjust_score_to_win`
