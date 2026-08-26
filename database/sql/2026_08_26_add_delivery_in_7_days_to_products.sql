-- Antikvarijat Biblos - obavijest o roku isporuke artikla od 7 dana
-- Datum: 2026-08-26
-- Pokretanje: odabrati produkcijsku bazu pa izvršiti cijelu skriptu u phpMyAdminu/CLI-u.
-- Sigurnost: označava samo osam navedenih artikala, postavlja dogovorene količine
-- i prije promjene jednom sprema njihove postojeće vrijednosti u backup tablicu.
-- Skripta se može pokrenuti više puta.
-- Očekivana završna provjera: 8 redaka; Strah od pletenja ima količinu 25, ostali 30.
-- Nakon izvršavanja preporučeno je u aplikaciji pokrenuti: php artisan cache:clear

SET @schema_name = DATABASE();

SET @sql = IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @schema_name
          AND table_name = 'products'
    )
    AND NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @schema_name
          AND table_name = 'products'
          AND column_name = 'delivery_in_7_days'
    ),
    'ALTER TABLE `products` ADD COLUMN `delivery_in_7_days` TINYINT(1) NOT NULL DEFAULT 0 AFTER `skl`',
    'SELECT ''delivery_in_7_days already present or products table missing'' AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @schema_name
          AND table_name = 'products'
          AND column_name = 'delivery_in_7_days'
    ),
    'CREATE TABLE IF NOT EXISTS `products_delivery_7d_backup_20260826` (
        `product_id` BIGINT UNSIGNED NOT NULL,
        `slug` VARCHAR(255) NOT NULL,
        `quantity` INT UNSIGNED NOT NULL,
        `delivery_in_7_days` TINYINT(1) NOT NULL,
        `backed_up_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`product_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
    'SELECT ''Backup table was not created because delivery_in_7_days is unavailable'' AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @schema_name
          AND table_name = 'products'
          AND column_name = 'delivery_in_7_days'
    ),
    'INSERT IGNORE INTO `products_delivery_7d_backup_20260826`
        (`product_id`, `slug`, `quantity`, `delivery_in_7_days`)
        SELECT `id`, `slug`, `quantity`, `delivery_in_7_days`
        FROM `products`
        WHERE `slug` IN (
            ''besplatna-dostava-vedrana-rudan'',
            ''crnci-u-firenci-vedrana-rudan'',
            ''dozivotna-robija-vedrana-rudan'',
            ''ljubav-na-posljednji-pogled-vedrana-rudan'',
            ''muskarac-u-grlu-vedrana-rudan'',
            ''ples-oko-sunca-vedrana-rudan'',
            ''strah-od-pletenja-vedrana-rudan'',
            ''zasto-psujem-vedrana-rudan''
        )',
    'SELECT ''Backup skipped because delivery_in_7_days is unavailable'' AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @schema_name
          AND table_name = 'products'
          AND column_name = 'delivery_in_7_days'
    ),
    'UPDATE `products`
        SET `delivery_in_7_days` = 1,
            `quantity` = CASE
                WHEN `slug` = ''strah-od-pletenja-vedrana-rudan'' THEN 25
                ELSE 30
            END
        WHERE `slug` IN (
            ''besplatna-dostava-vedrana-rudan'',
            ''crnci-u-firenci-vedrana-rudan'',
            ''dozivotna-robija-vedrana-rudan'',
            ''ljubav-na-posljednji-pogled-vedrana-rudan'',
            ''muskarac-u-grlu-vedrana-rudan'',
            ''ples-oko-sunca-vedrana-rudan'',
            ''strah-od-pletenja-vedrana-rudan'',
            ''zasto-psujem-vedrana-rudan''
        )',
    'SELECT ''delivery_in_7_days column missing; products were not updated'' AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @schema_name
          AND table_name = 'products'
          AND column_name = 'delivery_in_7_days'
    ),
    'SELECT `id`, `name`, `slug`, `quantity`, `delivery_in_7_days`
        FROM `products`
        WHERE `slug` IN (
            ''besplatna-dostava-vedrana-rudan'',
            ''crnci-u-firenci-vedrana-rudan'',
            ''dozivotna-robija-vedrana-rudan'',
            ''ljubav-na-posljednji-pogled-vedrana-rudan'',
            ''muskarac-u-grlu-vedrana-rudan'',
            ''ples-oko-sunca-vedrana-rudan'',
            ''strah-od-pletenja-vedrana-rudan'',
            ''zasto-psujem-vedrana-rudan''
        )
        ORDER BY `slug`',
    'SELECT ''Verification skipped because delivery_in_7_days is unavailable'' AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Product delivery notice deployment finished.' AS result;

-- Ručni rollback, samo ako je potreban:
-- UPDATE `products` AS `p`
-- INNER JOIN `products_delivery_7d_backup_20260826` AS `b`
--     ON `b`.`product_id` = `p`.`id`
-- SET
--     `p`.`quantity` = `b`.`quantity`,
--     `p`.`delivery_in_7_days` = `b`.`delivery_in_7_days`;
-- DROP TABLE `products_delivery_7d_backup_20260826`;
