<?php
require 'includes/db.php';
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS email VARCHAR(255) DEFAULT NULL");
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255) DEFAULT NULL");
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS balance DECIMAL(10,2) DEFAULT 0.00");
    $pdo->exec("ALTER TABLE categories MODIFY COLUMN user_id INT NULL");
    $pdo->exec("ALTER TABLE categories ADD COLUMN IF NOT EXISTS type ENUM('income', 'expense') NOT NULL DEFAULT 'expense'");
    $pdo->exec("UPDATE transactions SET type = 'inflow' WHERE type = 'income'");
    $pdo->exec("UPDATE transactions SET type = 'outflow' WHERE type = 'expense'");
    $pdo->exec("ALTER TABLE transactions MODIFY COLUMN type ENUM('inflow', 'outflow') NOT NULL DEFAULT 'inflow'");
    echo "Schema updated successfully";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
