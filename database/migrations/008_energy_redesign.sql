ALTER TABLE player_profiles
    ADD COLUMN IF NOT EXISTS instant_energy INT NOT NULL DEFAULT 300,
    ADD COLUMN IF NOT EXISTS max_instant_energy INT NOT NULL DEFAULT 300,
    ADD COLUMN IF NOT EXISTS total_energy INT NOT NULL DEFAULT 100000;

UPDATE player_profiles
SET
    instant_energy = GREATEST(0, instant_energy),
    max_instant_energy = GREATEST(1, max_instant_energy),
    total_energy = GREATEST(0, total_energy),
    last_energy_update = COALESCE(last_energy_update, NOW());
