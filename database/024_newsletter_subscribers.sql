ALTER TABLE `newsletter_subscribers`
  ADD COLUMN `order_id` BIGINT NOT NULL DEFAULT 0 AFTER `user_id`,
  ADD COLUMN `mailchimp_synced_at` TIMESTAMP NULL AFTER `subscribed_at`,
  ADD COLUMN `mailchimp_last_error` TEXT NULL AFTER `mailchimp_synced_at`,
  ADD INDEX `newsletter_subscribers_order_id_index` (`order_id`),
  ADD INDEX `newsletter_subscribers_mailchimp_synced_at_index` (`mailchimp_synced_at`);
