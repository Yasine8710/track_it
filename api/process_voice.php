<?php
if (session_status() === PHP_SESSION_NONE) {
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

$user_id = $_SESSION['user_id'];

if (!defined('TEST_MODE')) {
    $input = json_decode(file_get_contents('php://input'), true);
} else {
    $input = $mock_input ?? [];
}
$transcript = strtolower($input['transcript'] ?? '');

if (empty($transcript)) {
    if (!defined('TEST_MODE')) {
        echo json_encode(['success' => false, 'message' => 'Silence detected.']);
        exit;
    } else {
        return ['success' => false, 'message' => 'Silence detected.'];
    }
}

// 1. Extract Numeric Amount
$amount = 0;
if (preg_match('/(\d+(\.\d{1,2})?)/', $transcript, $matches)) {
    $amount = (float)$matches[1];
}

if ($amount <= 0) {
    if (!defined('TEST_MODE')) {
        echo json_encode(['success' => false, 'message' => "I heard you, but couldn't find an amount."]);
        exit;
    } else {
        return ['success' => false, 'message' => "I heard you, but couldn't find an amount."];
    }
}

// 2. Identify Intent (Inflow vs Outflow)
$type = 'outflow';
$incomeKeywords = ['salary', 'received', 'earned', 'won', 'found', 'gift', 'deposit', 'income', 'plus', 'add'];
foreach ($incomeKeywords as $kw) {
    if (strpos($transcript, $kw) !== false) {
        $type = 'inflow';
        break;
    }
}

// 3. Match Category
$stmt = $pdo->prepare("SELECT id, name FROM categories WHERE user_id = ? OR user_id IS NULL");
$stmt->execute([$user_id]);
$categories = $stmt->fetchAll();

$bestCategoryId = null;
foreach ($categories as $cat) {
    if (strpos($transcript, strtolower($cat['name'])) !== false) {
        $bestCategoryId = $cat['id'];
        break;
    }
}

// Default to "Other" or first available if no match
if (!$bestCategoryId && !empty($categories)) {
    $bestCategoryId = $categories[0]['id'];
}

// 4. Save Transaction
try {
    $stmt = $pdo->prepare("INSERT INTO transactions (user_id, category_id, amount, type, description, transaction_date) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$user_id, $bestCategoryId, $amount, $type, "Voice: " . $transcript]);
    if (!defined('TEST_MODE')) {
        echo json_encode(['success' => true, 'message' => "Logged $type of $$amount"]);
    } else {
        return ['success' => true, 'message' => "Logged $type of $$amount"];
    }
} catch (Exception $e) {
    if (!defined('TEST_MODE')) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } else {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
