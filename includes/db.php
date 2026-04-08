<?php
$host = 'localhost';
$dbname = 'trackit_db';
$username = 'root';
$password = ''; // Default XAMPP password is empty

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Patch missing user and category schema columns for older installations
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS email VARCHAR(255) DEFAULT NULL");
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255) DEFAULT NULL");
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS balance DECIMAL(10,2) DEFAULT 0.00");
        $pdo->exec("ALTER TABLE categories MODIFY COLUMN user_id INT NULL");
        $pdo->exec("ALTER TABLE categories ADD COLUMN IF NOT EXISTS type ENUM('income', 'expense') NOT NULL DEFAULT 'expense'");
        $pdo->exec("UPDATE transactions SET type = 'inflow' WHERE type = 'income'");
        $pdo->exec("UPDATE transactions SET type = 'outflow' WHERE type = 'expense'");
        $pdo->exec("ALTER TABLE transactions MODIFY COLUMN type ENUM('inflow', 'outflow') NOT NULL DEFAULT 'inflow'");
        
        // Create pets tables if they don't exist
        $pdo->exec("CREATE TABLE IF NOT EXISTS pets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            emoji VARCHAR(10) NOT NULL,
            description VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS user_pets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            pet_id INT NOT NULL,
            streak_count INT DEFAULT 0,
            last_updated DATE DEFAULT (CURRENT_DATE),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_pet (user_id, pet_id)
        )");
        
        // Insert default pets if not exists
        $pdo->exec("INSERT IGNORE INTO pets (id, name, emoji, description) VALUES
            (1, 'Kitten', '🐱', 'A cute little kitten that loves to play'),
            (2, 'Puppy', '🐶', 'An adorable puppy full of energy'),
            (3, 'Panda', '🐼', 'A gentle panda that eats bamboo'),
            (4, 'Rabbit', '🐰', 'A fluffy bunny that hops around'),
            (5, 'Fox', '🦊', 'A clever fox with a bushy tail'),
            (6, 'Owl', '🦉', 'A wise owl that hoots at night'),
            (7, 'Penguin', '🐧', 'A waddling penguin from the south'),
            (8, 'Koala', '🐨', 'A sleepy koala that loves eucalyptus')");
    } catch (Exception $e) {
        // Ignore migration errors, since this is a best-effort schema patch.
    }

} catch (PDOException $e) {
    // Attempt to create database if it doesn't exist (first run helper)
    try {
        $pdo = new PDO("mysql:host=$host", $username, $password);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
        $pdo->exec("USE `$dbname`");
        // Re-connect to the specific DB
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $ex) {
        die("Connection failed: " . $ex->getMessage());
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function jsonResponse($success, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}
?>