# Task-16: Dünya Haritası Modülü (İnteraktif)

## Tamamlananlar

- Oyun dashboard haritasına etkileşim katmanı eklendi:
  - Ülkeye tıklayınca detay kartı güncelleniyor.
  - Bölge tooltip’inde ülke adı ve oyuncu sayısı gösteriliyor.
- Harita katman seçimi eklendi:
  - `influence` (admin/world builder katmanı)
  - `population` (ülke oyuncu yoğunluğu)
  - `resource_total_yield` (ülke toplam günlük kaynak üretimi)
- Marker filtreleme eklendi:
  - Tümü / Şehir / POI ayrımı ile görsel yoğunluk kontrolü.
- Dinamik lejant eklendi:
  - Seçili katmana göre açıklama metni anlık değişiyor.
- Ülke detay paneli eklendi:
  - Oyuncu sayısı, şehir sayısı, POI sayısı, günlük üretim özeti
  - En aktif 3 şehir listesi

## Teknik Notlar

- Katman verileri yeni endpoint gerektirmeden mevcut dashboard state içinden beslenir (`state.map`, `state.countries`).
- Yoğunluk/üretim katmanlarında renk skalası verinin normalleştirilmiş oranından türetilir.
- Varsayılan odak, giriş yapan kullanıcının ülke koduna göre seçilir.
