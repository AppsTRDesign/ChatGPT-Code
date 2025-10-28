-- Uyumlu MySQL 8 / MariaDB 10.4+

SET NAMES utf8mb4;
SET time_zone = '+00:00';

DROP TABLE IF EXISTS realtime_events;
DROP TABLE IF EXISTS file_access_logs;
DROP TABLE IF EXISTS retention_policies;
DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS payment_notifications;
DROP TABLE IF EXISTS contacts;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS files;
DROP TABLE IF EXISTS folders;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS packages;

CREATE TABLE packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    storage_limit BIGINT NOT NULL,
    max_concurrent_uploads INT NOT NULL,
    features TEXT NOT NULL,
    allowed_mime_types TEXT DEFAULT NULL,
    plesk_service_plan VARCHAR(191) DEFAULT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO packages (name, storage_limit, max_concurrent_uploads, features, allowed_mime_types, plesk_service_plan, price) VALUES
    ('Başlangıç', 524288000, 2, JSON_ARRAY('Temel depolama', 'Sınırlı destek'), NULL, NULL, 0.00),
    ('Profesyonel', 2147483648, 5, JSON_ARRAY('Gelişmiş depolama', 'Öncelikli destek', 'Analitik raporlar'), NULL, NULL, 14.99),
    ('Kurumsal', 5368709120, 10, JSON_ARRAY('Sınırsız paylaşım', 'Takım yönetimi', 'Özel SLA'), NULL, NULL, 49.99);

CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meta_title VARCHAR(255) DEFAULT NULL,
    meta_description TEXT DEFAULT NULL,
    header_html TEXT DEFAULT NULL,
    footer_html TEXT DEFAULT NULL,
    logo VARCHAR(255) DEFAULT NULL,
    favicon VARCHAR(255) DEFAULT NULL,
    mail_enabled TINYINT(1) DEFAULT 0,
    mail_method ENUM('phpmail','smtp') DEFAULT 'smtp',
    mail_host VARCHAR(255) DEFAULT NULL,
    mail_port INT DEFAULT NULL,
    mail_username VARCHAR(255) DEFAULT NULL,
    mail_password VARCHAR(255) DEFAULT NULL,
    mail_encryption VARCHAR(10) DEFAULT NULL,
    mail_from_name VARCHAR(150) DEFAULT NULL,
    mail_from_address VARCHAR(191) DEFAULT NULL,
    analytics_code TEXT DEFAULT NULL,
    analytics_enabled TINYINT(1) DEFAULT 0,
    allowed_mime_types TEXT DEFAULT NULL,
    share_expiry_minutes INT DEFAULT 1440,
    public_sharing_enabled TINYINT(1) DEFAULT 1,
    folder_passwords_enabled TINYINT(1) DEFAULT 1,
    share_download_delay INT DEFAULT 0,
    share_password_required TINYINT(1) DEFAULT 0,
    share_stats_enabled TINYINT(1) DEFAULT 1,
    ad_dashboard_html TEXT DEFAULT NULL,
    ad_share_top_html TEXT DEFAULT NULL,
    ad_share_bottom_html TEXT DEFAULT NULL,
    payment_currency VARCHAR(10) DEFAULT 'TRY',
    iyzico_enabled TINYINT(1) DEFAULT 0,
    iyzico_api_key VARCHAR(191) DEFAULT NULL,
    iyzico_secret_key VARCHAR(191) DEFAULT NULL,
    iyzico_base_url VARCHAR(191) DEFAULT NULL,
    stripe_enabled TINYINT(1) DEFAULT 0,
    stripe_api_key VARCHAR(191) DEFAULT NULL,
    stripe_publishable_key VARCHAR(191) DEFAULT NULL,
    stripe_webhook_secret VARCHAR(191) DEFAULT NULL,
    bank_transfer_enabled TINYINT(1) DEFAULT 1,
    bank_transfer_instructions TEXT DEFAULT NULL,
    auto_archive_enabled TINYINT(1) DEFAULT 0,
    auto_delete_enabled TINYINT(1) DEFAULT 0,
    archive_after_days INT DEFAULT NULL,
    delete_after_days INT DEFAULT NULL,
    geoip_database_path VARCHAR(255) DEFAULT NULL,
    realtime_updates_enabled TINYINT(1) DEFAULT 0,
    realtime_ws_url VARCHAR(255) DEFAULT NULL,
    plesk_api_url VARCHAR(255) DEFAULT NULL,
    plesk_api_login VARCHAR(191) DEFAULT NULL,
    plesk_api_password VARCHAR(191) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO settings (
    meta_title,
    meta_description,
    header_html,
    footer_html,
    analytics_enabled,
    allowed_mime_types,
    share_expiry_minutes,
    public_sharing_enabled,
    folder_passwords_enabled,
    share_download_delay,
    payment_currency,
    bank_transfer_enabled,
    realtime_ws_url
) VALUES (
    'NoaSoft Dosya Deposu',
    'Güvenli ve hızlı dosya yükleme platformu.',
    '',
    CONCAT('© ', YEAR(CURDATE()), ' NoaSoft'),
    0,
    JSON_ARRAY('image/jpeg','image/png','image/gif','application/pdf','text/plain','application/zip','application/x-rar-compressed','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
    1440,
    1,
    1,
    0,
    'TRY',
    1,
    NULL
);

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('client', 'admin') NOT NULL DEFAULT 'client',
    package_id INT DEFAULT NULL,
    email_verified TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT fk_users_packages FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO users (name, email, password_hash, role, email_verified)
VALUES ('Sistem Yöneticisi', 'admin@noasoft.org', '$2y$12$J.8D8vKhhm0k0HNKk4L0PeoZrygw9EwzBtuE70tL5z9KgMRgzd43W', 'admin', 1);

CREATE TABLE folders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    parent_id INT DEFAULT NULL,
    name VARCHAR(120) NOT NULL,
    is_public TINYINT(1) NOT NULL DEFAULT 0,
    is_protected TINYINT(1) NOT NULL DEFAULT 0,
    password_hash VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_folders_user (user_id),
    INDEX idx_folders_parent (parent_id),
    CONSTRAINT fk_seed_folders_users FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_seed_folders_parent FOREIGN KEY (parent_id) REFERENCES folders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE retention_policies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    archive_after_days INT DEFAULT NULL,
    delete_after_days INT DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    size BIGINT NOT NULL,
    type VARCHAR(100) NOT NULL,
    uploader_ip VARCHAR(45) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_id INT DEFAULT NULL,
    folder_id INT DEFAULT NULL,
    is_public TINYINT(1) NOT NULL DEFAULT 0,
    share_token VARCHAR(64) DEFAULT NULL,
    share_created_at DATETIME DEFAULT NULL,
    share_expires_at DATETIME DEFAULT NULL,
    retention_policy_id INT DEFAULT NULL,
    INDEX (user_id),
    INDEX (folder_id),
    UNIQUE KEY uniq_share_token (share_token),
    CONSTRAINT fk_files_users FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_files_folders FOREIGN KEY (folder_id) REFERENCES folders(id) ON DELETE SET NULL,
    CONSTRAINT fk_files_retention FOREIGN KEY (retention_policy_id) REFERENCES retention_policies(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (user_id),
    CONSTRAINT fk_password_resets_users FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    package_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(10) NOT NULL DEFAULT 'TRY',
    provider ENUM('iyzico','stripe','bank_transfer') NOT NULL DEFAULT 'bank_transfer',
    status ENUM('pending', 'paid', 'failed', 'cancelled', 'refunded') DEFAULT 'pending',
    reference VARCHAR(191) DEFAULT NULL,
    payload JSON DEFAULT NULL,
    paid_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_transactions_users FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_transactions_packages FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE,
    INDEX idx_transactions_provider (provider),
    INDEX idx_transactions_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE payment_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT DEFAULT NULL,
    user_id INT NOT NULL,
    provider ENUM('iyzico','stripe','bank_transfer') NOT NULL DEFAULT 'bank_transfer',
    amount DECIMAL(10,2) DEFAULT NULL,
    currency VARCHAR(10) DEFAULT 'TRY',
    status ENUM('pending','approved','rejected','insufficient') NOT NULL DEFAULT 'pending',
    attachments JSON DEFAULT NULL,
    note TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_payment_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_payment_notifications_transaction FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE SET NULL,
    INDEX idx_payment_notifications_status (status),
    UNIQUE KEY uniq_payment_notifications_tx (transaction_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE file_access_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    file_id INT NOT NULL,
    user_id INT DEFAULT NULL,
    share_token VARCHAR(64) DEFAULT NULL,
    ip_address VARCHAR(45) NOT NULL,
    country VARCHAR(120) DEFAULT NULL,
    city VARCHAR(120) DEFAULT NULL,
    latitude DECIMAL(10,6) DEFAULT NULL,
    longitude DECIMAL(10,6) DEFAULT NULL,
    device_type VARCHAR(50) DEFAULT NULL,
    os VARCHAR(100) DEFAULT NULL,
    browser VARCHAR(100) DEFAULT NULL,
    platform VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_access_file (file_id),
    INDEX idx_access_token (share_token),
    CONSTRAINT fk_access_file FOREIGN KEY (file_id) REFERENCES files(id) ON DELETE CASCADE,
    CONSTRAINT fk_access_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE realtime_events (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    channel VARCHAR(120) NOT NULL,
    payload JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_events_channel (channel),
    INDEX idx_events_user (user_id),
    CONSTRAINT fk_events_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
