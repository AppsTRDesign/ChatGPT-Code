# Task-10: Savaş Sistemi Çekirdeği + Raporlama

## Tamamlananlar

- Savaş domain tabloları eklendi:
  - `wars` (ülke savaşı üst kaydı)
  - `war_battles` (saldırı logları)
- Başlatma akışı:
  - Kullanıcının ülkesi adına hedef ülkeye savaş açma
  - Aktif savaş çakışma kontrolü
  - Seviye/savaş gücü eşik kontrolü
- Saldırı akışı:
  - Enerji maliyeti
  - Oyuncu bazlı cooldown
  - Stat + nation tier + rastgele roll bazlı hasar
  - Skor güncelleme ve `score_to_win` ile otomatik savaş bitişi
- Raporlama:
  - Her saldırı `war_battles` tablosuna yazılır
  - Dashboard üzerinde son savaş logları tablo halinde gösterilir
- API uçları:
  - `POST /api/war/start`
  - `POST /api/war/attack`

## Yeni Ayarlar (settings)

- `war_attack_energy_cost` (varsayılan: 280)
- `war_attack_cooldown_seconds` (varsayılan: 45)
- `war_damage_min` (varsayılan: 40)
- `war_damage_max` (varsayılan: 160)
- `war_score_to_win` (varsayılan: 1000)
