CREATE TABLE IF NOT EXISTS admin_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(150) NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
);

CREATE TABLE IF NOT EXISTS password_resets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email VARCHAR(150) NOT NULL,
    token VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    key VARCHAR(150) NOT NULL UNIQUE,
    value TEXT NULL,
    updated_at DATETIME NULL
);

CREATE TABLE IF NOT EXISTS telegram_accounts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    phone_number VARCHAR(32) NOT NULL UNIQUE,
    label VARCHAR(100) NULL,
    is_active TINYINT NOT NULL DEFAULT 0,
    banned_at DATETIME NULL,
    last_seen_at DATETIME NULL,
    session_status VARCHAR(32) NULL,
    phone_code_hash VARCHAR(150) NULL,
    two_factor_hint VARCHAR(150) NULL,
    last_error TEXT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
);

CREATE TABLE IF NOT EXISTS members (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    telegram_id VARCHAR(64) NOT NULL,
    username VARCHAR(150) NULL,
    first_name VARCHAR(150) NULL,
    last_name VARCHAR(150) NULL,
    is_public TINYINT NOT NULL DEFAULT 0,
    joined_from_channel VARCHAR(150) NULL,
    last_active_at VARCHAR(100) NULL,
    online_status VARCHAR(100) NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
);

CREATE TABLE IF NOT EXISTS message_templates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title VARCHAR(150) NOT NULL,
    body TEXT NOT NULL,
    attachment_path VARCHAR(255) NULL,
    attachment_name VARCHAR(255) NULL,
    attachment_type VARCHAR(100) NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
);

CREATE TABLE IF NOT EXISTS services (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(150) NOT NULL UNIQUE,
    status VARCHAR(50) NOT NULL,
    last_heartbeat_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
);

CREATE TABLE IF NOT EXISTS dispatch_jobs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(150) NOT NULL,
    template_id INTEGER NOT NULL,
    target_type VARCHAR(50) NOT NULL,
    target_value VARCHAR(150) NOT NULL,
    scheduled_for DATETIME NULL,
    status VARCHAR(50) NOT NULL,
    created_by INTEGER NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
);
