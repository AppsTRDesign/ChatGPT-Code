CREATE TABLE `files` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `filename` VARCHAR(255) NOT NULL,
  `stored_name` VARCHAR(255) NOT NULL,
  `size` BIGINT UNSIGNED NOT NULL,
  `type` VARCHAR(120) NOT NULL,
  `uploader_ip` VARCHAR(45) NOT NULL,
  `uploaded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `stored_name_idx` (`stored_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
