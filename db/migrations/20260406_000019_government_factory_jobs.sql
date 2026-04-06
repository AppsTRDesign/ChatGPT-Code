SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS government_factories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  country_id INT UNSIGNED NOT NULL,
  city_id INT UNSIGNED NOT NULL,
  name VARCHAR(140) NOT NULL,
  level INT UNSIGNED NOT NULL DEFAULT 50,
  max_workers INT UNSIGNED NOT NULL DEFAULT 1000,
  base_wage_gold DECIMAL(12,2) NOT NULL DEFAULT 4.00,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_by_admin TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_gov_factory_city (city_id),
  KEY idx_gov_factory_country_city (country_id, city_id),
  CONSTRAINT fk_gov_factory_country FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE CASCADE,
  CONSTRAINT fk_gov_factory_city FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS factory_workers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  factory_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  role_key VARCHAR(40) NOT NULL DEFAULT 'worker',
  joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_shift_at DATETIME NULL,
  status ENUM('active','left') NOT NULL DEFAULT 'active',
  UNIQUE KEY uk_factory_worker_active (factory_id, user_id, status),
  KEY idx_factory_worker_user (user_id, status),
  CONSTRAINT fk_factory_worker_factory FOREIGN KEY (factory_id) REFERENCES government_factories(id) ON DELETE CASCADE,
  CONSTRAINT fk_factory_worker_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (`key`, `value`, `description`) VALUES
('gov_factory_default_level', '50', 'Yönetim fabrikası varsayılan seviyesi'),
('gov_factory_default_max_workers', '1000', 'Yönetim fabrikası max işçi'),
('gov_factory_shift_cooldown_seconds', '30', 'Vardiya cooldown (sn)')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `description` = VALUES(`description`), updated_at = NOW();
