CREATE TABLE IF NOT EXISTS places (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source VARCHAR(32) NOT NULL,
    city_name VARCHAR(128) DEFAULT '',
    name VARCHAR(255) NOT NULL,
    formatted_address TEXT,
    latitude VARCHAR(64),
    longitude VARCHAR(64),
    rating DECIMAL(3,1) DEFAULT NULL,
    user_ratings_total INT UNSIGNED DEFAULT NULL,
    formatted_phone_number VARCHAR(64),
    telephone_type VARCHAR(32),
    website TEXT,
    business_type VARCHAR(255),
    opening_hours JSON,
    busy_hours JSON,
    business_image TEXT,
    business_default_image TEXT,
    reviews JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_source (source),
    KEY idx_city_name (city_name),
    KEY idx_name (name(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_reviews (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    place_id BIGINT UNSIGNED NOT NULL,
    author_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    review_text TEXT,
    text_extra JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_place_id (place_id),
    CONSTRAINT fk_reviews_place FOREIGN KEY (place_id) REFERENCES places(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
