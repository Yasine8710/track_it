<?php
if (session_status() === PHP_SESSION_NONE && !defined('TEST_MODE')) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';
if (!defined('TEST_MODE')) {
    header('Content-Type: application/json');
}

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    if (!defined('TEST_MODE')) {
        echo json_encode(['success' => false]);
        exit;
    } else {
        $result = ['success' => false];
        echo json_encode($result);
        return $result;
    }
}

global $pdo;

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $id = $_GET['id'] ?? null;
    $stmt = $pdo->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $result = ['success' => true];
    echo json_encode($result);
    if (!defined('TEST_MODE')) {
        exit;
    } else {
        return $result;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $raw_input = defined('TEST_MODE') && isset($GLOBALS['INPUT_DATA']) ? $GLOBALS['INPUT_DATA'] : file_get_contents('php://input');
    $data = json_decode($raw_input, true);
    $id = $data['id'] ?? null;
    $amount =floatval($data['amount'] ?? 0);
    
    if ($id && $amount > 0) {
        $stmt = $pdo->prepare("UPDATE transactions SET amount = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$amount, $id, $_SESSION['user_id']]);
        $result = ['success' => true];
        echo json_encode($result);
        if (!defined('TEST_MODE')) {
            exit;
        } else {
            return $result;
        }
    } else {
        $result = ['success' => false];
        echo json_encode($result);
        if (defined('TEST_MODE')) return $result;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw_input = defined('TEST_MODE') && isset($GLOBALS['INPUT_DATA']) ? $GLOBALS['INPUT_DATA'] : file_get_contents('php://input');
    $data = json_decode($raw_input, true);
    $amount = $data['amount'] ?? 0;
    $type = $data['type'] ?? 'outflow';
    $category_id = $data['category_id'] ?? null;
    $description = $data['description'] ?? '';
    $date = $data['date'] ?? date('Y-m-d');

    if ($amount > 0) {
        try {
            $stmt = $pdo->prepare("INSERT INTO transactions (user_id, amount, type, category_id, description, transaction_date) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $amount, $type, $category_id, $description, $date]);

            $result = ['success' => true];
            echo json_encode($result);
            if (!defined('TEST_MODE')) {
                exit;
            } else {
                return $result;
            }
        } catch (Exception $e) {
            $result = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
            echo json_encode($result);
            if (!defined('TEST_MODE')) {
                exit;
            } else {
                return $result;
            }
        }
    } else {
        $result = ['success' => false, 'message' => 'Invalid amount'];
        echo json_encode($result);
        if (!defined('TEST_MODE')) {
            exit;
        } else {
            return $result;
        }
    }
}
