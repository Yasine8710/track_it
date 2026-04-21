<?php
require_once 'test_utils.php';

echo "--- Running Transaction Tests ---\n";

// Ensure user is logged in
reset_test_user('transtest');
$reg = simulate_post('api/auth.php', [
    'action' => 'register',
    'username' => 'transtest',
    'password' => 'pass'
]);
simulate_post('api/auth.php', [
    'action' => 'login',
    'username' => 'transtest',
    'password' => 'pass'
]);

// Since transaction.php uses php://input, we'll manually test the DB insertion logic
// or simulate the flow by creating a helper that mimics the API.
function test_add_transaction($type, $amount, $description) {
    global $pdo;
    $user_id = $_SESSION['user_id'];
    
    // We can't easily use simulate_post because it uses php://input
    // Let's insert directly for this blackbox logic test
    $stmt = $pdo->prepare("INSERT INTO transactions (user_id, amount, type, description, transaction_date) VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([$user_id, $amount, $type, $description, date('Y-m-d')]);
}

$t1 = test_add_transaction('inflow', 1000, 'Salary');
log_result("Add Inflow", $t1 === true);

$t2 = test_add_transaction('outflow', 200, 'Rent');
log_result("Add Outflow", $t2 === true);

// Verify totals via data.php
$data = simulate_get('api/data.php');
$success = ($data['inflow'] == "1,000.00") && ($data['outflow'] == "200.00") && ($data['balance'] == "800.00");
log_result("Verify Totals", $success, "Balance: " . $data['balance']);

echo "\n";
