ALTER TABLE player_profiles
    DROP COLUMN IF EXISTS energy,
    DROP COLUMN IF EXISTS max_energy;
