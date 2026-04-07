ALTER TABLE player_travel
    MODIFY COLUMN status ENUM('traveling','returning','completed','cancelled') NOT NULL DEFAULT 'traveling';

ALTER TABLE player_travel
    ADD COLUMN IF NOT EXISTS start_progress DECIMAL(5,4) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS end_progress DECIMAL(5,4) NOT NULL DEFAULT 1;
