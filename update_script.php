<?php
$code = <<<'PHP'
<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) $input = $_POST;

$action = $input['action'] ?? 'add';
$id = $input['id'] ?? null;
$type = $input['type'] ?? 'expense';
$amount = (float)($input['amount'] ?? 0);
$description = trim($input['description'] ?? '');
$categoryId = !empty($input['category_id']) ? $input['category_id'] : null;
$date = $input['date'] ?? ($input['transaction_date'] ?? date('Y-m-d'));

try {
    if ($action === 'add') {
        if ($amount <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid amount']);
            exit;
        }
        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, category_id, amount, description, type, transaction_date) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $categoryId, $amount, $description, $type, $date]);
        echo json_encode(['success' => true, 'message' => 'Transaction added']);
    } elseif ($action === 'update' && $id) {
        if ($amount <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid amount']);
            exit;
        }
        $stmt = $pdo->prepare("UPDATE transactions SET category_id = ?, amount = ?, description = ?, transaction_date = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$categoryId, $amount, $description, $date, $id, $_SESSION['user_id']]);
        echo json_encode(['success' => true, 'message' => 'Transaction updated']);
    } elseif ($action === 'delete' && $id) {
        $stmt = $pdo->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $_SESSION['user_id']]);
        echo json_encode(['success' => true, 'message' => 'Transaction deleted']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
PHP;
file_put_contents('api/transaction.php', $code);
