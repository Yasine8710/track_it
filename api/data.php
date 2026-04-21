<?php
if (session_status() === PHP_SESSION_NONE && !defined('TEST_MODE')) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';
if (!defined('TEST_MODE')) {
    header('Content-Type: application/json');
}

if (!isset($_SESSION['user_id'])) {
    if (!defined('TEST_MODE')) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    } else {
        return ['success' => false, 'message' => 'Unauthorized'];
    }
}

$user_id = $_SESSION['user_id'];

// Fetch Total Inflow
$st1 = $pdo->prepare("SELECT SUM(amount) as total FROM transactions WHERE user_id = ? AND type = 'inflow'");
$st1->execute([$user_id]);
$inflow = (float)($st1->fetch()['total'] ?? 0);

// Fetch Total Outflow
$st2 = $pdo->prepare("SELECT SUM(amount) as total FROM transactions WHERE user_id = ? AND type = 'outflow'");
$st2->execute([$user_id]);
$outflow = (float)($st2->fetch()['total'] ?? 0);

$balance = $inflow - $outflow;

if (!defined('TEST_MODE')) {
    echo json_encode([
        'success' => true,
        'balance' => number_format($balance, 2),
        'inflow' => number_format($inflow, 2),
        'outflow' => number_format($outflow, 2)
    ]);
} else {
    return [
        'success' => true,
        'balance' => number_format($balance, 2),
        'inflow' => number_format($inflow, 2),
        'outflow' => number_format($outflow, 2)
    ];
}
