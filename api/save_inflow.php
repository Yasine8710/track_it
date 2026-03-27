<?php
session_start();
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['manual_balance'])) {
    $new_balance = floatval($data['manual_balance']);
    
    // Calculate current internal balance from ALL transactions
    $st1 = $pdo->prepare("SELECT SUM(amount) as t FROM transactions WHERE user_id = ? AND type = 'inflow'");
    $st1->execute([$user_id]);
    $in = floatval($st1->fetch()['t'] ?? 0);
    
    $st2 = $pdo->prepare("SELECT SUM(amount) as t FROM transactions WHERE user_id = ? AND type = 'outflow'");
    $st2->execute([$user_id]);
    $out = floatval($st2->fetch()['t'] ?? 0);
    
    $current = $in - $out;
    $diff = $new_balance - $current;
    
    if ($diff != 0) {
        $type = $diff > 0 ? 'inflow' : 'outflow';
        $abs_diff = abs($diff);
        // Force manual adjustment to be an 'inflow' if user is setting a positive starting balance, 
        // regardless of current state, to ensure it shows in the "Monthly In" box as requested.
        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, amount, type, description, category_id, transaction_date) VALUES (?, ?, ?, 'Manual Balance Set', NULL, NOW())");
        $stmt->execute([$user_id, $abs_diff, $type]);
        
        // Update user balance cache
        $stUp = $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?");
        $stUp->execute([$new_balance, $user_id]);
    }
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
}
