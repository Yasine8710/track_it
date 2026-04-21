<?php
if (!defined('TEST_MODE')) {
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

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
    } catch (\PDOException $e) {
        // Log error or display message
    }
} else {
    // In TEST_MODE, we expect $pdo to be injected or mocked
    global $pdo_mock;
    if (isset($pdo_mock)) {
        $pdo = $pdo_mock;
    }
}
