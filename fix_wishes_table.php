<?php
require_once 'includes/db.php';
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS wishes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        target_amount DECIMAL(15, 2) NOT NULL,
        current_amount DECIMAL(15, 2) DEFAULT 0,
        status ENUM('active', 'completed') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    echo "Wishes table ensured!\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>