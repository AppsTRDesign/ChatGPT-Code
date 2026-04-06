# Phase 15: Production Deployment Guide (AlmaLinux + Plesk + Apache + Node 16.20.2 + MariaDB)

Bu rehber hedef ortam için birebir hazırlanmıştır:

- Domain: `game.noasoft.org`
- Web root path: `/var/www/vhosts/noasoft.org/game.noasoft.org`
- OS: AlmaLinux
- Panel: Plesk
- Runtime: Node.js 16.20.2
- DB: MariaDB
- Web server: Apache (Plesk)

---

## 1) Sunucuda önerilen klasör yapısı

```text
/var/www/vhosts/noasoft.org/game.noasoft.org/
  app/
    backend/
    frontend/
    shared/
    lang/
    scripts/
    docs/
  public/
    dist/                 # frontend build output
  logs/
    backend.log
    backend-error.log
  storage/
    uploads/
    cache/
```

> Öneri: repo kodunu `app/` altında tutun, frontend çıktısını `public/dist` altında yayınlayın.

---

## 2) Plesk domain ve Node.js uygulama kurulumu

1. Plesk > **Websites & Domains** > `game.noasoft.org`.
2. **Node.js** extension kurulu olmalı.
3. Node.js app root: `app/backend`.
4. Document root: `public`.
5. Application startup file: `src/server.js`.
6. Node.js version: **16.20.2**.

---

## 3) Backend bağımlılık ve çalışma adımları

```bash
cd /var/www/vhosts/noasoft.org/game.noasoft.org/app/backend
npm ci --omit=dev
npm run check
npm test
```

Plesk Node.js panelinden:

- **NPM install**
- **Restart App**

---

## 4) Frontend build ve yayın adımları

```bash
cd /var/www/vhosts/noasoft.org/game.noasoft.org/app/frontend
npm ci
npm run build
```

Build çıktısını yayın köküne alın:

```bash
mkdir -p /var/www/vhosts/noasoft.org/game.noasoft.org/public/dist
rsync -a --delete dist/ /var/www/vhosts/noasoft.org/game.noasoft.org/public/dist/
```

Apache/Plesk için `public/index.php` yerine static sunum tercih ediliyorsa, domain document root `public` kalıp `dist` altına rewrite yapılır.

---

## 5) Environment variable yapılandırması

`app/backend/.env` (örnek):

```env
NODE_ENV=production
PORT=3001
CORS_ORIGIN=https://game.noasoft.org

DB_HOST=localhost
DB_PORT=3306
DB_USER=game_user
DB_PASSWORD=strong_password
DB_NAME=game_db

JWT_ACCESS_SECRET=change_me_access_secret
JWT_REFRESH_SECRET=change_me_refresh_secret
JWT_ACCESS_EXPIRES_IN=15m
JWT_REFRESH_EXPIRES_IN=30d
```

Plesk Node.js panelinden env değerleri ayrıca set edilmelidir.

---

## 6) MariaDB kurulum, şema ve seed import

```bash
mysql -u root -p
```

```sql
CREATE DATABASE game_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'game_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON game_db.* TO 'game_user'@'localhost';
FLUSH PRIVILEGES;
```

Şema + seed import:

```bash
cd /var/www/vhosts/noasoft.org/game.noasoft.org/app
mysql -u game_user -p game_db < backend/database/schema.sql
mysql -u game_user -p game_db < backend/database/seeds/001_base_languages.sql
mysql -u game_user -p game_db < backend/database/seeds/generated/002_world_bootstrap.sql
```

Migration dosyaları kronolojik olarak ayrıca uygulanmalıdır.

---

## 7) Reverse proxy / Apache yönlendirme notları

- Frontend static: `https://game.noasoft.org/`
- API backend: `https://game.noasoft.org/api/v1/...`
- Socket.IO: `https://game.noasoft.org/socket.io/...`

Plesk Apache ek yapılandırmasında Node app’e proxy kuralı gerektiğinde, `/api` ve `/socket.io` backend portuna yönlendirilir.

---

## 8) Dosya izinleri ve writable alanlar

Önerilen owner/group: Plesk domain kullanıcısı.

```bash
chown -R <plesk_user>:psacln /var/www/vhosts/noasoft.org/game.noasoft.org
chmod -R 750 /var/www/vhosts/noasoft.org/game.noasoft.org/app
chmod -R 770 /var/www/vhosts/noasoft.org/game.noasoft.org/logs
chmod -R 770 /var/www/vhosts/noasoft.org/game.noasoft.org/storage
```

---

## 9) Loglama stratejisi

- Backend stdout/stderr logları Plesk Node.js log ekranı + dosya loglarına alınmalı.
- Minimum log dosyaları:
  - `logs/backend.log`
  - `logs/backend-error.log`
- Log rotate (logrotate) ile günlük/haftalık rotate önerilir.

---

## 10) Production güvenlik önerileri

- Zorunlu HTTPS + HSTS.
- JWT secret’ları uzun, rastgele ve environment bazlı olmalı.
- DB user yalnızca gerekli yetkilerle sınırlandırılmalı.
- CORS sadece `https://game.noasoft.org` olmalı.
- Rate limit / anti-abuse katmanı açık tutulmalı.
- Admin endpointleri IP allowlist + rol kontrolü ile korunmalı.

---

## 11) Güncelleme/deploy süreci (zero-downtime odaklı)

1. Yeni sürümü `app/` altına çekin.
2. `npm ci` (backend/frontend) çalıştırın.
3. Frontend `npm run build` alın, `public/dist` swap edin.
4. DB migration çalıştırın.
5. Health check yapın.
6. Plesk Node.js app restart.

Öneri: Release klasörleri ile symlink swap yaklaşımı kullanın.

---

## 12) Rollback stratejisi

- Son release klasörünü saklayın (`releases/<timestamp>`).
- Sorunda symlink’i eski release’e alın.
- Geriye dönük migration yoksa DB snapshot restore edin.

Örnek rollback scripti: `scripts/deploy/rollback.sh`.

---

## 13) Dil dosyalarının production yönetimi

- Kanonik dosyalar: `lang/en.json`, `lang/tr.json`.
- Frontend/backend kopyaları deployment’da senkron tutulmalı.
- Yeni dil ekleme akışı:
  1. `lang/<code>.json` oluştur
  2. DB `languages` tablosuna `is_enabled=1` ekle
  3. Admin panel i18n yönetiminden key/value düzenle
  4. Frontend cache temizle ve app restart

---

## 14) Go-Live kontrol listesi

- [ ] `/api/v1/health` 200 dönüyor
- [ ] Socket bağlantısı kuruluyor
- [ ] Login/register çalışıyor
- [ ] Map yükleniyor
- [ ] Travel/Economy/Politics/War endpointleri 2xx
- [ ] Realtime event ve notification akışı aktif
- [ ] EN/TR switch doğru çalışıyor

---

## 15) Komut referansı

```bash
# Backend
cd app/backend && npm ci --omit=dev && npm run check && npm test

# Frontend
cd app/frontend && npm ci && npm run build

# Seed regenerate (opsiyonel)
node app/scripts/world/build-world-seed.js
```
