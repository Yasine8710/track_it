<?php
require_once 'test_utils.php';

echo "--- Running Auth Tests ---\n";

reset_test_user('authtest');

// 1. Test Registration
$regData = [
    'action' => 'register',
    'username' => 'authtest',
    'password' => 'password123',
    'full_name' => 'Auth Test User',
    'email' => 'auth@test.com'
];

$regResult = simulate_post('api/auth.php', $regData);
log_result("User Registration", $regResult['success'] === true, $regResult['message'] ?? '');

// 2. Test Login
$loginData = [
    'action' => 'login',
    'username' => 'authtest',
    'password' => 'password123'
];

$loginResult = simulate_post('api/auth.php', $loginData);
log_result("User Login", $loginResult['success'] === true, $loginResult['message'] ?? '');

// 3. Test Invalid Credentials
$failData = [
    'action' => 'login',
    'username' => 'authtest',
    'password' => 'wrongpass'
];
$failResult = simulate_post('api/auth.php', $failData);
log_result("Invalid Login", $failResult['success'] === false, "Should fail with wrong password");

echo "\n";
