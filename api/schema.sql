CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(255) NOT NULL,
    points INT NOT NULL DEFAULT 0,
    device_fingerprint VARCHAR(128) NULL,
    allow_multi_account TINYINT(1) NOT NULL DEFAULT 0,
    max_multi_accounts INT NOT NULL DEFAULT 5,
    max_daily_site_visits INT NOT NULL DEFAULT 2,
    max_daily_reward INT NOT NULL DEFAULT 1200,
    max_weekly_reward INT NOT NULL DEFAULT 5000,
    max_monthly_reward INT NOT NULL DEFAULT 12000,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_users_device_fingerprint ON users(device_fingerprint);

CREATE TABLE IF NOT EXISTS sites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    url TEXT NOT NULL,
    dwell_seconds INT NOT NULL DEFAULT 30,
    google_enabled TINYINT(1) DEFAULT 0,
    google_keyword VARCHAR(255) NULL,
    google_country VARCHAR(16) NULL,
    google_pages INT DEFAULT 1,
    google_dwell INT DEFAULT 30,
    youtube_enabled TINYINT(1) DEFAULT 0,
    youtube_keyword VARCHAR(255) NULL,
    youtube_link TEXT NULL,
    youtube_pages INT DEFAULT 1,
    youtube_dwell INT DEFAULT 60,
    mobile TINYINT(1) DEFAULT 0,
    realistic TINYINT(1) DEFAULT 0,
    mouse_moves TINYINT(1) DEFAULT 0,
    link_clicks TINYINT(1) DEFAULT 0,
    scroll TINYINT(1) DEFAULT 0,
    form_fill TINYINT(1) DEFAULT 0,
    media TINYINT(1) DEFAULT 0,
    media_actions TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS surf_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_id INT NOT NULL,
    surfer_id INT NOT NULL,
    task_mode ENUM('standard','google','youtube') DEFAULT 'standard',
    planned_dwell INT NOT NULL DEFAULT 0,
    planned_pages INT NOT NULL DEFAULT 0,
    status ENUM('in_progress','completed') DEFAULT 'in_progress',
    started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    FOREIGN KEY (surfer_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS site_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_id INT NOT NULL,
    surfer_id INT NOT NULL,
    ip VARCHAR(64) NULL,
    country_code VARCHAR(8) NULL,
    country VARCHAR(128) NULL,
    continent VARCHAR(128) NULL,
    city VARCHAR(128) NULL,
    latitude DECIMAL(10,6) NULL,
    longitude DECIMAL(10,6) NULL,
    asn INT NULL,
    isp VARCHAR(255) NULL,
    network VARCHAR(64) NULL,
    platform VARCHAR(128) NULL,
    device VARCHAR(64) NULL,
    user_agent VARCHAR(255) NULL,
    clicks INT DEFAULT 0,
    scrolls INT DEFAULT 0,
    highlights INT DEFAULT 0,
    forms INT DEFAULT 0,
    media INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    FOREIGN KEY (surfer_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_site_stats_site_created_at (site_id, created_at)
);

CREATE TABLE IF NOT EXISTS point_ledger (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    change_amount INT NOT NULL,
    reason VARCHAR(64) NOT NULL,
    meta JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
