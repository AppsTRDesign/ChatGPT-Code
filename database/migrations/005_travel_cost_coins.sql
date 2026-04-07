ALTER TABLE player_travel
    ADD COLUMN IF NOT EXISTS cost_coins INT NOT NULL DEFAULT 0 AFTER distance_km;
