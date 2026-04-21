<?php
require_once 'whitebox_utils.php';

if (!defined('TEST_MODE')) define('TEST_MODE', true);

function run_wishes_test($pdo, $method, $input = [], $get = [], $userId = 1) {
    global $mock_method, $mock_input, $mock_get;
    $mock_method = $method;
    $mock_input = $input;
    $mock_get = $get;
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['user_id'] = $userId;
    return include __DIR__ . '/../../api/wishes.php';
}

echo "=== Running Wishes Logic White-box Tests ===\n";

// 1. Condition Coverage: $title !== '' && $target > 0
// T/T
$pdo = new MockPDO();
$res = run_wishes_test($pdo, 'POST', ['title' => 'Car', 'target_amount' => 1000]);
assertTrue($res['success'], "Condition Coverage (T/T): Valid title and amount");

// T/F
$pdo = new MockPDO();
$res = run_wishes_test($pdo, 'POST', ['title' => 'Car', 'target_amount' => 0]);
assertEquals('Invalid data', $res['message'], "Condition Coverage (T/F): Valid title, zero amount");

// F/T
$pdo = new MockPDO();
$res = run_wishes_test($pdo, 'POST', ['title' => '', 'target_amount' => 1000]);
assertEquals('Invalid data', $res['message'], "Condition Coverage (F/T): Empty title, valid amount");

// 2. Loop Coverage / Transaction check in PUT
$pdo = new MockPDO();
$pdo->results = [
    ['Car'] // fetchColumn result for title
];
$res = run_wishes_test($pdo, 'PUT', ['id' => 5, 'amount' => 100]);
assertTrue($res['success'], "Path Coverage: Successfully funded wish");
assertTrue(count($pdo->queries) >= 3, "Operation Coverage: Multiple steps in funding (Update wish, Select title, Insert transaction)");

// 3. DELETE branch
$pdo = new MockPDO();
$res = run_wishes_test($pdo, 'DELETE', [], ['id' => 10]);
assertTrue($res['success'], "Branch Coverage: Delete wish");

echo "Done.\n\n";
