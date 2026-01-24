<?php

/**
 * Cron Service Test
 * Tests for cron job management functionality
 */

require_once __DIR__ . '/../app/Services/CronService.php';
require_once __DIR__ . '/../app/Models/CronJob.php';
require_once __DIR__ . '/../app/Models/CronJobLog.php';
require_once __DIR__ . '/../app/Helpers/functions.php';

class CronServiceTest
{
    private $cronService;
    private $cronModel;
    private $testJobId;

    public function __construct()
    {
        $this->cronService = new CronService();
        $this->cronModel = new CronJob();
    }

    public function run()
    {
        echo "Running Cron Service Tests...\n";

        try {
            $this->testScheduleValidation();
            $this->testCommonSchedules();
            $this->testNextRunCalculation();
            $this->testJobCreation();
            $this->testJobToggle();
            $this->testJobExecution();
            $this->cleanup();

            echo "✓ All Cron Service tests passed!\n\n";
            return true;
        } catch (Exception $e) {
            echo "✗ Test failed: " . $e->getMessage() . "\n";
            echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
            $this->cleanup();
            return false;
        }
    }

    private function testScheduleValidation()
    {
        echo "  Testing cron schedule validation...\n";

        // Valid schedules
        $validSchedules = [
            '* * * * *',           // Every minute
            '0 * * * *',           // Every hour
            '0 0 * * *',           // Daily at midnight
            '*/5 * * * *',         // Every 5 minutes
            '0 2 * * 0',           // Weekly on Sunday at 2 AM
            '0 0 1 * *',           // Monthly on the 1st
            '0,30 * * * *',        // Every 30 minutes (0 and 30)
            '0 9-17 * * 1-5',      // Weekdays 9 AM to 5 PM
        ];

        foreach ($validSchedules as $schedule) {
            $isValid = $this->cronModel->isValidSchedule($schedule);
            if (!$isValid) {
                throw new Exception("Valid schedule rejected: $schedule");
            }
        }

        // Invalid schedules (format-based validation, not range-based)
        $invalidSchedules = [
            '',                    // Empty
            '* * * *',             // Only 4 parts
            '* * * * * *',         // 6 parts
            'invalid',             // Not a cron expression
            'abc * * * *',         // Non-numeric value
            '* xyz * * *',         // Non-numeric value
            '** * * * *',          // Invalid format
            '- * * * *',           // Invalid format
        ];

        foreach ($invalidSchedules as $schedule) {
            $isValid = $this->cronModel->isValidSchedule($schedule);
            if ($isValid) {
                throw new Exception("Invalid schedule accepted: $schedule");
            }
        }

        echo "    ✓ Schedule validation works correctly\n";
    }

    private function testCommonSchedules()
    {
        echo "  Testing common schedule templates...\n";

        $schedules = $this->cronService->getCommonSchedules();

        // Verify it returns an array
        if (!is_array($schedules)) {
            throw new Exception("getCommonSchedules should return an array");
        }

        // Verify it has expected schedules
        $expectedSchedules = [
            'Every minute' => '* * * * *',
            'Every hour' => '0 * * * *',
            'Daily at midnight' => '0 0 * * *',
            'Weekly (Sunday midnight)' => '0 0 * * 0',
            'Monthly (1st at midnight)' => '0 0 1 * *',
        ];

        foreach ($expectedSchedules as $label => $schedule) {
            if (!isset($schedules[$label]) || $schedules[$label] !== $schedule) {
                throw new Exception("Missing or incorrect common schedule: $label");
            }
        }

        echo "    ✓ Common schedules are correct\n";
    }

    private function testNextRunCalculation()
    {
        echo "  Testing next run time calculation...\n";

        // Test every minute schedule
        $nextRun = $this->cronModel->calculateNextRun('* * * * *');
        if (empty($nextRun)) {
            throw new Exception("Next run calculation failed for '* * * * *'");
        }

        // Verify it's in the future
        $nextRunTime = strtotime($nextRun);
        $now = time();
        if ($nextRunTime <= $now) {
            throw new Exception("Next run should be in the future");
        }

        // Test daily schedule
        $nextRun = $this->cronModel->calculateNextRun('0 0 * * *');
        if (empty($nextRun)) {
            throw new Exception("Next run calculation failed for '0 0 * * *'");
        }

        echo "    ✓ Next run calculation works\n";
    }

