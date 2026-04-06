INSERT IGNORE INTO countries (id, code, name, flag_emoji, map_code) VALUES
(1, 'TR', 'Türkiye', '🇹🇷', 'TR'),
(2, 'DE', 'Almanya', '🇩🇪', 'DE'),
(3, 'RU', 'Rusya', '🇷🇺', 'RU'),
(4, 'US', 'Amerika Birleşik Devletleri', '🇺🇸', 'US');

INSERT IGNORE INTO cities (country_id, name, lat, lng, base_population, airport_level, industry_level, education_level, army_level, port_level, space_level) VALUES
(1, 'İstanbul', 41.0082, 28.9784, 15000000, 3, 4, 3, 3, 2, 1),
(1, 'Ankara', 39.9334, 32.8597, 5800000, 2, 3, 4, 4, 0, 1),
(1, 'İzmir', 38.4237, 27.1428, 4300000, 2, 3, 3, 2, 3, 0),
(2, 'Berlin', 52.5200, 13.4050, 3700000, 3, 4, 4, 3, 1, 1),
(2, 'Hamburg', 53.5511, 9.9937, 1900000, 2, 4, 3, 2, 4, 0),
(2, 'Münih', 48.1351, 11.5820, 1500000, 2, 3, 4, 2, 0, 0),
(3, 'Moskova', 55.7558, 37.6173, 13000000, 3, 4, 4, 4, 1, 2),
(3, 'St. Petersburg', 59.9311, 30.3609, 5400000, 2, 3, 3, 3, 4, 1),
(3, 'Novosibirsk', 55.0084, 82.9357, 1600000, 1, 3, 2, 3, 0, 1),
(4, 'New York', 40.7128, -74.0060, 8400000, 4, 5, 4, 4, 4, 2),
(4, 'Los Angeles', 34.0522, -118.2437, 3900000, 4, 4, 3, 3, 4, 2),
(4, 'Houston', 29.7604, -95.3698, 2300000, 3, 4, 3, 3, 3, 1);

INSERT IGNORE INTO resources (id, resource_key, name, unit, base_price, volatility_percent) VALUES
(1, 'gold', 'Altın', 'kg', 120.00, 2.5),
(2, 'oil', 'Petrol', 'varil', 75.00, 4.0),
(3, 'diamond', 'Elmas', 'karat', 380.00, 5.0),
(4, 'rare_earth', 'Nadir Toprak Elementi', 'ton', 410.00, 6.0),
(5, 'uranium', 'Uranyum', 'kg', 520.00, 5.5),
(6, 'iron', 'Demir', 'ton', 45.00, 2.0),
(7, 'stone', 'Taş', 'ton', 12.00, 1.0),
(8, 'wood', 'Tahta', 'm3', 18.00, 1.8),
(9, 'copper', 'Bakır', 'ton', 85.00, 2.7),
(10, 'silicon', 'Silikon', 'ton', 95.00, 3.3);

INSERT IGNORE INTO country_resources (country_id, resource_id, daily_yield, stock, extraction_cost) VALUES
(1, 1, 2500, 50000, 68.00), (1, 2, 100000, 400000, 31.00), (1, 3, 900, 15000, 140.00), (1, 4, 1800, 20000, 120.00), (1, 5, 250, 5000, 190.00), (1, 6, 22000, 150000, 12.00), (1, 7, 50000, 300000, 4.50), (1, 8, 18000, 120000, 6.00), (1, 9, 6000, 35000, 18.00), (1, 10, 3500, 22000, 22.00),
(2, 1, 1400, 30000, 70.00), (2, 2, 85000, 290000, 34.00), (2, 3, 500, 10000, 150.00), (2, 4, 1500, 16000, 130.00), (2, 5, 300, 4500, 210.00), (2, 6, 25000, 170000, 11.00), (2, 7, 42000, 250000, 4.00), (2, 8, 16000, 110000, 6.50), (2, 9, 4500, 28000, 20.00), (2, 10, 5000, 26000, 24.00),
(3, 1, 2200, 45000, 66.00), (3, 2, 240000, 900000, 27.00), (3, 3, 700, 12000, 135.00), (3, 4, 3200, 35000, 110.00), (3, 5, 800, 12000, 170.00), (3, 6, 41000, 280000, 10.50), (3, 7, 60000, 370000, 3.90), (3, 8, 30000, 210000, 5.80), (3, 9, 7800, 44000, 17.00), (3, 10, 4200, 27000, 21.00),
(4, 1, 1000, 24000, 74.00), (4, 2, 250000, 950000, 29.00), (4, 3, 1300, 19000, 145.00), (4, 4, 2800, 29000, 118.00), (4, 5, 950, 15000, 165.00), (4, 6, 33000, 230000, 10.00), (4, 7, 47000, 300000, 4.20), (4, 8, 40000, 280000, 5.10), (4, 9, 11000, 65000, 16.00), (4, 10, 9000, 48000, 20.00);

INSERT INTO settings (`key`, `value`, `description`) VALUES
('schema_version', '20260405_000002_seed_core_data.sql', 'Şema sürüm etiketi'),
('energy_tick_seconds', '600', 'Enerji dolum baz süresi (sn)'),
('energy_per_tick', '300', 'Her enerji tickinde kazanılan enerji'),
('top_city_energy_bonus_percent', '40', 'Top şehir enerji dolum hız bonusu (%)'),
('top_city_production_bonus_percent', '25', 'Top şehir üretim bonusu (%)')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `description` = VALUES(`description`), updated_at = NOW();
