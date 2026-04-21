<?php
require_once 'whitebox_utils.php';

if (!defined('TEST_MODE')) define('TEST_MODE', true);

function run_categories_test($pdo, $method, $data = [], $queryParams = []) {
    global $pdo_mock;
    $pdo_mock = $pdo;
    $_SESSION['user_id'] = 1;
    $_SERVER['REQUEST_METHOD'] = $method;
    $_GET = $queryParams;
    
    if ($method === 'POST' || $method === 'PUT') {
        $GLOBALS['INPUT_DATA'] = json_encode($data);
    }

    ob_start();
    include __DIR__ . '/../../api/categories.php';
    $output = ob_get_clean();
    return json_decode($output, true);
}

echo "=== Running Categories Logic White-box Tests ===\n";

// 1. GET Categories
$pdo = new MockPDO();
$pdo->results = [[['id' => 1, 'name' => 'Food'], ['id' => 2, 'name' => 'Rent']]];
$res = run_categories_test($pdo, 'GET');
assertTrue($res['success'] ?? false, "Decision Coverage: GET success");
assertEquals(2, count($res['categories'] ?? []), "Statement Coverage: GET returns categories");

// 2. POST Category (Success)
$pdo = new MockPDO();
$pdo->results = [0]; 
$res = run_categories_test($pdo, 'POST', ['name' => 'New Cat']);
assertTrue($res['success'] ?? false, "Path Coverage: POST category success");
$lastQuery = end($pdo->queries);
assertEquals('INSERT INTO categories (user_id, name, color) VALUES (?, ?, ?)', $lastQuery['query'], "Statement Coverage: Insert query called");

// 3. POST Category (Loop Coverage: Color Collisions)
$pdo = new MockPDO();
$pdo->results = [1, 1, 0]; 
$res = run_categories_test($pdo, 'POST', ['name' => 'Retry Color']);
assertTrue($res['success'] ?? false, "Loop Coverage: POST category handles color collisions");
$checkCount = 0;
foreach($pdo->queries as $q) {
    if (strpos($q['query'], 'SELECT COUNT(*)') !== false) $checkCount++;
}
assertEquals(3, $checkCount, "Loop Coverage: Checked color 3 times");

// 4. DELETE Category
$pdo = new MockPDO();
$res = run_categories_test($pdo, 'DELETE', [], ['id' => 123]);
assertTrue($res['success'] ?? false, "Decision Coverage: DELETE success");
assertEquals('DELETE FROM categories WHERE id = ? AND user_id = ?', $pdo->queries[0]['query'], "Statement Coverage: Delete query called");

echo "Done.\n\n";
