-- Wolt Drive module bootstrap for the live database.
-- No credentials are included: configure and enable the module in
-- Admin > Postavke > Načini dostave > Wolt Drive.

INSERT INTO `settings` (`user_id`, `code`, `key`, `value`, `json`, `created_at`, `updated_at`)
SELECT NULL, 'shipping', 'list.wolt_drive',
       '[{"title":"Wolt Drive","title_en":"Wolt Drive","code":"wolt_drive","data":{"price":0,"time":"Dostava isti dan","time_en":"Same-day delivery","short_description":"Brza dostava na adresu putem Wolt Drivea.","short_description_en":"Fast delivery to your address with Wolt Drive.","description":null,"description_en":null,"rules":{"min_subtotal":null,"max_subtotal":null,"max_items":null,"allowed_postal_codes":null,"excluded_postal_codes":null,"allowed_cities":null,"weekdays":[1,2,3,4,5,6,7],"time_from":null,"time_to":null,"free_shipping_mode":"never","free_shipping_threshold":null}},"geo_zone":0,"sort_order":0,"status":false}]',
       1, NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `settings` WHERE `code` = 'shipping' AND `key` = 'list.wolt_drive'
);

INSERT INTO `settings` (`user_id`, `code`, `key`, `value`, `json`, `created_at`, `updated_at`)
SELECT NULL, 'shipping', 'wolt_drive_api',
       '{"module_enabled":false,"environment":"development","venue_id":"","merchant_id":"","api_key_encrypted":"","webhook_secret_encrypted":"","availability_cache_seconds":300,"preparation_time_minutes":30,"request_timeout_seconds":20,"fallback_weight_grams":500,"cod_enabled":false,"pricing_mode":"fixed","quote_markup_percent":0,"max_quote_price":0,"support_url":"","support_email":"","support_phone":""}',
       1, NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `settings` WHERE `code` = 'shipping' AND `key` = 'wolt_drive_api'
);
