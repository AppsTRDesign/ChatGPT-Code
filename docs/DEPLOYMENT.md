# AlmaLinux deployment guide

## 1) Install packages

```bash
sudo dnf install -y httpd php php-cli php-mysqlnd php-json php-mbstring mariadb-server
sudo systemctl enable --now mariadb httpd
```

## 2) Deploy app

```bash
cd /var/www/vhosts/noasoft.org/game.noasoft.org
cp -r /path/to/repo/* .
cp .env.example .env
composer install --no-dev --optimize-autoloader
```

Update `.env` with DB credentials + geo settings.

## 3) Create database and user

```sql
CREATE DATABASE mmo_game CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'mmo_user'@'localhost' IDENTIFIED BY 'change_me';
GRANT ALL PRIVILEGES ON mmo_game.* TO 'mmo_user'@'localhost';
FLUSH PRIVILEGES;
```

## 4) Run migrations and seed map data

```bash
mysql -u mmo_user -p mmo_game < database/migrations/001_schema.sql
mysql -u mmo_user -p mmo_game < database/migrations/002_geo_columns.sql
mysql -u mmo_user -p mmo_game < database/migrations/003_player_travel.sql
mysql -u mmo_user -p mmo_game < database/migrations/004_player_progression.sql
mysql -u mmo_user -p mmo_game < database/migrations/005_travel_cost_coins.sql
mysql -u mmo_user -p mmo_game < database/migrations/006_region_stats.sql
mysql -u mmo_user -p mmo_game < database/migrations/007_travel_returning.sql
mysql -u mmo_user -p mmo_game < database/migrations/008_energy_redesign.sql
mysql -u mmo_user -p mmo_game < database/migrations/009_drop_legacy_energy_columns.sql
mysql -u mmo_user -p mmo_game < database/migrations/010_region_governance.sql
php database/seed_world.php
```

## 5) Web root
Point vhost document root to `/var/www/vhosts/noasoft.org/game.noasoft.org/public`.

## 6) Smoke test

```bash
curl -s https://game.noasoft.org/api/map/regions
```


## 7) Realtime travel socket server

```bash
cd realtime
npm install
SC_PORT=3001 DB_HOST=127.0.0.1 DB_PORT=3306 DB_USER=mmo_user DB_PASS=change_me DB_NAME=mmo_game node server.js
```


## 8) NGINX reverse proxy for Socket.IO (production)

```nginx
location /socket.io/ {
    proxy_pass http://127.0.0.1:3001/socket.io/;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";
    proxy_set_header Host $host;
}
```
