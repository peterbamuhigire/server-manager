<?php

/**
 * Test Runner
 * Runs all tests for the Chwezi Server Manager
 */

// Load configuration
$_ENV['DB_HOST'] = 'localhost';
$_ENV['DB_DATABASE'] = 'chwezi_sm';
$_ENV['DB_USERNAME'] = 'peter';
$_ENV['DB_PASSWORD'] = 'Wirtz2025@';

echo "===================================\n";
echo "Chwezi Server Manager - Test Suite\n";
echo "===================================\n\n";

$testFiles = glob(__DIR__ . '/*Test.php');
$totalTests = count($testFiles);
$passedTests = 0;
$failedTests = 0;

foreach ($testFiles as $testFile) {
    echo "Running: " . basename($testFile) . "\n";
    echo str_repeat('-', 50) . "\n";

    try {
        require_once $testFile;

        // Get class name from file name
        $className = basename($testFile, '.php');

        if (class_exists($className)) {
            $test = new $className();
            if (method_exists($test, 'run')) {
                $test->run();
                $passedTests++;
            }
        }
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n\n";
        $failedTests++;
    }
}

echo "===================================\n";
echo "Test Results\n";
echo "===================================\n";
echo "Total Test Files: $totalTests\n";
echo "Passed: $passedTests\n";
echo "Failed: $failedTests\n";
echo "===================================\n";

exit($failedTests > 0 ? 1 : 0);
