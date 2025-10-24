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

CREATE TABLE onesignal_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    player_id VARCHAR(190) NOT NULL,
    platform VARCHAR(60) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_player (player_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
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

INSERT INTO packages (name, description, monthly_limit, duration_days, features, price, is_active) VALUES
('Ücretsiz', 'Ayda 100 QR API isteği sunan temel paket', 100, 30, "100 API isteği\nTemel renk ayarı\nLogo desteği", 0.00, 1),
('Başlangıç', 'Ayda 500 QR API isteği', 500, 30, "500 API isteği\nRenk & arka plan özelleştirme\nLogo ekleme", 99.90, 1),
('Profesyonel', 'Ayda 2.500 QR API isteği', 2500, 30, "2.500 API isteği\nÖncelikli destek\nRenk & logo varyasyonları", 249.90, 1),
('Kurumsal', 'Ayda 10.000 QR API isteği', 10000, 30, "10.000 API isteği\nÇoklu ekip yönetimi\nÖzel alan adı yönlendirme", 599.90, 1);
