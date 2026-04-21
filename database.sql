CREATE DATABASE IF NOT EXISTS trackit_db;
USE trackit_db;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    profile_picture VARCHAR(255) DEFAULT NULL,
    balance DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    name VARCHAR(50) NOT NULL,
    percentage DECIMAL(5,2) DEFAULT 0,
    color VARCHAR(7) DEFAULT '#3b82f6',
    type ENUM('income', 'expense') NOT NULL DEFAULT 'expense',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    type ENUM('inflow', 'outflow') NOT NULL,
    transaction_date DATE DEFAULT (CURRENT_DATE),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS pets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    emoji VARCHAR(10) NOT NULL,
    image_url VARCHAR(255) DEFAULT NULL,
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS user_pets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    pet_type ENUM('cat', 'dog', 'dragon', 'owl') DEFAULT 'cat',
    streak_count INT DEFAULT 0,
    level INT DEFAULT 1,
    xp INT DEFAULT 0,
    last_move_date DATE DEFAULT NULL,
    evolution_stage ENUM('egg', 'baby', 'teen', 'adult', 'legend') DEFAULT 'egg',
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Insert default pets
INSERT INTO pets (name, emoji, image_url, description) VALUES
('Kitten', '🐱', 'https://cdn-icons-png.flaticon.com/512/1998/1998468.png', 'A cute little kitten that loves to play'),
('Puppy', '🐶', 'https://cdn-icons-png.flaticon.com/512/1998/1998500.png', 'An adorable puppy full of energy'),
('Panda', '🐼', 'https://cdn-icons-png.flaticon.com/512/1946/1946436.png', 'A gentle panda that eats bamboo'),
('Rabbit', '🐰', 'https://cdn-icons-png.flaticon.com/512/1998/1998674.png', 'A fluffy bunny that hops around'),
('Fox', '🦊', 'https://cdn-icons-png.flaticon.com/512/1998/1998394.png', 'A clever fox with a bushy tail'),
('Owl', '🦉', 'https://cdn-icons-png.flaticon.com/512/1998/1998467.png', 'A wise owl that hoots at night'),
('Penguin', '🐧', 'https://cdn-icons-png.flaticon.com/512/1998/1998535.png', 'A waddling penguin from the south'),
('Koala', '🐨', 'https://cdn-icons-png.flaticon.com/512/1998/1998411.png', 'A sleepy koala that loves eucalyptus');

-- Basic Default Categories for new users (handled in PHP usually, but good to know)
-- Daily Spending, Bills, Savings, Emergency Fund
