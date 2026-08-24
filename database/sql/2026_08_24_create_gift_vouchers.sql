-- Antikvarijat Biblos - poklon bonovi, saldo i sigurno iskorištavanje
-- Datum: 2026-08-24
-- Pokretanje: odabrati produkcijsku bazu pa izvršiti cijelu skriptu u phpMyAdminu/CLI-u.
-- Sigurnost: skripta samo dodaje dvije nove tablice; ne mijenja niti briše postojeće podatke.
-- Skripta se može pokrenuti više puta.

CREATE TABLE IF NOT EXISTS `gift_vouchers` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `purchase_order_id` BIGINT UNSIGNED NULL,
    `cart_item_key` VARCHAR(64) NULL,
    `code_hash` VARCHAR(64) NULL,
    `code_ciphertext` TEXT NULL,
    `code_suffix` VARCHAR(10) NULL,
    `initial_amount` DECIMAL(15,4) NOT NULL,
    `balance` DECIMAL(15,4) NOT NULL,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'EUR',
    `buyer_name` VARCHAR(255) NULL,
    `buyer_email` VARCHAR(255) NULL,
    `recipient_name` VARCHAR(255) NULL,
    `recipient_email` VARCHAR(255) NOT NULL,
    `sender_name` VARCHAR(255) NULL,
    `message` TEXT NULL,
    `locale` VARCHAR(5) NOT NULL DEFAULT 'hr',
    `status` VARCHAR(32) NOT NULL DEFAULT 'pending',
    `issued_at` TIMESTAMP NULL DEFAULT NULL,
    `email_sent_at` TIMESTAMP NULL DEFAULT NULL,
    `last_email_sent_at` TIMESTAMP NULL DEFAULT NULL,
    `email_error` TEXT NULL,
    `disabled_at` TIMESTAMP NULL DEFAULT NULL,
    `cancelled_at` TIMESTAMP NULL DEFAULT NULL,
    `expires_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `gift_vouchers_code_hash_unique` (`code_hash`),
    UNIQUE KEY `gift_voucher_order_item_unique` (`purchase_order_id`, `cart_item_key`),
    KEY `gift_vouchers_purchase_order_id_index` (`purchase_order_id`),
    KEY `gift_vouchers_code_suffix_index` (`code_suffix`),
    KEY `gift_vouchers_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `gift_voucher_redemptions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `gift_voucher_id` BIGINT UNSIGNED NOT NULL,
    `order_id` BIGINT UNSIGNED NOT NULL,
    `amount` DECIMAL(15,4) NOT NULL,
    `status` VARCHAR(24) NOT NULL DEFAULT 'reserved',
    `reserved_until` TIMESTAMP NULL DEFAULT NULL,
    `redeemed_at` TIMESTAMP NULL DEFAULT NULL,
    `released_at` TIMESTAMP NULL DEFAULT NULL,
    `release_reason` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `gift_voucher_order_redemption_unique` (`gift_voucher_id`, `order_id`),
    KEY `gift_voucher_redemptions_gift_voucher_id_index` (`gift_voucher_id`),
    KEY `gift_voucher_redemptions_order_id_index` (`order_id`),
    KEY `gift_voucher_redemptions_status_index` (`status`),
    KEY `gift_voucher_redemptions_reserved_until_index` (`reserved_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT
    (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'gift_vouchers') AS gift_vouchers_ready,
    (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'gift_voucher_redemptions') AS gift_voucher_redemptions_ready;

-- Ručni rollback, samo ako je izričito potreban i prije korištenja featurea:
-- DROP TABLE IF EXISTS `gift_voucher_redemptions`;
-- DROP TABLE IF EXISTS `gift_vouchers`;
