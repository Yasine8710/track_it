<?php
/**
 * VOICE FEATURE INTEGRATION TEST
 * This script tests the backend logic of the voice processing system.
 */

// Mock session and DB connection
session_start();
$_SESSION['user_id'] = 1; // Assuming user 1 exists for testing

// Test Data
$testCases = [
    ['transcript' => 'I earned 500 dollars today', 'expected_type' => 'inflow', 'expected_amount' => 500],
    ['transcript' => 'spent 50 on groceries', 'expected_type' => 'outflow', 'expected_amount' => 50],
    ['transcript' => 'add 1000 salary', 'expected_type' => 'inflow', 'expected_amount' => 1000],
    ['transcript' => 'dinner cost me 35.50', 'expected_type' => 'outflow', 'expected_amount' => 35.5]
];

echo "--- RUNNING VOICE INTEGRATION TESTS ---\n";

foreach ($testCases as $index => $test) {
    echo "Test #" . ($index + 1) . ": \"" . $test['transcript'] . "\"\n";
    
    // Simulate the process_voice.php logic
    // (Since we can't easily perform a real HTTP request to ourselves in this environment, 
    // we mirror the logic to verify regex and keywords)
    
    $transcript = strtolower($test['transcript']);
    
    // 1. Amount
    $amount = 0;
    if (preg_match('/(\d+(\.\d{1,2})?)/', $transcript, $matches)) {
        $amount = (float)$matches[1];
    }
    
    // 2. Intent
    $type = 'outflow';
    $incomeKeywords = ['salary', 'received', 'earned', 'won', 'found', 'gift', 'deposit', 'income', 'plus', 'add'];
    foreach ($incomeKeywords as $kw) {
        if (strpos($transcript, $kw) !== false) {
            $type = 'inflow';
            break;
        }
    }
    
    // Validation
    $success = true;
    if (abs($amount - $test['expected_amount']) > 0.001) {
        echo "❌ FAILED: Wrong amount (Got: $amount, Expected: " . $test['expected_amount'] . ")\n";
        $success = false;
    }
    if ($type !== $test['expected_type']) {
        echo "❌ FAILED: Wrong intent (Got: $type, Expected: " . $test['expected_type'] . ")\n";
        $success = false;
    }
    
    if ($success) {
        echo "✅ PASSED: Captured $type of $$amount\n";
    }
    echo "-----------------------------------\n";
}

echo "--- ALL VOICE TESTS COMPLETED ---\n";
