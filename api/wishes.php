<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->prepare("SELECT * FROM wishes WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    echo json_encode(['success' => true, 'wishes' => $stmt->fetchAll()]);
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $title = trim($data['title'] ?? '');
    $target = floatval($data['target_amount'] ?? 0);
    
    if ($title !== '' && $target > 0) {
        $stmt = $pdo->prepare("INSERT INTO wishes (user_id, title, target_amount) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $title, $target]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
    }
} elseif ($method === 'PUT') {
    // Add funds to a wish
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;
    $amount = floatval($data['amount'] ?? 0);
    
    if ($id && $amount > 0) {
        $stmt = $pdo->prepare("UPDATE wishes SET current_amount = current_amount + ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$amount, $id, $user_id]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid update']);
    }
} elseif ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if ($id) {
        $stmt = $pdo->prepare("DELETE FROM wishes WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        echo json_encode(['success' => true]);
    }
}
?>
