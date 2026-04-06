# Phase 14: Testing + Balancing

Bu fazda anti-abuse placeholder, dengeleme parametreleri ve otomasyon test altyapısı eklendi.

## 1) Dengeleme (Balancing) katmanı

Yeni dosya: `backend/config/balancing.js`

- Economy parametreleri (tax sınırları, XP/work, multiplier değerleri)
- Travel parametreleri (hızlar, minimum süreler, buffer ve bilet katsayıları)
- War parametreleri (katkı gücü formülü katsayıları, score delta sınırları)
- Anti-abuse cooldown/burst limit parametreleri

## 2) Anti-abuse placeholder

Yeni dosya: `backend/core/anti-abuse.js`

- In-memory cooldown + burst kontrolü
- `assertActionAllowed(userId, actionKey)` ile aksiyon koruması
- Test desteği için `__resetForTests()`

Uygulanan aksiyonlar:

- Economy `performWork` (`work` cooldown)
- Chat `sendMessage` (`chat_message` cooldown/burst)
- War `reinforceBattle` (`battle_reinforce` cooldown)

## 3) Servislerin balancing parametrelerine taşınması

- Economy servisindeki hardcoded oranlar balancing config’e bağlandı.
- Travel servisindeki hız/süre/maliyet hesapları balancing config’e bağlandı.
- War servisindeki contribution ve resolve score formülleri balancing config’e bağlandı.

## 4) Otomasyon testleri

Yeni dosya: `backend/tests/run-tests.js`

Node16 uyumlu, dependency gerektirmeyen test koşucusu ile şunlar doğrulanıyor:

- Geo distance sanity (Haversine)
- Balancing config sanity
- Travel helper hesapları
- Anti-abuse cooldown davranışı

Çalıştırma:

```bash
cd backend
npm test
```

## Sonuç

Phase 14 kapsamı tamamlandı: anti-abuse placeholder, ekonomi/seyahat/savaş dengeleme parametreleştirmesi ve otomasyon testleri eklendi.
