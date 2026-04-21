<?php
require_once 'test_utils.php';

echo "--- Running Settings Tests ---\n";

reset_test_user('settest');
simulate_post('api/auth.php', [
    'action' => 'register',
    'username' => 'settest',
    'password' => 'pass'
]);
simulate_post('api/auth.php', [
    'action' => 'login',
    'username' => 'settest',
    'password' => 'pass'
]);

// Update profile
$updateData = [
    'username' => 'settest_updated',
    'email' => 'updated@test.com',
    'currency' => 'EUR',
    'full_name' => 'Updated Name',
    'bio' => 'New bio'
];

$result = simulate_post('api/save_settings.php', $updateData);
log_result("Update Profile", isset($result['success']) && $result['success'] === true, $result);

// Verify change in DB
global $pdo;
$stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
log_result("Verify Profile Change", $user['email'] === 'updated@test.com');

echo "\n";
