

INSERT INTO reviews (review_id, user_id, item_id, rating, comment, review_date)
VALUES
  (1, 1, 1, 5, 'Fantastic product, highly recommend!', '2025-03-06'),
  (2, 2, 2, 4, 'Works pretty well, delivery was quick.', '2025-03-07'),
  (3, 3, 3, 3, 'Average quality, packaging was decent.', '2025-03-08'),
  (4, 4, 1, 2, 'Not quite what I expected, but okay.', '2025-03-08'),
  (5, 5, 4, 5, 'Exceeded my expectations!', '2025-03-09'),
  (6, 2, 3, 1, 'Poor quality. Broke after a few uses.', '2025-03-10'),
  (7, 1, 2, 4, 'So far so good, would buy again.', '2025-03-11'),
  (8, 6, 5, 5, 'Absolutely love it! 5 stars.', '2025-03-11');

COMMIT;
