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

INSERT INTO users (name, email, password, user_type)
SELECT 'administrator', 'admin@handmadegoods.com', '$2y$10$8BVxD8EpPDunffWh3ih2FeBpfjZxsHwLVW53eOZJISs.O5BlBByNK', 'admin'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@handmadegoods.com');

INSERT INTO users (name, email, password, user_type)
SELECT 'John Doe', 'johndoe@mail.com', '$2y$10$tA.PReExstX08tJym/gF8u95ZAsBR8mvMIEBRTbAkquFQSyoyJDCq', 'normal'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'johndoe@mail.com');
