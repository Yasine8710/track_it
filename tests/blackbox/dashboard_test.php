<?php
require_once 'test_utils.php';

echo "--- Running Dashboard Tests ---\n";

reset_test_user('dashtest');
simulate_post('api/auth.php', [
    'action' => 'register',
    'username' => 'dashtest',
    'password' => 'pass'
]);
simulate_post('api/auth.php', [
    'action' => 'login',
    'username' => 'dashtest',
    'password' => 'pass'
]);

// Add some data
global $pdo;
$user_id = $_SESSION['user_id'];
$pdo->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, 500, 'inflow', 'Gift')")->execute([$user_id]);

// Get dashboard data
$dash = simulate_get('api/data.php');
log_result("Dashboard Balance", $dash['balance'] == "500.00");

echo "\n";
