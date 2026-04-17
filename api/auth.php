<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$action = $_POST['action'] ?? '';
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (!$username || !$password) {
    echo json_encode(['success' => false, 'message' => 'Username and password required']);
    exit;
}

if ($action === 'register') {
    // Check if user exists
    $email = trim($_POST['email'] ?? '');
    $balance = (float)($_POST['balance'] ?? 0);

    if (!$email) {
        echo json_encode(['success' => false, 'message' => 'Email is required']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Username or Email already taken']);
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, balance) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $hash, $email, $balance]);
        $userId = $pdo->lastInsertId();

        // Create Default Categories
        $defaults = [
            // Expense Defaults
            ['Bills', 50, '#ef4444', 'expense'],
            ['Daily Spending', 30, '#f59e0b', 'expense'],
            ['Savings', 20, '#10b981', 'expense'],
            // Income Defaults
            ['Salary', 0, '#10b981', 'income'],
            ['Freelance', 0, '#3b82f6', 'income']
        ];

        $catStmt = $pdo->prepare("INSERT INTO categories (user_id, name, percentage, color, type) VALUES (?, ?, ?, ?, ?)");
        foreach ($defaults as $cat) {
            $catStmt->execute([$userId, $cat[0], $cat[1], $cat[2], $cat[3]]);
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'User registered']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
    }

} elseif ($action === 'login') {
    $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        echo json_encode(['success' => true, 'message' => 'Login successful']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
    }
} elseif ($action === 'logout') {
    session_destroy();
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>