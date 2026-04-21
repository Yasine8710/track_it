<?php
require_once 'includes/db.php';

try {
    $pdo->exec("ALTER TABLE users 
        ADD COLUMN IF NOT EXISTS full_name VARCHAR(100) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS phone VARCHAR(20) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS address TEXT DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS bio TEXT DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS location VARCHAR(100) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS notification_pref ENUM('all', 'minimal', 'off') DEFAULT 'all'");
    
    echo "Database schema updated successfully!\n";
} catch (PDOException $e) {
    echo "Error updating database: " . $e->getMessage() . "\n";
}
?>