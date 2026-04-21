<?php
require_once 'whitebox_utils.php';

if (!defined('TEST_MODE')) define('TEST_MODE', true);

function run_history_test($pdo) {
    global $pdo_mock;
    $pdo_mock = $pdo;
    $_SESSION['user_id'] = 1;

    ob_start();
    include __DIR__ . '/../../api/history.php';
    $output = ob_get_clean();
    return json_decode($output, true);
}

echo "=== Running History Logic White-box Tests ===\n";

// 1. Fetch History (Success)
$pdo = new MockPDO();
$pdo->results = [[
    ['id' => 1, 'amount' => 100, 'type' => 'inflow', 'category_name' => 'Salary'],
    ['id' => 2, 'amount' => 50, 'type' => 'outflow', 'category_name' => 'Food']
]];
$res = run_history_test($pdo);
assertTrue($res['success'], "Decision Coverage: History retrieval success");
assertEquals(2, count($res['data']), "Statement Coverage: Correct number of transactions returned");

// 2. Database Error Path
$pdo = new MockPDO();
// To simulate exception, we might need to modify MockPDO or just force a fail.
// MockPDO prepare currently always returns a statement.
// Let's mock a failure by having execute return false or similar if we want to test catch block.
// But api/history.php catches PDOException.
class FailingMockPDO extends MockPDO {
    public function prepare($query, $options = []): PDOStatement|false {
        throw new PDOException("DB Error");
    }
}
$pdo = new FailingMockPDO();
$res = run_history_test($pdo);
assertTrue(!$res['success'], "Path Coverage: Handling DB Exception");
assertEquals("DB Error", $res['message'], "Statement Coverage: Error message captured");

echo "Done.\n\n";
