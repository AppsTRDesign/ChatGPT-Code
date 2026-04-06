# Task-26 & Task-27: Gezgin Tüccar + Node.js Socket.io Gerçek Zamanlı Altyapı

## Tamamlananlar

- Gezgin tüccar event veri modeli eklendi (`traveler_merchants`).
- Gerçek zamanlı savaş odası veri modeli eklendi (`realtime_war_rooms`, `realtime_war_events`).
- Node.js 16.20.2 uyumlu socket servis klasörü eklendi (`realtime-socket/`).
- Socket event tasarımı eklendi:
  - `room:join`
  - `war:attack`
  - `upgrade:tick`
- Frontend ve backend, realtime savaş/stat geri sayımına hazır state alanlarıyla genişletildi.
