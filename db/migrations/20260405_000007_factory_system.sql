SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS factory_types (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type_key VARCHAR(50) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  input_resource_id INT UNSIGNED NULL,
  output_resource_id INT UNSIGNED NOT NULL,
  base_cycle_minutes INT UNSIGNED NOT NULL DEFAULT 30,
  base_output INT UNSIGNED NOT NULL DEFAULT 10,
  base_workers INT UNSIGNED NOT NULL DEFAULT 5,
  base_energy_cost INT UNSIGNED NOT NULL DEFAULT 300,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_factory_type_input FOREIGN KEY (input_resource_id) REFERENCES resources(id) ON DELETE SET NULL,
  CONSTRAINT fk_factory_type_output FOREIGN KEY (output_resource_id) REFERENCES resources(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_factories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  country_id INT UNSIGNED NOT NULL,
  city_id INT UNSIGNED NOT NULL,
  factory_type_id INT UNSIGNED NOT NULL,
  level INT UNSIGNED NOT NULL DEFAULT 1,
  workers INT UNSIGNED NOT NULL DEFAULT 5,
  status ENUM('active','paused') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  last_production_at DATETIME NULL,
  KEY idx_user_factories_user (user_id),
  KEY idx_user_factories_city (city_id),
  CONSTRAINT fk_user_factories_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_user_factories_country FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE CASCADE,
  CONSTRAINT fk_user_factories_city FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE CASCADE,
  CONSTRAINT fk_user_factories_type FOREIGN KEY (factory_type_id) REFERENCES factory_types(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS factory_production_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  factory_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  input_resource_id INT UNSIGNED NULL,
  output_resource_id INT UNSIGNED NOT NULL,
  input_amount BIGINT UNSIGNED NOT NULL DEFAULT 0,
  output_amount BIGINT UNSIGNED NOT NULL DEFAULT 0,
  energy_used INT UNSIGNED NOT NULL DEFAULT 0,
  workers_used INT UNSIGNED NOT NULL DEFAULT 0,
  produced_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_factory_logs_factory_time (factory_id, produced_at),
  CONSTRAINT fk_factory_logs_factory FOREIGN KEY (factory_id) REFERENCES user_factories(id) ON DELETE CASCADE,
  CONSTRAINT fk_factory_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_factory_logs_input FOREIGN KEY (input_resource_id) REFERENCES resources(id) ON DELETE SET NULL,
  CONSTRAINT fk_factory_logs_output FOREIGN KEY (output_resource_id) REFERENCES resources(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO factory_types (id, type_key, name, input_resource_id, output_resource_id, base_cycle_minutes, base_output, base_workers, base_energy_cost) VALUES
(1, 'gold_refinery', 'Altın Rafinerisi', NULL, 1, 30, 8, 6, 300),
(2, 'oil_refinery', 'Petrol Rafinerisi', NULL, 2, 30, 40, 8, 300),
(3, 'steel_factory', 'Çelik Fabrikası', 6, 9, 40, 12, 10, 360),
(4, 'diamond_lab', 'Elmas İşleme Tesisi', 3, 3, 45, 6, 7, 320),
(5, 'uranium_plant', 'Uranyum Zenginleştirme', 5, 5, 50, 4, 12, 420);

INSERT INTO migration_history (migration_name)
VALUES ('20260405_000007_factory_system.sql')
ON DUPLICATE KEY UPDATE migration_name = VALUES(migration_name);
