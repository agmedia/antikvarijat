-- Indexes for fast author/publisher visibility refresh and listing.
-- Run once on live before running clean:authors and clean:publishers.

ALTER TABLE `products`
    ADD INDEX `idx_products_author_visible` (`author_id`, `status`, `quantity`, `price`);

ALTER TABLE `products`
    ADD INDEX `idx_products_publisher_visible` (`publisher_id`, `status`, `quantity`, `price`);

ALTER TABLE `authors`
    ADD INDEX `idx_authors_status_letter` (`status`, `letter`);

ALTER TABLE `publishers`
    ADD INDEX `idx_publishers_status_letter` (`status`, `letter`);
