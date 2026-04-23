ALTER TABLE `orders`
    ADD COLUMN `birthday_year` DATE NULL DEFAULT NULL AFTER `payment_phone`;
