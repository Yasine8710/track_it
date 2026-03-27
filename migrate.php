<?php
require_once 'includes/db.php';

try {
    // Add type column to categories if it doesn't exist
    $pdo->exec("
        ALTER TABLE categories 
        ADD COLUMN type ENUM('income', 'expense') NOT NULL DEFAULT 'expense';
    ");
    echo "Added 'type' column to categories table.<br>";

    // Add some default income categories for existing users? 
    // Or just let them add. But maybe good to have defaults.
    // For now, let's just update the schema.

} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "Column 'type' already exists.<br>";
    } else {
        echo "Error updating table: " . $e->getMessage() . "<br>";
    }
}

echo "Migration complete. Please delete this file.";
?>