CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(120) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','client') DEFAULT 'client',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
    status ENUM('pending','active','cancelled') DEFAULT 'pending',
    payment_method ENUM('iyzico','bank') DEFAULT 'bank',
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activated_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    limit_snapshot INT DEFAULT NULL,
    duration_days INT DEFAULT NULL,
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

CREATE TABLE payment_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    iyzico_enabled TINYINT(1) DEFAULT 0,
    iyzico_api_key VARCHAR(255) DEFAULT '',
    iyzico_secret_key VARCHAR(255) DEFAULT '',
    bank_account TEXT,
    bank_enabled TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL
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

INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@qrmenu.noasoft.org', '$2y$12$qvP60IWAGEcLbDnmr0fLqugHlViMLGAM8Nt.bSPemwwgWyhXGfPwS', 'admin');

INSERT INTO packages (name, description, monthly_limit, duration_days, features, price, is_active) VALUES
('Başlangıç', 'Ayda 500 QR API isteği', 500, 30, "500 API isteği\nRenk & arka plan özelleştirme\nLogo ekleme", 99.90, 1),
('Profesyonel', 'Ayda 2.500 QR API isteği', 2500, 30, "2.500 API isteği\nÖncelikli destek\nRenk & logo varyasyonları", 249.90, 1),
('Kurumsal', 'Ayda 10.000 QR API isteği', 10000, 30, "10.000 API isteği\nÇoklu ekip yönetimi\nÖzel alan adı yönlendirme", 599.90, 1);
