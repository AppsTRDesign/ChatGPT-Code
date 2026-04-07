CREATE TABLE IF NOT EXISTS player_travel (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    from_region_id INT NOT NULL,
    to_region_id INT NOT NULL,
    distance_km DECIMAL(10,2) NOT NULL,
    cost_coins INT NOT NULL,
    duration_seconds INT NOT NULL,
    start_time TIMESTAMP NOT NULL,
    end_time TIMESTAMP NOT NULL,
    status ENUM('traveling','completed','cancelled') NOT NULL DEFAULT 'traveling',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_player_travel_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_player_travel_from FOREIGN KEY (from_region_id) REFERENCES regions(id),
    CONSTRAINT fk_player_travel_to FOREIGN KEY (to_region_id) REFERENCES regions(id)
);
