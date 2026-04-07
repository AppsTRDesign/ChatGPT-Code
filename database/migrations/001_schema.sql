CREATE TABLE IF NOT EXISTS countries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(8) NOT NULL UNIQUE,
    iso_code CHAR(2) NULL,
    flag_url VARCHAR(255) NOT NULL,
    color CHAR(7) NOT NULL,
    capital_region_id INT NULL,
    government_type VARCHAR(80) DEFAULT 'republic',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS regions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    country_id INT NOT NULL,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    lat DECIMAL(10,7) NOT NULL,
    lng DECIMAL(10,7) NOT NULL,
    polygon_json JSON NOT NULL,
    population INT NOT NULL DEFAULT 0,
    resource_type ENUM('oil','gold','gas','iron','uranium','agriculture','tech','tourism') DEFAULT 'agriculture',
    owner_country_id INT NOT NULL,
    army_level INT NOT NULL DEFAULT 1,
    education_level INT NOT NULL DEFAULT 1,
    hospital_level INT NOT NULL DEFAULT 1,
    airport_level INT NOT NULL DEFAULT 1,
    port_level INT NOT NULL DEFAULT 0,
    is_coastal TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_regions_country FOREIGN KEY (country_id) REFERENCES countries(id),
    CONSTRAINT fk_regions_owner_country FOREIGN KEY (owner_country_id) REFERENCES countries(id)
);

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(60) NOT NULL UNIQUE,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS player_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    current_region_id INT NOT NULL,
    current_country_id INT NOT NULL,
    level INT NOT NULL DEFAULT 1,
    xp INT NOT NULL DEFAULT 0,
    xp_to_next INT NOT NULL DEFAULT 100,
    gold DECIMAL(15,2) NOT NULL DEFAULT 1000,
    coins DECIMAL(15,2) NOT NULL DEFAULT 0,
    energy INT NOT NULL DEFAULT 100,
    max_energy INT NOT NULL DEFAULT 100,
    last_energy_update DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_profile_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_profile_region FOREIGN KEY (current_region_id) REFERENCES regions(id),
    CONSTRAINT fk_profile_country FOREIGN KEY (current_country_id) REFERENCES countries(id)
);

CREATE TABLE IF NOT EXISTS citizenships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    country_id INT NOT NULL,
    is_homeland TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_citizenship_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_citizenship_country FOREIGN KEY (country_id) REFERENCES countries(id),
    UNIQUE KEY uq_user_country (user_id, country_id)
);

CREATE TABLE IF NOT EXISTS travel_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    from_region_id INT NOT NULL,
    to_region_id INT NOT NULL,
    status ENUM('pending','completed') NOT NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    CONSTRAINT fk_travel_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_travel_from_region FOREIGN KEY (from_region_id) REFERENCES regions(id),
    CONSTRAINT fk_travel_to_region FOREIGN KEY (to_region_id) REFERENCES regions(id)
);

CREATE TABLE IF NOT EXISTS api_tokens (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    CONSTRAINT fk_token_user FOREIGN KEY (user_id) REFERENCES users(id)
);
