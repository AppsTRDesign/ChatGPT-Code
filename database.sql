-- NoaSoft File Upload Platform schema & seed data
-- Uyumlu MySQL 8 / MariaDB 10.4+

SET NAMES utf8mb4;
SET time_zone = '+00:00';

DROP TABLE IF EXISTS transactions;
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
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO packages (name, storage_limit, max_concurrent_uploads, features, price) VALUES
    ('Başlangıç', 524288000, 2, JSON_ARRAY('Temel depolama', 'Sınırlı destek'), 0.00),
    ('Profesyonel', 2147483648, 5, JSON_ARRAY('Gelişmiş depolama', 'Öncelikli destek', 'Analitik raporlar'), 14.99),
    ('Kurumsal', 5368709120, 10, JSON_ARRAY('Sınırsız paylaşım', 'Takım yönetimi', 'Özel SLA'), 49.99);

CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meta_title VARCHAR(255) DEFAULT NULL,
    meta_description TEXT DEFAULT NULL,
    header_html TEXT DEFAULT NULL,
    footer_html TEXT DEFAULT NULL,
    logo VARCHAR(255) DEFAULT NULL,
    favicon VARCHAR(255) DEFAULT NULL,
    mail_host VARCHAR(255) DEFAULT NULL,
    mail_port INT DEFAULT NULL,
    mail_username VARCHAR(255) DEFAULT NULL,
    mail_password VARCHAR(255) DEFAULT NULL,
    mail_encryption VARCHAR(10) DEFAULT NULL,
    analytics_code TEXT DEFAULT NULL,
    analytics_enabled TINYINT(1) DEFAULT 0,
    allowed_mime_types TEXT DEFAULT NULL,
    share_expiry_minutes INT DEFAULT 1440,
    public_sharing_enabled TINYINT(1) DEFAULT 1,
    folder_passwords_enabled TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO settings (meta_title, meta_description, header_html, footer_html, analytics_enabled, allowed_mime_types, share_expiry_minutes, public_sharing_enabled, folder_passwords_enabled)
VALUES (
    'NoaSoft Dosya Deposu',
    'Güvenli ve hızlı dosya yükleme platformu.',
    '',
    CONCAT('© ', YEAR(CURDATE()), ' NoaSoft'),
    0,
    JSON_ARRAY('image/jpeg','image/png','image/gif','application/pdf','text/plain','application/zip','application/x-rar-compressed','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
    1440,
    1,
    1
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
VALUES ('Sistem Yöneticisi', 'admin@fileupload.noasoft.org', '$2y$12$wcnefRGKeNssAK6zh42BlOR8W0KaehWhMQ/DzP4NEeah8x5stJjmu', 'admin', 1);

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
    INDEX (user_id),
    INDEX (folder_id),
    UNIQUE KEY uniq_share_token (share_token),
    CONSTRAINT fk_files_users FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_files_folders FOREIGN KEY (folder_id) REFERENCES folders(id) ON DELETE SET NULL
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
    status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_transactions_users FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_transactions_packages FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
