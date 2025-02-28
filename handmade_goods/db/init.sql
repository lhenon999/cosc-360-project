CREATE DATABASE IF NOT EXISTS handmade_goods;
USE handmade_goods;
CREATE TABLE IF NOT EXISTS USERS( id INT auto_increment PRIMARY KEY,
                                  name VARCHAR ( 100 ) NOT NULL,
                                  email VARCHAR ( 255 ) NOT NULL UNIQUE,
                                  password VARCHAR ( 255 ) NOT NULL,
                                  user_type ENUM('admin', 'normal') NOT NULL DEFAULT 'normal',
                                  created_at timestamp DEFAULT CURRENT_TIMESTAMP );
CREATE TABLE IF NOT EXISTS ITEMS( id INT auto_increment PRIMARY KEY,
                                  name VARCHAR ( 255 ) NOT NULL,
                                  description TEXT NOT NULL,
                                  price DECIMAL ( 10, 2 ) NOT NULL,
                                  stock INT NOT NULL DEFAULT 0,
                                  category VARCHAR ( 100 ),
                                  img VARCHAR ( 255 ) NOT NULL,
                                  created_at timestamp DEFAULT CURRENT_TIMESTAMP );
CREATE TABLE IF NOT EXISTS ITEM_IMAGES( id INT auto_increment PRIMARY KEY,
                                  item_id INT NOT NULL,
                                  image_url VARCHAR ( 255 ) NOT NULL,
                                  FOREIGN KEY ( item_id ) REFERENCES ITEMS(id) ON DELETE CASCADE );
CREATE TABLE IF NOT EXISTS REVIEWS( id INT auto_increment PRIMARY KEY,
                                  item_id INT NOT NULL,
                                  user_id INT NOT NULL,
                                  rating INT CHECK ( rating BETWEEN 1 AND 5 ),
                                  comment TEXT,
                                  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                                  FOREIGN KEY ( item_id ) REFERENCES ITEMS(id) ON DELETE CASCADE,
                                  FOREIGN KEY ( user_id ) REFERENCES USERS(id) ON DELETE CASCADE );
CREATE TABLE IF NOT EXISTS CART( id INT auto_increment PRIMARY KEY,
                                  user_id INT NOT NULL,
                                  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                                  FOREIGN KEY ( user_id ) REFERENCES USERS(id) ON DELETE CASCADE );
CREATE TABLE IF NOT EXISTS CART_ITEMS( id INT auto_increment PRIMARY KEY,
                                  cart_id INT NOT NULL,
                                  item_id INT NOT NULL,
                                  quantity INT NOT NULL DEFAULT 1,
                                  added_at timestamp DEFAULT CURRENT_TIMESTAMP,
                                  FOREIGN KEY ( cart_id ) REFERENCES CART(id) ON DELETE CASCADE,
                                  FOREIGN KEY ( item_id ) REFERENCES ITEMS(id) ON DELETE CASCADE );
CREATE TABLE IF NOT EXISTS ORDERS( id INT auto_increment PRIMARY KEY,
                                  user_id INT NOT NULL,
                                  total_price DECIMAL ( 10, 2 ) NOT NULL,
                                  status ENUM('Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled') DEFAULT 'Pending',
                                  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                                  FOREIGN KEY ( user_id ) REFERENCES USERS(id) ON DELETE CASCADE );
CREATE TABLE IF NOT EXISTS ORDER_ITEMS( id INT auto_increment PRIMARY KEY,
                                  order_id INT NOT NULL,
                                  item_id INT NOT NULL,
                                  quantity INT NOT NULL,
                                  price_at_purchase DECIMAL ( 10, 2 ) NOT NULL,
                                  FOREIGN KEY ( order_id ) REFERENCES ORDERS(id) ON DELETE CASCADE,
                                  FOREIGN KEY ( item_id ) REFERENCES ITEMS(id) ON DELETE CASCADE );
                                                                                          
ALTER TABLE users CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

INSERT INTO ITEMS (name, description, price, stock, category, img) VALUES
('Handmade Necklace', 'A beautiful handmade necklace.', 19.99, 10, 'Jewelry', 'img1.jpg'),
('Wooden Carving', 'Intricate hand-carved wooden sculpture.', 49.99, 5, 'Woodwork', 'img2.jpg'),
('Handmade Pottery Bowl', 'A unique ceramic bowl.', 29.99, 8, 'Pottery', 'img3.jpg');

INSERT INTO users (name, email, password, user_type)
SELECT 'administrator', 'admin@handmadegoods.com', '$2y$10$E4LsPni7YFBS96DJ6tK8PeCJVgswuLXnd6XDPUySc3yCgbv6lnyeG', 'admin'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@handmadegoods.com');

INSERT INTO users (name, email, password, user_type)
SELECT 'John Doe', 'johndoe@mail.com', '$2y$10$6FRuRI3lBFOpHtYhd29XIOs.sT7WeAYXWxs8ORhzLIvAqQApYchRu', 'normal'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'johndoe@mail.com');
