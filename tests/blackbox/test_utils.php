<?php
/**
 * Test Utilities for Blackbox Testing
 * Handles database interaction and simulated API requests
 */

if (!defined('TEST_MODE')) {
    define('TEST_MODE', true);
}

// Establish real connection for blackbox tests
$host = 'localhost';
$db   = 'trackit_db';
$user = 'root';
$pass = '';
$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
]);
global $pdo_mock;
$pdo_mock = $pdo;

require_once __DIR__ . '/../../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Reset test user data
 */
function reset_test_user($username = 'testuser') {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user) {
        $user_id = $user['id'];
        $pdo->prepare("DELETE FROM transactions WHERE user_id = ?")->execute([$user_id]);
        $pdo->prepare("DELETE FROM wishes WHERE user_id = ?")->execute([$user_id]);
        $pdo->prepare("DELETE FROM categories WHERE user_id = ?")->execute([$user_id]);
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
    }

    // Also check for 'settest_updated' if exploring renames
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'settest_updated'");
    $stmt->execute();
    $alt_user = $stmt->fetch();
    if ($alt_user) {
        $alt_id = $alt_user['id'];
        $pdo->prepare("DELETE FROM transactions WHERE user_id = ?")->execute([$alt_id]);
        $pdo->prepare("DELETE FROM wishes WHERE user_id = ?")->execute([$alt_id]);
        $pdo->prepare("DELETE FROM categories WHERE user_id = ?")->execute([$alt_id]);
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$alt_id]);
    }
}

/**
 * Simulate a POST request to an API endpoint
 */
function simulate_post($url, $data) {
    global $pdo;
    
    // Set up environment for the included file
    $_POST = $data;
    $_SERVER['REQUEST_METHOD'] = 'POST';
    
    // Change directory to the target file's directory so relative paths work
    $original_cwd = getcwd();
    chdir(dirname(__DIR__ . '/../../' . $url));
    
    ob_start();
    $result = include basename(__DIR__ . '/../../' . $url);
    $output = ob_get_clean();
    
    chdir($original_cwd);
    
    if (is_array($result)) return $result;
    return json_decode($output, true);
}

/**
 * Simulate a GET request to an API endpoint
 */
function simulate_get($url, $params = []) {
    global $pdo;
    
    $_GET = $params;
    $_SERVER['REQUEST_METHOD'] = 'GET';
    
    $original_cwd = getcwd();
    chdir(dirname(__DIR__ . '/../../' . $url));
    
    ob_start();
    $result = include basename(__DIR__ . '/../../' . $url);
    $output = ob_get_clean();
    
    chdir($original_cwd);
    
    if (is_array($result)) return $result;
    return json_decode($output, true);
}

/**
 * Simulate a JSON request (PUT/POST with body)
 */
function simulate_json_request($url, $method, $data) {
    global $pdo;
    
    $_SERVER['REQUEST_METHOD'] = $method;
    // We can't easily mock php://input for include, 
    // so we'll have to rely on the fact that some of our APIs 
    // also use $_POST or we might need to modify them.
    // However, for blackbox testing of the logic, we can often 
    // just call the database directly or use a helper that mocks the input.
    
    // For this specific environment, we'll try to set up the globals
    if ($method === 'PUT' || $method === 'POST') {
        // Mocking php://input is tricky in PHP unless using a framework.
        // We'll use a trick or just test through direct logic if needed.
    }
    
    // Alternative: many of our endpoints use json_decode(file_get_contents('php://input'), true)
    // To test those, we might need to use curl to localhost or refactor.
    // For now, let's stick to POST for simple cases.
}

function log_result($test_name, $success, $message = '') {
    $status = $success ? "PASSED" : "FAILED";
    echo "[$status] $test_name" . ($message ? ": " . (is_array($message) ? json_encode($message) : $message) : "") . "\n";
}
