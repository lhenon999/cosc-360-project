USE handmade_goods;

ALTER TABLE ORDERS 
ADD COLUMN payment_id VARCHAR(255) DEFAULT NULL,
ADD COLUMN payment_method VARCHAR(50) DEFAULT NULL;

-- Add payment_id column to orders table
ALTER TABLE orders ADD COLUMN payment_id VARCHAR(255) NULL COMMENT 'Stripe payment intent ID';