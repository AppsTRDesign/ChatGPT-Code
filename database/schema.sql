CREATE TABLE IF NOT EXISTS restaurants (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NULL,
    description TEXT NULL,
    address VARCHAR(255) NULL,
    currency CHAR(3) DEFAULT 'TRY',
    timezone VARCHAR(64) DEFAULT 'Europe/Istanbul',
    language VARCHAR(5) DEFAULT 'tr',
    theme_color VARCHAR(7) DEFAULT '#0f9d58',
    logo VARCHAR(255) NULL,
    favicon VARCHAR(255) NULL,
    qr_logo VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS restaurant_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    restaurant_id INT UNSIGNED NOT NULL,
    section VARCHAR(50) NOT NULL,
    payload JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_section (restaurant_id, section),
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS tables (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    restaurant_id INT UNSIGNED NOT NULL,
    name VARCHAR(50) NOT NULL,
    status ENUM('available', 'occupied') DEFAULT 'available',
    qr_code_url VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    restaurant_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    icon VARCHAR(60) NULL,
    image VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    restaurant_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT NULL,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS product_variants (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    restaurant_id INT UNSIGNED NOT NULL,
    table_id INT UNSIGNED NOT NULL,
    status ENUM('Beklemede', 'Hazırlanıyor', 'Hazırlandı', 'Ödeme Alındı', 'İptal') DEFAULT 'Beklemede',
    total DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
    FOREIGN KEY (table_id) REFERENCES tables(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    variant_id INT UNSIGNED NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS waiter_calls (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    restaurant_id INT UNSIGNED NOT NULL,
    table_id INT UNSIGNED NOT NULL,
    status ENUM('waiting', 'on_the_way', 'completed') DEFAULT 'waiting',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
    FOREIGN KEY (table_id) REFERENCES tables(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS restaurant_currencies (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    restaurant_id INT UNSIGNED NOT NULL,
    code VARCHAR(10) NOT NULL,
    symbol VARCHAR(10) DEFAULT '₺',
    name VARCHAR(120) NOT NULL,
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY currency_unique (restaurant_id, code),
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS restaurant_languages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    restaurant_id INT UNSIGNED NOT NULL,
    code VARCHAR(10) NOT NULL,
    label VARCHAR(120) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY lang_unique (restaurant_id, code),
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS restaurant_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    restaurant_id INT UNSIGNED NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(120) NOT NULL,
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY user_unique (restaurant_id, email),
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
);

INSERT INTO restaurants (id, name, phone, description, address, currency, timezone, language, theme_color)
VALUES (1, 'NoaSoft Restaurant', '+90 555 000 0000', 'NoaSoft QR Menü demo restoranı', 'İstanbul', 'TRY', 'Europe/Istanbul', 'tr', '#0f9d58')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO restaurant_settings (restaurant_id, section, payload) VALUES
(1, 'qr', JSON_OBJECT('token', '', 'width', 400, 'height', 400, 'format', 'png', 'transparent', false, 'color', '#000000', 'background', '#ffffff'))
ON DUPLICATE KEY UPDATE payload = VALUES(payload);

INSERT INTO restaurant_currencies (restaurant_id, code, symbol, name, is_default) VALUES
(1, 'TRY', '₺', 'Türk Lirası', 1),
(1, 'USD', '$', 'Amerikan Doları', 0)
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO restaurant_languages (restaurant_id, code, label) VALUES
(1, 'tr', 'Türkçe'),
(1, 'en', 'İngilizce')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO restaurant_users (restaurant_id, email, password, name)
VALUES (1, 'admin@noasoft.com', '$2y$10$4KoBmGdltEm8JpVcYFXYwuW2PFe5mpvlbNJ66IWaKOsfD9PfrxGXu', 'Demo Yönetici')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO tables (id, restaurant_id, name, status, qr_code_url) VALUES
(1, 1, 'Salon 1', 'occupied', NULL),
(2, 1, 'Salon 2', 'available', NULL),
(3, 1, 'Teras 1', 'available', NULL)
ON DUPLICATE KEY UPDATE name = VALUES(name), status = VALUES(status);

INSERT INTO categories (id, restaurant_id, name, icon) VALUES
(1, 1, 'İçecekler', '🥤'),
(2, 1, 'Tatlılar', '🍰')
ON DUPLICATE KEY UPDATE name = VALUES(name), icon = VALUES(icon);

INSERT INTO products (id, restaurant_id, category_id, name, description, price, image) VALUES
(1, 1, 1, 'Soğuk Kahve', 'Özel çekirdeklerden hazırlanan buzlu kahve', 89.00, 'assets/vendor/demo/coffee-1.png'),
(2, 1, 2, 'Çikolatalı Pasta', 'Sıcak çikolata soslu taze pasta', 129.50, 'assets/vendor/demo/cake-1.png'),
(3, 1, 1, 'Limonata', 'Taze sıkılmış limonata', 49.90, 'assets/vendor/demo/coffee-1.png')
ON DUPLICATE KEY UPDATE name = VALUES(name), price = VALUES(price);

INSERT INTO product_variants (id, product_id, name, price) VALUES
(1, 1, 'Standart', 89.00),
(2, 1, 'Büyük', 109.00),
(3, 2, 'Dilim', 129.50),
(4, 3, 'Standart', 49.90)
ON DUPLICATE KEY UPDATE name = VALUES(name), price = VALUES(price);

INSERT INTO orders (id, restaurant_id, table_id, status, total, created_at) VALUES
(101, 1, 1, 'Hazırlandı', 258.40, NOW() - INTERVAL 1 DAY),
(102, 1, 2, 'Ödeme Alındı', 180.00, NOW() - INTERVAL 2 DAY),
(103, 1, 3, 'Beklemede', 95.00, NOW())
ON DUPLICATE KEY UPDATE status = VALUES(status), total = VALUES(total);

INSERT INTO order_items (id, order_id, product_id, variant_id, quantity, unit_price) VALUES
(1, 101, 1, 1, 2, 89.00),
(2, 101, 2, 3, 1, 129.50),
(3, 102, 3, 4, 2, 49.90),
(4, 103, 1, 1, 1, 89.00)
ON DUPLICATE KEY UPDATE quantity = VALUES(quantity), unit_price = VALUES(unit_price), variant_id = VALUES(variant_id);

INSERT INTO waiter_calls (id, restaurant_id, table_id, status, created_at) VALUES
(1, 1, 1, 'on_the_way', NOW() - INTERVAL 15 MINUTE),
(2, 1, 3, 'waiting', NOW() - INTERVAL 5 MINUTE)
ON DUPLICATE KEY UPDATE status = VALUES(status);
