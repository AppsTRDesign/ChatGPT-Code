# Task-9: Market Tam Sürüm (Vergi, Komisyon, Korumalar)

## Tamamlananlar

- Market ilanlarına finansal kırılım alanları eklendi:
  - `tax_rate_percent`
  - `seller_commission_percent`
  - `gross_total`
  - `tax_total`
  - `seller_commission_total`
  - `seller_net_total`
- `market_transactions` tablosu ile her satışın muhasebe kayıtları kalıcı hale getirildi.
- İlan açma sırasında:
  - Dinamik referans fiyat (`resource_market_prices` / `resources.base_price`) üzerinden taban-tavan bandı kontrolü eklendi.
  - Oyuncu başına açık ilan limiti eklendi.
- Satın alma sırasında:
  - Satış satırı `FOR UPDATE` kilidiyle okunuyor.
  - Alıcı/satıcı bakiye ve ilan durumu yarış koşullarına karşı transaction içinde yönetiliyor.
  - Kendi ilanını satın alma engellendi.
- Dashboard market tablosu toplam tutar ve vergi kolonlarıyla genişletildi.

## Yeni Ayarlar (settings)

- `market_buyer_tax_percent` (varsayılan: 4)
- `market_seller_commission_percent` (varsayılan: 3)
- `market_price_floor_ratio` (varsayılan: 0.50)
- `market_price_ceiling_ratio` (varsayılan: 2.50)
- `market_max_open_offers_per_user` (varsayılan: 15)
