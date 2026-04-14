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
        do {
            $color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
            $check = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE color = ? AND (user_id = ? OR user_id IS NULL)");
            $check->execute([$color, $user_id]);
        } while ($check->fetchColumn() > 0);

        $stmt = $pdo->prepare("INSERT INTO categories (user_id, name, color) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $name, $color]);
        echo json_encode(['success' => true]);
    }
} elseif ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    echo json_encode(['success' => true]);
} elseif ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;
    $name = $data['name'] ?? null;
    $color = $data['color'] ?? null;
    if ($id && ($name !== null || $color !== null)) {
        if ($name !== null && $color !== null) {
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, color = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$name, $color, $id, $user_id]);
        } elseif ($name !== null) {
            $stmt = $pdo->prepare("UPDATE categories SET name = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$name, $id, $user_id]);
        } elseif ($color !== null) {
            $stmt = $pdo->prepare("UPDATE categories SET color = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$color, $id, $user_id]);
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
}
