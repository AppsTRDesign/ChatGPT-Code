PRAGMA foreign_keys = ON;

CREATE TABLE settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    setting_key TEXT UNIQUE NOT NULL,
    setting_value TEXT NOT NULL
);

CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    phone TEXT,
    address TEXT,
    avatar TEXT,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'customer',
    created_at TEXT NOT NULL
);

CREATE TABLE categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    parent_id INTEGER,
    name TEXT NOT NULL,
    slug TEXT UNIQUE NOT NULL,
    description TEXT,
    icon TEXT,
    image TEXT,
    created_at TEXT NOT NULL,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT UNIQUE NOT NULL,
    sku TEXT,
    short_description TEXT,
    description TEXT,
    price REAL NOT NULL DEFAULT 0,
    stock INTEGER NOT NULL DEFAULT 0,
    badge_text TEXT,
    free_shipping INTEGER NOT NULL DEFAULT 0,
    discount_type TEXT,
    discount_value REAL NOT NULL DEFAULT 0,
    main_image TEXT,
    category_id INTEGER,
    order_channel TEXT NOT NULL DEFAULT 'whatsapp',
    order_link TEXT,
    visit_count INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE product_images (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    image_path TEXT NOT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE product_features (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    feature_name TEXT NOT NULL,
    feature_value TEXT NOT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE reviews (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    user_id INTEGER,
    reviewer_name TEXT NOT NULL,
    rating INTEGER NOT NULL DEFAULT 5,
    comment TEXT,
    likes INTEGER NOT NULL DEFAULT 0,
    approved INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    full_name TEXT NOT NULL,
    email TEXT NOT NULL,
    phone TEXT NOT NULL,
    address TEXT,
    order_note TEXT,
    status TEXT NOT NULL DEFAULT 'pending',
    channel TEXT NOT NULL DEFAULT 'whatsapp',
    total_amount REAL NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    quantity INTEGER NOT NULL DEFAULT 1,
    unit_price REAL NOT NULL DEFAULT 0,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE bank_transfer_notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    user_id INTEGER,
    full_name TEXT NOT NULL,
    bank_name TEXT NOT NULL,
    receipt_path TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'pending',
    created_at TEXT NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE pages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    slug TEXT UNIQUE NOT NULL,
    summary TEXT,
    content TEXT,
    created_at TEXT NOT NULL
);

CREATE TABLE faqs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    question TEXT NOT NULL,
    answer TEXT NOT NULL,
    created_at TEXT NOT NULL
);

CREATE TABLE favorites (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    created_at TEXT NOT NULL,
    UNIQUE (user_id, product_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE social_links (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    label TEXT NOT NULL,
    icon_class TEXT NOT NULL,
    url TEXT NOT NULL,
    created_at TEXT NOT NULL
);

CREATE TABLE sliders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    description TEXT,
    button_text TEXT,
    button_url TEXT,
    image TEXT,
    gradient_start TEXT,
    gradient_end TEXT,
    title_color TEXT,
    description_color TEXT,
    image_position TEXT NOT NULL DEFAULT 'right',
    slide_effect TEXT NOT NULL DEFAULT 'fade-up',
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL
);

INSERT INTO settings (setting_key, setting_value) VALUES
('base_url', 'https://cicek.noasoft.org'),
('site_name', 'NoaSoft Çiçek'),
('site_address', 'İstanbul'),
('map_embed', 'https://www.openstreetmap.org/export/embed.html'),
('contact_phone', '+90 555 000 0000'),
('contact_email', 'info@noasoft.org'),
('meta_title', 'NoaSoft Çiçek | Online Sipariş'),
('meta_description', 'Taze çiçekler, hızlı teslimat ve güvenli ödeme seçenekleriyle yanınızdayız.'),
('theme_color', '#E85D75'),
('paytr_active', '0'),
('whatsapp_number', '+905550000000'),
('lightbox_provider', 'glightbox'),
('homepage_layout', 'grid'),
('homepage_latest_limit', '8'),
('homepage_ordered_limit', '8'),
('homepage_visited_limit', '8'),
('homepage_favorited_limit', '8'),
('reviews_per_page', '5'),
('bank_transfer_active', '0'),
('bank_name', ''),
('bank_iban', ''),
('bank_account_name', ''),
('paytr_merchant_id', ''),
('paytr_merchant_key', ''),
('paytr_merchant_salt', ''),
('paytr_success_url', ''),
('paytr_fail_url', '');

INSERT INTO users (name, email, phone, password_hash, role, created_at) VALUES
('Admin', 'admin@noasoft.org', '+90 555 000 0001', '$2a$12$ANgmvXJurVX6nmbFsIG9yendGFdClptNcytcNJ9vAjJ9/7i6J8Opu', 'admin', datetime('now'));
