SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE country_travel_policies
  ADD COLUMN permit_duration_hours INT UNSIGNED NOT NULL DEFAULT 72 AFTER min_level;

ALTER TABLE residence_permits
  MODIFY status ENUM('pending','approved','rejected','used','expired','violated') NOT NULL DEFAULT 'pending',
  ADD COLUMN approved_at DATETIME NULL AFTER requested_at,
  ADD COLUMN valid_until DATETIME NULL AFTER approved_at,
  ADD COLUMN violated_at DATETIME NULL AFTER valid_until,
  ADD COLUMN violation_reason VARCHAR(255) NULL AFTER violated_at;

CREATE TABLE IF NOT EXISTS citizenship_requests (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  from_country_id INT UNSIGNED NOT NULL,
  to_country_id INT UNSIGNED NOT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  decided_at DATETIME NULL,
  decided_by_user_id BIGINT UNSIGNED NULL,
  note VARCHAR(255) NULL,
  KEY idx_citizenship_user_time (user_id, requested_at),
  KEY idx_citizenship_country_status (to_country_id, status),
  CONSTRAINT fk_citizenship_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_citizenship_from_country FOREIGN KEY (from_country_id) REFERENCES countries(id),
  CONSTRAINT fk_citizenship_to_country FOREIGN KEY (to_country_id) REFERENCES countries(id),
  CONSTRAINT fk_citizenship_decider FOREIGN KEY (decided_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS border_event_queue (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_type VARCHAR(80) NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  country_id INT UNSIGNED NOT NULL,
  execute_at DATETIME NOT NULL,
  payload_json JSON NULL,
  status ENUM('pending','processing','done','failed') NOT NULL DEFAULT 'pending',
  attempts INT UNSIGNED NOT NULL DEFAULT 0,
  last_error VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  processed_at DATETIME NULL,
  KEY idx_border_queue_status_time (status, execute_at),
  KEY idx_border_queue_user (user_id),
  CONSTRAINT fk_border_queue_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_border_queue_country FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (`key`, `value`, `description`) VALUES
('citizenship_min_level', '8', 'Vatandaşlık başvurusu minimum seviye'),
('citizenship_gold_cost', '120', 'Vatandaşlık başvurusu altın maliyeti'),
('border_queue_batch_size', '50', 'Sınır event queue tek sefer işlem limiti')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `description` = VALUES(`description`), updated_at = NOW();
