SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS government_role_permissions (
  role_key VARCHAR(50) NOT NULL,
  permission_key VARCHAR(80) NOT NULL,
  PRIMARY KEY (role_key, permission_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ministry_action_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  country_id INT UNSIGNED NOT NULL,
  actor_user_id BIGINT UNSIGNED NOT NULL,
  role_key VARCHAR(50) NOT NULL,
  action_key VARCHAR(80) NOT NULL,
  payload_json JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_ministry_country_time (country_id, created_at),
  KEY idx_ministry_actor_time (actor_user_id, created_at),
  CONSTRAINT fk_ministry_country FOREIGN KEY (country_id) REFERENCES countries(id),
  CONSTRAINT fk_ministry_actor FOREIGN KEY (actor_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO government_role_permissions (role_key, permission_key) VALUES
('president', 'gov.assign_roles'),
('president', 'gov.market.adjust_tax'),
('president', 'gov.war.adjust_score_to_win'),
('minister_economy', 'gov.market.adjust_tax'),
('minister_defense', 'gov.war.adjust_score_to_win');
