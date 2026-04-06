SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS country_travel_policies (
  country_id INT UNSIGNED PRIMARY KEY,
  visa_required TINYINT(1) NOT NULL DEFAULT 1,
  visa_fee DECIMAL(12,2) NOT NULL DEFAULT 5.00,
  min_level INT UNSIGNED NOT NULL DEFAULT 2,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_travel_policy_country FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS residence_permits (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  from_country_id INT UNSIGNED NOT NULL,
  to_country_id INT UNSIGNED NOT NULL,
  status ENUM('pending','approved','rejected','used') NOT NULL DEFAULT 'pending',
  visa_fee DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  decided_by_user_id BIGINT UNSIGNED NULL,
  decided_at DATETIME NULL,
  note VARCHAR(255) NULL,
  KEY idx_permits_user_time (user_id, requested_at),
  KEY idx_permits_country_status (to_country_id, status),
  CONSTRAINT fk_permits_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_permits_from_country FOREIGN KEY (from_country_id) REFERENCES countries(id),
  CONSTRAINT fk_permits_to_country FOREIGN KEY (to_country_id) REFERENCES countries(id),
  CONSTRAINT fk_permits_decider FOREIGN KEY (decided_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS travel_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  from_city_id INT UNSIGNED NOT NULL,
  to_city_id INT UNSIGNED NOT NULL,
  from_country_id INT UNSIGNED NOT NULL,
  to_country_id INT UNSIGNED NOT NULL,
  permit_id BIGINT UNSIGNED NULL,
  traveled_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_travel_user_time (user_id, traveled_at),
  CONSTRAINT fk_travel_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_travel_from_city FOREIGN KEY (from_city_id) REFERENCES cities(id),
  CONSTRAINT fk_travel_to_city FOREIGN KEY (to_city_id) REFERENCES cities(id),
  CONSTRAINT fk_travel_permit FOREIGN KEY (permit_id) REFERENCES residence_permits(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO country_travel_policies (country_id, visa_required, visa_fee, min_level)
SELECT id, 1, 5.00, 2 FROM countries;
