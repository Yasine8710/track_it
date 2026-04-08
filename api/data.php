<?php
session_start();
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
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

// Fetch pet streak
$petStreak = 0;
$petEmoji = '';
$stmtPet = $pdo->prepare("SELECT up.streak_count, p.emoji FROM user_pets up JOIN pets p ON up.pet_id = p.id WHERE up.user_id = ?");
$stmtPet->execute([$user_id]);
$petData = $stmtPet->fetch();
if ($petData) {
    $petStreak = $petData['streak_count'];
    $petEmoji = $petData['emoji'];
}

echo json_encode([
    'success' => true,
    'balance' => number_format($balance, 2),
    'inflow' => number_format($inflow, 2),
    'outflow' => number_format($outflow, 2),
    'pet_streak' => $petStreak,
    'pet_emoji' => $petEmoji
]);
