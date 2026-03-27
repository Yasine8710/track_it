<?php
require 'includes/db.php';
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) DEFAULT NULL");
    echo "Column added successfully";
} catch (PDOException $e) {
    if ($e->getCode() == "42S21") {
        echo "Column already exists";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>