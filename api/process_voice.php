<?php
session_start();
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$transcript = strtolower($input['transcript'] ?? '');

if (empty($transcript)) {
    echo json_encode(['success' => false, 'message' => 'Silence detected.']);
    exit;
}

// 1. Extract Numeric Amount
$amount = 0;
if (preg_match('/(\d+(\.\d{1,2})?)/', $transcript, $matches)) {
    $amount = (float)$matches[1];
}

if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => "I heard you, but couldn't find an amount."]);
    exit;
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
    echo json_encode(['success' => true, 'message' => "Logged $type of $$amount"]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
