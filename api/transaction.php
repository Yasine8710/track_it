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
    $date = $data['date'] ?? date('Y-m-d');

    if ($amount > 0) {
        try {
            $stmt = $pdo->prepare("INSERT INTO transactions (user_id, amount, type, category_id, description, transaction_date) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $amount, $type, $category_id, $description, $date]);

            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid amount']);
    }
}
