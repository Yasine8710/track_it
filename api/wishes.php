<?php
if (session_status() === PHP_SESSION_NONE && !defined('TEST_MODE')) {
    session_start();
}
if (!defined('TEST_MODE')) {
    require_once '../includes/db.php';
}

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

$user_id = $_SESSION['user_id'] ?? 0;
if (!defined('TEST_MODE')) {
    $method = $_SERVER['REQUEST_METHOD'];
} else {
    $method = $GLOBALS['mock_method'] ?? $_SERVER['REQUEST_METHOD'] ?? 'GET';
}

if ($method === 'GET') {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM wishes WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    if (!defined('TEST_MODE')) {
        echo json_encode(['success' => true, 'wishes' => $stmt->fetchAll()]);
    } else {
        return ['success' => true, 'wishes' => $stmt->fetchAll()];
    }
} elseif ($method === 'POST') {
    global $pdo;
    $user_id = $_SESSION['user_id'];
    if (!defined('TEST_MODE')) {
        $data = json_decode(file_get_contents('php://input'), true);
    } else {
        $data = $GLOBALS['mock_input'] ?? [];
    }
    $title = trim($data['title'] ?? '');
    $target = floatval($data['target_amount'] ?? 0);
    
    if ($title !== '' && $target > 0) {
        $stmt = $pdo->prepare("INSERT INTO wishes (user_id, title, target_amount) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $title, $target]);
        if (!defined('TEST_MODE')) {
            echo json_encode(['success' => true]);
        } else {
            return ['success' => true];
        }
    } else {
        if (!defined('TEST_MODE')) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
        } else {
            return ['success' => false, 'message' => 'Invalid data'];
        }
    }
} elseif ($method === 'PUT') {
    global $pdo;
    $user_id = $_SESSION['user_id'];
    // Add funds to a wish
    if (!defined('TEST_MODE')) {
        $data = json_decode(file_get_contents('php://input'), true);
    } else {
        $data = $GLOBALS['mock_input'] ?? [];
    }
    $id = $data['id'] ?? null;
    $amount = floatval($data['amount'] ?? 0);
    
    if ($id && $amount > 0) {
        global $pdo;
        try {
            if ($pdo instanceof PDO) {
                $pdo->beginTransaction();
            } else {
                return ['success' => false, 'message' => 'PDO instance not found: ' . gettype($pdo)];
            }
            // Update wish amount
            $stmt = $pdo->prepare("UPDATE wishes SET current_amount = current_amount + ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$amount, $id, $user_id]);
            
            // Get wish info for context
            $stmtWish = $pdo->prepare("SELECT title FROM wishes WHERE id = ?");
            $stmtWish->execute([$id]);
            $wishTitle = $stmtWish->fetchColumn() ?: "Wish";
            
            // Create corresponding expense transaction to deduct from balance implicitly
            $stmtTx = $pdo->prepare("INSERT INTO transactions (user_id, amount, description, type, transaction_date) VALUES (?, ?, ?, 'outflow', CURRENT_DATE())");
            $stmtTx->execute([$user_id, $amount, "Funded Wish: " . $wishTitle]);
            
            $pdo->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $pdo->rollBack();
            if (!defined('TEST_MODE')) {
                echo json_encode(['success' => false, 'message' => 'Failed to fund wish']);
            } else {
                return ['success' => false, 'message' => 'Failed to fund wish'];
            }
        }
    } else {
        $msg = "Invalid data in PUT: id=" . ($id ?? 'null') . ", amount=" . ($amount ?? 'null');
        if (!defined('TEST_MODE')) {
            echo json_encode(['success' => false, 'message' => $msg]);
        } else {
            return ['success' => false, 'message' => $msg];
        }
    }
} elseif ($method === 'DELETE') {
    $id = $_GET['id'] ?? ($mock_get['id'] ?? null);
    if ($id) {
        $stmt = $pdo->prepare("DELETE FROM wishes WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        if (!defined('TEST_MODE')) {
            echo json_encode(['success' => true]);
        } else {
            return ['success' => true];
        }
    }
}
?>
