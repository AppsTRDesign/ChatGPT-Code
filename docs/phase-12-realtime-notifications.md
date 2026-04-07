# Phase 12: Realtime + Notifications

Bu fazda chat, bildirim ve canlı event akışları tamamlandı.

## Backend kapsamı

- Socket.io sunucu kurulumu (`backend/sockets/`)
- JWT destekli socket handshake ve user/country/city/chat room aboneliği
- Chat modülü:
  - `GET /api/v1/chat/messages`
  - `POST /api/v1/chat/messages`
- Notifications modülü:
  - `GET /api/v1/notifications`
  - `PATCH /api/v1/notifications/:notificationId/read`
  - `POST /api/v1/notifications/demo`
- Realtime modülü:
  - `GET /api/v1/realtime/snapshot`
  - `POST /api/v1/realtime/publish-demo`

## Canlı event kanalları

- `chat:message`
- `notification:new`
- `realtime:travel`
- `realtime:election`
- `realtime:war`

## Frontend kapsamı

Yeni sayfa: `frontend/src/pages/realtime/RealtimePage.jsx`

- Socket.io-client ile canlı event dinleme
- Realtime snapshot ve demo publish tetikleme
- Chat scope bazlı mesaj listeleme + gönderme
- Bildirim listeleme + read state güncelleme

Route: `/realtime`

## Not

- Uçtan uca akış auth token (`localStorage.accessToken`) ile çalışır.
- Node.js 16.20.2 ile uyumlu dependency sürümleri kullanıldı.
