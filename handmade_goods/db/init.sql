DROP DATABASE IF EXISTS handmade_goods;
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
                                  user_id INT NOT NULL,
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
CREATE TABLE IF NOT EXISTS ADDRESSES (
    id INT auto_increment PRIMARY KEY,
    user_id INT NOT NULL,
    street_address VARCHAR(255) NOT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    country VARCHAR(100) NOT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES USERS(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS ORDERS( id INT auto_increment PRIMARY KEY,
                                  user_id INT NOT NULL,
                                  address_id INT,
                                  total_price DECIMAL ( 10, 2 ) NOT NULL,
                                  status ENUM('Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled') DEFAULT 'Pending',
                                  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
                                  FOREIGN KEY ( user_id ) REFERENCES USERS(id) ON DELETE CASCADE,
                                  FOREIGN KEY ( address_id ) REFERENCES ADDRESSES(id) ON DELETE SET NULL );
CREATE TABLE IF NOT EXISTS ORDER_ITEMS( id INT auto_increment PRIMARY KEY,
                                  order_id INT NOT NULL,
                                  item_id INT,
                                  item_name VARCHAR(255) NOT NULL,
                                  quantity INT NOT NULL,
                                  price_at_purchase DECIMAL ( 10, 2 ) NOT NULL,
                                  FOREIGN KEY ( order_id ) REFERENCES ORDERS(id) ON DELETE CASCADE,
                                  FOREIGN KEY ( item_id ) REFERENCES ITEMS(id) ON DELETE SET NULL );
CREATE TABLE IF NOT EXISTS password_resets (id INT AUTO_INCREMENT PRIMARY KEY,
                                email VARCHAR(255) NOT NULL,
                                token VARCHAR(100) NOT NULL,
                                expires DATETIME NOT NULL,
                                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS sales (id INT AUTO_INCREMENT PRIMARY KEY,
                                order_id INT NOT NULL,
                                seller_id INT NOT NULL,
                                buyer_id INT NOT NULL,
                                item_id INT NOT NULL,
                                quantity INT NOT NULL,
                                price DECIMAL(10,2) NOT NULL,
                                sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                                FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
                                FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
                                FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE);

ALTER TABLE users CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE USERS ADD COLUMN profile_picture VARCHAR(255) NOT NULL DEFAULT '/cosc-360-project/handmade_goods/assets/images/default_profile.png';
ALTER TABLE password_resets ADD COLUMN short_code VARCHAR(8) NOT NULL;
ALTER TABLE password_resets ADD UNIQUE (email);
ALTER TABLE users ADD COLUMN remember_token VARCHAR(255) NULL;

INSERT INTO users (id, name, email, password, user_type)
SELECT 1, 'administrator', 'admin@handmadegoods.com', '$2y$10$E4LsPni7YFBS96DJ6tK8PeCJVgswuLXnd6XDPUySc3yCgbv6lnyeG', 'admin'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@handmadegoods.com');

INSERT INTO users (id, name, email, password, user_type)
SELECT 2, 'John Doe', 'johndoe@mail.com', '$2y$10$6FRuRI3lBFOpHtYhd29XIOs.sT7WeAYXWxs8ORhzLIvAqQApYchRu', 'normal'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'johndoe@mail.com');
