SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS leaderboard_snapshots (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  scope ENUM('global','country') NOT NULL DEFAULT 'global',
  country_id INT UNSIGNED NULL,
  payload_json JSON NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_leaderboard_scope_created (scope, created_at),
  CONSTRAINT fk_leaderboard_country FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS achievements (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  achievement_key VARCHAR(80) NOT NULL,
  title VARCHAR(120) NOT NULL,
  description VARCHAR(255) NOT NULL,
  metric_key VARCHAR(50) NOT NULL,
  target_value BIGINT UNSIGNED NOT NULL,
  reward_gold DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  reward_xp BIGINT UNSIGNED NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_achievement_key (achievement_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_achievements (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  achievement_id INT UNSIGNED NOT NULL,
  unlocked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_user_achievement (user_id, achievement_id),
  KEY idx_user_achievements_user (user_id, unlocked_at),
  CONSTRAINT fk_user_achievements_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_user_achievements_achievement FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS daily_quest_templates (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  quest_key VARCHAR(80) NOT NULL,
  title VARCHAR(120) NOT NULL,
  description VARCHAR(255) NOT NULL,
  metric_key VARCHAR(50) NOT NULL,
  target_min BIGINT UNSIGNED NOT NULL,
  target_max BIGINT UNSIGNED NOT NULL,
  reward_gold_min DECIMAL(12,2) NOT NULL,
  reward_gold_max DECIMAL(12,2) NOT NULL,
  reward_xp_min BIGINT UNSIGNED NOT NULL,
  reward_xp_max BIGINT UNSIGNED NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_daily_quest_template (quest_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_daily_quests (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  quest_template_id INT UNSIGNED NOT NULL,
  quest_date DATE NOT NULL,
  status ENUM('active','completed','claimed','expired') NOT NULL DEFAULT 'active',
  target_value BIGINT UNSIGNED NOT NULL,
  progress_value BIGINT UNSIGNED NOT NULL DEFAULT 0,
  reward_gold DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  reward_xp BIGINT UNSIGNED NOT NULL DEFAULT 0,
  claimed_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_user_daily_quest (user_id, quest_template_id, quest_date),
  KEY idx_user_daily_quest_status (user_id, quest_date, status),
  CONSTRAINT fk_user_daily_quest_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_user_daily_quest_template FOREIGN KEY (quest_template_id) REFERENCES daily_quest_templates(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_notifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  type_key VARCHAR(60) NOT NULL,
  title VARCHAR(160) NOT NULL,
  body VARCHAR(500) NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  read_at DATETIME NULL,
  KEY idx_user_notifications_user (user_id, is_read, created_at),
  CONSTRAINT fk_user_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS event_feed (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  country_id INT UNSIGNED NULL,
  actor_user_id BIGINT UNSIGNED NULL,
  event_type VARCHAR(80) NOT NULL,
  title VARCHAR(160) NOT NULL,
  body VARCHAR(500) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_event_feed_created (created_at),
  KEY idx_event_feed_country (country_id, created_at),
  CONSTRAINT fk_event_feed_country FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE SET NULL,
  CONSTRAINT fk_event_feed_actor FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO achievements (achievement_key, title, description, metric_key, target_value, reward_gold, reward_xp)
VALUES
  ('level_5', 'Yükselen Güç', '5. seviyeye ulaş.', 'level', 5, 80.00, 120),
  ('gold_1000', 'İlk Servet', '1000 altına ulaş.', 'gold', 1000, 120.00, 100),
  ('war_power_200', 'Cephe Ustası', '200 savaş gücüne ulaş.', 'war_power', 200, 160.00, 180)
ON DUPLICATE KEY UPDATE
  title = VALUES(title),
  description = VALUES(description),
  metric_key = VALUES(metric_key),
  target_value = VALUES(target_value),
  reward_gold = VALUES(reward_gold),
  reward_xp = VALUES(reward_xp),
  is_active = 1;

INSERT INTO daily_quest_templates (quest_key, title, description, metric_key, target_min, target_max, reward_gold_min, reward_gold_max, reward_xp_min, reward_xp_max)
VALUES
  ('daily_level', 'Günlük Seviye Hedefi', 'Bugün belirlenen seviyeye ulaş.', 'level', 2, 8, 25.00, 120.00, 30, 140),
  ('daily_gold', 'Günlük Altın Hedefi', 'Bugün belirlenen altın eşiğini geç.', 'gold', 200, 2500, 30.00, 180.00, 20, 120),
  ('daily_war_power', 'Günlük Savaş Gücü Hedefi', 'Bugün belirlenen savaş gücüne ulaş.', 'war_power', 40, 260, 35.00, 190.00, 30, 160)
ON DUPLICATE KEY UPDATE
  title = VALUES(title),
  description = VALUES(description),
  metric_key = VALUES(metric_key),
  target_min = VALUES(target_min),
  target_max = VALUES(target_max),
  reward_gold_min = VALUES(reward_gold_min),
  reward_gold_max = VALUES(reward_gold_max),
  reward_xp_min = VALUES(reward_xp_min),
  reward_xp_max = VALUES(reward_xp_max),
  is_active = 1;
