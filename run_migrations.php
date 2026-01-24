<?php
/**
 * Migration Runner
 * Runs all pending SQL migrations
 */

try {
    $db = new PDO('mysql:host=localhost', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('USE chwezi_sm');

    $migrations = [
        '006_create_databases_table.sql',
        '007_create_cron_jobs_table.sql'
    ];

    foreach ($migrations as $migration) {
        $file = __DIR__ . '/database/migrations/' . $migration;

        if (!file_exists($file)) {
            echo "Migration file not found: $migration\n";
            continue;
        }

        echo "Running migration: $migration\n";
        $sql = file_get_contents($file);
        $db->exec($sql);
        echo "✓ Migration $migration completed successfully\n\n";
    }

    echo "All migrations completed successfully!\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
