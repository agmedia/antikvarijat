-- Antikvarijat Biblos - mjerenje klikova i konverzija wishlist mailova
-- Datum: 2026-08-09
-- LIVE: izvršiti nakon 032_add_wishlist_sent_at.sql.
-- Skripta je idempotentna i ne mijenja postojeće sent/sent_at vrijednosti.
-- Povijesni mailovi bez sent_at namjerno se ne pripisuju naknadnim kupnjama.

SET @schema_name = DATABASE();

SET @sql = IF(
    EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = @schema_name AND table_name = 'wishlist'
    ) AND NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @schema_name AND table_name = 'wishlist' AND column_name = 'clicked_at'
    ),
    'ALTER TABLE `wishlist` ADD COLUMN `clicked_at` TIMESTAMP NULL DEFAULT NULL AFTER `sent_at`',
    'SELECT ''wishlist.clicked_at already present or wishlist table missing'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = @schema_name AND table_name = 'wishlist'
    ) AND NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @schema_name AND table_name = 'wishlist' AND column_name = 'click_count'
    ),
    'ALTER TABLE `wishlist` ADD COLUMN `click_count` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `clicked_at`',
    'SELECT ''wishlist.click_count already present or wishlist table missing'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @schema_name AND table_name = 'wishlist' AND column_name = 'clicked_at'
    ) AND NOT EXISTS (
        SELECT 1 FROM information_schema.statistics
        WHERE table_schema = @schema_name AND table_name = 'wishlist' AND index_name = 'wishlist_clicked_at_index'
    ),
    'ALTER TABLE `wishlist` ADD INDEX `wishlist_clicked_at_index` (`clicked_at`)',
    'SELECT ''wishlist_clicked_at_index already present or wishlist table missing'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT 'Biblos wishlist tracking deployment finished.' AS result;
