-- Ispravlja sve postojece products.url_en vrijednosti koje su ranije bile
-- spremljene s ID-jevima (npr. en/books/2/30/69860).
--
-- Nova vrijednost uvijek koristi aktualne EN slugove, a kada oni nisu uneseni
-- koristi hrvatske slugove. Skripta je sigurna za ponovno pokretanje.

-- HR URL stupac je vec 255 znakova. EN stupac je u staroj migraciji ostao
-- na 191, sto nije dovoljno za najdulje postojece slug putanje.
ALTER TABLE `products`
    MODIFY `url_en` VARCHAR(255) NULL;

-- Trajna jednokratna sigurnosna kopija izvornih vrijednosti.
CREATE TABLE IF NOT EXISTS `products_url_en_backup_20260807` (
    `product_id` BIGINT UNSIGNED NOT NULL,
    `url_en` VARCHAR(255) NULL,
    `backed_up_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `products_url_en_backup_20260807` (`product_id`, `url_en`)
SELECT `id`, `url_en`
FROM `products`;

DROP TEMPORARY TABLE IF EXISTS `tmp_product_english_urls`;

CREATE TEMPORARY TABLE `tmp_product_english_urls` (
    `product_id` BIGINT UNSIGNED NOT NULL,
    `url_en` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`product_id`)
) ENGINE=InnoDB;

INSERT INTO `tmp_product_english_urls` (`product_id`, `url_en`)
SELECT
    p.`id`,
    CONCAT(
        'en/',
        CASE
            WHEN LOWER(TRIM(parent_category.`group`)) IN ('knjige', 'books')
                THEN 'books'
            WHEN LOWER(TRIM(parent_category.`group`)) IN (
                'zemljovidi i vedute',
                'zemljovidi-i-vedute',
                'maps and views',
                'maps-and-views'
            )
                THEN 'maps-and-views'
        END,
        '/',
        COALESCE(NULLIF(TRIM(parent_category.`slug_en`), ''), parent_category.`slug`),
        CASE
            WHEN child_category.`id` IS NOT NULL THEN CONCAT(
                '/',
                COALESCE(NULLIF(TRIM(child_category.`slug_en`), ''), child_category.`slug`)
            )
            ELSE ''
        END,
        '/',
        COALESCE(NULLIF(TRIM(p.`slug_en`), ''), p.`slug`)
    ) AS `url_en`
FROM `products` AS p
LEFT JOIN (
    SELECT pc.`product_id`, MIN(pc.`category_id`) AS `category_id`
    FROM `product_category` AS pc
    INNER JOIN `categories` AS c ON c.`id` = pc.`category_id`
    WHERE c.`parent_id` <> 0
    GROUP BY pc.`product_id`
) AS selected_child ON selected_child.`product_id` = p.`id`
LEFT JOIN `categories` AS child_category
    ON child_category.`id` = selected_child.`category_id`
LEFT JOIN (
    SELECT pc.`product_id`, MIN(pc.`category_id`) AS `category_id`
    FROM `product_category` AS pc
    INNER JOIN `categories` AS c ON c.`id` = pc.`category_id`
    WHERE c.`parent_id` = 0
    GROUP BY pc.`product_id`
) AS selected_parent ON selected_parent.`product_id` = p.`id`
INNER JOIN `categories` AS parent_category
    ON parent_category.`id` = COALESCE(child_category.`parent_id`, selected_parent.`category_id`)
WHERE
    p.`slug` IS NOT NULL
    AND TRIM(p.`slug`) <> ''
    AND CASE
        WHEN LOWER(TRIM(parent_category.`group`)) IN ('knjige', 'books') THEN 'books'
        WHEN LOWER(TRIM(parent_category.`group`)) IN (
            'zemljovidi i vedute',
            'zemljovidi-i-vedute',
            'maps and views',
            'maps-and-views'
        ) THEN 'maps-and-views'
    END IS NOT NULL;

-- Pregled promjena prije UPDATE-a.
SELECT
    p.`id`,
    p.`url_en` AS `old_url_en`,
    fixed.`url_en` AS `new_url_en`
FROM `products` AS p
INNER JOIN `tmp_product_english_urls` AS fixed ON fixed.`product_id` = p.`id`
WHERE NOT (p.`url_en` <=> fixed.`url_en`)
ORDER BY p.`id`
LIMIT 100;

START TRANSACTION;

UPDATE `products` AS p
INNER JOIN `tmp_product_english_urls` AS fixed ON fixed.`product_id` = p.`id`
SET p.`url_en` = fixed.`url_en`
WHERE NOT (p.`url_en` <=> fixed.`url_en`);

SELECT ROW_COUNT() AS `updated_products`;

-- Nakon ovoga broj mora biti 0.
SELECT COUNT(*) AS `remaining_id_based_english_urls`
FROM `products`
WHERE `url_en` REGEXP '^en/(books|maps-and-views)/[0-9]+(/[0-9]+)?/[0-9]+$';

COMMIT;

-- Primjer povrata podataka, samo ako je potreban:
-- UPDATE `products` AS p
-- INNER JOIN `products_url_en_backup_20260807` AS b ON b.`product_id` = p.`id`
-- SET p.`url_en` = b.`url_en`;
