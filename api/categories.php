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
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE user_id = ? OR user_id IS NULL ORDER BY name ASC");
    $stmt->execute([$user_id]);
    $result = ['success' => true, 'categories' => $stmt->fetchAll()];
    if (defined('TEST_MODE')) { 
        echo json_encode($result);
        return $result;
    }
    echo json_encode($result);
} elseif ($method === 'POST') {
    $raw_input = defined('TEST_MODE') && isset($GLOBALS['INPUT_DATA']) ? $GLOBALS['INPUT_DATA'] : file_get_contents('php://input');
    $data = json_decode($raw_input, true);
    $name = $data['name'] ?? '';
    if (!empty($name)) {
        do {
            $color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
            $check = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE color = ? AND (user_id = ? OR user_id IS NULL)");
            $check->execute([$color, $user_id]);
        } while ($check->fetchColumn() > 0);

        $stmt = $pdo->prepare("INSERT INTO categories (user_id, name, color) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $name, $color]);
        if (defined('TEST_MODE')) {
             echo json_encode(['success' => true]);
             return ['success' => true];
        }
        echo json_encode(['success' => true]);
    }
} elseif ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    if (defined('TEST_MODE')) {
        echo json_encode(['success' => true]);
        return ['success' => true];
    }
    echo json_encode(['success' => true]);
} elseif ($method === 'PUT') {
    $raw_input = defined('TEST_MODE') && isset($GLOBALS['INPUT_DATA']) ? $GLOBALS['INPUT_DATA'] : file_get_contents('php://input');
    $data = json_decode($raw_input, true);
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
