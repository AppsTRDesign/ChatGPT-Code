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
    qr_token VARCHAR(128),
    qr_color VARCHAR(20) DEFAULT '#000000',
    qr_background VARCHAR(20) DEFAULT '#FFFFFF',
    qr_width INT DEFAULT 420,
    qr_height INT DEFAULT 420,
    qr_transparent TINYINT(1) DEFAULT 0,
    qr_format ENUM('png','svg','jpg') DEFAULT 'png',
    qr_logo_url VARCHAR(255),
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

INSERT INTO users (id, name, email, password, role, restaurant_id, status, api_key, created_at) VALUES
(1, 'Sistem Yöneticisi', 'admin@noasoft.org', '$2y$12$Q7A91oKX.TUPgPfmNaQS.OfPAW/CG3p38ezpYE9tJP1aLtCPLyvLC', 'admin', NULL, 'active', NULL, NOW()),
(2, 'Ef Onur Sahibi', 'owner@efonur.com', '$2y$12$kc2w8L5qppcxj84fhNKLlechvXNrSjwWMu6J/z3VhUxI.kiiG1Xse', 'restaurant', NULL, 'active', 'rk_live_demo_f1d4596a6c2c48cbb3c1b7ef3dd7ad68', NOW());

INSERT INTO restaurants (id, user_id, name, slug, description, phone, address, plan_id, status, timezone, theme, primary_color, currency, primary_language, supported_languages, logo_url, favicon_url, qr_token, qr_color, qr_background, qr_width, qr_height, qr_transparent, qr_format, qr_logo_url, menu_layout, qr_table_prefix, created_at, updated_at) VALUES
(1, 2, 'Ef Onur', 'ef-onur', 'Anadolu ve dünya mutfağından seçkin lezzetler sunan modern restoran.', '+90 212 555 0199', 'Bağdat Caddesi No:99 Kadıköy / İstanbul', 2, 'active', 'Europe/Istanbul', 'emerald', '#16a34a', 'TRY', 'tr', '["tr","en","ar","fr"]', '/public/uploads/demo/ef-onur-logo.png', '/public/uploads/demo/ef-onur-favicon.png', 'dc49f4aed1b51e3b2751eb7fdb779d8ecb8514b880579acfc474f2938f6a59fc', '#000000', '#FFFFFF', 420, 420, 0, 'png', '/public/uploads/demo/ef-onur-logo.png', 'modern', 'Masa', NOW(), NOW());

UPDATE users SET restaurant_id = 1 WHERE id = 2;

INSERT INTO restaurant_tables (id, restaurant_id, name, slug, qr_token, seats, status, created_at) VALUES
(1, 1, 'Masa 1', 'masa-1', 'efonur-table-1', 4, 'occupied', NOW()),
(2, 1, 'Masa 2', 'masa-2', 'efonur-table-2', 4, 'vacant', NOW()),
(3, 1, 'Bahçe 1', 'bahce-1', 'efonur-table-3', 6, 'vacant', NOW());

INSERT INTO categories (id, restaurant_id, name, description, icon_class, image_url, sort_order, created_at) VALUES
(1, 1, 'Başlangıçlar', 'Paylaşmalık sıcak ve soğuk başlangıçlar', 'bi bi-egg-fried', '/public/uploads/demo/baslangiclar.jpg', 1, NOW()),
(2, 1, 'Ana Yemekler', 'Odun fırınından çıkan günlük ana yemekler', 'bi bi-fire', '/public/uploads/demo/anayemekler.jpg', 2, NOW()),
(3, 1, 'İçecekler', 'Sıcak ve soğuk içecek seçenekleri', 'bi bi-cup-hot', '/public/uploads/demo/icecekler.jpg', 3, NOW());

INSERT INTO products (id, restaurant_id, category_id, name, description, price, currency, image_url, sort_order, is_available, nutrition, created_at) VALUES
(1, 1, 1, 'Humus Trio', 'Klasik, pancarlı ve pastırmalı humus seçenekleri', 145.00, 'TRY', '/public/uploads/demo/humus.jpg', 1, 1, '{"kalori": "320", "alerjen": "Susam"}', NOW()),
(2, 1, 1, 'Füme Somon Bruschetta', 'Közlenmiş biber sosu ile servis edilir', 175.00, 'TRY', '/public/uploads/demo/bruschetta.jpg', 2, 1, '{"kalori": "280"}', NOW()),
(3, 1, 2, 'Odun Fırınından Kuzu İncik', 'Kekik ve sebze yatağı ile 8 saat pişirilmiş', 365.00, 'TRY', '/public/uploads/demo/kuzu-incik.jpg', 1, 1, '{"kalori": "540"}', NOW()),
(4, 1, 2, 'Izgara Levrek', 'Ege otları ve narenciye sosu eşliğinde', 295.00, 'TRY', '/public/uploads/demo/levrek.jpg', 2, 1, '{"kalori": "410"}', NOW()),
(5, 1, 3, 'Soğuk Demleme Kahve', '12 saat demleme yöntemi ile hazırlanır', 95.00, 'TRY', '/public/uploads/demo/coldbrew.jpg', 1, 1, '{"kafein": "Yüksek"}', NOW());

INSERT INTO orders (id, restaurant_id, table_id, table_number, order_number, customer_note, items, total_amount, currency, status, payment_status, payment_method, locale, receipt_path, paid_at, created_at, updated_at) VALUES
(1, 1, 1, 'Masa 1', 'EFONUR-2024-0001', 'Çocuk sandalyesi rica ediyoruz.', '[{"name":"Humus Trio","quantity":1,"price":145.0},{"name":"Odun Fırınından Kuzu İncik","quantity":2,"price":365.0},{"name":"Soğuk Demleme Kahve","quantity":2,"price":95.0}]', 1065.00, 'TRY', 'preparing', 'unpaid', 'cash', 'tr', NULL, NULL, NOW(), NOW()),
(2, 1, 2, 'Masa 2', 'EFONUR-2024-0002', NULL, '[{"name":"Izgara Levrek","quantity":1,"price":295.0},{"name":"Soğuk Demleme Kahve","quantity":2,"price":95.0}]', 485.00, 'TRY', 'completed', 'paid', 'card', 'en', NULL, NOW(), NOW(), NOW());

INSERT INTO order_status_logs (order_id, status, note, created_at) VALUES
(1, 'pending', 'Sipariş alındı', NOW()),
(1, 'preparing', 'Mutfakta hazırlanıyor', NOW()),
(2, 'pending', 'Sipariş alındı', NOW()),
(2, 'preparing', 'Hazırlanıyor', NOW()),
(2, 'ready', 'Servise hazır', NOW()),
(2, 'completed', 'Teslim edildi', NOW());

INSERT INTO waiter_calls (id, restaurant_id, table_id, table_number, status, created_at) VALUES
(1, 1, 1, 'Masa 1', 'pending', NOW()),
(2, 1, 3, 'Bahçe 1', 'resolved', NOW());

INSERT INTO settings (`key`, `value`) VALUES
('supported_currencies', '["TRY","USD","EUR","GBP"]'),
('socket_notifications', 'true')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);
