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

SOURCE db/migrations/20260405_000001_core_schema.sql;
SOURCE db/migrations/20260405_000002_seed_core_data.sql;

SOURCE db/migrations/20260405_000003_auth_hardening.sql;
SOURCE db/migrations/20260405_000004_geoip_fallback_cache.sql;
SOURCE db/migrations/20260405_000005_balance_tuning.sql;
SOURCE db/migrations/20260405_000006_resource_balance.sql;
SOURCE db/migrations/20260405_000007_factory_system.sql;
SOURCE db/migrations/20260406_000008_market_hardening.sql;
SOURCE db/migrations/20260406_000009_war_core.sql;
