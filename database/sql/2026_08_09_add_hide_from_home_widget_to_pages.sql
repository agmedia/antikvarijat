-- Antikvarijat Biblos - skrivanje blog objave iz automatskog widgeta na naslovnici
-- Datum: 2026-08-09
-- Pokretanje: odabrati produkcijsku bazu pa izvršiti cijelu skriptu u phpMyAdminu/CLI-u.
-- Sigurnost: postojeće objave ostaju vidljive (vrijednost 0), a skripta se može pokrenuti više puta.

SET @schema_name = DATABASE();

SET @sql = IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @schema_name
          AND table_name = 'pages'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @schema_name
          AND table_name = 'pages'
          AND column_name = 'hide_from_home_widget'
    ),
    'ALTER TABLE `pages` ADD COLUMN `hide_from_home_widget` TINYINT(1) NOT NULL DEFAULT 0 AFTER `featured`',
    'SELECT ''hide_from_home_widget already present or pages table missing'' AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Blog home-widget visibility deployment finished.' AS result;

-- Ručni rollback, samo ako je potreban:
-- ALTER TABLE `pages` DROP COLUMN `hide_from_home_widget`;
