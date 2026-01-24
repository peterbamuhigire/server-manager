<?php

require_once __DIR__ . '/../Models/CronJob.php';
require_once __DIR__ . '/../Models/CronJobLog.php';
require_once __DIR__ . '/ActivityLogService.php';

/**
 * Cron Service
 * Manages system crontab on Ubuntu/Debian
 *
 * @package ChweziSM
 * @subpackage Services
 */
class CronService
{
    private $cronModel;
    private $logModel;
    private $activityLog;
    private $cronUser;

    public function __construct()
    {
        $this->cronModel = new CronJob();
        $this->logModel = new CronJobLog();
        $this->activityLog = new ActivityLogService();

        // Cron jobs run as www-data user on Ubuntu/Debian
        $this->cronUser = config('cron.user', 'www-data');
    }

    /**
     * Add cron job to database and system crontab
     */
    public function addJob($name, $command, $schedule, $description, $userId)
    {
        try {
            // Validate schedule
            if (!$this->cronModel->isValidSchedule($schedule)) {
                throw new Exception('Invalid cron schedule format');
            }

            // Calculate next run
            $nextRun = $this->cronModel->calculateNextRun($schedule);

            // Create job in database
            $jobId = $this->cronModel->create([
                'name' => $name,
                'command' => $command,
                'schedule' => $schedule,
                'description' => $description,
                'enabled' => 1,
                'next_run_at' => $nextRun,
                'user_id' => $userId
            ]);

            if (!$jobId) {
                throw new Exception('Failed to create cron job in database');
            }

            // Sync to system crontab
            $syncResult = $this->syncToSystemCrontab();

            if (!$syncResult['success']) {
                // Rollback database entry
                $this->cronModel->delete($jobId);
                throw new Exception($syncResult['message']);
            }

            $this->activityLog->log(
                'cron_job_created',
                'Cron job created: ' . $name,
                ['job_id' => $jobId, 'schedule' => $schedule]
            );

            return [
                'success' => true,
                'message' => 'Cron job created successfully',
                'job_id' => $jobId
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Remove cron job from database and system crontab
     */
    public function removeJob($jobId)
    {
        try {
            $job = $this->cronModel->find($jobId);

            if (!$job) {
                throw new Exception('Cron job not found');
            }

            // Delete from database
            $this->cronModel->delete($jobId);

            // Sync to system crontab
            $this->syncToSystemCrontab();

            $this->activityLog->log(
                'cron_job_deleted',
                'Cron job deleted: ' . $job['name'],
                ['job_id' => $jobId]
            );

            return [
                'success' => true,
                'message' => 'Cron job removed successfully'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Enable/disable cron job
     */
    public function toggleJob($jobId, $enabled)
    {
        try {
            $job = $this->cronModel->find($jobId);

            if (!$job) {
                throw new Exception('Cron job not found');
            }

            // Update database
            $this->cronModel->update($jobId, ['enabled' => $enabled ? 1 : 0]);

            // Sync to system crontab
            $this->syncToSystemCrontab();

            $action = $enabled ? 'enabled' : 'disabled';
            $this->activityLog->log(
                'cron_job_' . $action,
                'Cron job ' . $action . ': ' . $job['name'],
                ['job_id' => $jobId]
            );

            return [
                'success' => true,
                'message' => 'Cron job ' . $action . ' successfully'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Sync all enabled jobs to system crontab
     */
    public function syncToSystemCrontab()
    {
        try {
            // Check if running on Linux
            if (stripos(PHP_OS, 'WIN') === 0) {
                return [
                    'success' => true,
                    'message' => 'Crontab sync skipped on Windows (dev environment)'
                ];
            }

            // Get all enabled jobs
            $jobs = $this->cronModel->getEnabled();

            // Build crontab content
            $crontabContent = "# Chwezi Server Manager - Managed Cron Jobs\n";
            $crontabContent .= "# DO NOT EDIT MANUALLY - Changes will be overwritten\n";
            $crontabContent .= "# Generated: " . date('Y-m-d H:i:s') . "\n\n";

            foreach ($jobs as $job) {
                $crontabContent .= "# Job ID: {$job['id']} - {$job['name']}\n";
                $crontabContent .= "{$job['schedule']} {$job['command']}\n\n";
            }

            // Write to temp file
            $tempFile = sys_get_temp_dir() . '/chwezi_crontab_' . time();
            file_put_contents($tempFile, $crontabContent);

            // Install crontab for www-data user
            $command = "sudo crontab -u {$this->cronUser} {$tempFile} 2>&1";
            exec($command, $output, $returnCode);

            // Clean up temp file
            unlink($tempFile);

            if ($returnCode !== 0) {
                throw new Exception('Failed to update crontab: ' . implode("\n", $output));
            }

            return [
                'success' => true,
                'message' => 'System crontab updated successfully'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get current system crontab
     */
    public function getSystemCrontab()
    {
        try {
            // Check if running on Linux
            if (stripos(PHP_OS, 'WIN') === 0) {
                return [
                    'success' => true,
                    'crontab' => "# Crontab not available on Windows\n# This is a development environment"
                ];
            }

            $command = "sudo crontab -u {$this->cronUser} -l 2>&1";
            exec($command, $output, $returnCode);

            $crontab = implode("\n", $output);

            return [
                'success' => true,
                'crontab' => $crontab
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Execute a cron job manually
     */
    public function executeJob($jobId)
    {
        try {
            $job = $this->cronModel->find($jobId);

            if (!$job) {
                throw new Exception('Cron job not found');
            }

            // Mark as running
            $this->cronModel->update($jobId, ['last_run_status' => 'running']);

            $startTime = microtime(true);
            $startedAt = date('Y-m-d H:i:s');

            // Execute command
            $command = $job['command'] . ' 2>&1';
            exec($command, $output, $returnCode);

            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 3);

            $status = $returnCode === 0 ? 'success' : 'failed';
            $outputText = implode("\n", $output);

            // Update job
            $this->cronModel->updateExecution($jobId, $status, $outputText);

            // Calculate next run
            $nextRun = $this->cronModel->calculateNextRun($job['schedule']);
            $this->cronModel->update($jobId, ['next_run_at' => $nextRun]);

            // Log execution
            $this->logModel->create([
                'cron_job_id' => $jobId,
                'status' => $status,
                'output' => $status === 'success' ? $outputText : null,
                'error_output' => $status === 'failed' ? $outputText : null,
                'execution_time' => $executionTime,
                'started_at' => $startedAt,
                'completed_at' => date('Y-m-d H:i:s')
            ]);

            return [
                'success' => true,
                'status' => $status,
                'output' => $outputText,
                'execution_time' => $executionTime
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get common cron schedules
     */
    public function getCommonSchedules()
    {
        return [
            'Every minute' => '* * * * *',
            'Every 5 minutes' => '*/5 * * * *',
            'Every 15 minutes' => '*/15 * * * *',
            'Every 30 minutes' => '*/30 * * * *',
            'Every hour' => '0 * * * *',
            'Every 2 hours' => '0 */2 * * *',
            'Every 6 hours' => '0 */6 * * *',
            'Daily at midnight' => '0 0 * * *',
            'Daily at 2am' => '0 2 * * *',
            'Daily at noon' => '0 12 * * *',
            'Weekly (Sunday midnight)' => '0 0 * * 0',
            'Monthly (1st at midnight)' => '0 0 1 * *',
            'Yearly (Jan 1st at midnight)' => '0 0 1 1 *'
        ];
    }
}
