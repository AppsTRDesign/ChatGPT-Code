# Task-5 Çıktısı: Ulus/Şehir Domain Kuralları ve Stat Formülleri

## Yapılanlar
1. `StatFormulaService` eklendi.
   - city score
   - nation tier
   - energy tick seconds
   - work yield
   - battle win chance / battle xp
   - stat upgrade cost
2. `GameService` formül servisiyle entegre edildi.
   - enerji rejenerasyonu nation tier + top city bonusuna bağlandı.
   - çalışma verimi ve XP formülle hesaplanıyor.
   - savaş kazanma olasılığı ve savaş XP formülle hesaplanıyor.
   - stat geliştirme maliyeti dinamik hale getirildi.
3. Dashboard state genişletildi.
   - `nation` context: `nation_tier`, `player_count`, `avg_city_score`.
4. Frontend düzeltmesi.
   - Dashboard'a Nation Tier göstergesi eklendi.
   - `jsVectorMap is not defined` için dinamik script yükleme/fallback uygulandı.

## Etkilenen dosyalar
- `src/Services/StatFormulaService.php`
- `src/Services/GameService.php`
- `views/game/index.php`
- `assets/js/app.js`
- `views/layouts/base.php`
- `docs/RIVALREGIONS_TODO.md`

## Not
- Bu adım formül motorunun temelini kurdu; bir sonraki adımda canlı ekonomi/savaş verileriyle balans iterasyonu yapılmalı.
