SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS app_heartbeat_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  status VARCHAR(20) NOT NULL,
  db_ok TINYINT(1) NOT NULL DEFAULT 0,
  app_version VARCHAR(80) NOT NULL,
  response_ms INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_heartbeat_time (created_at),
  KEY idx_heartbeat_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS app_error_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  level VARCHAR(20) NOT NULL,
  context_key VARCHAR(80) NOT NULL,
  message TEXT NOT NULL,
  payload_json JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_error_level_time (level, created_at),
  KEY idx_error_context_time (context_key, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (`key`, `value`, `description`) VALUES
('app_release_channel', 'stable', 'Release kanalı'),
('app_release_version', '0.1.0', 'Uygulama sürüm etiketi'),
('monitor_heartbeat_enabled', '1', 'Heartbeat monitor etkinliği')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `description` = VALUES(`description`), updated_at = NOW();
