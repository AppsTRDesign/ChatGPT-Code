CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'restaurant') NOT NULL DEFAULT 'restaurant',
    restaurant_id INT NULL,
    status ENUM('pending', 'active', 'suspended') DEFAULT 'pending',
    api_key VARCHAR(128) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) DEFAULT 0,
    restaurant_limit INT DEFAULT 0,
    menu_limit INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE restaurants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(160) NOT NULL UNIQUE,
    description TEXT,
    phone VARCHAR(30),
    address VARCHAR(255),
    plan_id INT DEFAULT 1,
    status ENUM('pending', 'active', 'suspended') DEFAULT 'pending',
    timezone VARCHAR(60) DEFAULT 'Europe/Istanbul',
    theme VARCHAR(20) DEFAULT 'emerald',
    primary_color VARCHAR(10) DEFAULT '#22c55e',
    currency VARCHAR(8) DEFAULT 'TRY',
    primary_language VARCHAR(5) DEFAULT 'tr',
    supported_languages JSON DEFAULT (JSON_ARRAY('tr','en','ar','fr')),
    logo_url VARCHAR(255),
    favicon_url VARCHAR(255),
    menu_layout VARCHAR(20) DEFAULT 'modern',
    qr_table_prefix VARCHAR(40) DEFAULT 'Masa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES plans(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE restaurant_tables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    restaurant_id INT NOT NULL,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(160) NOT NULL,
    qr_token VARCHAR(64) NOT NULL,
    seats INT DEFAULT 4,
    status ENUM('vacant','occupied') DEFAULT 'vacant',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_table_slug (restaurant_id, slug),
    UNIQUE KEY uniq_table_token (qr_token),
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    restaurant_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    icon_class VARCHAR(120),
    image_url VARCHAR(255),
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    restaurant_id INT NOT NULL,
    category_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'TRY',
    image_url VARCHAR(255),
    sort_order INT DEFAULT 0,
    is_available TINYINT(1) DEFAULT 1,
    nutrition JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    restaurant_id INT NOT NULL,
    table_id INT NULL,
    table_number VARCHAR(40) NOT NULL,
    order_number VARCHAR(32) NOT NULL,
    customer_note TEXT,
    items JSON NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(8) DEFAULT 'TRY',
    status ENUM('pending','preparing','ready','completed','cancelled') DEFAULT 'pending',
    payment_status ENUM('unpaid','paid') DEFAULT 'unpaid',
    payment_method VARCHAR(40) DEFAULT 'cash',
    locale VARCHAR(5) DEFAULT 'tr',
    receipt_path VARCHAR(255),
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
    FOREIGN KEY (table_id) REFERENCES restaurant_tables(id) ON DELETE SET NULL,
    UNIQUE KEY uniq_order_number (order_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE order_status_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    status ENUM('pending','preparing','ready','completed','cancelled') NOT NULL,
    note VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE waiter_calls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    restaurant_id INT NOT NULL,
    table_id INT NOT NULL,
    table_number VARCHAR(40) NOT NULL,
    status ENUM('pending','acknowledged','resolved') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
    FOREIGN KEY (table_id) REFERENCES restaurant_tables(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE settings (
    `key` VARCHAR(120) PRIMARY KEY,
    `value` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO plans (name, description, price) VALUES
('Ücretsiz', 'Temel özellikler', 0.00),
('Premium', 'Gelişmiş menü ve raporlama', 199.00);
