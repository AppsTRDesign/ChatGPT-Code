CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(120) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    email_verified TINYINT(1) DEFAULT 0,
    verification_token VARCHAR(120) DEFAULT NULL,
    verification_sent_at TIMESTAMP NULL,
    role ENUM('admin','client') DEFAULT 'client',
    firebase_uid VARCHAR(120) DEFAULT NULL,
    firebase_provider VARCHAR(60) DEFAULT NULL,
    is_approved TINYINT(1) DEFAULT 1,
    login_blocked TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE UNIQUE INDEX idx_users_firebase_uid ON users (firebase_uid);

CREATE TABLE packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    description TEXT,
    monthly_limit INT NOT NULL,
    duration_days INT NOT NULL DEFAULT 30,
    features TEXT,
    price DECIMAL(10,2) NOT NULL DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    qr_features TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE user_packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    package_id INT NOT NULL,
    status ENUM('pending','active','cancelled','awaiting_payment','payment_missing','rejected','failed') DEFAULT 'pending',
    payment_method ENUM('iyzico','bank') DEFAULT 'bank',
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activated_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    limit_snapshot INT DEFAULT NULL,
    duration_days INT DEFAULT NULL,
    threshold_50_notified TINYINT(1) DEFAULT 0,
    threshold_25_notified TINYINT(1) DEFAULT 0,
    threshold_5_notified TINYINT(1) DEFAULT 0,
    last_error TEXT NULL,
    last_error_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE api_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(128) NOT NULL UNIQUE,
    label VARCHAR(150) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    revoked_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE api_usage_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    endpoint VARCHAR(120) NOT NULL,
    status VARCHAR(45) NOT NULL,
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE qr_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    origin ENUM('client','api') DEFAULT 'client',
    type VARCHAR(60) NOT NULL,
    content TEXT NOT NULL,
    options_json TEXT,
    files_json TEXT NOT NULL,
    meta_json TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE session_activity (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_key VARCHAR(120) NOT NULL UNIQUE,
    user_id INT DEFAULT NULL,
    ip VARCHAR(45) DEFAULT NULL,
    user_agent TEXT,
    platform VARCHAR(60) DEFAULT NULL,
    country VARCHAR(60) DEFAULT NULL,
    city VARCHAR(60) DEFAULT NULL,
    referer TEXT,
    search_engine VARCHAR(60) DEFAULT NULL,
    search_term VARCHAR(120) DEFAULT NULL,
    last_url TEXT,
    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE web_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT DEFAULT NULL,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    languages_json TEXT DEFAULT NULL,
    platforms_json TEXT DEFAULT NULL,
    url TEXT DEFAULT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE web_notification_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notification_id INT NOT NULL,
    session_key VARCHAR(120) NOT NULL,
    user_id INT DEFAULT NULL,
    action ENUM('delivered','clicked','dismissed') NOT NULL,
    ip VARCHAR(45) DEFAULT NULL,
    user_agent TEXT,
    platform VARCHAR(60) DEFAULT NULL,
    language VARCHAR(20) DEFAULT NULL,
    country VARCHAR(80) DEFAULT NULL,
    city VARCHAR(80) DEFAULT NULL,
    referer TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_notification_session_action (notification_id, session_key, action),
    INDEX idx_notification_action (notification_id, action),
    FOREIGN KEY (notification_id) REFERENCES web_notifications(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE payment_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    iyzico_enabled TINYINT(1) DEFAULT 0,
    iyzico_api_key VARCHAR(255) DEFAULT '',
    iyzico_secret_key VARCHAR(255) DEFAULT '',
    iyzico_base_url VARCHAR(255) DEFAULT 'https://sandbox-api.iyzipay.com',
    bank_account TEXT,
    bank_enabled TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE mail_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    is_active TINYINT(1) DEFAULT 0,
    transport ENUM('mail','smtp') DEFAULT 'mail',
    host VARCHAR(190) DEFAULT NULL,
    port INT DEFAULT NULL,
    username VARCHAR(190) DEFAULT NULL,
    password VARCHAR(190) DEFAULT NULL,
    encryption ENUM('none','ssl','tls') DEFAULT 'none',
    from_email VARCHAR(190) DEFAULT NULL,
    from_name VARCHAR(190) DEFAULT NULL,
    reply_to_email VARCHAR(190) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(120) NOT NULL UNIQUE,
    value TEXT,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(120) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE email_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(120) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    consumed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE iyzico_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_package_id INT NOT NULL,
    iyzico_token VARCHAR(120) NOT NULL,
    status VARCHAR(60) NOT NULL,
    raw_response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_package_id) REFERENCES user_packages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE payment_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_package_id INT DEFAULT NULL,
    amount VARCHAR(50) DEFAULT NULL,
    note TEXT,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (user_package_id) REFERENCES user_packages(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO users (username, email, password, role, email_verified) VALUES
('admin', 'admin@qrmenu.noasoft.org', '$2y$12$qvP60IWAGEcLbDnmr0fLqugHlViMLGAM8Nt.bSPemwwgWyhXGfPwS', 'admin', 1);

INSERT INTO packages (name, description, monthly_limit, duration_days, features, price, is_active, qr_features) VALUES
('Ücretsiz', 'Ayda 100 QR API isteği sunan temel paket', 100, 30, "100 API isteği\nTemel renk ayarı\nLogo desteği", 0.00, 1, NULL),
('Başlangıç', 'Ayda 500 QR API isteği', 500, 30, "500 API isteği\nRenk & arka plan özelleştirme\nLogo ekleme", 99.90, 1, NULL),
('Profesyonel', 'Ayda 2.500 QR API isteği', 2500, 30, "2.500 API isteği\nÖncelikli destek\nRenk & logo varyasyonları", 249.90, 1, NULL),
('Kurumsal', 'Ayda 10.000 QR API isteği', 10000, 30, "10.000 API isteği\nÇoklu ekip yönetimi\nÖzel alan adı yönlendirme", 599.90, 1, NULL);
