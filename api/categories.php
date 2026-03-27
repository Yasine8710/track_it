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
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE user_id = ? OR user_id IS NULL ORDER BY name ASC");
    $stmt->execute([$user_id]);
    echo json_encode(['success' => true, 'categories' => $stmt->fetchAll()]);
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $name = $data['name'] ?? '';
    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO categories (user_id, name, color) VALUES (?, ?, ?)");
        $color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
        $stmt->execute([$user_id, $name, $color]);
        echo json_encode(['success' => true]);
    }
} elseif ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    echo json_encode(['success' => true]);
}
