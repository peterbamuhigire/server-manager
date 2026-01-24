<?php

/**
 * Test Summary
 * Quick summary of all test results
 */

// Load configuration
$_ENV['DB_HOST'] = 'localhost';
$_ENV['DB_DATABASE'] = 'chwezi_sm';
$_ENV['DB_USERNAME'] = 'peter';
$_ENV['DB_PASSWORD'] = 'Wirtz2025@';

echo "===================================\n";
echo "Test Summary\n";
echo "===================================\n\n";

$testFiles = [
    'AppModelTest.php',
    'CronServiceTest.php',
    'DomainModelTest.php',
    'LogViewerServiceTest.php',
    'MysqlDatabaseModelTest.php',
    'SslCertificateModelTest.php',
    'WordPressInstallerTest.php',
];

$results = [];

foreach ($testFiles as $testFile) {
    $output = [];
    $returnCode = 0;

    exec("cd " . __DIR__ . " && /c/wamp64/bin/php/php8.3.28/php.exe " . escapeshellarg($testFile) . " 2>&1", $output, $returnCode);

    $passed = $returnCode === 0;
    $results[] = [
        'file' => $testFile,
        'passed' => $passed,
        'output' => implode("\n", $output)
    ];

    $status = $passed ? '✓ PASS' : '✗ FAIL';
    $testName = str_replace('.php', '', $testFile);

    echo sprintf("%-35s %s\n", $testName, $status);
}

echo "\n===================================\n";
$passedCount = count(array_filter($results, fn($r) => $r['passed']));
$totalCount = count($results);

echo "Total: $totalCount tests\n";
echo "Passed: $passedCount\n";
echo "Failed: " . ($totalCount - $passedCount) . "\n";
echo "===================================\n";

exit($passedCount === $totalCount ? 0 : 1);
