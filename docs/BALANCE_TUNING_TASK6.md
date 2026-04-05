# Task-6 Çıktısı: Enerji/XP/Level/Stat Formül Motoru İyileştirme

## Yapılanlar
1. Balance config katmanı eklendi
   - `BalanceConfigService` ile ayarlar `settings` tablosundan okunur.
   - Sabit değerler koddan çıkarılıp ayarlanabilir hale getirildi.

2. Stat formülleri genişletildi
   - `StatFormulaService` artık config parametreleri ile çalışır.
   - Level XP gereksinim fonksiyonu (`levelXpRequirement`) eklendi.

3. GameService entegrasyonu
   - Enerji regen, work XP, battle XP config tabanlı oldu.
   - `autoLevelUp` tek seviye yerine çoklu seviye atlama (while loop) destekli hale geldi.
   - Dashboard state'e `progress.next_level_xp` eklendi.

4. Veri katmanı
   - `20260405_000005_balance_tuning.sql` ile balance ayarları seedlendi.

5. UI yansıması
   - Dashboard'a “Sonraki seviye XP” kartı eklendi.

## Not
- Bu adım balans motorunu parametreleştirdi. Sonraki adımda canlı telemetriye göre tuning yapılmalı.
