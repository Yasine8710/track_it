<?php
require_once 'includes/db.php';
try {
    echo "--- TABLES ---\n";
    $stmt = $pdo->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) { echo $row[0] . "\n"; }
    
    echo "\n--- USERS SCHEMA ---\n";
    $stmt = $pdo->query("DESCRIBE users");
    while ($row = $stmt->fetch()) { echo $row['Field'] . " (" . $row['Type'] . ")\n"; }
    
    echo "\n--- CATEGORIES COUNT ---\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM categories");
    echo $stmt->fetchColumn() . " categories found.\n";
    
} catch (Exception $e) { echo "ERROR: " . $e->getMessage(); }
?>