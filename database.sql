-- Noa Political Wars / RivalRegions-like DB bootstrap
-- Production-oriented migration entrypoint
-- Run files in this order:
--  1) db/migrations/20260405_000001_core_schema.sql
--  2) db/migrations/20260405_000002_seed_core_data.sql

SOURCE db/migrations/20260405_000001_core_schema.sql;
SOURCE db/migrations/20260405_000002_seed_core_data.sql;
