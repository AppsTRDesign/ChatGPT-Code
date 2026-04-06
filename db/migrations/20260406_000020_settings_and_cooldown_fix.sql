SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE settings
  ADD COLUMN IF NOT EXISTS `description` VARCHAR(255) NULL AFTER `value`;

ALTER TABLE user_action_cooldowns
  ADD COLUMN IF NOT EXISTS next_available_at DATETIME NULL AFTER last_at;

UPDATE settings
SET `description` = COALESCE(NULLIF(`description`, ''), CONCAT('Auto-filled description for ', `key`))
WHERE `description` IS NULL OR `description` = '';

INSERT INTO settings (`key`, `value`, `description`) VALUES
('anticheat_work_cooldown_seconds', '600', 'Work aksiyonu min aralık saniyesi'),
('anticheat_battle_cooldown_seconds', '600', 'Battle aksiyonu min aralık saniyesi'),
('cooldown_top1_bonus_percent', '40', 'Top 1 şehir cooldown hız bonusu (%)'),
('cooldown_top2_5_bonus_percent', '25', 'Top 2-5 şehir cooldown hız bonusu (%)'),
('cooldown_top6_10_bonus_percent', '15', 'Top 6-10 şehir cooldown hız bonusu (%)')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `description` = VALUES(`description`), updated_at = NOW();

INSERT INTO migration_history (migration_name)
VALUES ('20260406_000020_settings_and_cooldown_fix.sql')
ON DUPLICATE KEY UPDATE migration_name = VALUES(migration_name);
