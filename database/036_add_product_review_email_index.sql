-- Indeks za brzo pronalaženje već poslanih poziva po normaliziranoj e-mail adresi.
-- Sigurno za ponovno pokretanje na MySQL 8 / MariaDB 10.2+.

SET @schema_name = DATABASE();

SET @sql = IF(
    EXISTS(
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = @schema_name
          AND table_name = 'product_review_invitations'
    ) AND NOT EXISTS(
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @schema_name
          AND table_name = 'product_review_invitations'
          AND column_name = 'recipient_email_normalized'
    ),
    'ALTER TABLE `product_review_invitations` ADD COLUMN `recipient_email_normalized` VARCHAR(191) GENERATED ALWAYS AS (LOWER(TRIM(`recipient_email`))) STORED AFTER `recipient_email`',
    'SELECT ''recipient_email_normalized already present or table missing'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
    EXISTS(
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = @schema_name
          AND table_name = 'product_review_invitations'
          AND column_name = 'recipient_email_normalized'
    ) AND NOT EXISTS(
        SELECT 1
        FROM information_schema.statistics
        WHERE table_schema = @schema_name
          AND table_name = 'product_review_invitations'
          AND index_name = 'review_invitations_email_sent_index'
    ),
    'ALTER TABLE `product_review_invitations` ADD INDEX `review_invitations_email_sent_index` (`recipient_email_normalized`, `sent_at`)',
    'SELECT ''review_invitations_email_sent_index already present or column missing'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
