# Maps ingestion API

Minimal PDO-based endpoint for receiving JSON payloads from the desktop scraper.

## Deploy

1. Upload the `backend/api` directory to your server (e.g., `/var/www/html/api`).
2. Ensure PHP 8+ with PDO is enabled. SQLite DSN is used by default; override `DB_DSN`/`DB_USER`/`DB_PASS` in `config.php` or via environment variables for MySQL.
3. Set a strong `API_TOKEN` value (env `MAPS_API_TOKEN` or constant in `config.php`).
4. Make `backend/data` writable if you keep SQLite storage.

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
