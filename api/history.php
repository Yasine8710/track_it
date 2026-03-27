<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT t.id, t.transaction_date, t.description, t.amount, t.type, c.name as category_name, c.color as category_color
        FROM transactions t
        LEFT JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ?
        ORDER BY t.transaction_date DESC, t.id DESC
        LIMIT 100
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $transactions]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>