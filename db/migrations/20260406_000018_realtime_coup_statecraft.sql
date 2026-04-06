SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS stat_upgrade_queue (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  stat_key ENUM('strength','education','endurance') NOT NULL,
  target_value INT UNSIGNED NOT NULL,
  ready_at DATETIME NOT NULL,
  status ENUM('queued','completed','cancelled') NOT NULL DEFAULT 'queued',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_stat_upgrade_user_status (user_id, status, ready_at),
  CONSTRAINT fk_stat_upgrade_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS traveler_merchants (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  merchant_key VARCHAR(60) NOT NULL,
  title VARCHAR(120) NOT NULL,
  offer_payload_json JSON NOT NULL,
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NOT NULL,
  status ENUM('scheduled','active','closed') NOT NULL DEFAULT 'scheduled',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_traveler_status_time (status, starts_at, ends_at),
  UNIQUE KEY uk_traveler_key (merchant_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS realtime_war_rooms (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  war_id BIGINT UNSIGNED NOT NULL,
  room_key VARCHAR(80) NOT NULL,
  attacker_hp INT UNSIGNED NOT NULL DEFAULT 1000,
  defender_hp INT UNSIGNED NOT NULL DEFAULT 1000,
  ends_at DATETIME NOT NULL,
  winner_country_id INT UNSIGNED NULL,
  status ENUM('active','finished') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_realtime_war_room_key (room_key),
  KEY idx_realtime_war_status (status, ends_at),
  CONSTRAINT fk_realtime_war_war FOREIGN KEY (war_id) REFERENCES wars(id) ON DELETE CASCADE,
  CONSTRAINT fk_realtime_war_winner FOREIGN KEY (winner_country_id) REFERENCES countries(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS realtime_war_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  room_id BIGINT UNSIGNED NOT NULL,
  actor_user_id BIGINT UNSIGNED NOT NULL,
  actor_country_id INT UNSIGNED NOT NULL,
  target_country_id INT UNSIGNED NOT NULL,
  damage INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_realtime_events_room_created (room_id, created_at),
  CONSTRAINT fk_realtime_events_room FOREIGN KEY (room_id) REFERENCES realtime_war_rooms(id) ON DELETE CASCADE,
  CONSTRAINT fk_realtime_events_actor FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS provinces (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  city_id INT UNSIGNED NOT NULL,
  country_id INT UNSIGNED NOT NULL,
  owner_user_id BIGINT UNSIGNED NULL,
  name VARCHAR(120) NOT NULL,
  color_hex VARCHAR(7) NOT NULL DEFAULT '#0D6EFD',
  flag_asset_path VARCHAR(255) NULL,
  protection_until DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_province_city (city_id),
  KEY idx_province_country_owner (country_id, owner_user_id),
  CONSTRAINT fk_province_city FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE CASCADE,
  CONSTRAINT fk_province_country FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE CASCADE,
  CONSTRAINT fk_province_owner FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS coups (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  country_id INT UNSIGNED NOT NULL,
  province_id BIGINT UNSIGNED NULL,
  initiator_user_id BIGINT UNSIGNED NOT NULL,
  coup_type ENUM('coup','uprising') NOT NULL,
  fund_gold DECIMAL(14,2) NOT NULL,
  required_gold DECIMAL(14,2) NOT NULL,
  status ENUM('funding','active','won','lost') NOT NULL DEFAULT 'funding',
  started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ends_at DATETIME NOT NULL,
  result_note VARCHAR(255) NULL,
  KEY idx_coup_country_status (country_id, status, ends_at),
  CONSTRAINT fk_coups_country FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE CASCADE,
  CONSTRAINT fk_coups_province FOREIGN KEY (province_id) REFERENCES provinces(id) ON DELETE SET NULL,
  CONSTRAINT fk_coups_initiator FOREIGN KEY (initiator_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS coup_contributions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  coup_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  gold_amount DECIMAL(14,2) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_coup_contrib (coup_id, user_id),
  CONSTRAINT fk_coup_contrib_coup FOREIGN KEY (coup_id) REFERENCES coups(id) ON DELETE CASCADE,
  CONSTRAINT fk_coup_contrib_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS province_transfers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  province_id BIGINT UNSIGNED NOT NULL,
  from_country_id INT UNSIGNED NOT NULL,
  to_country_id INT UNSIGNED NOT NULL,
  transferred_by_user_id BIGINT UNSIGNED NOT NULL,
  transfer_type ENUM('war','donation','coup') NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_province_transfer_province (province_id, created_at),
  CONSTRAINT fk_transfer_province FOREIGN KEY (province_id) REFERENCES provinces(id) ON DELETE CASCADE,
  CONSTRAINT fk_transfer_actor FOREIGN KEY (transferred_by_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (`key`, `value`, `description`) VALUES
('stat_upgrade_seconds', '90', 'Stat geliştirme geri sayım süresi (sn)'),
('factory_min_level', '5', 'Fabrika kurulum minimum seviye'),
('coup_min_gold', '5000', 'Darbe başlatma minimum altın'),
('uprising_min_gold', '3500', 'Ayaklanma başlatma minimum altın'),
('province_protection_days', '3', 'Yeni kurulan bölge koruma süresi (gün)'),
('war_room_default_seconds', '600', 'Gerçek zamanlı savaş odası varsayılan süre (sn)')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `description` = VALUES(`description`), updated_at = NOW();
