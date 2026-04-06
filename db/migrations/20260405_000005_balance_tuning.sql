SET NAMES utf8mb4;
SET time_zone = '+00:00';

INSERT INTO settings (`key`, `value`, `description`) VALUES
('energy_base_tick_seconds', '600', 'Enerji dolum baz süresi (sn)'),
('energy_top_city_tick_seconds', '360', 'Top şehirde enerji dolum süresi (sn)'),
('energy_nation_tier_bonus_seconds', '12', 'Ulus tier başına enerji tick süresi azaltımı (sn)'),
('work_base_xp', '10', 'Çalışma aksiyonu baz XP'),
('battle_xp_win_min', '20', 'Savaş kazanım min XP'),
('battle_xp_win_max', '35', 'Savaş kazanım max XP'),
('battle_xp_lose_min', '8', 'Savaş kayıp min XP'),
('battle_xp_lose_max', '16', 'Savaş kayıp max XP'),
('level_xp_base', '120', 'Level progression baz XP'),
('level_xp_curve', '1.2', 'Level XP eğri katsayısı'),
('level_energy_gain', '150', 'Seviye atlayınca enerji max artışı')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `description` = VALUES(`description`), updated_at = NOW();

INSERT INTO migration_history (migration_name)
VALUES ('20260405_000005_balance_tuning.sql')
ON DUPLICATE KEY UPDATE migration_name = VALUES(migration_name);
