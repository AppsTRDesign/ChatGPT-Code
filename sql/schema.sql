CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS languages (
  code VARCHAR(8) PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 10
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

CREATE TABLE IF NOT EXISTS country_translations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  country_id INT NOT NULL,
  lang_code VARCHAR(8) NOT NULL,
  name VARCHAR(100) NOT NULL,
  UNIQUE KEY uniq_country_lang (country_id, lang_code),
  CONSTRAINT fk_country_translations_country FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS price_categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(190) NOT NULL,
  description TEXT
);

CREATE TABLE IF NOT EXISTS price_category_translations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NOT NULL,
  lang_code VARCHAR(8) NOT NULL,
  title VARCHAR(190) NOT NULL,
  description TEXT,
  UNIQUE KEY uniq_category_lang (category_id, lang_code),
  CONSTRAINT fk_category_translations_category FOREIGN KEY (category_id) REFERENCES price_categories(id) ON DELETE CASCADE
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

INSERT INTO languages (code, name, is_active, sort_order) VALUES
('tr', 'Türkçe', 1, 1),
('en', 'English', 1, 2),
('de', 'Deutsch', 1, 3),
('fr', 'Français', 1, 4)
ON DUPLICATE KEY UPDATE name=VALUES(name), is_active=VALUES(is_active), sort_order=VALUES(sort_order);

INSERT INTO admins (email, password_hash)
VALUES ('admin@cargoafrik.org', '$2y$10$JXxIa4HEQvdkfQ68EP8OGe9J3v3l0Y6lzmfCyR6wvM9jibMdn8k0m')
ON DUPLICATE KEY UPDATE email = VALUES(email);

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
('osm_embed_url', 'https://www.openstreetmap.org/export/embed.html?bbox=28.83%2C40.97%2C29.15%2C41.12&layer=mapnik')
ON DUPLICATE KEY UPDATE value=VALUES(value);

