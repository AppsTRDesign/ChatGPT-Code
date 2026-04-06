-- Noa Political Wars / RivalRegions-like DB bootstrap
-- Production-oriented migration entrypoint
-- Run files in this order:
--  1) db/migrations/20260405_000001_core_schema.sql
--  2) db/migrations/20260405_000002_seed_core_data.sql
--  3) db/migrations/20260405_000003_auth_hardening.sql
--  4) db/migrations/20260405_000004_geoip_fallback_cache.sql
--  5) db/migrations/20260405_000005_balance_tuning.sql
--  6) db/migrations/20260405_000006_resource_balance.sql
--  7) db/migrations/20260405_000007_factory_system.sql
--  8) db/migrations/20260406_000008_market_hardening.sql
--  9) db/migrations/20260406_000009_war_core.sql
-- 10) db/migrations/20260406_000010_politics_core.sql
-- 11) db/migrations/20260406_000011_government_roles.sql
-- 12) db/migrations/20260406_000012_travel_permits.sql
-- 13) db/migrations/20260406_000013_map_worldbuilder.sql
-- 14) db/migrations/20260406_000014_observability_release.sql
-- 15) db/migrations/20260406_000015_border_citizenship_queue.sql

SOURCE db/migrations/20260405_000001_core_schema.sql;
SOURCE db/migrations/20260405_000002_seed_core_data.sql;

SOURCE db/migrations/20260405_000003_auth_hardening.sql;
SOURCE db/migrations/20260405_000004_geoip_fallback_cache.sql;
SOURCE db/migrations/20260405_000005_balance_tuning.sql;
SOURCE db/migrations/20260405_000006_resource_balance.sql;
SOURCE db/migrations/20260405_000007_factory_system.sql;
SOURCE db/migrations/20260406_000008_market_hardening.sql;
SOURCE db/migrations/20260406_000009_war_core.sql;
SOURCE db/migrations/20260406_000010_politics_core.sql;
SOURCE db/migrations/20260406_000011_government_roles.sql;
SOURCE db/migrations/20260406_000012_travel_permits.sql;
SOURCE db/migrations/20260406_000013_map_worldbuilder.sql;
SOURCE db/migrations/20260406_000014_observability_release.sql;
SOURCE db/migrations/20260406_000015_border_citizenship_queue.sql;
SOURCE db/migrations/20260406_000016_rankings_quests_notifications.sql;
SOURCE db/migrations/20260406_000017_anticheat_balance_testinfra.sql;
SOURCE db/migrations/20260406_000018_realtime_coup_statecraft.sql;
