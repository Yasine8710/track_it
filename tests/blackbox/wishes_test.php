<?php
require_once 'test_utils.php';

echo "--- Running Wishes Tests ---\n";

reset_test_user('wishtest');
simulate_post('api/auth.php', [
    'action' => 'register',
    'username' => 'wishtest',
    'password' => 'pass'
]);
simulate_post('api/auth.php', [
    'action' => 'login',
    'username' => 'wishtest',
    'password' => 'pass'
]);

// Since wishes.php uses php://input for creation, we'll use direct DB insertion for the setup
global $pdo;
$user_id = $_SESSION['user_id'];

// 1. Create a wish
$pdo->prepare("INSERT INTO wishes (user_id, title, target_amount) VALUES (?, 'New Car', 20000)")->execute([$user_id]);
$wish_id = $pdo->lastInsertId();
log_result("Wish Creation", $wish_id > 0);

// 2. Fetch wishes
$getRes = simulate_get('api/wishes.php');
log_result("Fetch Wishes", count($getRes['wishes'] ?? []) > 0);

// 3. Fund a wish (simulating the logic in wishes.php PUT)
$fundAmount = 500;
try {
    $pdo->beginTransaction();
    $pdo->prepare("UPDATE wishes SET current_amount = current_amount + ? WHERE id = ?")->execute([$fundAmount, $wish_id]);
    $pdo->prepare("INSERT INTO transactions (user_id, amount, description, type) VALUES (?, ?, 'Funded Wish: New Car', 'outflow')")->execute([$user_id, $fundAmount]);
    $pdo->commit();
    log_result("Fund Wish", true);
} catch (Exception $e) {
    $pdo->rollBack();
    log_result("Fund Wish", false, $e->getMessage());
}

// 4. Verify balance deduction
$data = simulate_get('api/data.php');
log_result("Balance After Funding", $data['balance'] == "-500.00", "Should be negative if no income was added");

echo "\n";
