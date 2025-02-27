CREATE DATABASE IF NOT EXISTS handmade_goods;
USE handmade_goods;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('admin', 'normal') NOT NULL DEFAULT 'normal',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE users CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

INSERT INTO users (name, email, password, user_type)
SELECT 'administrator', 'admin@handmadegoods.com', '$2y$10$E4LsPni7YFBS96DJ6tK8PeCJVgswuLXnd6XDPUySc3yCgbv6lnyeG', 'admin'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@handmadegoods.com');

INSERT INTO users (name, email, password, user_type)
SELECT 'John Doe', 'johndoe@mail.com', '$2y$10$6FRuRI3lBFOpHtYhd29XIOs.sT7WeAYXWxs8ORhzLIvAqQApYchRu', 'normal'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'johndoe@mail.com');