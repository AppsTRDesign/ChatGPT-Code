-- Phase 2 / MariaDB schema
-- Compatible with MariaDB (InnoDB, utf8mb4)

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS battle_logs;
DROP TABLE IF EXISTS battle_participations;
DROP TABLE IF EXISTS battles;
DROP TABLE IF EXISTS wars;
DROP TABLE IF EXISTS work_permits;
DROP TABLE IF EXISTS visas;
DROP TABLE IF EXISTS travels;
DROP TABLE IF EXISTS travel_routes;
DROP TABLE IF EXISTS country_policy_rules;
DROP TABLE IF EXISTS country_policies;
DROP TABLE IF EXISTS laws;
DROP TABLE IF EXISTS votes;
DROP TABLE IF EXISTS election_candidates;
DROP TABLE IF EXISTS elections;
DROP TABLE IF EXISTS governors;
DROP TABLE IF EXISTS presidents;
DROP TABLE IF EXISTS city_projects;
DROP TABLE IF EXISTS city_stats_daily;
DROP TABLE IF EXISTS country_stats_daily;
DROP TABLE IF EXISTS chats;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS inventory_items;
DROP TABLE IF EXISTS inventories;
DROP TABLE IF EXISTS items;
DROP TABLE IF EXISTS moderation_actions;
DROP TABLE IF EXISTS admin_balancing_settings;
DROP TABLE IF EXISTS user_profiles;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS cities;
DROP TABLE IF EXISTS regions;
DROP TABLE IF EXISTS countries;
DROP TABLE IF EXISTS translation_values;
DROP TABLE IF EXISTS translation_keys;
DROP TABLE IF EXISTS languages;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE languages (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  code VARCHAR(8) NOT NULL,
  name VARCHAR(64) NOT NULL,
  native_name VARCHAR(64) NOT NULL,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  is_enabled TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_languages_code (code),
  KEY idx_languages_code_enabled (code, is_enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE translation_keys (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `key` VARCHAR(191) NOT NULL,
  description VARCHAR(255) NULL,
  domain VARCHAR(64) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_translation_keys_key (`key`),
  KEY idx_translation_keys_domain (domain)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE translation_values (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  translation_key_id BIGINT UNSIGNED NOT NULL,
  language_id BIGINT UNSIGNED NOT NULL,
  value TEXT NOT NULL,
  is_approved TINYINT(1) NOT NULL DEFAULT 1,
  updated_by_user_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_translation_value_key_lang (translation_key_id, language_id),
  KEY idx_translation_values_language (language_id),
  CONSTRAINT fk_translation_values_key FOREIGN KEY (translation_key_id) REFERENCES translation_keys(id),
  CONSTRAINT fk_translation_values_language FOREIGN KEY (language_id) REFERENCES languages(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE countries (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(128) NOT NULL,
  code CHAR(2) NOT NULL,
  iso3 CHAR(3) NULL,
  flag_emoji VARCHAR(16) NULL,
  capital_city_id BIGINT UNSIGNED NULL,
  treasury DECIMAL(18,2) NOT NULL DEFAULT 0,
  total_population BIGINT NOT NULL DEFAULT 0,
  average_happiness DECIMAL(5,2) NOT NULL DEFAULT 50,
  military_power INT NOT NULL DEFAULT 0,
  economy_score DECIMAL(10,2) NOT NULL DEFAULT 0,
  diplomacy_status VARCHAR(32) NOT NULL DEFAULT 'neutral',
  government_type VARCHAR(64) NOT NULL DEFAULT 'republic',
  active_president_user_id BIGINT UNSIGNED NULL,
  base_tax_rate DECIMAL(5,2) NOT NULL DEFAULT 10,
  visa_policy_mode VARCHAR(32) NOT NULL DEFAULT 'mixed',
  work_permit_policy_mode VARCHAR(32) NOT NULL DEFAULT 'regulated',
  war_eligibility TINYINT(1) NOT NULL DEFAULT 1,
  alliances_json JSON NULL,
  is_playable TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  map_source VARCHAR(64) NOT NULL DEFAULT 'natural_earth',
  source_external_id VARCHAR(128) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_countries_code (code),
  KEY idx_countries_code (code),
  KEY idx_countries_playable_active (is_playable, is_active),
  KEY idx_countries_president (active_president_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE regions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  country_id BIGINT UNSIGNED NOT NULL,
  controlling_country_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(128) NOT NULL,
  code VARCHAR(32) NULL,
  strategic_value INT NOT NULL DEFAULT 10,
  population BIGINT NOT NULL DEFAULT 0,
  defense_level INT NOT NULL DEFAULT 1,
  infrastructure_level INT NOT NULL DEFAULT 1,
  battle_status VARCHAR(32) NOT NULL DEFAULT 'peace',
  resource_production_score DECIMAL(10,2) NOT NULL DEFAULT 0,
  city_count INT NOT NULL DEFAULT 0,
  geometry_geojson LONGTEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_regions_country_name (country_id, name),
  KEY idx_regions_country_id (country_id),
  KEY idx_regions_controlling_country (controlling_country_id),
  KEY idx_regions_battle_status (battle_status),
  CONSTRAINT fk_regions_country FOREIGN KEY (country_id) REFERENCES countries(id),
  CONSTRAINT fk_regions_controlling_country FOREIGN KEY (controlling_country_id) REFERENCES countries(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cities (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  country_id BIGINT UNSIGNED NOT NULL,
  region_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(128) NOT NULL,
  latitude DECIMAL(10,7) NOT NULL,
  longitude DECIMAL(10,7) NOT NULL,
  population BIGINT NOT NULL DEFAULT 0,
  economy_score DECIMAL(10,2) NOT NULL DEFAULT 0,
  infrastructure_score DECIMAL(10,2) NOT NULL DEFAULT 0,
  safety_score DECIMAL(10,2) NOT NULL DEFAULT 0,
  education_score DECIMAL(10,2) NOT NULL DEFAULT 0,
  healthcare_score DECIMAL(10,2) NOT NULL DEFAULT 0,
  transport_score DECIMAL(10,2) NOT NULL DEFAULT 0,
  local_treasury DECIMAL(18,2) NOT NULL DEFAULT 0,
  active_governor_user_id BIGINT UNSIGNED NULL,
  development_level INT NOT NULL DEFAULT 1,
  housing_level INT NOT NULL DEFAULT 1,
  employment_rate DECIMAL(5,2) NOT NULL DEFAULT 0,
  airport_level INT NOT NULL DEFAULT 1,
  industry_level INT NOT NULL DEFAULT 1,
  is_capital TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_cities_country_region_name (country_id, region_id, name),
  KEY idx_cities_country_id (country_id),
  KEY idx_cities_region_id (region_id),
  KEY idx_cities_governor (active_governor_user_id),
  KEY idx_cities_coords (latitude, longitude),
  CONSTRAINT fk_cities_country FOREIGN KEY (country_id) REFERENCES countries(id),
  CONSTRAINT fk_cities_region FOREIGN KEY (region_id) REFERENCES regions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE countries
  ADD CONSTRAINT fk_countries_capital_city FOREIGN KEY (capital_city_id) REFERENCES cities(id);

CREATE TABLE users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  email VARCHAR(191) NOT NULL,
  username VARCHAR(64) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'active',
  role VARCHAR(32) NOT NULL DEFAULT 'player',
  level INT NOT NULL DEFAULT 1,
  experience BIGINT NOT NULL DEFAULT 0,
  energy INT NOT NULL DEFAULT 100,
  current_country_id BIGINT UNSIGNED NULL,
  current_city_id BIGINT UNSIGNED NULL,
  home_country_id BIGINT UNSIGNED NULL,
  home_city_id BIGINT UNSIGNED NULL,
  preferred_language_code VARCHAR(8) NOT NULL DEFAULT 'en',
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_users_email (email),
  UNIQUE KEY uk_users_username (username),
  KEY idx_users_city_id (current_city_id),
  KEY idx_users_country_id (current_country_id),
  KEY idx_users_language_code (preferred_language_code),
  CONSTRAINT fk_users_current_country FOREIGN KEY (current_country_id) REFERENCES countries(id),
  CONSTRAINT fk_users_current_city FOREIGN KEY (current_city_id) REFERENCES cities(id),
  CONSTRAINT fk_users_home_country FOREIGN KEY (home_country_id) REFERENCES countries(id),
  CONSTRAINT fk_users_home_city FOREIGN KEY (home_city_id) REFERENCES cities(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE countries
  ADD CONSTRAINT fk_countries_active_president FOREIGN KEY (active_president_user_id) REFERENCES users(id);

ALTER TABLE cities
  ADD CONSTRAINT fk_cities_active_governor FOREIGN KEY (active_governor_user_id) REFERENCES users(id);

CREATE TABLE user_profiles (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  display_name VARCHAR(96) NULL,
  avatar_url VARCHAR(255) NULL,
  bio TEXT NULL,
  reputation INT NOT NULL DEFAULT 0,
  cash_balance DECIMAL(18,2) NOT NULL DEFAULT 0,
  detected_country VARCHAR(128) NULL,
  detected_city VARCHAR(128) NULL,
  assigned_country_id BIGINT UNSIGNED NULL,
  assigned_city_id BIGINT UNSIGNED NULL,
  assignment_reason VARCHAR(64) NULL,
  geoip_provider VARCHAR(64) NULL,
  geoip_confidence DECIMAL(5,2) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_user_profiles_user_id (user_id),
  KEY idx_user_profiles_assigned_country (assigned_country_id),
  KEY idx_user_profiles_assigned_city (assigned_city_id),
  CONSTRAINT fk_user_profiles_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_user_profiles_assigned_country FOREIGN KEY (assigned_country_id) REFERENCES countries(id),
  CONSTRAINT fk_user_profiles_assigned_city FOREIGN KEY (assigned_city_id) REFERENCES cities(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_balancing_settings (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  category VARCHAR(64) NOT NULL,
  settings_json JSON NOT NULL,
  updated_by_user_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_admin_balancing_category (category),
  KEY idx_admin_balancing_updated_by (updated_by_user_id),
  CONSTRAINT fk_admin_balancing_updated_by FOREIGN KEY (updated_by_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE moderation_actions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  target_user_id BIGINT UNSIGNED NULL,
  admin_user_id BIGINT UNSIGNED NOT NULL,
  action_type VARCHAR(64) NOT NULL,
  reason VARCHAR(255) NULL,
  payload_json JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_moderation_actions_target (target_user_id),
  KEY idx_moderation_actions_admin (admin_user_id),
  KEY idx_moderation_actions_type_created (action_type, created_at),
  CONSTRAINT fk_moderation_actions_target FOREIGN KEY (target_user_id) REFERENCES users(id),
  CONSTRAINT fk_moderation_actions_admin FOREIGN KEY (admin_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE presidents (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  country_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  election_id BIGINT UNSIGNED NULL,
  term_start DATETIME NOT NULL,
  term_end DATETIME NULL,
  approval_rating DECIMAL(5,2) NOT NULL DEFAULT 50,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_presidents_country_active (country_id, is_active),
  KEY idx_presidents_user (user_id),
  CONSTRAINT fk_presidents_country FOREIGN KEY (country_id) REFERENCES countries(id),
  CONSTRAINT fk_presidents_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE governors (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  city_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  election_id BIGINT UNSIGNED NULL,
  term_start DATETIME NOT NULL,
  term_end DATETIME NULL,
  reputation DECIMAL(5,2) NOT NULL DEFAULT 50,
  experience BIGINT NOT NULL DEFAULT 0,
  completed_projects INT NOT NULL DEFAULT 0,
  corruption_risk DECIMAL(5,2) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_governors_city_active (city_id, is_active),
  KEY idx_governors_user (user_id),
  CONSTRAINT fk_governors_city FOREIGN KEY (city_id) REFERENCES cities(id),
  CONSTRAINT fk_governors_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE city_projects (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  city_id BIGINT UNSIGNED NOT NULL,
  started_by_user_id BIGINT UNSIGNED NOT NULL,
  project_type VARCHAR(64) NOT NULL,
  title VARCHAR(128) NOT NULL,
  description TEXT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'planned',
  budget_allocated DECIMAL(18,2) NOT NULL DEFAULT 0,
  budget_spent DECIMAL(18,2) NOT NULL DEFAULT 0,
  progress_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
  started_at DATETIME NULL,
  expected_end_at DATETIME NULL,
  completed_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_city_projects_city_status (city_id, status),
  KEY idx_city_projects_starter (started_by_user_id),
  CONSTRAINT fk_city_projects_city FOREIGN KEY (city_id) REFERENCES cities(id),
  CONSTRAINT fk_city_projects_starter FOREIGN KEY (started_by_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE elections (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  election_scope VARCHAR(32) NOT NULL,
  country_id BIGINT UNSIGNED NULL,
  city_id BIGINT UNSIGNED NULL,
  office_type VARCHAR(32) NOT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'scheduled',
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NOT NULL,
  result_announced_at DATETIME NULL,
  created_by_user_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_elections_status (status),
  KEY idx_elections_country (country_id),
  KEY idx_elections_city (city_id),
  CONSTRAINT fk_elections_country FOREIGN KEY (country_id) REFERENCES countries(id),
  CONSTRAINT fk_elections_city FOREIGN KEY (city_id) REFERENCES cities(id),
  CONSTRAINT fk_elections_creator FOREIGN KEY (created_by_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE election_candidates (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  election_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  party_name VARCHAR(128) NULL,
  manifesto TEXT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_candidates_election_user (election_id, user_id),
  KEY idx_candidates_status (status),
  CONSTRAINT fk_candidates_election FOREIGN KEY (election_id) REFERENCES elections(id),
  CONSTRAINT fk_candidates_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE votes (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  election_id BIGINT UNSIGNED NOT NULL,
  voter_user_id BIGINT UNSIGNED NOT NULL,
  candidate_user_id BIGINT UNSIGNED NOT NULL,
  cast_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ip_hash VARCHAR(128) NULL,
  device_hash VARCHAR(128) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_votes_one_vote_per_user (election_id, voter_user_id),
  KEY idx_votes_election (election_id),
  KEY idx_votes_candidate (candidate_user_id),
  KEY idx_votes_voter (voter_user_id),
  CONSTRAINT fk_votes_election FOREIGN KEY (election_id) REFERENCES elections(id),
  CONSTRAINT fk_votes_voter FOREIGN KEY (voter_user_id) REFERENCES users(id),
  CONSTRAINT fk_votes_candidate FOREIGN KEY (candidate_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE laws (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  country_id BIGINT UNSIGNED NOT NULL,
  proposed_by_user_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(191) NOT NULL,
  law_type VARCHAR(64) NOT NULL,
  payload_json JSON NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'proposed',
  voting_start DATETIME NULL,
  voting_end DATETIME NULL,
  enacted_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_laws_country_status (country_id, status),
  CONSTRAINT fk_laws_country FOREIGN KEY (country_id) REFERENCES countries(id),
  CONSTRAINT fk_laws_proposer FOREIGN KEY (proposed_by_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE country_policies (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  country_id BIGINT UNSIGNED NOT NULL,
  tax_rate DECIMAL(5,2) NOT NULL DEFAULT 10,
  military_spending_ratio DECIMAL(5,2) NOT NULL DEFAULT 10,
  visa_mode VARCHAR(32) NOT NULL DEFAULT 'mixed',
  work_permit_mode VARCHAR(32) NOT NULL DEFAULT 'regulated',
  immigration_difficulty INT NOT NULL DEFAULT 50,
  permit_duration_days INT NOT NULL DEFAULT 30,
  immigration_fee DECIMAL(12,2) NOT NULL DEFAULT 0,
  updated_by_user_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_country_policies_country (country_id),
  CONSTRAINT fk_country_policies_country FOREIGN KEY (country_id) REFERENCES countries(id),
  CONSTRAINT fk_country_policies_updater FOREIGN KEY (updated_by_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE country_policy_rules (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  country_policy_id BIGINT UNSIGNED NOT NULL,
  target_country_id BIGINT UNSIGNED NOT NULL,
  visa_required TINYINT(1) NOT NULL DEFAULT 1,
  work_permit_required TINYINT(1) NOT NULL DEFAULT 1,
  is_allowed TINYINT(1) NOT NULL DEFAULT 1,
  rule_note VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_policy_target_country (country_policy_id, target_country_id),
  CONSTRAINT fk_policy_rules_policy FOREIGN KEY (country_policy_id) REFERENCES country_policies(id),
  CONSTRAINT fk_policy_rules_target_country FOREIGN KEY (target_country_id) REFERENCES countries(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE travel_routes (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  departure_city_id BIGINT UNSIGNED NOT NULL,
  arrival_city_id BIGINT UNSIGNED NOT NULL,
  distance_km DECIMAL(10,2) NOT NULL,
  route_type VARCHAR(32) NOT NULL,
  base_duration_seconds INT NOT NULL,
  base_ticket_cost DECIMAL(12,2) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_travel_routes_unique (departure_city_id, arrival_city_id, route_type),
  KEY idx_travel_routes_departure (departure_city_id),
  KEY idx_travel_routes_arrival (arrival_city_id),
  CONSTRAINT fk_travel_routes_departure FOREIGN KEY (departure_city_id) REFERENCES cities(id),
  CONSTRAINT fk_travel_routes_arrival FOREIGN KEY (arrival_city_id) REFERENCES cities(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE visas (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  from_country_id BIGINT UNSIGNED NOT NULL,
  to_country_id BIGINT UNSIGNED NOT NULL,
  visa_type VARCHAR(32) NOT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'pending',
  applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  decided_at DATETIME NULL,
  valid_from DATETIME NULL,
  valid_until DATETIME NULL,
  fee_paid DECIMAL(12,2) NOT NULL DEFAULT 0,
  rejection_reason VARCHAR(255) NULL,
  PRIMARY KEY (id),
  KEY idx_visas_user_status (user_id, status),
  KEY idx_visas_target_country (to_country_id),
  CONSTRAINT fk_visas_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_visas_from_country FOREIGN KEY (from_country_id) REFERENCES countries(id),
  CONSTRAINT fk_visas_to_country FOREIGN KEY (to_country_id) REFERENCES countries(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE work_permits (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  country_id BIGINT UNSIGNED NOT NULL,
  city_id BIGINT UNSIGNED NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'pending',
  applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  decided_at DATETIME NULL,
  valid_from DATETIME NULL,
  valid_until DATETIME NULL,
  fee_paid DECIMAL(12,2) NOT NULL DEFAULT 0,
  rejection_reason VARCHAR(255) NULL,
  PRIMARY KEY (id),
  KEY idx_work_permits_user_status (user_id, status),
  KEY idx_work_permits_country (country_id),
  KEY idx_work_permits_city (city_id),
  CONSTRAINT fk_work_permits_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_work_permits_country FOREIGN KEY (country_id) REFERENCES countries(id),
  CONSTRAINT fk_work_permits_city FOREIGN KEY (city_id) REFERENCES cities(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE travels (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  departure_city_id BIGINT UNSIGNED NOT NULL,
  arrival_city_id BIGINT UNSIGNED NOT NULL,
  distance_km DECIMAL(10,2) NOT NULL,
  duration_seconds INT NOT NULL,
  travel_status VARCHAR(32) NOT NULL DEFAULT 'scheduled',
  travel_type VARCHAR(32) NOT NULL,
  permit_status VARCHAR(32) NOT NULL DEFAULT 'not_required',
  ticket_cost DECIMAL(12,2) NOT NULL DEFAULT 0,
  start_time DATETIME NOT NULL,
  end_time DATETIME NOT NULL,
  completed_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_travels_user_status (user_id, travel_status),
  KEY idx_travels_status (travel_status),
  KEY idx_travels_departure (departure_city_id),
  KEY idx_travels_arrival (arrival_city_id),
  KEY idx_travels_end_time (end_time),
  CONSTRAINT fk_travels_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_travels_departure FOREIGN KEY (departure_city_id) REFERENCES cities(id),
  CONSTRAINT fk_travels_arrival FOREIGN KEY (arrival_city_id) REFERENCES cities(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE wars (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  attacker_country_id BIGINT UNSIGNED NOT NULL,
  defender_country_id BIGINT UNSIGNED NOT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'declared',
  war_score_attacker DECIMAL(8,2) NOT NULL DEFAULT 0,
  war_score_defender DECIMAL(8,2) NOT NULL DEFAULT 0,
  war_exhaustion_attacker DECIMAL(5,2) NOT NULL DEFAULT 0,
  war_exhaustion_defender DECIMAL(5,2) NOT NULL DEFAULT 0,
  declared_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ended_at DATETIME NULL,
  peace_terms_json JSON NULL,
  PRIMARY KEY (id),
  KEY idx_wars_status (status),
  KEY idx_wars_attacker (attacker_country_id),
  KEY idx_wars_defender (defender_country_id),
  CONSTRAINT fk_wars_attacker FOREIGN KEY (attacker_country_id) REFERENCES countries(id),
  CONSTRAINT fk_wars_defender FOREIGN KEY (defender_country_id) REFERENCES countries(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE battles (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  war_id BIGINT UNSIGNED NOT NULL,
  target_region_id BIGINT UNSIGNED NOT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'scheduled',
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NULL,
  attacker_power INT NOT NULL DEFAULT 0,
  defender_power INT NOT NULL DEFAULT 0,
  attacker_score DECIMAL(8,2) NOT NULL DEFAULT 0,
  defender_score DECIMAL(8,2) NOT NULL DEFAULT 0,
  winner_country_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_battles_war_status (war_id, status),
  KEY idx_battles_region (target_region_id),
  CONSTRAINT fk_battles_war FOREIGN KEY (war_id) REFERENCES wars(id),
  CONSTRAINT fk_battles_region FOREIGN KEY (target_region_id) REFERENCES regions(id),
  CONSTRAINT fk_battles_winner FOREIGN KEY (winner_country_id) REFERENCES countries(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE battle_participations (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  battle_id BIGINT UNSIGNED NOT NULL,
  war_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  side VARCHAR(16) NOT NULL,
  contribution_power INT NOT NULL,
  contribution_energy INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_battle_participations_battle (battle_id),
  KEY idx_battle_participations_war (war_id),
  KEY idx_battle_participations_user (user_id),
  KEY idx_battle_participations_side (side),
  CONSTRAINT fk_battle_participations_battle FOREIGN KEY (battle_id) REFERENCES battles(id),
  CONSTRAINT fk_battle_participations_war FOREIGN KEY (war_id) REFERENCES wars(id),
  CONSTRAINT fk_battle_participations_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE battle_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  battle_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NULL,
  action_type VARCHAR(64) NOT NULL,
  payload_json JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_battle_logs_battle (battle_id),
  KEY idx_battle_logs_user (user_id),
  CONSTRAINT fk_battle_logs_battle FOREIGN KEY (battle_id) REFERENCES battles(id),
  CONSTRAINT fk_battle_logs_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE items (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  code VARCHAR(64) NOT NULL,
  name VARCHAR(128) NOT NULL,
  item_type VARCHAR(32) NOT NULL,
  rarity VARCHAR(32) NOT NULL DEFAULT 'common',
  effects_json JSON NULL,
  base_price DECIMAL(12,2) NOT NULL DEFAULT 0,
  is_tradeable TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_items_code (code),
  KEY idx_items_type (item_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE inventories (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  max_slots INT NOT NULL DEFAULT 50,
  used_slots INT NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_inventories_user (user_id),
  CONSTRAINT fk_inventories_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE inventory_items (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  inventory_id BIGINT UNSIGNED NOT NULL,
  item_id BIGINT UNSIGNED NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  is_equipped TINYINT(1) NOT NULL DEFAULT 0,
  meta_json JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_inventory_item_unique (inventory_id, item_id),
  KEY idx_inventory_items_item (item_id),
  CONSTRAINT fk_inventory_items_inventory FOREIGN KEY (inventory_id) REFERENCES inventories(id),
  CONSTRAINT fk_inventory_items_item FOREIGN KEY (item_id) REFERENCES items(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE transactions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NULL,
  country_id BIGINT UNSIGNED NULL,
  city_id BIGINT UNSIGNED NULL,
  tx_type VARCHAR(64) NOT NULL,
  amount DECIMAL(18,2) NOT NULL,
  currency_code VARCHAR(8) NOT NULL DEFAULT 'GMC',
  balance_before DECIMAL(18,2) NULL,
  balance_after DECIMAL(18,2) NULL,
  reference_type VARCHAR(64) NULL,
  reference_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_transactions_user (user_id),
  KEY idx_transactions_country (country_id),
  KEY idx_transactions_city (city_id),
  KEY idx_transactions_type (tx_type),
  CONSTRAINT fk_transactions_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_transactions_country FOREIGN KEY (country_id) REFERENCES countries(id),
  CONSTRAINT fk_transactions_city FOREIGN KEY (city_id) REFERENCES cities(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  category VARCHAR(64) NOT NULL,
  title_key VARCHAR(191) NOT NULL,
  body_key VARCHAR(191) NULL,
  payload_json JSON NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  read_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_notifications_user_read (user_id, is_read),
  CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE chats (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  scope VARCHAR(32) NOT NULL,
  country_id BIGINT UNSIGNED NULL,
  city_id BIGINT UNSIGNED NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  message TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_chats_scope_country_city (scope, country_id, city_id),
  KEY idx_chats_user (user_id),
  CONSTRAINT fk_chats_country FOREIGN KEY (country_id) REFERENCES countries(id),
  CONSTRAINT fk_chats_city FOREIGN KEY (city_id) REFERENCES cities(id),
  CONSTRAINT fk_chats_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE country_stats_daily (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  country_id BIGINT UNSIGNED NOT NULL,
  snapshot_date DATE NOT NULL,
  population BIGINT NOT NULL DEFAULT 0,
  active_citizens BIGINT NOT NULL DEFAULT 0,
  average_wealth DECIMAL(12,2) NOT NULL DEFAULT 0,
  economy_score DECIMAL(10,2) NOT NULL DEFAULT 0,
  tax_revenue DECIMAL(18,2) NOT NULL DEFAULT 0,
  unemployment_rate DECIMAL(5,2) NOT NULL DEFAULT 0,
  military_strength INT NOT NULL DEFAULT 0,
  happiness DECIMAL(5,2) NOT NULL DEFAULT 50,
  war_success_rate DECIMAL(5,2) NOT NULL DEFAULT 0,
  city_count INT NOT NULL DEFAULT 0,
  region_count INT NOT NULL DEFAULT 0,
  immigration_inflow INT NOT NULL DEFAULT 0,
  emigration_outflow INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_country_stats_unique (country_id, snapshot_date),
  KEY idx_country_stats_date (snapshot_date),
  CONSTRAINT fk_country_stats_country FOREIGN KEY (country_id) REFERENCES countries(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE city_stats_daily (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  city_id BIGINT UNSIGNED NOT NULL,
  snapshot_date DATE NOT NULL,
  population BIGINT NOT NULL DEFAULT 0,
  active_citizens BIGINT NOT NULL DEFAULT 0,
  development_level INT NOT NULL DEFAULT 1,
  economy_score DECIMAL(10,2) NOT NULL DEFAULT 0,
  local_treasury DECIMAL(18,2) NOT NULL DEFAULT 0,
  airport_level INT NOT NULL DEFAULT 1,
  employment_rate DECIMAL(5,2) NOT NULL DEFAULT 0,
  safety_score DECIMAL(5,2) NOT NULL DEFAULT 0,
  healthcare_score DECIMAL(5,2) NOT NULL DEFAULT 0,
  education_score DECIMAL(5,2) NOT NULL DEFAULT 0,
  transport_quality DECIMAL(5,2) NOT NULL DEFAULT 0,
  migration_attractiveness DECIMAL(5,2) NOT NULL DEFAULT 0,
  current_projects INT NOT NULL DEFAULT 0,
  governor_rating DECIMAL(5,2) NOT NULL DEFAULT 50,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_city_stats_unique (city_id, snapshot_date),
  KEY idx_city_stats_date (snapshot_date),
  CONSTRAINT fk_city_stats_city FOREIGN KEY (city_id) REFERENCES cities(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
