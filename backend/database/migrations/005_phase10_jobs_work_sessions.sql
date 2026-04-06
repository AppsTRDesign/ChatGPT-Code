-- Phase 10 economy extension

CREATE TABLE IF NOT EXISTS jobs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  city_id BIGINT UNSIGNED NULL,
  country_id BIGINT UNSIGNED NULL,
  code VARCHAR(64) NOT NULL,
  title VARCHAR(128) NOT NULL,
  base_salary DECIMAL(12,2) NOT NULL,
  energy_cost INT NOT NULL DEFAULT 5,
  required_level INT NOT NULL DEFAULT 1,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_jobs_scope_code (city_id, country_id, code),
  KEY idx_jobs_city_active (city_id, is_active),
  KEY idx_jobs_country_active (country_id, is_active),
  CONSTRAINT fk_jobs_city FOREIGN KEY (city_id) REFERENCES cities(id),
  CONSTRAINT fk_jobs_country FOREIGN KEY (country_id) REFERENCES countries(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS work_sessions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  city_id BIGINT UNSIGNED NOT NULL,
  country_id BIGINT UNSIGNED NOT NULL,
  job_id BIGINT UNSIGNED NULL,
  gross_salary DECIMAL(12,2) NOT NULL,
  tax_amount DECIMAL(12,2) NOT NULL,
  net_salary DECIMAL(12,2) NOT NULL,
  performed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_work_sessions_user_date (user_id, performed_at),
  KEY idx_work_sessions_city_date (city_id, performed_at),
  CONSTRAINT fk_work_sessions_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_work_sessions_city FOREIGN KEY (city_id) REFERENCES cities(id),
  CONSTRAINT fk_work_sessions_country FOREIGN KEY (country_id) REFERENCES countries(id),
  CONSTRAINT fk_work_sessions_job FOREIGN KEY (job_id) REFERENCES jobs(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
