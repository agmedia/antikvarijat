CREATE TABLE `loyalty` (
                           `id` bigint UNSIGNED NOT NULL,
                           `user_id` bigint NOT NULL,
                           `reference_id` bigint NOT NULL,
                           `target` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
                           `earned` bigint NOT NULL,
                           `spend` bigint NOT NULL,
                           `created_at` timestamp NULL DEFAULT NULL,
                           `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


ALTER TABLE `loyalty`
    ADD PRIMARY KEY (`id`);
