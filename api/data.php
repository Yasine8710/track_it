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
$petLevel = 1;
$petStage = 'egg';
$petXp = 0;
$petType = 'cat';
$stmtPet = $pdo->prepare("SELECT streak_count, level, evolution_stage, xp, pet_type FROM user_pets WHERE user_id = ?");
$stmtPet->execute([$user_id]);
$petData = $stmtPet->fetch();
if ($petData) {
    $petStreak = $petData['streak_count'];
    $petLevel = $petData['level'];
    $petStage = $petData['evolution_stage'];
    $petXp = $petData['xp'];
    $petType = $petData['pet_type'];
}

echo json_encode([
    'success' => true,
    'balance' => number_format($balance, 2),
    'inflow' => number_format($inflow, 2),
    'outflow' => number_format($outflow, 2),
    'pet_streak' => $petStreak,
    'pet_level' => $petLevel,
    'pet_stage' => $petStage,
    'pet_xp' => $petXp,
    'pet_type' => $petType
]);
