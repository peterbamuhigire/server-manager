<?php

require_once __DIR__ . '/Model.php';

/**
 * Terminal Session Model
 * Manages web terminal command history
 *
 * @package ChweziSM
 * @subpackage Models
 */
class TerminalSession extends Model
{
    protected string $table = 'terminal_sessions';

    /**
     * Get recent terminal sessions for a user
     */
    public function getByUser($userId, $limit = 50)
    {
        $sql = "SELECT * FROM terminal_sessions
                WHERE user_id = ?
                ORDER BY started_at DESC
                LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $limit]);

        return $stmt->fetchAll();
    }

    /**
     * Get all recent terminal sessions (admin view)
     */
    public function getRecent($limit = 100)
    {
        $sql = "SELECT ts.*, u.username
                FROM terminal_sessions ts
                LEFT JOIN users u ON ts.user_id = u.id
                ORDER BY ts.started_at DESC
                LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);

        return $stmt->fetchAll();
    }

    /**
     * Search terminal sessions by command
     */
    public function search($query, $userId = null, $limit = 50)
    {
        if ($userId) {
            $sql = "SELECT * FROM terminal_sessions
                    WHERE user_id = ? AND command LIKE ?
                    ORDER BY started_at DESC
                    LIMIT ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, "%$query%", $limit]);
        } else {
            $sql = "SELECT ts.*, u.username
                    FROM terminal_sessions ts
                    LEFT JOIN users u ON ts.user_id = u.id
                    WHERE ts.command LIKE ?
                    ORDER BY ts.started_at DESC
                    LIMIT ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(["%$query%", $limit]);
        }

        return $stmt->fetchAll();
    }

    /**
     * Get failed commands
     */
    public function getFailed($userId = null, $limit = 50)
    {
        if ($userId) {
            $sql = "SELECT * FROM terminal_sessions
                    WHERE user_id = ? AND status = 'failed'
                    ORDER BY started_at DESC
                    LIMIT ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $limit]);
        } else {
            $sql = "SELECT ts.*, u.username
                    FROM terminal_sessions ts
                    LEFT JOIN users u ON ts.user_id = u.id
                    WHERE ts.status = 'failed'
                    ORDER BY ts.started_at DESC
                    LIMIT ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$limit]);
        }

        return $stmt->fetchAll();
    }

    /**
     * Update session completion
     */
    public function completeSession($id, $status, $output, $errorOutput, $exitCode, $executionTime)
    {
        return $this->update($id, [
            'status' => $status,
            'output' => $output,
            'error_output' => $errorOutput,
            'exit_code' => $exitCode,
            'execution_time' => $executionTime,
            'completed_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Clean up old terminal sessions (keep only last N days)
     */
    public function cleanup($days = 30)
    {
        $sql = "DELETE FROM terminal_sessions
                WHERE started_at < DATE_SUB(NOW(), INTERVAL ? DAY)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days]);

        return $stmt->rowCount();
    }

    /**
     * Get command statistics for a user
     */
    public function getStats($userId)
    {
        $sql = "SELECT
                    COUNT(*) as total_commands,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as successful,
                    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed,
                    AVG(execution_time) as avg_execution_time,
                    MAX(started_at) as last_command_at
                FROM terminal_sessions
                WHERE user_id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);

        return $stmt->fetch();
    }
}
