ALTER TABLE `orders`
    ADD COLUMN `checkout_processed_at` TIMESTAMP NULL DEFAULT NULL AFTER `printed`;
