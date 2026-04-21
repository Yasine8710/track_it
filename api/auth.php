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

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !defined('TEST_MODE')) {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$action = $_POST['action'] ?? '';
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$full_name = trim($_POST['full_name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$bio = trim($_POST['bio'] ?? '');

if (!$username || !$password) {
    if (!defined('TEST_MODE')) {
        echo json_encode(['success' => false, 'message' => 'Username and password required']);
        exit;
    } else {
        return ['success' => false, 'message' => 'Username and password required'];
    }
}

if ($action === 'register') {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        if (!defined('TEST_MODE')) {
            echo json_encode(['success' => false, 'message' => 'Username already taken']);
            exit;
        } else {
            return ['success' => false, 'message' => 'Username already taken'];
        }
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, phone, address, bio) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$username, $hash, $full_name, $phone, $address, $bio]);
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
        if (!defined('TEST_MODE')) {
            echo json_encode(['success' => true, 'message' => 'User registered']);
        } else {
            return ['success' => true, 'message' => 'User registered'];
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        if (!defined('TEST_MODE')) {
            echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
        } else {
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }

} elseif ($action === 'login') {
    $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        if (!defined('TEST_MODE')) {
            echo json_encode(['success' => true, 'message' => 'Login successful']);
        } else {
            return ['success' => true, 'message' => 'Login successful'];
        }
    } else {
        if (!defined('TEST_MODE')) {
            echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        } else {
            return ['success' => false, 'message' => 'Invalid credentials'];
        }
    }
} elseif ($action === 'logout') {
    session_destroy();
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>