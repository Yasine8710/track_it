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
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS user_pets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    pet_id INT NOT NULL,
    streak_count INT DEFAULT 0,
    last_updated DATE DEFAULT (CURRENT_DATE),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_pet (user_id, pet_id)
);

-- Insert default pets
INSERT INTO pets (name, emoji, description) VALUES
('Kitten', '🐱', 'A cute little kitten that loves to play'),
('Puppy', '🐶', 'An adorable puppy full of energy'),
('Panda', '🐼', 'A gentle panda that eats bamboo'),
('Rabbit', '🐰', 'A fluffy bunny that hops around'),
('Fox', '🦊', 'A clever fox with a bushy tail'),
('Owl', '🦉', 'A wise owl that hoots at night'),
('Penguin', '🐧', 'A waddling penguin from the south'),
('Koala', '🐨', 'A sleepy koala that loves eucalyptus');

-- Basic Default Categories for new users (handled in PHP usually, but good to know)
-- Daily Spending, Bills, Savings, Emergency Fund
