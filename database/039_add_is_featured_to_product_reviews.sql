-- Antikvarijat Biblos - istaknute recenzije za naslovnicu
-- Datum: 2026-08-11
-- Laravel ekvivalent:
-- database/migrations/2026_08_11_092000_add_is_featured_to_product_reviews_table.php
--
-- LIVE: izvršiti jednom nakon deploya koda. Skripta je idempotentna i ne briše podatke.

SET @schema_name = DATABASE();

SET @sql = IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @schema_name
          AND table_name = 'product_reviews'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @schema_name
          AND table_name = 'product_reviews'
          AND column_name = 'is_featured'
    ),
    'ALTER TABLE `product_reviews` ADD COLUMN `is_featured` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_verified_purchase`',
    'SELECT ''is_featured already present or product_reviews table missing'' AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @schema_name
          AND table_name = 'product_reviews'
          AND column_name = 'is_featured'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.statistics
        WHERE table_schema = @schema_name
          AND table_name = 'product_reviews'
          AND index_name = 'product_reviews_featured_index'
    ),
    'CREATE INDEX `product_reviews_featured_index` ON `product_reviews` (`is_featured`, `status`, `approved_at`)',
    'SELECT ''product_reviews_featured_index already present or column missing'' AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Biblos featured product reviews deployment finished.' AS result;
