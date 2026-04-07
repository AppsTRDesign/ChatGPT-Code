-- Phase 16 - Admin deepening (moderation/balancing/i18n live edit)

CREATE TABLE IF NOT EXISTS admin_balancing_settings (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  category VARCHAR(64) NOT NULL,
  settings_json JSON NOT NULL,
  updated_by_user_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_admin_balancing_category (category),
  KEY idx_admin_balancing_updated_by (updated_by_user_id),
  CONSTRAINT fk_admin_balancing_updated_by FOREIGN KEY (updated_by_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS moderation_actions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  target_user_id BIGINT UNSIGNED NULL,
  admin_user_id BIGINT UNSIGNED NOT NULL,
  action_type VARCHAR(64) NOT NULL,
  reason VARCHAR(255) NULL,
  payload_json JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_moderation_actions_target (target_user_id),
  KEY idx_moderation_actions_admin (admin_user_id),
  KEY idx_moderation_actions_type_created (action_type, created_at),
  CONSTRAINT fk_moderation_actions_target FOREIGN KEY (target_user_id) REFERENCES users(id),
  CONSTRAINT fk_moderation_actions_admin FOREIGN KEY (admin_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
