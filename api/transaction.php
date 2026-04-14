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
    $date = $data['date'] ?? date('Y-m-d H:i:s');
    $split = $data['split'] ?? false;

    if ($amount > 0) {
        if ($type === 'outflow') {
            $stIn = $pdo->prepare("SELECT SUM(amount) as t FROM transactions WHERE user_id = ? AND type = 'inflow'");
            $stIn->execute([$_SESSION['user_id']]);
            $in = floatval($stIn->fetch()['t'] ?? 0);

            $stOut = $pdo->prepare("SELECT SUM(amount) as t FROM transactions WHERE user_id = ? AND type = 'outflow'");
            $stOut->execute([$_SESSION['user_id']]);
            $out = floatval($stOut->fetch()['t'] ?? 0);

            if ($amount > ($in - $out)) {
                echo json_encode(['success' => false, 'message' => 'Cannot spend more than your income balance.']);
                exit;
            }
        }
        
        if ($type === 'inflow' && $split) {
            // Fetch categories with percentage > 0 for this user
            $stmt = $pdo->prepare("SELECT id, name, percentage FROM categories WHERE (user_id = ? OR user_id IS NULL) AND percentage > 0");
            $stmt->execute([$_SESSION['user_id']]);
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
                        $descText = trim($description) !== '' ? trim($description) . " - " . $catName . " (" . floatval($cat['percentage']) . "%)" : $catName . " (" . floatval($cat['percentage']) . "%)";
                        
                        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, amount, type, category_id, description, transaction_date) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$_SESSION['user_id'], $splitAmount, $type, $cat['id'], $descText, $date]);
                    }
                }
                
                // If percentages don't add up to 100%, put the remaining in a general Inflow
                if ($totalAssignedPercent < 100) {
                    $remainingPercent = 100 - $totalAssignedPercent;
                    $remainingAmount = ($amount * $remainingPercent) / 100;
                    if ($remainingAmount > 0) {
                        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, amount, type, category_id, description, transaction_date) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$_SESSION['user_id'], $remainingAmount, $type, null, $description . " (Unassigned Split)", $date]);
                    }
                }
            } else {
                // Treat as normal inflow
                $stmt = $pdo->prepare("INSERT INTO transactions (user_id, amount, type, category_id, description, transaction_date) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $amount, $type, $category_id, $description, $date]);
            }
        } else {
            $stmt = $pdo->prepare("INSERT INTO transactions (user_id, amount, type, category_id, description, transaction_date) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $amount, $type, $category_id, $description, $date]);
        }
        
        // Update pet streak
        $pdo->exec("UPDATE user_pets SET streak_count = streak_count + 1, last_updated = CURDATE() WHERE user_id = {$_SESSION['user_id']}");
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid amount']);
    }
}
