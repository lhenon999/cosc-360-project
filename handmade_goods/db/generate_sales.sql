
INSERT INTO orders (order_id, user_id, order_date, total_amount, shipping_address, status)
VALUES
  (1, 1, '2025-03-01', 55.50, '123 Main St, Springfield', 'SHIPPED'),
  (2, 2, '2025-03-02', 98.99, '456 Elm St, Springfield', 'DELIVERED'),
  (3, 3, '2025-03-03', 20.00, '789 Pine St, Capital City', 'CANCELLED'),
  (4, 1, '2025-03-04', 43.25, '123 Main St, Springfield', 'PROCESSING'),
  (5, 5, '2025-03-05', 62.50, '55 Lotus Rd, Shelbyville', 'SHIPPED');

INSERT INTO sales (sale_id, order_id, item_id, quantity, price, discount, tax, total)
VALUES
  (1, 1, 1, 2, 10.00, 0.00, 1.50, 21.50),
  (2, 1, 2, 1, 30.00, 5.00, 1.50, 26.50),
  (3, 2, 3, 4, 10.00, 1.00, 2.99, 41.99),
  (4, 2, 1, 1, 10.00, 0.00, 0.00, 10.00),
  (5, 3, 4, 1, 20.00, 0.00, 0.00, 20.00),
  (6, 4, 2, 1, 30.00, 0.00, 2.25, 32.25),
  (7, 5, 3, 3, 10.00, 0.00, 2.50, 32.50);

COMMIT;
