USE rsodhi03;

-- test file to create sample sales for user john doe id = 2

INSERT INTO ORDERS (user_id, total_price, status)
VALUES (3, 53.97, 'Pending');


SET @order_id_1 = LAST_INSERT_ID();

INSERT INTO ORDER_ITEMS (order_id, item_id, item_name, quantity, price_at_purchase)
VALUES
  (@order_id_1, 8, 'Handmade Soap Set', 2, 12.99),
  (@order_id_1, 9, 'Woven Basket',      1, 27.99);

INSERT INTO SALES (order_id, seller_id, buyer_id, item_id, quantity, price)
VALUES
  (@order_id_1, 2, 3, 8, 2, 12.99),
  (@order_id_1, 2, 3, 9, 1, 27.99);

INSERT INTO ORDERS (user_id, total_price, status)
VALUES (2, 29.99, 'Pending');

SET @order_id_2 = LAST_INSERT_ID();

INSERT INTO ORDER_ITEMS (order_id, item_id, item_name, quantity, price_at_purchase)
VALUES
  (@order_id_2, 1, 'Handmade Wooden Bowl', 1, 29.99);

INSERT INTO SALES (order_id, seller_id, buyer_id, item_id, quantity, price)
VALUES
  (@order_id_2, 3, 2, 1, 1, 29.99);

