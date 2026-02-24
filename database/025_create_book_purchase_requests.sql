CREATE TABLE `book_purchase_requests` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `submission_id` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(255) NOT NULL,
  `postal_code` VARCHAR(20) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(50) NOT NULL,
  `photos` JSON NOT NULL,
  `storage_path` VARCHAR(255) NULL,
  `submitted_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `book_purchase_requests_submission_id_unique` (`submission_id`),
  KEY `book_purchase_requests_email_index` (`email`),
  KEY `book_purchase_requests_full_name_index` (`full_name`),
  KEY `book_purchase_requests_submitted_at_index` (`submitted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
