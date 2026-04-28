CREATE TABLE `vialibri_books` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `translated_title` varchar(255) DEFAULT NULL,
  `translated_description` longtext,
  `edition` varchar(255) DEFAULT NULL,
  `keywords` text,
  `first_edition` tinyint(1) DEFAULT NULL,
  `signed` tinyint(1) DEFAULT NULL,
  `dust_jacket` tinyint(1) DEFAULT NULL,
  `translated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vialibri_books_product_id_unique` (`product_id`),
  CONSTRAINT `vialibri_books_product_id_foreign`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
