SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS anti_cheat_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  event_key VARCHAR(80) NOT NULL,
  severity ENUM('low','medium','high') NOT NULL DEFAULT 'low',
  detail_json JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_anti_cheat_user_created (user_id, created_at),
  KEY idx_anti_cheat_event_created (event_key, created_at),
  CONSTRAINT fk_anti_cheat_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_action_cooldowns (
  user_id BIGINT UNSIGNED NOT NULL,
  action_key VARCHAR(60) NOT NULL,
  last_at DATETIME NOT NULL,
  next_available_at DATETIME NULL,
  strike_count INT UNSIGNED NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, action_key),
  KEY idx_action_cooldowns_updated (updated_at),
  CONSTRAINT fk_action_cooldowns_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (`key`, `value`, `description`) VALUES
('anticheat_work_cooldown_seconds', '600', 'Work aksiyonu min aralık saniyesi'),
('anticheat_battle_cooldown_seconds', '600', 'Battle aksiyonu min aralık saniyesi'),
('anticheat_market_create_cooldown_seconds', '2', 'Market create aksiyonu min aralık saniyesi'),
('anticheat_market_buy_cooldown_seconds', '2', 'Market buy aksiyonu min aralık saniyesi'),
('anticheat_factory_produce_cooldown_seconds', '3', 'Factory produce aksiyonu min aralık saniyesi'),
('anticheat_max_single_trade_gold', '1000000', 'Tek işlemde izin verilen maksimum gold karşılığı'),
('balance_daily_quest_max_claim_count', '20', 'Günlük claim güvenlik limiti'),
('cooldown_top1_bonus_percent', '40', 'Top 1 şehir cooldown hız bonusu (%)'),
('cooldown_top2_5_bonus_percent', '25', 'Top 2-5 şehir cooldown hız bonusu (%)'),
('cooldown_top6_10_bonus_percent', '15', 'Top 6-10 şehir cooldown hız bonusu (%)')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `description` = VALUES(`description`), updated_at = NOW();
