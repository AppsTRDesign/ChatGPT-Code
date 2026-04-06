SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS parliament_laws (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  country_id INT UNSIGNED NOT NULL,
  proposer_user_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(180) NOT NULL,
  body TEXT NOT NULL,
  status ENUM('open','accepted','rejected') NOT NULL DEFAULT 'open',
  yes_votes INT UNSIGNED NOT NULL DEFAULT 0,
  no_votes INT UNSIGNED NOT NULL DEFAULT 0,
  ends_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_parliament_country_status (country_id, status),
  KEY idx_parliament_end (ends_at),
  CONSTRAINT fk_parliament_country FOREIGN KEY (country_id) REFERENCES countries(id),
  CONSTRAINT fk_parliament_proposer FOREIGN KEY (proposer_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS parliament_law_votes (
  law_id BIGINT UNSIGNED NOT NULL,
  voter_user_id BIGINT UNSIGNED NOT NULL,
  vote ENUM('yes','no') NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (law_id, voter_user_id),
  KEY idx_law_votes_voter (voter_user_id),
  CONSTRAINT fk_law_votes_law FOREIGN KEY (law_id) REFERENCES parliament_laws(id) ON DELETE CASCADE,
  CONSTRAINT fk_law_votes_voter FOREIGN KEY (voter_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO settings (`key`, `value`) VALUES
('election_default_duration_hours', '24'),
('election_vote_energy_cost', '120'),
('party_create_gold_cost', '75'),
('law_default_duration_hours', '24'),
('law_vote_energy_cost', '60');
