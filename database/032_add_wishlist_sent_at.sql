-- Antikvarijat Biblos - povijest slanja wishlist obavijesti
-- Datum: 2026-08-09
-- Laravel ekvivalent:
-- database/migrations/2026_08_09_140000_add_sent_at_to_wishlist.php
--
-- LIVE: ovu skriptu izvršiti nakon 031_add_product_reviews_and_isbn.sql,
-- prije uključivanja Laravel schedule:run crona.
-- Skripta je idempotentna i ne briše postojeće wishlist zapise.

SET @schema_name = DATABASE();

SET @sql = IF(
    EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = @schema_name AND table_name = 'wishlist'
    ) AND NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @schema_name AND table_name = 'wishlist' AND column_name = 'sent_at'
    ),
    'ALTER TABLE `wishlist` ADD COLUMN `sent_at` TIMESTAMP NULL DEFAULT NULL AFTER `sent`',
    'SELECT ''wishlist.sent_at already present or wishlist table missing'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = @schema_name AND table_name = 'wishlist'
    ) AND NOT EXISTS (
        SELECT 1 FROM information_schema.statistics
        WHERE table_schema = @schema_name AND table_name = 'wishlist' AND index_name = 'wishlist_sent_at_index'
    ),
    'ALTER TABLE `wishlist` ADD INDEX `wishlist_sent_at_index` (`sent_at`)',
    'SELECT ''wishlist_sent_at_index already present or wishlist table missing'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = @schema_name AND table_name = 'wishlist'
    ) AND NOT EXISTS (
        SELECT 1 FROM information_schema.statistics
        WHERE table_schema = @schema_name AND table_name = 'wishlist' AND index_name = 'wishlist_sent_status_index'
    ),
    'ALTER TABLE `wishlist` ADD INDEX `wishlist_sent_status_index` (`sent`, `status`)',
    'SELECT ''wishlist_sent_status_index already present or wishlist table missing'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT 'Biblos wishlist sent history deployment finished.' AS result;
