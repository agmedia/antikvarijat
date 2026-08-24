CREATE TABLE IF NOT EXISTS `user_impersonation_audits` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `audit_id` CHAR(36) NOT NULL,
  `actor_user_id` BIGINT UNSIGNED NOT NULL,
  `target_user_id` BIGINT UNSIGNED NOT NULL,
  `started_at` TIMESTAMP NOT NULL,
  `expires_at` TIMESTAMP NOT NULL,
  `ended_at` TIMESTAMP NULL DEFAULT NULL,
  `end_reason` VARCHAR(64) NULL DEFAULT NULL,
  `ip_address` VARCHAR(45) NULL DEFAULT NULL,
  `user_agent_hash` CHAR(64) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_impersonation_audits_audit_id_unique` (`audit_id`),
  KEY `user_impersonation_audits_actor_user_id_index` (`actor_user_id`),
  KEY `user_impersonation_audits_target_user_id_index` (`target_user_id`),
  KEY `user_impersonation_audits_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
