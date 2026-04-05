SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS auth_rate_limits (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  action_key VARCHAR(64) NOT NULL,
  identifier VARCHAR(190) NOT NULL,
  ip_address VARCHAR(50) NOT NULL,
  attempt_count INT UNSIGNED NOT NULL DEFAULT 0,
  window_start DATETIME NOT NULL,
  blocked_until DATETIME NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_auth_rate_limit (action_key, identifier, ip_address),
  KEY idx_auth_rate_blocked (blocked_until),
  KEY idx_auth_rate_window (window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_reset_tokens (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ip_address VARCHAR(50) NULL,
  UNIQUE KEY uk_password_reset_token_hash (token_hash),
  KEY idx_password_reset_user (user_id, expires_at),
  KEY idx_password_reset_exp (expires_at),
  CONSTRAINT fk_password_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO migration_history (migration_name)
VALUES ('20260405_000003_auth_hardening.sql')
ON DUPLICATE KEY UPDATE migration_name = VALUES(migration_name);
