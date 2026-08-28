-- Adds and enables a separate Apple Pay / Google Pay checkout option.
-- The new option intentionally inherits ShopID, SecretKey, CallbackURL and test mode
-- from payment/list.corvus in application code. No credentials are duplicated here.
-- Safe to run more than once on MySQL 5.7+.

SET @corvus_value := (
    SELECT `value`
    FROM `settings`
    WHERE `code` = 'payment' AND `key` = 'list.corvus'
    ORDER BY `id`
    LIMIT 1
);

SET @corvus_shop_id := NULLIF(JSON_UNQUOTE(JSON_EXTRACT(@corvus_value, '$[0].data.shop_id')), 'null');
SET @corvus_secret_key := NULLIF(JSON_UNQUOTE(JSON_EXTRACT(@corvus_value, '$[0].data.secret_key')), 'null');
SET @wallet_enabled := IF(
    COALESCE(@corvus_shop_id, '') <> '' AND COALESCE(@corvus_secret_key, '') <> '',
    TRUE,
    FALSE
);
SET @wallet_min := JSON_EXTRACT(@corvus_value, '$[0].min');
SET @wallet_price := COALESCE(JSON_EXTRACT(@corvus_value, '$[0].data.price'), 0);
SET @wallet_geo_zone := JSON_EXTRACT(@corvus_value, '$[0].geo_zone');
SET @wallet_sort_order := COALESCE(
    CAST(JSON_UNQUOTE(JSON_EXTRACT(@corvus_value, '$[0].sort_order')) AS UNSIGNED),
    0
) + 1;

SET @wallet_value := JSON_ARRAY(JSON_OBJECT(
    'title', 'Apple Pay / Google Pay',
    'title_en', 'Apple Pay / Google Pay',
    'code', 'corvus_wallets',
    'min', @wallet_min,
    'data', JSON_OBJECT(
        'price', @wallet_price,
        'short_description', 'Brzo i sigurno plaćanje putem Apple Paya ili Google Paya',
        'short_description_en', 'Fast and secure payment with Apple Pay or Google Pay',
        'description', 'Plaćanje putem Apple Paya ili Google Paya na sigurnoj CorvusPay stranici.',
        'description_en', 'Pay with Apple Pay or Google Pay on the secure CorvusPay page.',
        'credential_source', 'corvus'
    ),
    'geo_zone', @wallet_geo_zone,
    'status', @wallet_enabled,
    'sort_order', @wallet_sort_order
));

INSERT INTO `settings` (`code`, `key`, `value`, `json`, `created_at`, `updated_at`)
SELECT 'payment', 'list.corvus_wallets', @wallet_value, 1, NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1
    FROM `settings`
    WHERE `code` = 'payment' AND `key` = 'list.corvus_wallets'
);

-- Preserve later administrator edits while filling fields that may be missing from
-- an automatically created stub record. Existing status is preserved so rerunning this
-- installer cannot re-enable a payment method disabled by an administrator.
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
        '$[0].code', 'corvus_wallets',
        '$[0].min', COALESCE(JSON_EXTRACT(`value`, '$[0].min'), @wallet_min),
        '$[0].data.price', COALESCE(JSON_EXTRACT(`value`, '$[0].data.price'), @wallet_price),
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
        '$[0].data.credential_source', 'corvus',
        '$[0].geo_zone', COALESCE(JSON_EXTRACT(`value`, '$[0].geo_zone'), @wallet_geo_zone),
        '$[0].status', IF(
            JSON_TYPE(JSON_EXTRACT(`value`, '$[0].status')) IS NULL
                OR JSON_TYPE(JSON_EXTRACT(`value`, '$[0].status')) = 'NULL'
                OR LOWER(TRIM(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`value`, '$[0].title')), ''))) IN ('', 'null', 'corvus_wallets'),
            @wallet_enabled,
            JSON_EXTRACT(`value`, '$[0].status')
        ),
        '$[0].sort_order', IF(
            LOWER(TRIM(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`value`, '$[0].title')), ''))) IN ('', 'null', 'corvus_wallets')
                AND COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(`value`, '$[0].sort_order')) AS UNSIGNED), 0) = 0,
            @wallet_sort_order,
            COALESCE(JSON_EXTRACT(`value`, '$[0].sort_order'), @wallet_sort_order)
        )
    ),
    `json` = 1,
    `updated_at` = NOW()
WHERE `code` = 'payment'
  AND `key` = 'list.corvus_wallets'
  AND JSON_VALID(`value`);

SELECT
    `key`,
    JSON_UNQUOTE(JSON_EXTRACT(`value`, '$[0].title')) AS `title`,
    JSON_EXTRACT(`value`, '$[0].status') AS `status`,
    JSON_UNQUOTE(JSON_EXTRACT(`value`, '$[0].data.credential_source')) AS `credential_source`
FROM `settings`
WHERE `code` = 'payment' AND `key` = 'list.corvus_wallets';
