-- Antikvarijat Biblos - carousel knjiga povezan s blog člankom
-- Datum: 2026-08-17
-- Pokretanje: odabrati produkcijsku bazu pa izvršiti cijelu skriptu u phpMyAdminu/CLI-u.
-- Sigurnost: postojeći članci dobivaju tip "none" i na njima se ništa novo ne prikazuje.
-- Skripta se može pokrenuti više puta.

SET @schema_name = DATABASE();

SET @sql = IF(
    EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = @schema_name AND table_name = 'pages'
    )
    AND NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @schema_name
          AND table_name = 'pages'
          AND column_name = 'recommendation_type'
    ),
    'ALTER TABLE `pages` ADD COLUMN `recommendation_type` VARCHAR(20) NOT NULL DEFAULT ''none''',
    'SELECT ''recommendation_type already present or pages table missing'' AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = @schema_name AND table_name = 'pages'
    )
    AND NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @schema_name
          AND table_name = 'pages'
          AND column_name = 'recommendation_author_id'
    ),
    'ALTER TABLE `pages` ADD COLUMN `recommendation_author_id` BIGINT UNSIGNED NULL',
    'SELECT ''recommendation_author_id already present or pages table missing'' AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = @schema_name AND table_name = 'pages'
    )
    AND NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @schema_name
          AND table_name = 'pages'
          AND column_name = 'recommendation_product_ids'
    ),
    'ALTER TABLE `pages` ADD COLUMN `recommendation_product_ids` TEXT NULL',
    'SELECT ''recommendation_product_ids already present or pages table missing'' AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Blog recommendation deployment finished.' AS result;

-- Ručni rollback, samo ako je potreban:
-- ALTER TABLE `pages`
--     DROP COLUMN `recommendation_product_ids`,
--     DROP COLUMN `recommendation_author_id`,
--     DROP COLUMN `recommendation_type`;
