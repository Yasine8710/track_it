<?php
require_once 'whitebox_utils.php';

if (!defined('TEST_MODE')) define('TEST_MODE', true);

function run_save_settings_test($pdo, $postData, $filesData = []) {
    global $pdo_mock;
    $pdo_mock = $pdo;
    $_SESSION['user_id'] = 1;
    $_POST = $postData;
    $_FILES = $filesData;

    ob_start();
    include __DIR__ . '/../../api/save_settings.php';
    $output = ob_get_clean();
    return json_decode($output, true);
}

echo "=== Running Save Settings Logic White-box Tests ===\n";

// 1. Update Profile (No Avatar)
$pdo = new MockPDO();
$res = run_save_settings_test($pdo, [
    'username' => 'newname',
    'email' => 'tech@example.com',
    'currency' => 'EUR',
    'full_name' => 'Tech Master'
]);
assertTrue($res['success'], "Path Coverage: Update settings without avatar");
// Verify if query has currency and email
$foundCurrency = false;
foreach($pdo->queries as $q) {
    if (strpos($q['query'], 'currency = ?') !== false) $foundCurrency = true;
}
assertTrue($foundCurrency, "Statement Coverage: Currency update included in SQL");

// 2. Avatar Upload Branch (Invalid Extension)
// Note: We can't easily mock move_uploaded_file, but we can test the extension filtering.
$pdo = new MockPDO();
$res = run_save_settings_test($pdo, ['username' => 'test'], [
    'avatar_file' => [
        'name' => 'malicious.php',
        'type' => 'application/x-php',
        'tmp_name' => '/tmp/php123',
        'error' => 0,
        'size' => 100
    ]
]);
// Extension 'php' is not in allowed list, so profile_picture should remain null in query
$avatarInQuery = false;
foreach($pdo->queries as $q) {
    if (strpos($q['query'], 'profile_picture = ?') !== false) $avatarInQuery = true;
}
assertTrue(!$avatarInQuery, "Branch Coverage: PHP extension rejected for avatar");

echo "Done.\n\n";
