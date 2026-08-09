-- Antikvarijat Biblos - admin, dashboard i statistike
-- Datum: 2026-08-09
-- Namjena: produkcijski indeksi potrebni za brži dashboard, statistike i liste.
-- Sigurnost: skripta ne mijenja niti briše podatke i može se pokrenuti više puta.
-- Pokretanje: odabrati produkcijsku bazu pa izvršiti cijelu skriptu u phpMyAdminu/CLI-u.

SET @schema_name = DATABASE();

SET @sql = IF(
    EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = @schema_name AND table_name = 'orders')
    AND NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = @schema_name AND table_name = 'orders' AND index_name = 'idx_orders_status_created'),
    'ALTER TABLE `orders` ADD INDEX `idx_orders_status_created` (`order_status_id`, `created_at`)',
    'SELECT ''idx_orders_status_created already present or table missing'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = @schema_name AND table_name = 'order_products')
    AND NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = @schema_name AND table_name = 'order_products' AND index_name = 'idx_order_products_order_id'),
    'ALTER TABLE `order_products` ADD INDEX `idx_order_products_order_id` (`order_id`)',
    'SELECT ''idx_order_products_order_id already present or table missing'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = @schema_name AND table_name = 'order_products')
    AND NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = @schema_name AND table_name = 'order_products' AND index_name = 'idx_order_products_product_created'),
    'ALTER TABLE `order_products` ADD INDEX `idx_order_products_product_created` (`product_id`, `created_at`)',
    'SELECT ''idx_order_products_product_created already present or table missing'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = @schema_name AND table_name = 'order_total')
    AND NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = @schema_name AND table_name = 'order_total' AND index_name = 'idx_order_total_order_sort'),
    'ALTER TABLE `order_total` ADD INDEX `idx_order_total_order_sort` (`order_id`, `sort_order`)',
    'SELECT ''idx_order_total_order_sort already present or table missing'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = @schema_name AND table_name = 'order_history')
    AND NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = @schema_name AND table_name = 'order_history' AND index_name = 'idx_order_history_order_created'),
    'ALTER TABLE `order_history` ADD INDEX `idx_order_history_order_created` (`order_id`, `created_at`)',
    'SELECT ''idx_order_history_order_created already present or table missing'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = @schema_name AND table_name = 'order_history')
    AND NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = @schema_name AND table_name = 'order_history' AND index_name = 'idx_order_history_user_id'),
    'ALTER TABLE `order_history` ADD INDEX `idx_order_history_user_id` (`user_id`)',
    'SELECT ''idx_order_history_user_id already present or table missing'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = @schema_name AND table_name = 'product_category')
    AND NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = @schema_name AND table_name = 'product_category' AND index_name = 'idx_product_category_product_category'),
    'ALTER TABLE `product_category` ADD INDEX `idx_product_category_product_category` (`product_id`, `category_id`)',
    'SELECT ''idx_product_category_product_category already present or table missing'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = @schema_name AND table_name = 'products')
    AND NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = @schema_name AND table_name = 'products' AND index_name = 'idx_products_author_visible'),
    'ALTER TABLE `products` ADD INDEX `idx_products_author_visible` (`author_id`, `status`, `quantity`, `price`)',
    'SELECT ''idx_products_author_visible already present or table missing'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = @schema_name AND table_name = 'products')
    AND NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = @schema_name AND table_name = 'products' AND index_name = 'idx_products_publisher_visible'),
    'ALTER TABLE `products` ADD INDEX `idx_products_publisher_visible` (`publisher_id`, `status`, `quantity`, `price`)',
    'SELECT ''idx_products_publisher_visible already present or table missing'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = @schema_name AND table_name = 'authors')
    AND NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = @schema_name AND table_name = 'authors' AND index_name = 'idx_authors_status_letter'),
    'ALTER TABLE `authors` ADD INDEX `idx_authors_status_letter` (`status`, `letter`)',
    'SELECT ''idx_authors_status_letter already present or table missing'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = @schema_name AND table_name = 'publishers')
    AND NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = @schema_name AND table_name = 'publishers' AND index_name = 'idx_publishers_status_letter'),
    'ALTER TABLE `publishers` ADD INDEX `idx_publishers_status_letter` (`status`, `letter`)',
    'SELECT ''idx_publishers_status_letter already present or table missing'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT 'Biblos admin/statistics index deployment finished.' AS result;
