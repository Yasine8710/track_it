<?php
/**
 * Test script for portfolio.php form validation
 * This script simulates POST requests to verify validation logic.
 */

function simulatePost($data) {
    $_POST = $data;
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST['submit_request'] = true;
    
    // Capture output and variables by including the file
    // Note: We need to handle the fact that portfolio.php also contains HTML.
    // For testing logic, we can look at the variables defined in the PHP block.
    ob_start();
    include 'portfolio.php';
    ob_end_clean();
    
    return [
        'errors' => $errors ?? [],
        'success_msg' => $success_msg ?? null,
        'full_name' => $full_name ?? '',
        'user_email' => $user_email ?? '',
        'message' => $message ?? ''
    ];
}

echo "Starting Form Validation Tests...\n\n";

// Test 1: Empty Fields
echo "Test 1: Empty Fields\n";
$result = simulatePost([]);
if (count($result['errors']) === 3) {
    echo "✅ Correctly identified 3 missing required fields.\n";
} else {
    echo "❌ Failed: Expected 3 errors, got " . count($result['errors']) . "\n";
}

// Test 2: Trimming
echo "\nTest 2: Trimming Empty Spaces\n";
$result = simulatePost([
    'full_name' => '   ',
    'user_email' => '  ',
    'message' => '   '
]);
if (count($result['errors']) === 3) {
    echo "✅ Correctly identified fields with only spaces as empty.\n";
} else {
    echo "❌ Failed: Trim logic not working as expected.\n";
}

// Test 3: Invalid Email
echo "\nTest 3: Invalid Email Format\n";
$result = simulatePost([
    'full_name' => 'John Doe',
    'user_email' => 'not-an-email',
    'message' => 'Hello'
]);
if (isset($result['errors']['user_email']) && $result['errors']['user_email'] === "The email address entered is not valid.") {
    echo "✅ Correctly flagged invalid email format.\n";
} else {
    echo "❌ Failed: Invalid email not caught or wrong message.\n";
}

// Test 4: Sticky Form (Persistence)
echo "\nTest 4: Sticky Form (Value Persistence)\n";
$result = simulatePost([
    'full_name' => 'John Doe',
    'user_email' => 'invalid',
    'message' => 'Existing message'
]);
if ($result['full_name'] === 'John Doe' && $result['message'] === 'Existing message') {
    echo "✅ Form values persisted after validation failure.\n";
} else {
    echo "❌ Failed: Values did not persist.\n";
}

// Test 5: Success Path
echo "\nTest 5: Success Path\n";
$result = simulatePost([
    'full_name' => 'John Doe',
    'user_email' => 'john@example.com',
    'service_type' => 'backend',
    'message' => 'Valid message'
]);
if (empty($result['errors']) && !empty($result['success_msg'])) {
    echo "✅ Success message generated for valid input.\n";
    if ($result['full_name'] === '' && $result['user_email'] === '') {
        echo "✅ Form cleared after success.\n";
    } else {
        echo "❌ Failed: Form not cleared after success.\n";
    }
} else {
    echo "❌ Failed: Success path not triggered for valid input.\n";
}

echo "\nTests Complete.\n";
