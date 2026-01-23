#!/usr/bin/env php
<?php

/**
 * Metrics Collection Script
 *
 * This script collects server metrics (CPU, RAM, Disk) and stores them in the database.
 * Run this script via cron every 5 minutes:
 *
 * */5 * * * * /usr/bin/php /var/www/server-manager/scripts/collect-metrics.php >> /var/www/server-manager/storage/logs/metrics.log 2>&1
 *
 * @package ChweziSM
 * @subpackage Scripts
 * @version 1.0
 */

// Ensure script is run from command line
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.' . PHP_EOL);
}

// Get the root directory (one level up from scripts/)
$rootDir = dirname(__DIR__);

// Load configuration
require_once $rootDir . '/config/paths.php';
require_once $rootDir . '/config/database.php';
require_once $rootDir . '/config/app.php';

// Load helpers
require_once $rootDir . '/app/Helpers/functions.php';

// Load required classes
require_once $rootDir . '/app/Models/Model.php';
require_once $rootDir . '/app/Models/SystemMetric.php';
require_once $rootDir . '/app/Services/ServerMonitoringService.php';

// Log script start
$timestamp = date('Y-m-d H:i:s');
echo "[{$timestamp}] Starting metrics collection..." . PHP_EOL;

try {
    // Collect metrics
    $success = ServerMonitoringService::collectAndStore();

    if ($success) {
        // Get the latest metrics for confirmation
        $metrics = ServerMonitoringService::getAllMetrics();

        echo "[{$timestamp}] Metrics collected successfully:" . PHP_EOL;
        echo "  - CPU: {$metrics['cpu']}%" . PHP_EOL;
        echo "  - RAM: {$metrics['ram']}%" . PHP_EOL;
        echo "  - Disk: {$metrics['disk']}%" . PHP_EOL;
        echo "  - Apache: " . ($metrics['apache'] ? 'Running' : 'Stopped') . PHP_EOL;
        echo "  - MySQL: " . ($metrics['mysql'] ? 'Running' : 'Stopped') . PHP_EOL;

        exit(0);
    } else {
        echo "[{$timestamp}] ERROR: Failed to collect metrics" . PHP_EOL;
        exit(1);
    }
} catch (Exception $e) {
    echo "[{$timestamp}] EXCEPTION: " . $e->getMessage() . PHP_EOL;
    echo "Stack trace:" . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
    exit(1);
}
