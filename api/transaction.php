<?php
session_start();
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $id = $_GET['id'] ?? null;
    $stmt = $pdo->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    echo json_encode(['success' => true]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;
    $amount =floatval($data['amount'] ?? 0);
    
    if ($id && $amount > 0) {
        $stmt = $pdo->prepare("UPDATE transactions SET amount = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$amount, $id, $_SESSION['user_id']]);
        echo json_encode(['success' => true]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $amount = $data['amount'] ?? 0;
    $type = $data['type'] ?? 'outflow';
    $category_id = $data['category_id'] ?? null;
    $description = $data['description'] ?? '';
    $date = $data['date'] ?? date('Y-m-d H:i:s');

    if ($amount > 0) {
        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, amount, type, category_id, description, transaction_date) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $amount, $type, $category_id, $description, $date]);
        
        // Update pet streak
        $pdo->exec("UPDATE user_pets SET streak_count = streak_count + 1, last_updated = CURDATE() WHERE user_id = {$_SESSION['user_id']}");
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid amount']);
    }
}
