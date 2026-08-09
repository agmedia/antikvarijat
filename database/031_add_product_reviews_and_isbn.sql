-- Antikvarijat Biblos - ISBN i recenzije artikala
-- Datum: 2026-08-09
-- Laravel ekvivalent:
-- database/migrations/2026_08_09_130000_add_product_reviews_and_isbn.php
--
-- LIVE: ovu skriptu izvršiti jednom nakon deploya koda, prije uključivanja schedulera.
-- Skripta je idempotentna: može se sigurno ponovno pokrenuti i ne briše podatke.

SET @schema_name = DATABASE();

SET @sql = IF(
    EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = @schema_name AND table_name = 'products'
    ) AND NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @schema_name AND table_name = 'products' AND column_name = 'isbn'
    ),
    'ALTER TABLE `products` ADD COLUMN `isbn` VARCHAR(20) NULL AFTER `ean`',
    'SELECT ''products.isbn already present or products table missing'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = @schema_name AND table_name = 'products'
    ) AND NOT EXISTS (
        SELECT 1 FROM information_schema.statistics
        WHERE table_schema = @schema_name AND table_name = 'products' AND index_name = 'products_isbn_index'
    ),
    'ALTER TABLE `products` ADD INDEX `products_isbn_index` (`isbn`)',
    'SELECT ''products_isbn_index already present or products table missing'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS `product_review_invitations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` BIGINT UNSIGNED NOT NULL,
    `token_hash` CHAR(64) NOT NULL,
    `recipient_email` VARCHAR(191) NOT NULL,
    `recipient_name` VARCHAR(191) NOT NULL,
    `locale` VARCHAR(5) NOT NULL DEFAULT 'hr',
    `eligible_at` TIMESTAMP NOT NULL,
    `sent_at` TIMESTAMP NULL DEFAULT NULL,
    `completed_at` TIMESTAMP NULL DEFAULT NULL,
    `attempts` INT UNSIGNED NOT NULL DEFAULT 0,
    `last_attempt_at` TIMESTAMP NULL DEFAULT NULL,
    `last_error` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `product_review_invitations_order_id_unique` (`order_id`),
    UNIQUE KEY `product_review_invitations_token_hash_unique` (`token_hash`),
    KEY `product_review_invitations_eligible_at_index` (`eligible_at`),
    KEY `product_review_invitations_sent_at_index` (`sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_reviews` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` BIGINT UNSIGNED NOT NULL,
    `order_id` BIGINT UNSIGNED NULL,
    `order_product_id` BIGINT UNSIGNED NULL,
    `invitation_id` BIGINT UNSIGNED NULL,
    `user_id` BIGINT UNSIGNED NULL,
    `reviewer_name` VARCHAR(191) NOT NULL,
    `reviewer_email` VARCHAR(191) NULL,
    `rating` TINYINT UNSIGNED NOT NULL,
    `title` VARCHAR(191) NULL,
    `body` TEXT NOT NULL,
    `locale` VARCHAR(5) NOT NULL DEFAULT 'hr',
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `is_verified_purchase` TINYINT(1) NOT NULL DEFAULT 0,
    `approved_at` TIMESTAMP NULL DEFAULT NULL,
    `approved_by` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `product_reviews_order_product_id_unique` (`order_product_id`),
    UNIQUE KEY `product_reviews_order_product_unique` (`order_id`, `product_id`),
    KEY `product_reviews_product_id_index` (`product_id`),
    KEY `product_reviews_order_id_index` (`order_id`),
    KEY `product_reviews_invitation_id_index` (`invitation_id`),
    KEY `product_reviews_user_id_index` (`user_id`),
    KEY `product_reviews_status_index` (`status`),
    KEY `product_reviews_approved_by_index` (`approved_by`),
    KEY `product_reviews_visible_index` (`product_id`, `status`, `approved_at`),
    KEY `product_reviews_moderation_index` (`status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'Biblos ISBN/reviews database deployment finished.' AS result;