    private function testJobCreation()
    {
        echo "  Testing cron job creation...\n";

        // Create a test job
        $result = $this->cronService->addJob(
            'Test Job',
            '/usr/bin/echo "Hello World"',
            '* * * * *',
            'Test cron job for unit testing',
            1 // user_id
        );

        if (!$result['success']) {
            throw new Exception("Job creation failed: " . ($result['message'] ?? 'Unknown error'));
        }

        $this->testJobId = $result['job_id'];

        // Verify job was created
        $job = $this->cronModel->find($this->testJobId);
        if (!$job) {
            throw new Exception("Created job not found in database");
        }

        // Verify job properties
        if ($job['name'] !== 'Test Job') {
            throw new Exception("Job name mismatch");
        }

        if ($job['command'] !== '/usr/bin/echo "Hello World"') {
            throw new Exception("Job command mismatch");
        }

        if ($job['schedule'] !== '* * * * *') {
            throw new Exception("Job schedule mismatch");
        }

        if ($job['enabled'] != 1) {
            throw new Exception("Job should be enabled by default");
        }

        echo "    ✓ Job creation works correctly\n";
    }

    private function testJobToggle()
    {
        echo "  Testing job enable/disable...\n";

        if (!$this->testJobId) {
            throw new Exception("No test job available for toggle test");
        }

        // Disable the job
        $result = $this->cronService->toggleJob($this->testJobId, false);
        if (!$result['success']) {
            throw new Exception("Failed to disable job: " . ($result['message'] ?? 'Unknown error'));
        }

        // Verify job is disabled
        $job = $this->cronModel->find($this->testJobId);
        if ($job['enabled'] != 0) {
            throw new Exception("Job should be disabled");
        }

        // Enable the job
        $result = $this->cronService->toggleJob($this->testJobId, true);
        if (!$result['success']) {
            throw new Exception("Failed to enable job: " . ($result['message'] ?? 'Unknown error'));
        }

        // Verify job is enabled
        $job = $this->cronModel->find($this->testJobId);
        if ($job['enabled'] != 1) {
            throw new Exception("Job should be enabled");
        }

        echo "    ✓ Job toggle works correctly\n";
    }

    private function testJobExecution()
    {
        echo "  Testing job execution...\n";

        if (!$this->testJobId) {
            throw new Exception("No test job available for execution test");
        }

        // Execute the job
        $result = $this->cronService->executeJob($this->testJobId);

        if (!$result['success']) {
            throw new Exception("Job execution failed: " . ($result['message'] ?? 'Unknown error'));
        }

        // Verify execution result
        if (!isset($result['status'])) {
            throw new Exception("Execution result missing status");
        }

        if (!isset($result['output'])) {
            throw new Exception("Execution result missing output");
        }

        // Verify job was updated
        $job = $this->cronModel->find($this->testJobId);
        if (empty($job['last_run_at'])) {
            throw new Exception("Job last_run_at should be updated");
        }

        if (empty($job['last_run_status'])) {
            throw new Exception("Job last_run_status should be updated");
        }

        if ($job['run_count'] < 1) {
            throw new Exception("Job run_count should be incremented");
        }

        // Verify execution log was created
        $logModel = new CronJobLog();
        $logs = $logModel->getByCronJob($this->testJobId, 1);
        if (empty($logs)) {
            throw new Exception("Execution log should be created");
        }

        $log = $logs[0];
        if (empty($log['started_at'])) {
            throw new Exception("Log should have started_at timestamp");
        }

        if (empty($log['status'])) {
            throw new Exception("Log should have status");
        }

        echo "    ✓ Job execution works correctly\n";
    }

    private function cleanup()
    {
        echo "  Cleaning up test data...\n";

        if ($this->testJobId) {
            // Delete test job (this will cascade to logs)
            $this->cronService->removeJob($this->testJobId);
        }

        echo "    ✓ Cleanup complete\n";
    }
}

// Run tests if executed directly
if (php_sapi_name() === 'cli') {
    $test = new CronServiceTest();
    $result = $test->run();
    exit($result ? 0 : 1);
}
