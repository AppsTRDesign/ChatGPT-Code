SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS wars (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  attacker_country_id INT UNSIGNED NOT NULL,
  defender_country_id INT UNSIGNED NOT NULL,
  started_by_user_id BIGINT UNSIGNED NOT NULL,
  status ENUM('active','ended') NOT NULL DEFAULT 'active',
  attacker_score INT UNSIGNED NOT NULL DEFAULT 0,
  defender_score INT UNSIGNED NOT NULL DEFAULT 0,
  score_to_win INT UNSIGNED NOT NULL DEFAULT 1000,
  winner_country_id INT UNSIGNED NULL,
  started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ended_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_wars_active_attacker (status, attacker_country_id),
  KEY idx_wars_active_defender (status, defender_country_id),
  CONSTRAINT fk_wars_attacker_country FOREIGN KEY (attacker_country_id) REFERENCES countries(id),
  CONSTRAINT fk_wars_defender_country FOREIGN KEY (defender_country_id) REFERENCES countries(id),
  CONSTRAINT fk_wars_starter_user FOREIGN KEY (started_by_user_id) REFERENCES users(id),
  CONSTRAINT fk_wars_winner_country FOREIGN KEY (winner_country_id) REFERENCES countries(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS war_battles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  war_id BIGINT UNSIGNED NOT NULL,
  attacker_user_id BIGINT UNSIGNED NOT NULL,
  attacker_country_id INT UNSIGNED NOT NULL,
  defender_country_id INT UNSIGNED NOT NULL,
  roll_value INT UNSIGNED NOT NULL,
  damage INT UNSIGNED NOT NULL,
  attacker_score_after INT UNSIGNED NOT NULL,
  defender_score_after INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_war_battles_war_time (war_id, created_at),
  KEY idx_war_battles_user_time (attacker_user_id, created_at),
  CONSTRAINT fk_war_battles_war FOREIGN KEY (war_id) REFERENCES wars(id) ON DELETE CASCADE,
  CONSTRAINT fk_war_battles_user FOREIGN KEY (attacker_user_id) REFERENCES users(id),
  CONSTRAINT fk_war_battles_attacker_country FOREIGN KEY (attacker_country_id) REFERENCES countries(id),
  CONSTRAINT fk_war_battles_defender_country FOREIGN KEY (defender_country_id) REFERENCES countries(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO settings (`key`, `value`) VALUES
('war_attack_energy_cost', '280'),
('war_attack_cooldown_seconds', '45'),
('war_damage_min', '40'),
('war_damage_max', '160'),
('war_score_to_win', '1000');
