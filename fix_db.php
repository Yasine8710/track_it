<?php
require 'includes/db.php';
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) DEFAULT NULL");
    echo "Column added successfully.\n";
} catch (PDOException $e) {
    // 1060 is "Duplicate column name", which means it already exists
    if ($e->getCode() == "42S21") {
        echo "Column already exists.\n";
    } else {
        echo "Database Error: " . $e->getMessage() . "\n";
    }
}
?>