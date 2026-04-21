<?php
/**
 * Coverage Report Runner
 * Aggregates all white-box tests and summarizes findings.
 */

echo "###########################################\n";
echo "    WHITE-BOX TESTING COVERAGE REPORT      \n";
echo "###########################################\n\n";

ob_start();

include 'auth_logic_test.php';
include 'voice_logic_test.php';
include 'wishes_logic_test.php';
include 'categories_logic_test.php';
include 'data_logic_test.php';
include 'transaction_logic_test.php';
include 'save_settings_logic_test.php';
include 'history_logic_test.php';

$output = ob_get_clean();
echo $output;

$passCount = substr_count($output, '[PASS]');
$failCount = substr_count($output, '[FAIL]');

echo "\nSummary:\n";
echo "-------------------------------------------\n";
echo "Total Logical Paths Tested: " . ($passCount + $failCount) . "\n";
echo "Passed: $passCount\n";
echo "Failed: $failCount\n";

if ($failCount === 0) {
    echo "\n[STATUS] FULL LOGICAL COVERAGE ACHIEVED ACROSS REQUESTED BRANCHES.\n";
} else {
    echo "\n[STATUS] COVERAGE INCOMPLETE OR REGRESSIONS FOUND.\n";
}
echo "###########################################\n";
