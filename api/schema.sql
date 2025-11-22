CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(255) NOT NULL,
    points INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS sites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    url TEXT NOT NULL,
    dwell_seconds INT NOT NULL DEFAULT 30,
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
    country VARCHAR(128) NULL,
    city VARCHAR(128) NULL,
    platform VARCHAR(128) NULL,
    device VARCHAR(64) NULL,
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
