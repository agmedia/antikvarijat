-- Antikvarijat Biblos - kontrolirano povijesno slanje poziva za recenzije
-- Datum: 2026-08-09
-- Laravel ekvivalent:
-- database/migrations/2026_08_09_170000_create_product_review_backfills.php
--
-- LIVE: izvršiti jednom nakon deploya koda. Skripta je idempotentna i ne briše podatke.

CREATE TABLE IF NOT EXISTS `product_review_backfills` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `date_from` DATE NOT NULL,
    `date_to` DATE NOT NULL,
    `requested_limit` INT UNSIGNED NOT NULL,
    `interval_seconds` SMALLINT UNSIGNED NOT NULL DEFAULT 5,
    `eligible_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `total_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `processed_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `sent_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `skipped_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `failed_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `created_by` BIGINT UNSIGNED NULL,
    `started_at` TIMESTAMP NULL DEFAULT NULL,
    `finished_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `review_backfills_status_created_index` (`status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_review_backfill_items` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `backfill_id` BIGINT UNSIGNED NOT NULL,
    `order_id` BIGINT UNSIGNED NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `last_attempt_at` TIMESTAMP NULL DEFAULT NULL,
    `processed_at` TIMESTAMP NULL DEFAULT NULL,
    `last_error` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `review_backfill_items_batch_order_unique` (`backfill_id`, `order_id`),
    KEY `review_backfill_items_due_index` (`backfill_id`, `status`, `id`),
    KEY `review_backfill_items_order_index` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'Biblos product review backfill deployment finished.' AS result;
