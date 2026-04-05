SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS geoip_cache (
  ip_address VARCHAR(50) PRIMARY KEY,
  country_code CHAR(2) NOT NULL,
  source_name VARCHAR(40) NOT NULL,
  confidence TINYINT UNSIGNED NOT NULL DEFAULT 50,
  cached_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME NOT NULL,
  KEY idx_geoip_expires (expires_at),
  KEY idx_geoip_country (country_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO migration_history (migration_name)
VALUES ('20260405_000004_geoip_fallback_cache.sql')
ON DUPLICATE KEY UPDATE migration_name = VALUES(migration_name);