INSERT INTO translations (lang_code, group_name, key_name, text_value) VALUES
('en','front','top_message','Global Cargo Operations • 24/7 Monitoring Center'),
('en','front','track','Track Shipment'),
('en','front','pricing','Price Calculator'),
('en','front','contact','Contact'),
('en','front','hero_eyebrow','Production Grade Logistics Technology'),
('en','front','hero_title','Enterprise Logistics for Africa & Europe'),
('en','front','hero_desc','Fast, transparent and secure shipment management with live tracking and map-based status updates.'),
('en','front','hero_card_title','Why CargoAfrik?'),
('en','front','hero_card_item_1','Country-based SLA and operation hubs'),
('en','front','hero_card_item_2','Live timeline visibility with shipment events'),
('en','front','hero_card_item_3','Multi-language portal and centralized admin'),
('en','front','service_1_title','Air / Sea / Road Freight'),
('en','front','service_1_desc','Integrated transport channels for urgent and scheduled shipments.'),
('en','front','service_2_title','Corporate Contract Pricing'),
('en','front','service_2_desc','Country and category based dynamic pricing strategy from admin.'),
('en','front','service_3_title','Real-time Monitoring'),
('en','front','service_3_desc','Tracking timeline with sender/receiver details and operational notes.'),
('en','front','cta_title','Ready for enterprise-level shipping?'),
('en','front','cta_desc','Configure countries, categories and rates from admin; publish instantly to users.'),
('en','front','tracking_number','Tracking Number'),
('en','front','captcha','Verification'),
('en','front','country','Country'),
('en','front','category','Category'),
('en','front','calculate','Calculate'),
('en','front','address','Address'),
('en','front','phone','Phone'),
('en','front','email','E-mail'),
('en','front','track_desc','Enter your shipment code to view route details and event history.'),
('en','front','pricing_desc','Select destination country and category to view current fees.'),
('en','front','contact_desc','Reach our logistics desk via call, email or office map.'),
('en','front','pricing_not_found','No pricing found for selected options.'),
('en','front','pricing_found','Price calculated successfully.'),
('en','front','captcha_invalid','Verification code is invalid.'),
('en','front','tracking_not_found','Tracking number not found.'),
('en','front','tracking_found','Shipment found.'),
('tr','front','top_message','Global Kargo Operasyonları • 7/24 İzleme Merkezi'),
('tr','front','track','Kargo Takip'),('tr','front','pricing','Fiyat Hesaplama'),('tr','front','contact','İletişim'),('tr','front','hero_eyebrow','Prodüksiyon Seviyesi Lojistik Teknolojisi'),('tr','front','hero_title','Afrika ve Avrupa İçin Kurumsal Lojistik'),('tr','front','hero_desc','Canlı takip ve harita tabanlı durum güncellemeleri ile hızlı, şeffaf ve güvenli taşıma yönetimi.'),('tr','front','hero_card_title','Neden CargoAfrik?'),('tr','front','hero_card_item_1','Ülke bazlı SLA ve operasyon merkezleri'),('tr','front','hero_card_item_2','Kargo eventleri ile canlı zaman çizelgesi'),('tr','front','hero_card_item_3','Çok dilli portal ve merkezi admin yönetimi'),('tr','front','service_1_title','Hava / Deniz / Kara Taşımacılığı'),('tr','front','service_1_desc','Acil ve planlı gönderiler için entegre taşıma kanalları.'),('tr','front','service_2_title','Kurumsal Sözleşmeli Fiyatlama'),('tr','front','service_2_desc','Admin panelinden ülke ve kategori bazlı dinamik fiyat stratejisi.'),('tr','front','service_3_title','Gerçek Zamanlı İzleme'),('tr','front','service_3_desc','Gönderici/alıcı bilgileri ve operasyon notları ile detaylı takip.'),('tr','front','cta_title','Kurumsal taşımacılığa hazır mısınız?'),('tr','front','cta_desc','Ülke, kategori ve ücretleri adminden yönetin, anında kullanıcıya yayınlayın.'),('tr','front','tracking_number','Takip Numarası'),('tr','front','captcha','Doğrulama'),('tr','front','country','Ülke'),('tr','front','category','Kategori'),('tr','front','calculate','Hesapla'),('tr','front','address','Adres'),('tr','front','phone','Telefon'),('tr','front','email','E-posta'),('tr','front','track_desc','Gönderi kodunu girerek rota detayını ve event geçmişini görüntüleyin.'),('tr','front','pricing_desc','Hedef ülke ve kategori seçerek güncel ücreti görüntüleyin.'),('tr','front','contact_desc','Lojistik destek masamıza telefon, e-posta veya ofis haritası ile ulaşın.'),('tr','front','pricing_not_found','Seçilen kombinasyon için fiyat bulunamadı.'),('tr','front','pricing_found','Fiyat başarıyla hesaplandı.'),('tr','front','captcha_invalid','Doğrulama kodu hatalı.'),('tr','front','tracking_not_found','Takip numarası bulunamadı.'),('tr','front','tracking_found','Kargo bulundu.'),
('de','front','top_message','Globale Cargo-Operationen • 24/7 Überwachungszentrum'),('de','front','track','Sendung verfolgen'),('de','front','pricing','Preisrechner'),('de','front','contact','Kontakt'),('de','front','hero_eyebrow','Logistik auf Produktionsniveau'),('de','front','hero_title','Enterprise-Logistik für Afrika & Europa'),('de','front','hero_desc','Schnelles, transparentes und sicheres Versandmanagement mit Live-Tracking.'),('de','front','hero_card_title','Warum CargoAfrik?'),('de','front','hero_card_item_1','Länderbasierte SLA und Operations-Hubs'),('de','front','hero_card_item_2','Live-Timeline mit Sendungsereignissen'),('de','front','hero_card_item_3','Mehrsprachiges Portal und zentrales Admin-Panel'),('de','front','service_1_title','Luft / See / Straße'),('de','front','service_1_desc','Integrierte Transportkanäle für eilige und geplante Sendungen.'),('de','front','service_2_title','Vertragsbasierte Preisgestaltung'),('de','front','service_2_desc','Dynamische Preise nach Land und Kategorie aus dem Admin.'),('de','front','service_3_title','Echtzeit-Überwachung'),('de','front','service_3_desc','Sendungsverlauf mit Absender/Empfänger und Notizen.'),('de','front','cta_title','Bereit für Enterprise-Versand?'),('de','front','cta_desc','Länder, Kategorien und Preise im Admin konfigurieren.'),('de','front','tracking_number','Sendungsnummer'),('de','front','captcha','Verifizierung'),('de','front','country','Land'),('de','front','category','Kategorie'),('de','front','calculate','Berechnen'),('de','front','address','Adresse'),('de','front','phone','Telefon'),('de','front','email','E-Mail'),('de','front','track_desc','Geben Sie den Versandcode ein, um Details und Verlauf zu sehen.'),('de','front','pricing_desc','Land und Kategorie wählen, um den Preis anzuzeigen.'),('de','front','contact_desc','Kontakt über Telefon, E-Mail oder Kartenansicht.'),('de','front','pricing_not_found','Kein Preis für die Auswahl gefunden.'),('de','front','pricing_found','Preis erfolgreich berechnet.'),('de','front','captcha_invalid','Verifizierungscode ist ungültig.'),('de','front','tracking_not_found','Sendungsnummer nicht gefunden.'),('de','front','tracking_found','Sendung gefunden.'),
('fr','front','top_message','Opérations cargo mondiales • Centre de supervision 24/7'),('fr','front','track','Suivi de colis'),('fr','front','pricing','Calculateur de prix'),('fr','front','contact','Contact'),('fr','front','hero_eyebrow','Technologie logistique niveau production'),('fr','front','hero_title','Logistique d\'entreprise pour l\'Afrique et l\'Europe'),('fr','front','hero_desc','Gestion rapide, transparente et sécurisée avec suivi en direct.'),('fr','front','hero_card_title','Pourquoi CargoAfrik ?'),('fr','front','hero_card_item_1','SLA par pays et hubs opérationnels'),('fr','front','hero_card_item_2','Chronologie en direct avec événements de colis'),('fr','front','hero_card_item_3','Portail multilingue et administration centralisée'),('fr','front','service_1_title','Air / Mer / Route'),('fr','front','service_1_desc','Canaux de transport intégrés pour expéditions urgentes et planifiées.'),('fr','front','service_2_title','Tarification contractuelle entreprise'),('fr','front','service_2_desc','Stratégie dynamique par pays et catégorie depuis l\'admin.'),('fr','front','service_3_title','Suivi en temps réel'),('fr','front','service_3_desc','Historique de suivi avec détails expéditeur/destinataire.'),('fr','front','cta_title','Prêt pour une expédition niveau entreprise ?'),('fr','front','cta_desc','Configurez pays, catégories et tarifs depuis l\'admin.'),('fr','front','tracking_number','Numéro de suivi'),('fr','front','captcha','Vérification'),('fr','front','country','Pays'),('fr','front','category','Catégorie'),('fr','front','calculate','Calculer'),('fr','front','address','Adresse'),('fr','front','phone','Téléphone'),('fr','front','email','E-mail'),('fr','front','track_desc','Saisissez le code d\'expédition pour voir le parcours et l\'historique.'),('fr','front','pricing_desc','Sélectionnez pays et catégorie pour afficher le tarif.'),('fr','front','contact_desc','Contactez notre équipe via téléphone, e-mail ou carte.'),('fr','front','pricing_not_found','Aucun tarif trouvé pour cette sélection.'),('fr','front','pricing_found','Tarif calculé avec succès.'),('fr','front','captcha_invalid','Le code de vérification est invalide.'),('fr','front','tracking_not_found','Numéro de suivi introuvable.'),('fr','front','tracking_found','Colis trouvé.')
ON DUPLICATE KEY UPDATE text_value=VALUES(text_value);
