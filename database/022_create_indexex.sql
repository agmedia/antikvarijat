CREATE INDEX orders_created_at_idx ON orders (created_at);
CREATE INDEX orders_status_created_idx ON orders (order_status_id, created_at);
CREATE INDEX orders_payment_email_idx ON orders (payment_email);
CREATE INDEX orders_payment_name_idx ON orders (payment_lname, payment_fname);

CREATE INDEX idx_order_products_order_id ON order_products(order_id);
CREATE INDEX idx_order_products_product_id ON order_products(product_id);
-- za LIKE pretrage nad name (prefix index da ne bude predug):
CREATE INDEX idx_order_products_name_prefix ON order_products(name(100));

CREATE INDEX idx_orders_created_at ON orders(created_at);
CREATE INDEX idx_orders_status_created ON orders(order_status_id, created_at);


CREATE INDEX idx_order_products_order_id ON order_products(order_id);
CREATE INDEX idx_order_products_product_id ON order_products(product_id);

-- products: za LIKE na velikim tekstovima razmisli o FULLTEXT (ako MySQL >= 5.6 InnoDB):
ALTER TABLE products ADD FULLTEXT ft_products_cat_tags (category_string, tags);

-- ako ne želiš FULLTEXT, barem normalni indeksi (manje učinkovito za %like% ali pomažu kad nije leading %):
CREATE INDEX idx_products_category_string ON products (category_string(191));
CREATE INDEX idx_products_tags ON products (tags(191));
