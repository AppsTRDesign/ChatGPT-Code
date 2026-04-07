# Phase 11: War

Bu fazda savaş sistemi için çekirdek akışlar backend ve frontend tarafında tamamlandı.

## Kapsam

- Savaş ilanı (president yetkisi)
- Hedef region seçimi ve battle başlatma
- Oyuncu katkısı (reinforce) ile saldıran/savunan güç birikimi
- Battle çözümleme (resolve) ile kazananın belirlenmesi
- Region kontrol transferi (`regions.controlling_country_id`)
- War sonucu kapanışı (`wars.status = ended` + `peace_terms_json`)

## Backend

Yeni modül: `backend/modules/war`

Ayrıca şema tarafı migration ile uyumlu olacak şekilde `battle_participations` tablosu `schema.sql` içerisine de işlendi.

Endpointler:

- `GET /api/v1/war/wars` → son savaşlar
- `GET /api/v1/war/overview` → kullanıcının ülkesine ait savaş/battle özeti (auth)
- `POST /api/v1/war/declare` → savaş ilanı (auth + active president)
- `POST /api/v1/war/battles/:battleId/reinforce` → battle katkısı (auth)
- `POST /api/v1/war/battles/:battleId/resolve` → battle sonuçlandırma (auth + war president)

## Savaş Akışı

1. Active president, savunmacı ülkeyi seçerek savaş ilan eder.
2. Sistem hedef region belirler (opsiyonel `targetRegionId`, yoksa strategic value en yüksek region).
3. War ve ilk battle oluşturulur; region `battle_status = active` olur.
4. Oyuncular energy harcayarak attacker/defender tarafına güç katkısı yapar.
5. Resolve anında güç karşılaştırılır, kazanan belirlenir.
6. Hedef region kontrolü kazanana transfer edilir.
7. War kapatılır, skor/exhaustion güncellenir.

## Frontend

Yeni sayfa: `frontend/src/pages/war/WarPage.jsx`

- Savaş ilanı formu
- Son savaşlar listesi
- Ülke battle listesi
- Reinforce (attacker/defender)
- Resolve aksiyonu

Route: `/war`

## Notlar

- Auth işlemleri mevcut token akışıyla uyumludur (`localStorage.accessToken`).
- Tüm implementasyon Node.js 16.20.2 ile uyumludur.
