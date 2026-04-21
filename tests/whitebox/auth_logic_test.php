<?php
require_once 'whitebox_utils.php';

if (!defined('TEST_MODE')) define('TEST_MODE', true);

function run_auth_test($pdo, $postData) {
    $_POST = $postData;
    return include __DIR__ . '/../../api/auth.php';
}

echo "=== Running Auth Logic White-box Tests ===\n";

// Register: Username taken branch
$pdo = new MockPDO();
$pdo->results = [['id' => 1]]; // simulate user exists
$res = run_auth_test($pdo, ['action' => 'register', 'username' => 'exists', 'password' => '123']);
assertEquals('Username already taken', $res['message'] ?? '', "Branch Coverage: Registration - username exists");

// Register: Success + Loop Coverage (Defaults)
$pdo = new MockPDO();
$pdo->results = []; // No user exists
$pdo->lastInsertId = 55;
$res = run_auth_test($pdo, ['action' => 'register', 'username' => 'newuser', 'password' => 'pass', 'full_name' => 'New User']);
assertTrue($res['success'] ?? false, "Path Coverage: Successful registration");
// Verify loop iterations (5 default categories)
$insertCount = 0;
foreach($pdo->queries as $q) {
    if (strpos($q['query'], 'INSERT INTO categories') !== false) $insertCount++;
}
assertEquals(5, $insertCount, "Loop Coverage: Inserted 5 default categories");

// Login: Success & Fail Paths
$pdo = new MockPDO();
$pdo->results = [['id' => 1, 'username' => 'user1', 'password' => password_hash('correct', PASSWORD_DEFAULT)]];
$res = run_auth_test($pdo, ['action' => 'login', 'username' => 'user1', 'password' => 'wrong']);
assertEquals('Invalid credentials', $res['message'] ?? '', "Path Coverage: Login - wrong password");

$pdo = new MockPDO();
$pdo->results = [['id' => 1, 'username' => 'user1', 'password' => password_hash('correct', PASSWORD_DEFAULT)]];
$res = run_auth_test($pdo, ['action' => 'login', 'username' => 'user1', 'password' => 'correct']);
assertTrue($res['success'] ?? false, "Path Coverage: Login - success");

echo "Done.\n\n";

