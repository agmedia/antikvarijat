-- English localization fields for Antikvarijat Biblos.
-- Run once on live before deploying code that reads *_en columns.

ALTER TABLE `products`
    ADD COLUMN `name_en` VARCHAR(191) NULL AFTER `name`,
    ADD COLUMN `description_en` LONGTEXT NULL AFTER `description`,
    ADD COLUMN `slug_en` VARCHAR(191) NULL AFTER `slug`,
    ADD COLUMN `url_en` VARCHAR(191) NULL AFTER `url`,
    ADD COLUMN `meta_title_en` VARCHAR(191) NULL AFTER `meta_title`,
    ADD COLUMN `meta_description_en` VARCHAR(191) NULL AFTER `meta_description`,
    ADD INDEX `products_slug_en_index` (`slug_en`);

ALTER TABLE `categories`
    ADD COLUMN `title_en` VARCHAR(191) NULL AFTER `title`,
    ADD COLUMN `description_en` LONGTEXT NULL AFTER `description`,
    ADD COLUMN `meta_title_en` TEXT NULL AFTER `meta_title`,
    ADD COLUMN `meta_description_en` TEXT NULL AFTER `meta_description`,
    ADD COLUMN `slug_en` VARCHAR(191) NULL AFTER `slug`,
    ADD INDEX `categories_slug_en_index` (`slug_en`);

ALTER TABLE `pages`
    ADD COLUMN `title_en` VARCHAR(191) NULL AFTER `title`,
    ADD COLUMN `short_description_en` TEXT NULL AFTER `short_description`,
    ADD COLUMN `description_en` LONGTEXT NULL AFTER `description`,
    ADD COLUMN `meta_title_en` VARCHAR(191) NULL AFTER `meta_title`,
    ADD COLUMN `meta_description_en` VARCHAR(191) NULL AFTER `meta_description`,
    ADD COLUMN `slug_en` VARCHAR(191) NULL AFTER `slug`,
    ADD COLUMN `keywords_en` VARCHAR(191) NULL AFTER `keywords`,
    ADD INDEX `pages_slug_en_index` (`slug_en`);

ALTER TABLE `faq`
    ADD COLUMN `title_en` VARCHAR(191) NULL AFTER `title`,
    ADD COLUMN `description_en` LONGTEXT NULL AFTER `description`;

ALTER TABLE `widgets`
    ADD COLUMN `title_en` VARCHAR(191) NULL AFTER `title`,
    ADD COLUMN `subtitle_en` TEXT NULL AFTER `subtitle`,
    ADD COLUMN `description_en` LONGTEXT NULL AFTER `description`,
    ADD COLUMN `url_en` VARCHAR(191) NULL AFTER `url`,
    ADD COLUMN `badge_en` VARCHAR(191) NULL AFTER `badge`;

ALTER TABLE `widget_groups`
    ADD COLUMN `title_en` VARCHAR(191) NULL AFTER `title`,
    ADD COLUMN `slug_en` VARCHAR(191) NULL AFTER `slug`,
    ADD INDEX `widget_groups_slug_en_index` (`slug_en`);

ALTER TABLE `authors`
    ADD COLUMN `title_en` VARCHAR(191) NULL AFTER `title`,
    ADD COLUMN `description_en` LONGTEXT NULL AFTER `description`,
    ADD COLUMN `meta_title_en` TEXT NULL AFTER `meta_title`,
    ADD COLUMN `meta_description_en` TEXT NULL AFTER `meta_description`,
    ADD COLUMN `slug_en` VARCHAR(191) NULL AFTER `slug`,
    ADD COLUMN `url_en` VARCHAR(191) NULL AFTER `url`,
    ADD INDEX `authors_slug_en_index` (`slug_en`);

ALTER TABLE `publishers`
    ADD COLUMN `title_en` VARCHAR(191) NULL AFTER `title`,
    ADD COLUMN `description_en` LONGTEXT NULL AFTER `description`,
    ADD COLUMN `meta_title_en` TEXT NULL AFTER `meta_title`,
    ADD COLUMN `meta_description_en` TEXT NULL AFTER `meta_description`,
    ADD COLUMN `slug_en` VARCHAR(191) NULL AFTER `slug`,
    ADD COLUMN `url_en` VARCHAR(191) NULL AFTER `url`,
    ADD INDEX `publishers_slug_en_index` (`slug_en`);
