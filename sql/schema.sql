CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS settings (
  key_name VARCHAR(120) PRIMARY KEY,
  value TEXT NULL
);

CREATE TABLE IF NOT EXISTS translations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lang_code VARCHAR(8) NOT NULL,
  group_name VARCHAR(100) NOT NULL,
  key_name VARCHAR(100) NOT NULL,
  text_value TEXT NOT NULL,
  UNIQUE KEY uniq_translation (lang_code, group_name, key_name)
);

CREATE TABLE IF NOT EXISTS pages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(190) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS page_translations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  page_id INT NOT NULL,
  lang_code VARCHAR(8) NOT NULL,
  title VARCHAR(190) NOT NULL,
  content_html MEDIUMTEXT NOT NULL,
  UNIQUE KEY uniq_page_lang (page_id, lang_code),
  CONSTRAINT fk_page_translations_page FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS menus (
  id INT AUTO_INCREMENT PRIMARY KEY,
  page_id INT NOT NULL,
  sort_order INT NOT NULL DEFAULT 1,
  CONSTRAINT fk_menu_page FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS shipments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tracking_number VARCHAR(60) UNIQUE NOT NULL,
  origin_country VARCHAR(100),
  origin_city VARCHAR(100),
  destination_country VARCHAR(100),
  destination_city VARCHAR(100),
  current_status VARCHAR(120),
  description TEXT,
  sender_name VARCHAR(190),
  sender_company VARCHAR(190),
  sender_phone VARCHAR(60),
  receiver_name VARCHAR(190),
  receiver_phone VARCHAR(60),
  receiver_address TEXT,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS shipment_events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  shipment_id INT NOT NULL,
  status_code VARCHAR(100) NOT NULL,
  status_note TEXT,
  country VARCHAR(100),
  city VARCHAR(100),
  latitude DECIMAL(10,7) NULL,
  longitude DECIMAL(10,7) NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_event_shipment FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS countries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS price_categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(190) NOT NULL,
  description TEXT
);

CREATE TABLE IF NOT EXISTS price_configs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  country_id INT NOT NULL,
  category_id INT NOT NULL,
  price_amount DECIMAL(10,2) NOT NULL,
  UNIQUE KEY uniq_country_category(country_id, category_id),
  CONSTRAINT fk_price_country FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE CASCADE,
  CONSTRAINT fk_price_category FOREIGN KEY (category_id) REFERENCES price_categories(id) ON DELETE CASCADE
);

INSERT INTO admins (email, password_hash)
VALUES ('admin@cargoafrik.org', '$2y$10$JXxIa4HEQvdkfQ68EP8OGe9J3v3l0Y6lzmfCyR6wvM9jibMdn8k0m');

INSERT INTO settings (key_name, value) VALUES
('site_name', 'CargoAfrik'),
('company_name', 'CargoAfrik Logistics'),
('company_email', 'info@cargoafrik.org'),
('company_phone', '+90 850 000 00 00'),
('company_address', 'Istanbul / Turkiye'),
('meta_title', 'CargoAfrik - Global Corporate Cargo'),
('meta_description', 'Track shipments, estimate prices, and manage logistics globally.'),
('logo_path', 'https://dummyimage.com/180x45/ffcc00/111&text=CargoAfrik'),
('favicon_path', 'https://dummyimage.com/32x32/ffcc00/111&text=C'),
('osm_embed_url', 'https://www.openstreetmap.org/export/embed.html?bbox=28.83%2C40.97%2C29.15%2C41.12&layer=mapnik');

INSERT INTO translations (lang_code, group_name, key_name, text_value) VALUES
('en','front','track','Track Shipment'),
('en','front','pricing','Price Calculator'),
('en','front','contact','Contact'),
('en','front','hero_title','Enterprise Logistics for Africa & Europe'),
('en','front','hero_desc','Fast, transparent and secure shipment management with live tracking and map-based status updates.'),
('en','front','service_1_title','Air / Sea / Road Freight'),
('en','front','service_1_desc','Integrated transport channels for urgent and scheduled shipments.'),
('en','front','service_2_title','Corporate Contract Pricing'),
('en','front','service_2_desc','Country and category based dynamic pricing strategy from admin.'),
('en','front','service_3_title','Real-time Monitoring'),
('en','front','service_3_desc','Tracking timeline with sender/receiver details and operational notes.'),
('en','front','tracking_number','Tracking Number'),
('en','front','captcha','Verification'),
('en','front','country','Country'),
('en','front','category','Category'),
('en','front','calculate','Calculate'),
('en','front','address','Address'),
('en','front','phone','Phone'),
('en','front','email','E-mail'),
('tr','front','track','Kargo Takip'),
('tr','front','pricing','Fiyat Hesaplama'),
('tr','front','contact','İletişim'),
('tr','front','hero_title','Afrika ve Avrupa için Kurumsal Lojistik'),
('tr','front','hero_desc','Canlı takip, harita tabanlı durum ve güvenli taşıma süreçleri tek panelde.'),
('de','front','track','Sendung verfolgen'),
('fr','front','track','Suivi de colis')
ON DUPLICATE KEY UPDATE text_value=VALUES(text_value);
