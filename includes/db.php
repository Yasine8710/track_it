<?php
$host = 'localhost';
$dbname = 'trackit_db';
$username = 'root';
$password = ''; // Default XAMPP password is empty

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Patch to add profile_picture if missing
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) DEFAULT NULL");
    } catch (Exception $e) {}

} catch (PDOException $e) {
    // Attempt to create database if it doesn't exist (first run helper)
    try {
        $pdo = new PDO("mysql:host=$host", $username, $password);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
        $pdo->exec("USE `$dbname`");
        // Re-connect to the specific DB
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $ex) {
        die("Connection failed: " . $ex->getMessage());
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function jsonResponse($success, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}
?>