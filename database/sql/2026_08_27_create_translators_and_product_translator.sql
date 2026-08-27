-- Antikvarijat Biblos - prevoditelji i njihove veze s artiklima
-- Datum: 2026-08-27
-- Pokretanje: odabrati produkcijsku bazu pa izvršiti cijelu skriptu u phpMyAdminu/CLI-u.
-- Redoslijed deploya: izvršiti ovu skriptu prije puštanja novog aplikacijskog koda u promet.
-- Sigurnost: skripta samo dodaje dvije nove tablice; ne mijenja niti briše postojeće podatke.
-- Skripta se može pokrenuti više puta.
-- Integritet: aplikacijski modeli pri brisanju artikla ili prevoditelja brišu njihove veze.
-- Pivot zato nema FK ovisnost o mogućem naslijeđenom storage engineu tablice products.

CREATE TABLE IF NOT EXISTS `translators` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(191) NOT NULL,
    `normalized_title` VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `translators_normalized_title_unique` (`normalized_title`),
    KEY `translators_title_index` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_translator` (
    `product_id` BIGINT UNSIGNED NOT NULL,
    `translator_id` BIGINT UNSIGNED NOT NULL,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY `product_translator_product_translator_unique` (`product_id`, `translator_id`),
    KEY `product_translator_product_sort_index` (`product_id`, `sort_order`),
    KEY `product_translator_translator_index` (`translator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT
    (SELECT COUNT(*)
       FROM information_schema.tables
      WHERE table_schema = DATABASE() AND table_name = 'translators') AS translators_ready,
    (SELECT COUNT(*)
       FROM information_schema.tables
      WHERE table_schema = DATABASE() AND table_name = 'product_translator') AS product_translator_ready;

SELECT
    (SELECT COUNT(DISTINCT index_name)
       FROM information_schema.statistics
      WHERE table_schema = DATABASE()
        AND table_name = 'translators'
        AND index_name = 'translators_normalized_title_unique') AS translator_unique_name_ready,
    (SELECT COUNT(DISTINCT index_name)
       FROM information_schema.statistics
      WHERE table_schema = DATABASE()
        AND table_name = 'product_translator'
        AND index_name = 'product_translator_product_translator_unique') AS product_translator_unique_pair_ready,
    (SELECT COUNT(DISTINCT index_name)
       FROM information_schema.statistics
      WHERE table_schema = DATABASE()
        AND table_name = 'product_translator'
        AND index_name = 'product_translator_product_sort_index') AS product_translator_order_index_ready;

-- Ručni rollback, samo ako je izričito potreban i prije unosa prevoditelja:
-- DROP TABLE IF EXISTS `product_translator`;
-- DROP TABLE IF EXISTS `translators`;
