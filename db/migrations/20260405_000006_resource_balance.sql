SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE country_resources
  ADD COLUMN IF NOT EXISTS regeneration_rate DECIMAL(6,4) NOT NULL DEFAULT 0.0200,
  ADD COLUMN IF NOT EXISTS quality_index DECIMAL(5,2) NOT NULL DEFAULT 1.00;

CREATE TABLE IF NOT EXISTS resource_market_prices (
  resource_id INT UNSIGNED PRIMARY KEY,
  current_price DECIMAL(12,2) NOT NULL,
  scarcity_factor DECIMAL(6,3) NOT NULL DEFAULT 1.000,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_rmp_resource FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO resource_market_prices (resource_id, current_price, scarcity_factor)
SELECT id, base_price, 1.000 FROM resources;

UPDATE country_resources SET regeneration_rate = 0.0200, quality_index = 1.00 WHERE regeneration_rate IS NULL OR quality_index IS NULL;

INSERT INTO migration_history (migration_name)
VALUES ('20260405_000006_resource_balance.sql')
ON DUPLICATE KEY UPDATE migration_name = VALUES(migration_name);
