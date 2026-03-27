<?php
require_once 'includes/db.php';
try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->exec("TRUNCATE TABLE transactions;");
    $pdo->exec("DELETE FROM categories WHERE user_id IS NOT NULL;");
    $pdo->exec("UPDATE users SET balance = 0.00;");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    echo "DATA_RESET_SUCCESSFUL";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>