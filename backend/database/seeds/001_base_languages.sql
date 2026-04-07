-- Phase 2 base language seed
INSERT INTO languages (code, name, native_name, is_default, is_enabled)
VALUES
  ('en', 'English', 'English', 1, 1),
  ('tr', 'Turkish', 'Türkçe', 0, 1)
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  native_name = VALUES(native_name),
  is_enabled = VALUES(is_enabled);

INSERT INTO translation_keys (`key`, description, domain)
VALUES
  ('common.save', 'Generic save action', 'common'),
  ('common.cancel', 'Generic cancel action', 'common'),
  ('common.language', 'Language selector label', 'common'),
  ('map.fly_here', 'Map action for travel', 'map'),
  ('city.population', 'City population metric label', 'city'),
  ('travel.in_progress', 'Travel status', 'travel'),
  ('auth.welcome', 'Welcome message for user', 'auth')
ON DUPLICATE KEY UPDATE
  description = VALUES(description),
  domain = VALUES(domain);
