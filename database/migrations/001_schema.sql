CREATE TABLE IF NOT EXISTS countries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(8) NOT NULL UNIQUE,
    iso_code CHAR(2) NULL,
    flag_url VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS regions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    country_id INT NOT NULL,
    country_code VARCHAR(12) NOT NULL,
    country_name VARCHAR(120) NOT NULL,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    lat DECIMAL(10,7) NOT NULL,
    lng DECIMAL(10,7) NOT NULL,
    polygon_json JSON NOT NULL,
    population INT NOT NULL DEFAULT 0,
    resource_type ENUM('uranium','mineral','oil','gold','diamond') DEFAULT 'mineral',
    owner_region_id INT NULL,
    region_type ENUM('region','country','independent') NOT NULL DEFAULT 'country',
    capital_region_id INT NULL,
    government_type ENUM('dictatorship','republic') NOT NULL DEFAULT 'republic',
    color CHAR(7) NOT NULL DEFAULT '#7c3aed',
    flag_url VARCHAR(255) NULL,
    neighbors_json JSON NULL,
    treasury_state_money BIGINT NOT NULL DEFAULT 250000000,
    treasury_gold BIGINT NOT NULL DEFAULT 250000000,
    treasury_uranium BIGINT NOT NULL DEFAULT 1000000,
    treasury_mineral BIGINT NOT NULL DEFAULT 1000000,
    treasury_oil BIGINT NOT NULL DEFAULT 1000000,
    treasury_diamond BIGINT NOT NULL DEFAULT 1000000,
    general_tax_rate DECIMAL(5,2) NOT NULL DEFAULT 10.00,
    sales_tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    factory_tax_uranium DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    factory_tax_mineral DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    factory_tax_oil DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    factory_tax_gold DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    factory_tax_diamond DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    airport_level INT NOT NULL DEFAULT 1,
    army_level INT NOT NULL DEFAULT 1,
    hospital_level INT NOT NULL DEFAULT 1,
    education_level INT NOT NULL DEFAULT 1,
    school_level INT NOT NULL DEFAULT 1,
    port_level INT NOT NULL DEFAULT 1,
    is_coastal TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_regions_country FOREIGN KEY (country_id) REFERENCES countries(id),
    CONSTRAINT fk_regions_owner_region FOREIGN KEY (owner_region_id) REFERENCES regions(id),
    CONSTRAINT fk_regions_capital_region FOREIGN KEY (capital_region_id) REFERENCES regions(id)
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
    current_country_region_id INT NOT NULL,
    level INT NOT NULL DEFAULT 1,
    xp INT NOT NULL DEFAULT 0,
    xp_to_next INT NOT NULL DEFAULT 100,
    gold DECIMAL(15,2) NOT NULL DEFAULT 1000,
    coins DECIMAL(15,2) NOT NULL DEFAULT 0,
    instant_energy INT NOT NULL DEFAULT 300,
    max_instant_energy INT NOT NULL DEFAULT 300,
    total_energy INT NOT NULL DEFAULT 100000,
    last_energy_update DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_profile_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_profile_region FOREIGN KEY (current_region_id) REFERENCES regions(id),
    CONSTRAINT fk_profile_country_region FOREIGN KEY (current_country_region_id) REFERENCES regions(id)
);

CREATE TABLE IF NOT EXISTS citizenships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    country_region_id INT NOT NULL,
    is_homeland TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_citizenship_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_citizenship_country_region FOREIGN KEY (country_region_id) REFERENCES regions(id),
    UNIQUE KEY uq_user_country (user_id, country_region_id)
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
    status ENUM('traveling','returning','completed','cancelled') NOT NULL DEFAULT 'traveling',
    start_progress DECIMAL(5,4) NOT NULL DEFAULT 0,
    end_progress DECIMAL(5,4) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_player_travel_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_player_travel_from FOREIGN KEY (from_region_id) REFERENCES regions(id),
    CONSTRAINT fk_player_travel_to FOREIGN KEY (to_region_id) REFERENCES regions(id)
);

CREATE TABLE IF NOT EXISTS api_tokens (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    CONSTRAINT fk_token_user FOREIGN KEY (user_id) REFERENCES users(id)
);
