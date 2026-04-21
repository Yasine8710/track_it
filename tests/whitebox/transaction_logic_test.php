<?php
require_once 'whitebox_utils.php';

if (!defined('TEST_MODE')) define('TEST_MODE', true);

function run_transaction_test($pdo, $method, $data = [], $queryParams = []) {
    global $pdo_mock;
    $pdo_mock = $pdo;
    $_SESSION['user_id'] = 1;
    $_SERVER['REQUEST_METHOD'] = $method;
    $_GET = $queryParams;
    
    if ($method === 'POST' || $method === 'PUT') {
        $GLOBALS['INPUT_DATA'] = json_encode($data);
    }

    ob_start();
    include __DIR__ . '/../../api/transaction.php';
    $output = ob_get_clean();
    // Some routes might not return JSON or might exit, but we try to decode
    return json_decode($output, true);
}

echo "=== Running Transaction Logic White-box Tests ===\n";

// 1. DELETE Transaction
$pdo = new MockPDO();
$res = run_transaction_test($pdo, 'DELETE', [], ['id' => 99]);
assertTrue($res['success'], "Decision Coverage: DELETE transaction");
assertEquals('DELETE FROM transactions WHERE id = ? AND user_id = ?', $pdo->queries[0]['query'], "Statement Coverage: Delete SQL");

// 2. PUT Transaction (Success)
$pdo = new MockPDO();
$res = run_transaction_test($pdo, 'PUT', ['id' => 99, 'amount' => 50.5]);
assertTrue($res['success'], "Decision Coverage: PUT transaction success");

// 3. PUT Transaction (Invalid Amount)
$pdo = new MockPDO();
$res = run_transaction_test($pdo, 'PUT', ['id' => 99, 'amount' => -10]);
// The code says if ($id && $amount > 0), so it should skip and not output success:true
assertTrue(!isset($res['success']) || !$res['success'], "Branch Coverage: PUT negative amount rejected");

// 4. POST Transaction (Success)
$pdo = new MockPDO();
$res = run_transaction_test($pdo, 'POST', ['amount' => 100, 'type' => 'outflow', 'category_id' => 1]);
assertTrue($res['success'], "Path Coverage: POST transaction success");

// 5. POST Transaction (Invalid Amount)
$pdo = new MockPDO();
$res = run_transaction_test($pdo, 'POST', ['amount' => 0]);
assertTrue(!isset($res['success']) || !$res['success'], "Branch Coverage: POST zero amount rejected");

echo "Done.\n\n";
