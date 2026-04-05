CREATE TABLE IF NOT EXISTS countries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code CHAR(2) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  flag_emoji VARCHAR(8) NOT NULL,
  map_code VARCHAR(8) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cities (
  id INT AUTO_INCREMENT PRIMARY KEY,
  country_id INT NOT NULL,
  name VARCHAR(120) NOT NULL,
  lat DECIMAL(10,6) NOT NULL,
  lng DECIMAL(10,6) NOT NULL,
  base_population INT NOT NULL DEFAULT 0,
  airport_level INT NOT NULL DEFAULT 1,
  industry_level INT NOT NULL DEFAULT 1,
  education_level INT NOT NULL DEFAULT 1,
  army_level INT NOT NULL DEFAULT 1,
  port_level INT NOT NULL DEFAULT 0,
  space_level INT NOT NULL DEFAULT 0,
  UNIQUE KEY uk_city (country_id, name),
  CONSTRAINT fk_city_country FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS resources (
  id INT AUTO_INCREMENT PRIMARY KEY,
  resource_key VARCHAR(50) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  unit VARCHAR(30) NOT NULL,
  base_price DECIMAL(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS country_resources (
  id INT AUTO_INCREMENT PRIMARY KEY,
  country_id INT NOT NULL,
  resource_id INT NOT NULL,
  daily_yield BIGINT NOT NULL,
  stock BIGINT NOT NULL DEFAULT 0,
  UNIQUE KEY uk_country_resource (country_id, resource_id),
  CONSTRAINT fk_cr_country FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE CASCADE,
  CONSTRAINT fk_cr_resource FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) NOT NULL UNIQUE,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  country_id INT NOT NULL,
  city_id INT NOT NULL,
  register_ip VARCHAR(50) NOT NULL,
  gold DECIMAL(12,2) NOT NULL DEFAULT 0,
  energy INT NOT NULL DEFAULT 3000,
  energy_max INT NOT NULL DEFAULT 3000,
  experience BIGINT NOT NULL DEFAULT 0,
  level INT NOT NULL DEFAULT 1,
  strength INT NOT NULL DEFAULT 5,
  education INT NOT NULL DEFAULT 5,
  endurance INT NOT NULL DEFAULT 5,
  labor_points INT NOT NULL DEFAULT 0,
  war_power INT NOT NULL DEFAULT 10,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_energy_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_user_country FOREIGN KEY (country_id) REFERENCES countries(id),
  CONSTRAINT fk_user_city FOREIGN KEY (city_id) REFERENCES cities(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS market_offers (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  seller_id BIGINT NOT NULL,
  resource_id INT NOT NULL,
  quantity BIGINT NOT NULL,
  price_per_unit DECIMAL(12,2) NOT NULL,
  status ENUM('open','sold','cancelled') NOT NULL DEFAULT 'open',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  buyer_id BIGINT NULL,
  CONSTRAINT fk_mo_seller FOREIGN KEY (seller_id) REFERENCES users(id),
  CONSTRAINT fk_mo_buyer FOREIGN KEY (buyer_id) REFERENCES users(id),
  CONSTRAINT fk_mo_resource FOREIGN KEY (resource_id) REFERENCES resources(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_resources (
  user_id BIGINT NOT NULL,
  resource_id INT NOT NULL,
  quantity BIGINT NOT NULL DEFAULT 0,
  PRIMARY KEY (user_id, resource_id),
  CONSTRAINT fk_ur_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_ur_resource FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS parties (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  country_id INT NOT NULL,
  founder_id BIGINT NOT NULL,
  name VARCHAR(120) NOT NULL,
  ideology VARCHAR(120) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_party_country_name (country_id, name),
  CONSTRAINT fk_party_country FOREIGN KEY (country_id) REFERENCES countries(id),
  CONSTRAINT fk_party_founder FOREIGN KEY (founder_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS party_members (
  party_id BIGINT NOT NULL,
  user_id BIGINT NOT NULL,
  role_name VARCHAR(80) NOT NULL DEFAULT 'member',
  joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (party_id, user_id),
  CONSTRAINT fk_pm_party FOREIGN KEY (party_id) REFERENCES parties(id) ON DELETE CASCADE,
  CONSTRAINT fk_pm_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS elections (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  country_id INT NOT NULL,
  system ENUM('monarchy','dictatorship','republic') NOT NULL DEFAULT 'republic',
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NOT NULL,
  status ENUM('scheduled','open','closed') NOT NULL DEFAULT 'scheduled',
  CONSTRAINT fk_election_country FOREIGN KEY (country_id) REFERENCES countries(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS election_votes (
  election_id BIGINT NOT NULL,
  voter_id BIGINT NOT NULL,
  party_id BIGINT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (election_id, voter_id),
  CONSTRAINT fk_ev_election FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE,
  CONSTRAINT fk_ev_voter FOREIGN KEY (voter_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_ev_party FOREIGN KEY (party_id) REFERENCES parties(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS country_government_roles (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  country_id INT NOT NULL,
  user_id BIGINT NOT NULL,
  role_key VARCHAR(50) NOT NULL,
  assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_country_role (country_id, role_key),
  CONSTRAINT fk_cgr_country FOREIGN KEY (country_id) REFERENCES countries(id),
  CONSTRAINT fk_cgr_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
  `key` VARCHAR(80) PRIMARY KEY,
  `value` TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO countries (id, code, name, flag_emoji, map_code) VALUES
(1, 'TR', 'Türkiye', '🇹🇷', 'TR'),
(2, 'DE', 'Almanya', '🇩🇪', 'DE'),
(3, 'RU', 'Rusya', '🇷🇺', 'RU'),
(4, 'US', 'Amerika Birleşik Devletleri', '🇺🇸', 'US');

INSERT IGNORE INTO cities (country_id, name, lat, lng, base_population, airport_level, industry_level, education_level, army_level, port_level, space_level) VALUES
(1, 'İstanbul', 41.0082, 28.9784, 15000000, 3, 4, 3, 3, 2, 1),
(1, 'Ankara', 39.9334, 32.8597, 5800000, 2, 3, 4, 4, 0, 1),
(1, 'İzmir', 38.4237, 27.1428, 4300000, 2, 3, 3, 2, 3, 0),
(2, 'Berlin', 52.5200, 13.4050, 3700000, 3, 4, 4, 3, 1, 1),
(2, 'Hamburg', 53.5511, 9.9937, 1900000, 2, 4, 3, 2, 4, 0),
(2, 'Münih', 48.1351, 11.5820, 1500000, 2, 3, 4, 2, 0, 0),
(3, 'Moskova', 55.7558, 37.6173, 13000000, 3, 4, 4, 4, 1, 2),
(3, 'St. Petersburg', 59.9311, 30.3609, 5400000, 2, 3, 3, 3, 4, 1),
(3, 'Novosibirsk', 55.0084, 82.9357, 1600000, 1, 3, 2, 3, 0, 1),
(4, 'New York', 40.7128, -74.0060, 8400000, 4, 5, 4, 4, 4, 2),
(4, 'Los Angeles', 34.0522, -118.2437, 3900000, 4, 4, 3, 3, 4, 2),
(4, 'Houston', 29.7604, -95.3698, 2300000, 3, 4, 3, 3, 3, 1);

INSERT IGNORE INTO resources (id, resource_key, name, unit, base_price) VALUES
(1, 'gold', 'Altın', 'kg', 120.00),
(2, 'oil', 'Petrol', 'varil', 75.00),
(3, 'diamond', 'Elmas', 'karat', 380.00),
(4, 'rare_earth', 'Nadir Toprak Elementi', 'ton', 410.00),
(5, 'uranium', 'Uranyum', 'kg', 520.00),
(6, 'iron', 'Demir', 'ton', 45.00),
(7, 'stone', 'Taş', 'ton', 12.00),
(8, 'wood', 'Tahta', 'm3', 18.00),
(9, 'copper', 'Bakır', 'ton', 85.00),
(10, 'silicon', 'Silikon', 'ton', 95.00);

INSERT IGNORE INTO country_resources (country_id, resource_id, daily_yield, stock) VALUES
(1, 1, 2500, 50000), (1, 2, 100000, 400000), (1, 3, 900, 15000), (1, 4, 1800, 20000), (1, 5, 250, 5000), (1, 6, 22000, 150000), (1, 7, 50000, 300000), (1, 8, 18000, 120000), (1, 9, 6000, 35000), (1, 10, 3500, 22000),
(2, 1, 1400, 30000), (2, 2, 85000, 290000), (2, 3, 500, 10000), (2, 4, 1500, 16000), (2, 5, 300, 4500), (2, 6, 25000, 170000), (2, 7, 42000, 250000), (2, 8, 16000, 110000), (2, 9, 4500, 28000), (2, 10, 5000, 26000),
(3, 1, 2200, 45000), (3, 2, 240000, 900000), (3, 3, 700, 12000), (3, 4, 3200, 35000), (3, 5, 800, 12000), (3, 6, 41000, 280000), (3, 7, 60000, 370000), (3, 8, 30000, 210000), (3, 9, 7800, 44000), (3, 10, 4200, 27000),
(4, 1, 1000, 24000), (4, 2, 250000, 950000), (4, 3, 1300, 19000), (4, 4, 2800, 29000), (4, 5, 950, 15000), (4, 6, 33000, 230000), (4, 7, 47000, 300000), (4, 8, 40000, 280000), (4, 9, 11000, 65000), (4, 10, 9000, 48000);
