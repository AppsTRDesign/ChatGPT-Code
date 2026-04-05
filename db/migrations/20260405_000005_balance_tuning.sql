SET NAMES utf8mb4;
SET time_zone = '+00:00';

INSERT IGNORE INTO settings (`key`, `value`) VALUES
('energy_base_tick_seconds', '600'),
('energy_top_city_tick_seconds', '360'),
('energy_nation_tier_bonus_seconds', '12'),
('work_base_xp', '10'),
('battle_xp_win_min', '20'),
('battle_xp_win_max', '35'),
('battle_xp_lose_min', '8'),
('battle_xp_lose_max', '16'),
('level_xp_base', '120'),
('level_xp_curve', '1.2'),
('level_energy_gain', '150');

INSERT INTO migration_history (migration_name)
VALUES ('20260405_000005_balance_tuning.sql')
ON DUPLICATE KEY UPDATE migration_name = VALUES(migration_name);
