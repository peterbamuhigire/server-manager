<?php

require_once __DIR__ . '/Model.php';

/**
 * Cron Job Model
 * Manages scheduled cron jobs
 *
 * @package ChweziSM
 * @subpackage Models
 */
class CronJob extends Model
{
    protected string $table = 'cron_jobs';

    /**
     * Get all cron jobs with user information
     */
    public function getAll()
    {
        $sql = "SELECT c.*, u.username as creator_name
                FROM cron_jobs c
                LEFT JOIN users u ON c.user_id = u.id
                ORDER BY c.enabled DESC, c.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Get enabled cron jobs
     */
    public function getEnabled()
    {
        $sql = "SELECT * FROM cron_jobs
                WHERE enabled = 1
                ORDER BY schedule";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Get jobs due for execution
     */
    public function getDueJobs()
    {
        $sql = "SELECT * FROM cron_jobs
                WHERE enabled = 1
                AND (next_run_at IS NULL OR next_run_at <= NOW())
                ORDER BY next_run_at ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Update job execution status
     */
    public function updateExecution($id, $status, $output = null)
    {
        return $this->update($id, [
            'last_run_at' => date('Y-m-d H:i:s'),
            'last_run_status' => $status,
            'last_run_output' => $output,
            'run_count' => $this->db->query("SELECT run_count FROM cron_jobs WHERE id = $id")->fetchColumn() + 1
        ]);
    }

    /**
     * Validate cron schedule
     */
    public function isValidSchedule($schedule)
    {
        // Basic cron schedule validation (5 parts)
        $parts = preg_split('/\s+/', trim($schedule));

        if (count($parts) !== 5) {
            return false;
        }

        // Each part should be either *, number, range, or step
        foreach ($parts as $part) {
            if (!preg_match('/^(\*|[0-9]+(-[0-9]+)?(,[0-9]+(-[0-9]+)?)*)(\\/[0-9]+)?$/', $part)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calculate next run time based on cron schedule
     */
    public function calculateNextRun($schedule)
    {
        // For simplicity, we'll estimate next run
        // In production, use a proper cron expression parser library
        $parts = preg_split('/\s+/', trim($schedule));

        if (count($parts) !== 5) {
            return null;
        }

        // Simple estimation - if it's "* * * * *" run every minute
        if ($schedule === '* * * * *') {
            return date('Y-m-d H:i:00', strtotime('+1 minute'));
        }

        // For other schedules, estimate based on hour
        $hour = $parts[1];
        if (is_numeric($hour)) {
            $today = date('Y-m-d') . ' ' . str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00:00';
            if (strtotime($today) > time()) {
                return $today;
            }
            return date('Y-m-d H:i:s', strtotime($today . ' +1 day'));
        }

        // Default: next hour
        return date('Y-m-d H:00:00', strtotime('+1 hour'));
    }
}
