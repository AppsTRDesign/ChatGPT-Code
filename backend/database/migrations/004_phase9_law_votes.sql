-- Phase 9 politics extension

CREATE TABLE IF NOT EXISTS law_votes (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  law_id BIGINT UNSIGNED NOT NULL,
  voter_user_id BIGINT UNSIGNED NOT NULL,
  vote_value VARCHAR(16) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_law_votes_one_vote (law_id, voter_user_id),
  KEY idx_law_votes_law (law_id),
  KEY idx_law_votes_user (voter_user_id),
  CONSTRAINT fk_law_votes_law FOREIGN KEY (law_id) REFERENCES laws(id),
  CONSTRAINT fk_law_votes_user FOREIGN KEY (voter_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
