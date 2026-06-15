-- English checkout setting fields for payment methods, shipping methods and order statuses.
-- These settings are stored as JSON in `settings.value`, so no table columns are added.
-- JSON_INSERT keeps existing EN values if the script is run more than once.

UPDATE `settings`
SET `value` = JSON_INSERT(
    `value`,
    '$[0].title_en', '',
    '$[0].data.short_description_en', '',
    '$[0].data.description_en', ''
)
WHERE `code` = 'payment'
  AND `key` LIKE 'list.%'
  AND JSON_VALID(`value`)
  AND JSON_LENGTH(`value`) > 0;

UPDATE `settings`
SET `value` = JSON_INSERT(
    `value`,
    '$[0].title_en', '',
    '$[0].data.time_en', '',
    '$[0].data.short_description_en', '',
    '$[0].data.description_en', ''
)
WHERE `code` = 'shipping'
  AND `key` LIKE 'list.%'
  AND JSON_VALID(`value`)
  AND JSON_LENGTH(`value`) > 0;

UPDATE `settings` SET `value` = JSON_INSERT(`value`, '$[0].title_en', '') WHERE `code` = 'order' AND `key` = 'statuses' AND JSON_VALID(`value`) AND JSON_LENGTH(`value`) > 0;
UPDATE `settings` SET `value` = JSON_INSERT(`value`, '$[1].title_en', '') WHERE `code` = 'order' AND `key` = 'statuses' AND JSON_VALID(`value`) AND JSON_LENGTH(`value`) > 1;
UPDATE `settings` SET `value` = JSON_INSERT(`value`, '$[2].title_en', '') WHERE `code` = 'order' AND `key` = 'statuses' AND JSON_VALID(`value`) AND JSON_LENGTH(`value`) > 2;
UPDATE `settings` SET `value` = JSON_INSERT(`value`, '$[3].title_en', '') WHERE `code` = 'order' AND `key` = 'statuses' AND JSON_VALID(`value`) AND JSON_LENGTH(`value`) > 3;
UPDATE `settings` SET `value` = JSON_INSERT(`value`, '$[4].title_en', '') WHERE `code` = 'order' AND `key` = 'statuses' AND JSON_VALID(`value`) AND JSON_LENGTH(`value`) > 4;
UPDATE `settings` SET `value` = JSON_INSERT(`value`, '$[5].title_en', '') WHERE `code` = 'order' AND `key` = 'statuses' AND JSON_VALID(`value`) AND JSON_LENGTH(`value`) > 5;
UPDATE `settings` SET `value` = JSON_INSERT(`value`, '$[6].title_en', '') WHERE `code` = 'order' AND `key` = 'statuses' AND JSON_VALID(`value`) AND JSON_LENGTH(`value`) > 6;
UPDATE `settings` SET `value` = JSON_INSERT(`value`, '$[7].title_en', '') WHERE `code` = 'order' AND `key` = 'statuses' AND JSON_VALID(`value`) AND JSON_LENGTH(`value`) > 7;
UPDATE `settings` SET `value` = JSON_INSERT(`value`, '$[8].title_en', '') WHERE `code` = 'order' AND `key` = 'statuses' AND JSON_VALID(`value`) AND JSON_LENGTH(`value`) > 8;
UPDATE `settings` SET `value` = JSON_INSERT(`value`, '$[9].title_en', '') WHERE `code` = 'order' AND `key` = 'statuses' AND JSON_VALID(`value`) AND JSON_LENGTH(`value`) > 9;
UPDATE `settings` SET `value` = JSON_INSERT(`value`, '$[10].title_en', '') WHERE `code` = 'order' AND `key` = 'statuses' AND JSON_VALID(`value`) AND JSON_LENGTH(`value`) > 10;
UPDATE `settings` SET `value` = JSON_INSERT(`value`, '$[11].title_en', '') WHERE `code` = 'order' AND `key` = 'statuses' AND JSON_VALID(`value`) AND JSON_LENGTH(`value`) > 11;
UPDATE `settings` SET `value` = JSON_INSERT(`value`, '$[12].title_en', '') WHERE `code` = 'order' AND `key` = 'statuses' AND JSON_VALID(`value`) AND JSON_LENGTH(`value`) > 12;
UPDATE `settings` SET `value` = JSON_INSERT(`value`, '$[13].title_en', '') WHERE `code` = 'order' AND `key` = 'statuses' AND JSON_VALID(`value`) AND JSON_LENGTH(`value`) > 13;
UPDATE `settings` SET `value` = JSON_INSERT(`value`, '$[14].title_en', '') WHERE `code` = 'order' AND `key` = 'statuses' AND JSON_VALID(`value`) AND JSON_LENGTH(`value`) > 14;
UPDATE `settings` SET `value` = JSON_INSERT(`value`, '$[15].title_en', '') WHERE `code` = 'order' AND `key` = 'statuses' AND JSON_VALID(`value`) AND JSON_LENGTH(`value`) > 15;
UPDATE `settings` SET `value` = JSON_INSERT(`value`, '$[16].title_en', '') WHERE `code` = 'order' AND `key` = 'statuses' AND JSON_VALID(`value`) AND JSON_LENGTH(`value`) > 16;
UPDATE `settings` SET `value` = JSON_INSERT(`value`, '$[17].title_en', '') WHERE `code` = 'order' AND `key` = 'statuses' AND JSON_VALID(`value`) AND JSON_LENGTH(`value`) > 17;
UPDATE `settings` SET `value` = JSON_INSERT(`value`, '$[18].title_en', '') WHERE `code` = 'order' AND `key` = 'statuses' AND JSON_VALID(`value`) AND JSON_LENGTH(`value`) > 18;
UPDATE `settings` SET `value` = JSON_INSERT(`value`, '$[19].title_en', '') WHERE `code` = 'order' AND `key` = 'statuses' AND JSON_VALID(`value`) AND JSON_LENGTH(`value`) > 19;
