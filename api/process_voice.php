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

$split = false;
if ($type === 'inflow' && (strpos($transcript, 'split') !== false || strpos($transcript, 'divide') !== false)) {
    $split = true;
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
    if ($type === 'outflow') {
        $stIn = $pdo->prepare("SELECT SUM(amount) as t FROM transactions WHERE user_id = ? AND type = 'inflow'");
        $stIn->execute([$user_id]);
        $in = floatval($stIn->fetch()['t'] ?? 0);

        $stOut = $pdo->prepare("SELECT SUM(amount) as t FROM transactions WHERE user_id = ? AND type = 'outflow'");
        $stOut->execute([$user_id]);
        $out = floatval($stOut->fetch()['t'] ?? 0);

        if ($amount > ($in - $out)) {
            echo json_encode(['success' => false, 'message' => "Cannot log $$amount expense. You only have $" . number_format($in - $out, 2) . " left."]);
            exit;
        }
    }

    if ($type === 'inflow' && $split) {
        $stmt = $pdo->prepare("SELECT id, name, percentage FROM categories WHERE (user_id = ? OR user_id IS NULL) AND percentage > 0");
        $stmt->execute([$user_id]);
        $categoriesByPercent = $stmt->fetchAll();
        
        $totalAssignedPercent = 0;
        foreach ($categoriesByPercent as $cat) {
            $totalAssignedPercent += $cat['percentage'];
        }
        
        if ($totalAssignedPercent > 0) {
            foreach ($categoriesByPercent as $cat) {
                $splitAmount = ($amount * $cat['percentage']) / 100;
                if ($splitAmount > 0) {
                    $catName = htmlspecialchars($cat['name']);
                    $descText = "Voice: " . trim($transcript) . " - " . $catName . " (" . floatval($cat['percentage']) . "%)";
                    $stmt = $pdo->prepare("INSERT INTO transactions (user_id, amount, type, category_id, description, transaction_date) VALUES (?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([$user_id, $splitAmount, $type, $cat['id'], $descText]);
                }
            }
            
            // If percentages don't add up to 100%, put the remaining in a general Inflow
            if ($totalAssignedPercent < 100) {
                $remainingPercent = 100 - $totalAssignedPercent;
                $remainingAmount = ($amount * $remainingPercent) / 100;
                if ($remainingAmount > 0) {
                    $stmt = $pdo->prepare("INSERT INTO transactions (user_id, amount, type, category_id, description, transaction_date) VALUES (?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([$user_id, $remainingAmount, $type, null, "Voice: " . $transcript . " (Unassigned Split)"]);
                }
            }
            echo json_encode(['success' => true, 'message' => "Logged and split $type of $$amount"]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO transactions (user_id, category_id, amount, type, description, transaction_date) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$user_id, null, $amount, $type, "Voice: " . $transcript]);
            echo json_encode(['success' => true, 'message' => "Logged $type of $$amount"]);
        }
    } else {
        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, category_id, amount, type, description, transaction_date) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$user_id, $bestCategoryId, $amount, $type, "Voice: " . $transcript]);
        echo json_encode(['success' => true, 'message' => "Logged $type of $$amount"]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
