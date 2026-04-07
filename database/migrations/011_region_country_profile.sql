ALTER TABLE regions
    ADD COLUMN IF NOT EXISTS region_color CHAR(7) NULL,
    ADD COLUMN IF NOT EXISTS region_flag_url VARCHAR(255) NULL;

UPDATE regions
SET region_color = COALESCE(region_color, '#ffffff')
WHERE region_type = 'independent';

-- Example neighbor mapping (city adjacency JSON)
-- Ankara region id example: set nearby region ids as JSON array.
UPDATE regions
SET neighbors_json = COALESCE(neighbors_json, JSON_ARRAY())
WHERE name = 'Ankara';
