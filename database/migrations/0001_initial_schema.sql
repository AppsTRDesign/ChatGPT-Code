CREATE TABLE IF NOT EXISTS settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `group` VARCHAR(50) NOT NULL,
    `key` VARCHAR(100) NOT NULL,
    `value` TEXT NULL,
    UNIQUE KEY unique_setting (`group`, `key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'client') NOT NULL DEFAULT 'client',
    provider VARCHAR(50) NOT NULL DEFAULT 'local',
    firebase_uid VARCHAR(191) NULL,
    email_verified_at TIMESTAMP NULL,
    login_banned_until TIMESTAMP NULL,
    is_blocked TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO users (username, email, password, role)
VALUES ('admin', 'admin@webpush.noasoft.org', '$2y$12$ll4mc24pIrX.93pun/nCkOWfp622SwLNgl.ge4QVZXt1KDwXZEUDO', 'admin')
ON DUPLICATE KEY UPDATE password = VALUES(password), email = VALUES(email);

CREATE TABLE IF NOT EXISTS clients (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NULL,
    phone VARCHAR(50) NULL,
    domain VARCHAR(150) NOT NULL,
    default_language VARCHAR(10) NULL,
    status ENUM('active', 'suspended') DEFAULT 'active',
    mail_verified_at TIMESTAMP NULL,
    logo_path VARCHAR(255) NULL,
    favicon_path VARCHAR(255) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS api_keys (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    api_key VARCHAR(64) NOT NULL UNIQUE,
    permissions JSON NOT NULL,
    status ENUM('active', 'revoked') DEFAULT 'active',
    last_used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS client_sites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    domain VARCHAR(191) NOT NULL,
    api_identifier VARCHAR(191) NOT NULL UNIQUE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS subscriptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    token VARCHAR(191) NOT NULL UNIQUE,
    endpoint TEXT NOT NULL,
    public_key VARCHAR(255) NULL,
    auth_token VARCHAR(255) NULL,
    status ENUM('active', 'revoked', 'unsubscribed') DEFAULT 'active',
    site_id INT UNSIGNED NULL,
    language VARCHAR(10) NULL,
    city VARCHAR(120) NULL,
    country VARCHAR(120) NULL,
    platform VARCHAR(120) NULL,
    browser VARCHAR(120) NULL,
    device_model VARCHAR(120) NULL,
    device_type VARCHAR(120) NULL,
    ip_address VARBINARY(16) NULL,
    last_seen_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (site_id) REFERENCES client_sites(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS templates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NULL,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(150) NOT NULL UNIQUE,
    content MEDIUMTEXT NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    template_id INT UNSIGNED NULL,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    target_url TEXT NULL,
    language VARCHAR(10) NULL,
    site_id INT UNSIGNED NULL,
    filters JSON NULL,
    button_text VARCHAR(120) NULL,
    button_url TEXT NULL,
    image_path VARCHAR(255) NULL,
    icon_path VARCHAR(255) NULL,
    ttl_seconds INT NULL,
    status ENUM('queued', 'sending', 'sent', 'failed') DEFAULT 'queued',
    schedule_at TIMESTAMP NULL,
    sent_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES templates(id) ON DELETE SET NULL,
    FOREIGN KEY (site_id) REFERENCES client_sites(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notification_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    notification_id BIGINT UNSIGNED NOT NULL,
    subscription_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    error_message TEXT NULL,
    opened_at TIMESTAMP NULL,
    clicked_at TIMESTAMP NULL,
    closed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE,
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    iyzico_payment_id VARCHAR(100) NOT NULL,
    status ENUM('initiated', 'paid', 'failed', 'refunded', 'partial') DEFAULT 'initiated',
    amount DECIMAL(10,2) NOT NULL,
    currency CHAR(3) DEFAULT 'TRY',
    method VARCHAR(30) DEFAULT 'iyzico',
    note TEXT NULL,
    error_message TEXT NULL,
    raw_response JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS packages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(150) NOT NULL UNIQUE,
    monthly_limit INT NULL,
    duration_days INT NULL,
    site_limit INT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0,
    currency CHAR(3) NOT NULL DEFAULT 'TRY',
    description TEXT NULL,
    features JSON NULL,
    allowed_features JSON NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS client_packages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    package_id INT UNSIGNED NOT NULL,
    status ENUM('pending', 'active', 'cancelled', 'rejected', 'partial') DEFAULT 'pending',
    payment_id BIGINT UNSIGNED NULL,
    payment_method ENUM('iyzico', 'bank_transfer') DEFAULT 'iyzico',
    amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    currency CHAR(3) NOT NULL DEFAULT 'TRY',
    note TEXT NULL,
    error_log TEXT NULL,
    auto_approved TINYINT(1) DEFAULT 0,
    requested_at TIMESTAMP NULL,
    approved_at TIMESTAMP NULL,
    activated_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS api_usage_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    api_key_id INT UNSIGNED NOT NULL,
    endpoint VARCHAR(150) NOT NULL,
    status ENUM('success', 'error') NOT NULL DEFAULT 'success',
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (api_key_id) REFERENCES api_keys(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS support_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NULL,
    email VARCHAR(150) NOT NULL,
    subject VARCHAR(191) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('open', 'closed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS webhooks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    type VARCHAR(100) NOT NULL,
    payload JSON NOT NULL,
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
