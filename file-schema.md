# File Schema / Dosya İskelet Şeması

Bu dosya proje iskeletinin güncel durumunu gösterir.

- ✅ = tamamlandı / mevcut
- 🟡 = kısmi (iskelet var, kapsam genişletilebilir)
- ⏳ = henüz yok / planlanabilir

---

## 1) Kök Dizin Şeması

```text
.
├── backend/                ✅
├── frontend/               ✅
├── shared/                 ✅
├── lang/                   ✅
├── docs/                   ✅
├── scripts/
│   ├── world/              ✅
│   └── deploy/             ✅
├── README.md               ✅
├── todolist.md             ✅
└── file-schema.md          ✅ (bu dosya)
```

---

## 2) Backend Şeması

```text
backend/
├── config/
│   ├── env.js                          ✅
│   └── balancing.js                    ✅
├── core/
│   ├── db.js                           ✅
│   ├── jwt.js                          ✅
│   ├── password.js                     ✅
│   ├── http-error.js                   ✅
│   ├── auth-middleware.js              ✅
│   └── anti-abuse.js                   ✅
├── database/
│   ├── schema.sql                      ✅
│   ├── migrations/                     ✅
│   ├── seeds/                          ✅
│   └── world-data/                     ✅
├── modules/
│   ├── auth/                           ✅
│   ├── users/                          ✅
│   ├── countries/                      ✅
│   ├── cities/                         ✅
│   ├── regions/                        ✅
│   ├── map/                            ✅
│   ├── travel/                         ✅
│   ├── economy/                        ✅
│   ├── politics/                       ✅
│   ├── war/                            ✅
│   ├── governors/                      ✅
│   ├── chat/                           ✅
│   ├── notifications/                  ✅
│   ├── realtime/                       ✅
│   ├── stats/                          ✅
│   ├── i18n/                           ✅
│   ├── admin/                          ✅
│   └── inventory/                      ✅
├── sockets/
│   ├── index.js                        ✅
│   └── socket-state.js                 ✅
├── tests/
│   └── run-tests.js                    ✅
├── utils/
│   ├── geo.js                          ✅
│   └── simulation-calculators.js       ✅
├── src/
│   ├── app.js                          ✅
│   └── server.js                       ✅
├── .env.example                        ✅
└── package.json                        ✅
```

---

## 3) Frontend Şeması

```text
frontend/
├── src/
│   ├── app/routes/                     ✅
│   ├── components/
│   │   ├── ui/                         ✅
│   │   ├── map/                        ✅
│   │   └── cards/                      ✅
│   ├── pages/
│   │   ├── dashboard/                  ✅
│   │   ├── map/                        ✅
│   │   ├── country/                    ✅
│   │   ├── city/                       ✅
│   │   ├── travel/                     ✅
│   │   ├── economy/                    ✅
│   │   ├── elections/                  ✅
│   │   ├── war/                        ✅
│   │   └── realtime/                   ✅
│   ├── services/                       ✅
│   ├── i18n/                           ✅
│   ├── lang/                           ✅
│   ├── main.jsx                        ✅
│   └── styles.css                      ✅
├── index.html                          ✅
├── package.json                        ✅
└── vite/tailwind/postcss config        ✅
```

---

## 4) Dokümantasyon Şeması

```text
docs/
├── phase-01-architecture.md                    ✅
├── phase-02-database.md                        ✅
├── phase-03-world-data.md                      ✅
├── phase-04-auth-user-system.md                ✅
├── phase-05-map-system.md                      ✅
├── phase-06-country-city-modules.md            ✅
├── phase-07-travel-engine.md                   ✅
├── phase-08-governor-city-development.md       ✅
├── phase-09-politics.md                        ✅
├── phase-10-economy.md                         ✅
├── phase-11-war.md                             ✅
├── phase-12-realtime-notifications.md          ✅
├── phase-13-ui-polish.md                       ✅
├── phase-14-testing-balancing.md               ✅
└── phase-15-deployment-guide.md                ✅
```

---

## 5) Tamamlananlar / Geriye Kalanlar

### Tamamlananlar
- ✅ Phase 1 → Phase 15 ana kapsamlarının tamamı.
- ✅ Backend + Frontend + DB + World seed + Realtime + Deployment guide.

### Geriye kalan teknik boşluklar (opsiyonel genişletme)
- 🟡 E2E test katmanı (API integration + UI browser tests) henüz yok.
- 🟡 CI/CD pipeline (GitHub Actions vb.) henüz tanımlı değil.

---

## 6) Yeni Yapılacaklar Listesi (Post-Scaffold Backlog)

1. ⏳ **CI/CD**: lint + test + build + deploy pipeline.
2. ⏳ **Integration testleri**: DB bağlı API testleri.
3. ⏳ **E2E UI testleri**: Playwright/Cypress ile kritik akışlar.
4. ⏳ **Admin panel derinleştirme**: moderation, balancing sliders, i18n live edit.
5. ⏳ **Inventory modülü tamamı**: item effects, equip logic, market/trade.
6. ⏳ **Regions modülü genişletme**: detaylı control/battle/front mekaniği.
7. ⏳ **Observability**: metrics, tracing, centralized logging.
8. ⏳ **Security hardening**: rate limit persistence, audit log, stricter RBAC.
9. ⏳ **Production SRE**: backup-restore drill + disaster recovery runbook.
10. ⏳ **Balancing iteration loop**: telemetry tabanlı ekonomi/savaş/seyahat tuning.
