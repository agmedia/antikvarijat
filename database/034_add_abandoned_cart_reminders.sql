-- Antikvarijat Biblos - podsjetnici za nedovršene košarice
-- Datum: 2026-08-09
-- LIVE: izvršiti nakon deploya koda.
-- Skripta je idempotentna i ne briše podatke.
-- Retroaktivno priprema ISKLJUČIVO nedovršene narudžbe od 09.08.2026. nadalje.

SET @schema_name = DATABASE();

SET @sql = IF(
    EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = @schema_name AND table_name = 'orders'
    ) AND NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @schema_name AND table_name = 'orders' AND column_name = 'locale'
    ),
    'ALTER TABLE `orders` ADD COLUMN `locale` VARCHAR(5) NULL DEFAULT NULL AFTER `payment_email`',
    'SELECT ''orders.locale already present or orders table missing'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = @schema_name AND table_name = 'orders'
    ) AND NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @schema_name AND table_name = 'orders' AND column_name = 'unfinished_at'
    ),
    'ALTER TABLE `orders` ADD COLUMN `unfinished_at` TIMESTAMP NULL DEFAULT NULL AFTER `checkout_processed_at`',
    'SELECT ''orders.unfinished_at already present or orders table missing'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS `abandoned_cart_reminders` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` BIGINT UNSIGNED NOT NULL,
    `sequence` TINYINT UNSIGNED NOT NULL,
    `scheduled_for` TIMESTAMP NOT NULL,
    `sent_at` TIMESTAMP NULL DEFAULT NULL,
    `source` VARCHAR(20) NOT NULL,
    `recipient_email` VARCHAR(191) NOT NULL,
    `locale` VARCHAR(5) NOT NULL DEFAULT 'hr',
    `sent_by` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `abandoned_cart_order_sequence_unique` (`order_id`, `sequence`),
    KEY `abandoned_cart_due_index` (`sent_at`, `scheduled_for`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Za današnje postojeće nedovršene narudžbe trenutak nastanka je početak statusa.
UPDATE `orders`
SET `unfinished_at` = `created_at`
WHERE `order_status_id` = 8
  AND `created_at` >= '2026-08-09 00:00:00'
  AND `unfinished_at` IS NULL;

-- Ako stari payment provider ima spremljen jezik, preuzmi ga samo za današnje zapise.
UPDATE `orders` AS o
INNER JOIN (
    SELECT `order_id`, MAX(LOWER(`lang`)) AS `lang`
    FROM `order_transactions`
    WHERE LOWER(`lang`) IN ('hr', 'en')
    GROUP BY `order_id`
) AS t ON t.`order_id` = o.`id`
SET o.`locale` = t.`lang`
WHERE o.`created_at` >= '2026-08-09 00:00:00'
  AND o.`locale` IS NULL;

SELECT 'Biblos abandoned cart reminder deployment finished.' AS result;
