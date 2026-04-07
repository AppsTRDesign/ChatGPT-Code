ALTER TABLE countries
    ADD COLUMN IF NOT EXISTS iso_code CHAR(2) NULL AFTER slug;

UPDATE countries
SET iso_code = UPPER(slug)
WHERE (iso_code IS NULL OR iso_code = '') AND slug REGEXP '^[a-z]{2}$';

ALTER TABLE regions
    ADD COLUMN IF NOT EXISTS population INT NOT NULL DEFAULT 0;
