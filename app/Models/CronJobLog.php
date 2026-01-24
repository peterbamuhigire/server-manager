<?php

require_once __DIR__ . '/Model.php';

/**
 * Cron Job Log Model
 * Manages cron job execution logs
 *
 * @package ChweziSM
 * @subpackage Models
 */
class CronJobLog extends Model
{
    protected string $table = 'cron_job_logs';

    /**
     * Get logs for a specific cron job
     */
    public function getByCronJob($cronJobId, $limit = 50)
    {
        $sql = "SELECT * FROM cron_job_logs
                WHERE cron_job_id = ?
                ORDER BY started_at DESC
                LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cronJobId, $limit]);

        return $stmt->fetchAll();
    }

    /**
     * Get recent logs
     */
    public function getRecent($limit = 100)
    {
        $sql = "SELECT l.*, c.name as job_name
                FROM cron_job_logs l
                LEFT JOIN cron_jobs c ON l.cron_job_id = c.id
                ORDER BY l.started_at DESC
                LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);

        return $stmt->fetchAll();
    }

    /**
     * Get failed executions
     */
    public function getFailed($limit = 50)
    {
        $sql = "SELECT l.*, c.name as job_name
                FROM cron_job_logs l
                LEFT JOIN cron_jobs c ON l.cron_job_id = c.id
                WHERE l.status = 'failed'
                ORDER BY l.started_at DESC
                LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);

        return $stmt->fetchAll();
    }

    /**
     * Clean up old logs (keep only last N days)
     */
    public function cleanup($days = 30)
    {
        $sql = "DELETE FROM cron_job_logs
                WHERE started_at < DATE_SUB(NOW(), INTERVAL ? DAY)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days]);

        return $stmt->rowCount();
    }
}
