-- Repairs the Apple Pay / Google Pay setting after the original standalone
-- installer converted explicit JSON null values into the string "null".
-- Safe to run more than once on MySQL 5.7+.

START TRANSACTION;

UPDATE `settings`
SET `value` = JSON_SET(
        `value`,
        '$[0].title', IF(
            LOWER(TRIM(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`value`, '$[0].title')), ''))) IN ('', 'null', 'corvus_wallets'),
            'Apple Pay / Google Pay',
            JSON_UNQUOTE(JSON_EXTRACT(`value`, '$[0].title'))
        ),
        '$[0].title_en', IF(
            LOWER(TRIM(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`value`, '$[0].title_en')), ''))) IN ('', 'null'),
            'Apple Pay / Google Pay',
            JSON_UNQUOTE(JSON_EXTRACT(`value`, '$[0].title_en'))
        ),
        '$[0].data.short_description', IF(
            LOWER(TRIM(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`value`, '$[0].data.short_description')), ''))) IN ('', 'null'),
            'Brzo i sigurno plaćanje putem Apple Paya ili Google Paya',
            JSON_UNQUOTE(JSON_EXTRACT(`value`, '$[0].data.short_description'))
        ),
        '$[0].data.short_description_en', IF(
            LOWER(TRIM(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`value`, '$[0].data.short_description_en')), ''))) IN ('', 'null'),
            'Fast and secure payment with Apple Pay or Google Pay',
            JSON_UNQUOTE(JSON_EXTRACT(`value`, '$[0].data.short_description_en'))
        ),
        '$[0].data.description', IF(
            LOWER(TRIM(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`value`, '$[0].data.description')), ''))) IN ('', 'null'),
            'Plaćanje putem Apple Paya ili Google Paya na sigurnoj CorvusPay stranici.',
            JSON_UNQUOTE(JSON_EXTRACT(`value`, '$[0].data.description'))
        ),
        '$[0].data.description_en', IF(
            LOWER(TRIM(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`value`, '$[0].data.description_en')), ''))) IN ('', 'null'),
            'Pay with Apple Pay or Google Pay on the secure CorvusPay page.',
            JSON_UNQUOTE(JSON_EXTRACT(`value`, '$[0].data.description_en'))
        ),
        '$[0].code', 'corvus_wallets',
        '$[0].data.credential_source', 'corvus'
    ),
    `json` = 1,
    `updated_at` = NOW()
WHERE `code` = 'payment'
  AND `key` = 'list.corvus_wallets'
  AND JSON_VALID(`value`);

UPDATE `orders`
SET `payment_method` = 'Apple Pay / Google Pay'
WHERE `payment_code` = 'corvus_wallets'
  AND LOWER(TRIM(`payment_method`)) = 'null';

COMMIT;

SELECT
    `key`,
    JSON_UNQUOTE(JSON_EXTRACT(`value`, '$[0].title')) AS `title`,
    JSON_UNQUOTE(JSON_EXTRACT(`value`, '$[0].title_en')) AS `title_en`,
    JSON_UNQUOTE(JSON_EXTRACT(`value`, '$[0].data.description_en')) AS `description_en`,
    JSON_EXTRACT(`value`, '$[0].status') AS `status`
FROM `settings`
WHERE `code` = 'payment'
  AND `key` = 'list.corvus_wallets';

SELECT `id`, `order_status_id`, `payment_method`, `payment_code`, `locale`
FROM `orders`
WHERE `id` IN (26042, 26046)
ORDER BY `id`;
