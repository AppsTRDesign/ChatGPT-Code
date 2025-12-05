# Maps ingestion API

Minimal PDO-based endpoint for receiving JSON payloads from the desktop scraper.

## Deploy

1. Upload the `backend/api` directory to your server (e.g., `/var/www/html/api`).
2. Ensure PHP 8+ with PDO and the MySQL driver are enabled.
3. Create a MySQL database and user, then import `backend/api/schema.sql` (e.g., `mysql -uUSER -p DBNAME < schema.sql`).
4. Set `DB_DSN`/`DB_USER`/`DB_PASS` and a strong `API_TOKEN` in `config.php` (or via environment variables `MAPS_API_DSN`, `MAPS_API_DB_USER`, `MAPS_API_DB_PASS`, `MAPS_API_TOKEN`).

## Endpoint

POST `https://maps.noasoft.org/api/ingest.php`

Request body:
```json
{
  "token": "YOUR_TOKEN",
  "payload": {
    "source": "bot",
    "generated_at": "2024-01-01T00:00:00Z",
    "results": [ { ... place result ... } ]
  }
}
```

Responses are JSON with `status: ok` or an `error` code.
