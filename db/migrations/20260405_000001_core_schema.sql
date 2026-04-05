SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS countries (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code CHAR(2) NOT NULL,
  name VARCHAR(120) NOT NULL,
  flag_emoji VARCHAR(8) NOT NULL,
  map_code VARCHAR(8) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_countries_code (code),
  KEY idx_countries_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cities (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  country_id INT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  lat DECIMAL(10,6) NOT NULL,
  lng DECIMAL(10,6) NOT NULL,
  base_population INT UNSIGNED NOT NULL DEFAULT 0,
  airport_level SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  industry_level SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  education_level SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  army_level SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  port_level SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  space_level SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_cities_country_name (country_id, name),
  KEY idx_cities_country_active (country_id, is_active),
  KEY idx_cities_coords (lat, lng),
  CONSTRAINT fk_cities_country FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS resources (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  resource_key VARCHAR(50) NOT NULL,
  name VARCHAR(120) NOT NULL,
  unit VARCHAR(30) NOT NULL,
  base_price DECIMAL(12,2) NOT NULL,
  volatility_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_resources_key (resource_key),
  KEY idx_resources_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS country_resources (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  country_id INT UNSIGNED NOT NULL,
  resource_id INT UNSIGNED NOT NULL,
  daily_yield BIGINT UNSIGNED NOT NULL,
  stock BIGINT UNSIGNED NOT NULL DEFAULT 0,
  extraction_cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_country_resource (country_id, resource_id),
  KEY idx_country_resource_stock (country_id, stock),
  CONSTRAINT fk_country_resources_country FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE CASCADE,
  CONSTRAINT fk_country_resources_resource FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) NOT NULL,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  country_id INT UNSIGNED NOT NULL,
  city_id INT UNSIGNED NOT NULL,
  register_ip VARCHAR(50) NOT NULL,
  gold DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  energy INT UNSIGNED NOT NULL DEFAULT 3000,
  energy_max INT UNSIGNED NOT NULL DEFAULT 3000,
  experience BIGINT UNSIGNED NOT NULL DEFAULT 0,
  level INT UNSIGNED NOT NULL DEFAULT 1,
  strength INT UNSIGNED NOT NULL DEFAULT 5,
  education INT UNSIGNED NOT NULL DEFAULT 5,
  endurance INT UNSIGNED NOT NULL DEFAULT 5,
  labor_points INT UNSIGNED NOT NULL DEFAULT 0,
  war_power INT UNSIGNED NOT NULL DEFAULT 10,
  is_banned TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  last_energy_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_login_at DATETIME NULL,
  UNIQUE KEY uk_users_username (username),
  UNIQUE KEY uk_users_email (email),
  KEY idx_users_country_city (country_id, city_id),
  KEY idx_users_last_energy (last_energy_at),
  KEY idx_users_created_at (created_at),
  CONSTRAINT fk_users_country FOREIGN KEY (country_id) REFERENCES countries(id),
  CONSTRAINT fk_users_city FOREIGN KEY (city_id) REFERENCES cities(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_resources (
  user_id BIGINT UNSIGNED NOT NULL,
  resource_id INT UNSIGNED NOT NULL,
  quantity BIGINT UNSIGNED NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, resource_id),
  KEY idx_user_resources_resource (resource_id, quantity),
  CONSTRAINT fk_user_resources_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_user_resources_resource FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS market_offers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  seller_id BIGINT UNSIGNED NOT NULL,
  resource_id INT UNSIGNED NOT NULL,
  quantity BIGINT UNSIGNED NOT NULL,
  price_per_unit DECIMAL(12,2) NOT NULL,
  status ENUM('open','sold','cancelled') NOT NULL DEFAULT 'open',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  buyer_id BIGINT UNSIGNED NULL,
  sold_at DATETIME NULL,
  KEY idx_market_status_created (status, created_at),
  KEY idx_market_seller_status (seller_id, status),
  KEY idx_market_resource_status (resource_id, status),
  CONSTRAINT fk_market_seller FOREIGN KEY (seller_id) REFERENCES users(id),
  CONSTRAINT fk_market_buyer FOREIGN KEY (buyer_id) REFERENCES users(id),
  CONSTRAINT fk_market_resource FOREIGN KEY (resource_id) REFERENCES resources(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS parties (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  country_id INT UNSIGNED NOT NULL,
  founder_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  ideology VARCHAR(120) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_parties_country_name (country_id, name),
  KEY idx_parties_country (country_id),
  CONSTRAINT fk_parties_country FOREIGN KEY (country_id) REFERENCES countries(id),
  CONSTRAINT fk_parties_founder FOREIGN KEY (founder_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS party_members (
  party_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  role_name VARCHAR(80) NOT NULL DEFAULT 'member',
  joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (party_id, user_id),
  KEY idx_party_members_user (user_id),
  CONSTRAINT fk_party_members_party FOREIGN KEY (party_id) REFERENCES parties(id) ON DELETE CASCADE,
  CONSTRAINT fk_party_members_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS elections (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  country_id INT UNSIGNED NOT NULL,
  system ENUM('monarchy','dictatorship','republic') NOT NULL DEFAULT 'republic',
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NOT NULL,
  status ENUM('scheduled','open','closed') NOT NULL DEFAULT 'scheduled',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_elections_country_status (country_id, status),
  KEY idx_elections_dates (starts_at, ends_at),
  CONSTRAINT fk_elections_country FOREIGN KEY (country_id) REFERENCES countries(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS election_votes (
  election_id BIGINT UNSIGNED NOT NULL,
  voter_id BIGINT UNSIGNED NOT NULL,
  party_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (election_id, voter_id),
  KEY idx_election_votes_party (party_id),
  CONSTRAINT fk_election_votes_election FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE,
  CONSTRAINT fk_election_votes_voter FOREIGN KEY (voter_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_election_votes_party FOREIGN KEY (party_id) REFERENCES parties(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS country_government_roles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  country_id INT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  role_key VARCHAR(50) NOT NULL,
  assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_country_roles_unique (country_id, role_key),
  KEY idx_country_roles_user (user_id),
  CONSTRAINT fk_country_roles_country FOREIGN KEY (country_id) REFERENCES countries(id),
  CONSTRAINT fk_country_roles_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
  `key` VARCHAR(80) PRIMARY KEY,
  `value` TEXT NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  actor_user_id BIGINT UNSIGNED NULL,
  actor_admin TINYINT(1) NOT NULL DEFAULT 0,
  event_type VARCHAR(80) NOT NULL,
  entity_type VARCHAR(80) NOT NULL,
  entity_id VARCHAR(80) NOT NULL,
  payload_json JSON NULL,
  ip_address VARCHAR(50) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_audit_entity (entity_type, entity_id),
  KEY idx_audit_event_time (event_type, created_at),
  CONSTRAINT fk_audit_actor_user FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS migration_history (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  migration_name VARCHAR(255) NOT NULL,
  executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_migration_name (migration_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
