ALTER TABLE regions
    ADD COLUMN IF NOT EXISTS region_type ENUM('region','country','independent') NOT NULL DEFAULT 'region',
    ADD COLUMN IF NOT EXISTS parent_country_region_id INT NULL,
    ADD COLUMN IF NOT EXISTS neighbors_json JSON NULL,
    ADD COLUMN IF NOT EXISTS has_sea_access TINYINT(1) NOT NULL DEFAULT 0;

UPDATE regions
SET has_sea_access = IF(is_coastal = 1, 1, has_sea_access)
WHERE has_sea_access = 0;
