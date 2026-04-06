-- Phase 11 war extension

CREATE TABLE IF NOT EXISTS battle_participations (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  battle_id BIGINT UNSIGNED NOT NULL,
  war_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  side VARCHAR(16) NOT NULL,
  contribution_power INT NOT NULL,
  contribution_energy INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_battle_participations_battle (battle_id),
  KEY idx_battle_participations_user (user_id),
  KEY idx_battle_participations_side (side),
  CONSTRAINT fk_battle_participations_battle FOREIGN KEY (battle_id) REFERENCES battles(id),
  CONSTRAINT fk_battle_participations_war FOREIGN KEY (war_id) REFERENCES wars(id),
  CONSTRAINT fk_battle_participations_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
