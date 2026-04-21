<?php
require_once 'whitebox_utils.php';

if (!defined('TEST_MODE')) define('TEST_MODE', true);

function run_data_test($pdo) {
    global $pdo_mock;
    $pdo_mock = $pdo;
    $_SESSION['user_id'] = 1;

    return include __DIR__ . '/../../api/data.php';
}

echo "=== Running Data Logic White-box Tests ===\n";

// 1. All zero
$pdo = new MockPDO();
$pdo->results = [['total' => 0], ['total' => 0]];
$res = run_data_test($pdo);
assertEquals('0.00', $res['balance'], "Condition Coverage: Zero balance");
assertEquals('0.00', $res['inflow'], "Condition Coverage: Zero inflow");
assertEquals('0.00', $res['outflow'], "Condition Coverage: Zero outflow");

// 2. Positive balance
$pdo = new MockPDO();
$pdo->results = [['total' => 1000.50], ['total' => 450.25]];
$res = run_data_test($pdo);
assertEquals('550.25', $res['balance'], "Decision Coverage: Positive balance calculation");
assertEquals('1,000.50', $res['inflow'], "Statement Coverage: Inflow formatted");

// 3. Negative balance
$pdo = new MockPDO();
$pdo->results = [['total' => 100], ['total' => 250]];
$res = run_data_test($pdo);
assertEquals('-150.00', $res['balance'], "Path Coverage: Negative balance calculation");

echo "Done.\n\n";
