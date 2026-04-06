SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE market_offers
  ADD COLUMN tax_rate_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER price_per_unit,
  ADD COLUMN seller_commission_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER tax_rate_percent,
  ADD COLUMN gross_total DECIMAL(14,2) NOT NULL DEFAULT 0.00 AFTER seller_commission_percent,
  ADD COLUMN tax_total DECIMAL(14,2) NOT NULL DEFAULT 0.00 AFTER gross_total,
  ADD COLUMN seller_commission_total DECIMAL(14,2) NOT NULL DEFAULT 0.00 AFTER tax_total,
  ADD COLUMN seller_net_total DECIMAL(14,2) NOT NULL DEFAULT 0.00 AFTER seller_commission_total;

CREATE TABLE IF NOT EXISTS market_transactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  offer_id BIGINT UNSIGNED NOT NULL,
  buyer_id BIGINT UNSIGNED NOT NULL,
  seller_id BIGINT UNSIGNED NOT NULL,
  resource_id INT UNSIGNED NOT NULL,
  quantity BIGINT UNSIGNED NOT NULL,
  price_per_unit DECIMAL(12,2) NOT NULL,
  gross_total DECIMAL(14,2) NOT NULL,
  buyer_tax_total DECIMAL(14,2) NOT NULL,
  seller_commission_total DECIMAL(14,2) NOT NULL,
  seller_net_total DECIMAL(14,2) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_market_tx_offer (offer_id),
  KEY idx_market_tx_buyer_time (buyer_id, created_at),
  KEY idx_market_tx_seller_time (seller_id, created_at),
  CONSTRAINT fk_market_tx_offer FOREIGN KEY (offer_id) REFERENCES market_offers(id) ON DELETE CASCADE,
  CONSTRAINT fk_market_tx_buyer FOREIGN KEY (buyer_id) REFERENCES users(id),
  CONSTRAINT fk_market_tx_seller FOREIGN KEY (seller_id) REFERENCES users(id),
  CONSTRAINT fk_market_tx_resource FOREIGN KEY (resource_id) REFERENCES resources(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (`key`, `value`, `description`) VALUES
('market_buyer_tax_percent', '4', 'Market alıcı vergi oranı (%)'),
('market_seller_commission_percent', '3', 'Market satıcı komisyon oranı (%)'),
('market_price_floor_ratio', '0.50', 'Market taban fiyat oranı'),
('market_price_ceiling_ratio', '2.50', 'Market tavan fiyat oranı'),
('market_max_open_offers_per_user', '15', 'Kullanıcı başına açık ilan limiti')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `description` = VALUES(`description`), updated_at = NOW();
