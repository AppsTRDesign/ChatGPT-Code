CREATE TABLE IF NOT EXISTS admin_users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(150) NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO admin_users (username, password, email, created_at, updated_at)
VALUES (
    'admin',
    '$2y$12$2tA4Dg5eRooRLWeRpdG1/.bdEluwy2mFa0zxt.Tvhd1edalyhCIDu',
    'admin@example.com',
    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE username = VALUES(username);

CREATE TABLE IF NOT EXISTS password_resets (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(150) NOT NULL,
    token VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `key` VARCHAR(150) NOT NULL UNIQUE,
    value TEXT NULL,
    updated_at DATETIME NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS telegram_accounts (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    phone_number VARCHAR(32) NOT NULL UNIQUE,
    label VARCHAR(100) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 0,
    banned_at DATETIME NULL,
    last_seen_at DATETIME NULL,
    session_status VARCHAR(32) NULL,
    phone_code_hash VARCHAR(150) NULL,
    two_factor_hint VARCHAR(150) NULL,
    last_error TEXT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS members (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    telegram_id VARCHAR(64) NOT NULL,
    username VARCHAR(150) NULL,
    first_name VARCHAR(150) NULL,
    last_name VARCHAR(150) NULL,
    is_public TINYINT(1) NOT NULL DEFAULT 0,
    joined_from_channel VARCHAR(150) NULL,
    last_active_at VARCHAR(100) NULL,
    online_status VARCHAR(100) NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS message_templates (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(150) NOT NULL,
    body TEXT NOT NULL,
    attachment_path VARCHAR(255) NULL,
    attachment_name VARCHAR(255) NULL,
    attachment_type VARCHAR(100) NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS services (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(150) NOT NULL UNIQUE,
    status VARCHAR(50) NOT NULL,
    description TEXT NULL,
    command TEXT NULL,
    last_heartbeat_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dispatch_jobs (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    action VARCHAR(50) NOT NULL DEFAULT 'send_message',
    template_id INT UNSIGNED NULL,
    target_type VARCHAR(50) NOT NULL,
    target_value VARCHAR(190) NOT NULL,
    metadata TEXT NULL,
    scheduled_for DATETIME NULL,
    status VARCHAR(50) NOT NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audience_templates (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    entity_type VARCHAR(32) NOT NULL,
    description TEXT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY unique_template_name (name, entity_type),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audience_template_members (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    template_id INT UNSIGNED NOT NULL,
    member_id INT UNSIGNED NOT NULL,
    created_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY unique_template_member (template_id, member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS channel_targets (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    telegram_id VARCHAR(64) NOT NULL,
    access_hash VARCHAR(128) NULL,
    username VARCHAR(150) NULL,
    title VARCHAR(255) NULL,
    type VARCHAR(32) NOT NULL,
    is_public TINYINT(1) NOT NULL DEFAULT 0,
    extra TEXT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY unique_channel (telegram_id, type),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audience_template_channels (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    template_id INT UNSIGNED NOT NULL,
    channel_id INT UNSIGNED NOT NULL,
    created_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY unique_template_channel (template_id, channel_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
