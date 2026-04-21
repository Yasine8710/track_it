<?php
require_once 'includes/db.php';

try {
    // Check tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables in database:\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }

    if (in_array('users', $tables)) {
        echo "\nStructure for 'users':\n";
        $columns = $pdo->query("DESCRIBE users")->fetchAll();
        foreach ($columns as $col) {
            echo "{$col['Field']} - {$col['Type']} - {$col['Null']} - {$col['Key']} - {$col['Default']}\n";
        }
    } else {
        echo "\n'users' table NOT found.\n";
    }

    if (in_array('categories', $tables)) {
        echo "\nStructure for 'categories':\n";
        $columns = $pdo->query("DESCRIBE categories")->fetchAll();
        foreach ($columns as $col) {
            echo "{$col['Field']} - {$col['Type']} - {$col['Null']} - {$col['Key']} - {$col['Default']}\n";
        }
    } else {
        echo "\n'categories' table NOT found.\n";
    }

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}
