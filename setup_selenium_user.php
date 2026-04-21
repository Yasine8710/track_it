<?php
// Define TEST_MODE as false to ensure db.php behaves normally
if (!defined('TEST_MODE')) define('TEST_MODE', false);
require_once 'includes/db.php';

// If TEST_MODE logic in db.php failed to set $pdo, let's try to set it manually if needed
// but db.php should set $pdo if TEST_MODE is not defined or false.
if (!isset($pdo)) {
    $host = 'localhost';
    $db   = 'trackit_db';
    $user = 'root';
    $pass = '';
    $charset = 'utf8mb4';
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $user, $pass, $options);
}

$username = 'testuser_selenium';
$password = 'password123';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    // Check if user already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        // Update password if user exists
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
        $stmt->execute([$hashed_password, $username]);
        echo "User 'testuser_selenium' updated successfully.\n";
    } else {
        // Insert new user
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
        $stmt->execute([$username, $hashed_password, 'selenium@test.com']);
        echo "User 'testuser_selenium' created successfully.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
