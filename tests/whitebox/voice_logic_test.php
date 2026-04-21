<?php
require_once 'whitebox_utils.php';

if (!defined('TEST_MODE')) define('TEST_MODE', true);

function run_voice_test($pdo, $input, $userId = 1) {
    global $mock_input;
    $mock_input = $input;
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['user_id'] = $userId;
    return include __DIR__ . '/../../api/process_voice.php';
}

echo "=== Running Voice Logic White-box Tests ===\n";

// Path 1: Silence
$pdo = new MockPDO();
$res = run_voice_test($pdo, ['transcript' => '']);
assertEquals('Silence detected.', $res['message'] ?? '', "Branch Coverage: Silence detected");

// Path 2: No amount found
$pdo = new MockPDO();
$res = run_voice_test($pdo, ['transcript' => 'hello world']);
assertEquals("I heard you, but couldn't find an amount.", $res['message'] ?? '', "Branch Coverage: No amount in transcript");

// Path 3: Best category matching vs Fallback
// Loop Coverage: Test with multiple categories
$pdo = new MockPDO();
$pdo->results = [
    // SELECT categories
    [
        ['id' => 1, 'name' => 'Food'],
        ['id' => 2, 'name' => 'Transport'],
        ['id' => 3, 'name' => 'Salary']
    ]
];
$res = run_voice_test($pdo, ['transcript' => 'spent 50 on transport']);
assertEquals(2, $pdo->queries[1]['params'][1] ?? null, "Logic: Matches exact category 'Transport'");

// Fallback logic (No match, use first)
$pdo = new MockPDO();
$pdo->results = [
    [['id' => 10, 'name' => 'Bills']]
];
$res = run_voice_test($pdo, ['transcript' => 'spent 20 on movies']);
assertEquals(10, $pdo->queries[1]['params'][1] ?? null, "Logic: Falls back to first category (ID 10) when no match");

// Path 4: Inflow detection (Loop keywords)
$pdo = new MockPDO();
$pdo->results = [[['id' => 1, 'name' => 'Salary']]];
$res = run_voice_test($pdo, ['transcript' => 'received 1000 for salary']);
assertEquals('inflow', $pdo->queries[1]['params'][3] ?? null, "Logic: Detects 'received' as inflow");

echo "Done.\n\n";

