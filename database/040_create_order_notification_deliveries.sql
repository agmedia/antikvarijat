-- Reliable admin/customer order e-mail outbox.
-- Run once on production before deploying the code that schedules retries.

CREATE TABLE IF NOT EXISTS `order_notification_deliveries` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` BIGINT UNSIGNED NOT NULL,
    `kind` VARCHAR(20) NOT NULL,
    `recipient_email` VARCHAR(191) NOT NULL,
    `locale` VARCHAR(5) NOT NULL DEFAULT 'hr',
    `attempts` INT UNSIGNED NOT NULL DEFAULT 0,
    `available_at` TIMESTAMP NULL DEFAULT NULL,
    `claimed_at` TIMESTAMP NULL DEFAULT NULL,
    `last_attempt_at` TIMESTAMP NULL DEFAULT NULL,
    `sent_at` TIMESTAMP NULL DEFAULT NULL,
    `failed_at` TIMESTAMP NULL DEFAULT NULL,
    `last_error` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `order_notification_order_kind_unique` (`order_id`, `kind`),
    KEY `order_notification_order_index` (`order_id`),
    KEY `order_notification_pending_index` (`sent_at`, `failed_at`, `available_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SMTP2GO activity confirms that the admin mail for #26030 is missing.
INSERT IGNORE INTO `order_notification_deliveries`
    (`order_id`, `kind`, `recipient_email`, `locale`, `attempts`, `available_at`, `created_at`, `updated_at`)
SELECT
    `id`, 'admin', 'info@antikvarijat-biblos.hr',
    CASE WHEN `locale` IN ('hr', 'en') THEN `locale` ELSE 'hr' END,
    0, NOW(), NOW(), NOW()
FROM `orders`
WHERE `id` = 26030
  AND `checkout_processed_at` IS NOT NULL
  AND `order_status_id` IN (1, 3, 4);

-- Record the already accepted customer confirmation so it cannot be resent.
INSERT IGNORE INTO `order_notification_deliveries`
    (`order_id`, `kind`, `recipient_email`, `locale`, `attempts`, `available_at`, `last_attempt_at`, `sent_at`, `created_at`, `updated_at`)
SELECT
    `id`, 'customer', TRIM(`payment_email`),
    CASE WHEN `locale` IN ('hr', 'en') THEN `locale` ELSE 'hr' END,
    1, NOW(), '2026-08-27 23:47:59', '2026-08-27 23:47:59', NOW(), NOW()
FROM `orders`
WHERE `id` = 26030
  AND `checkout_processed_at` IS NOT NULL
  AND `order_status_id` IN (1, 3, 4)
  AND TRIM(`payment_email`) <> '';

-- SMTP2GO activity confirms that both mails for #26031 are missing.
INSERT IGNORE INTO `order_notification_deliveries`
    (`order_id`, `kind`, `recipient_email`, `locale`, `attempts`, `available_at`, `created_at`, `updated_at`)
SELECT
    `id`, 'admin', 'info@antikvarijat-biblos.hr',
    CASE WHEN `locale` IN ('hr', 'en') THEN `locale` ELSE 'hr' END,
    0, NOW(), NOW(), NOW()
FROM `orders`
WHERE `id` = 26031
  AND `checkout_processed_at` IS NOT NULL
  AND `order_status_id` IN (1, 3, 4);

INSERT IGNORE INTO `order_notification_deliveries`
    (`order_id`, `kind`, `recipient_email`, `locale`, `attempts`, `available_at`, `created_at`, `updated_at`)
SELECT
    `id`, 'customer', TRIM(`payment_email`),
    CASE WHEN `locale` IN ('hr', 'en') THEN `locale` ELSE 'hr' END,
    0, NOW(), NOW(), NOW()
FROM `orders`
WHERE `id` = 26031
  AND `checkout_processed_at` IS NOT NULL
  AND `order_status_id` IN (1, 3, 4)
  AND TRIM(`payment_email`) <> '';

SELECT 'Biblos reliable order notification deployment finished.' AS result;
