<?php

require_once __DIR__ . '/../Models/ActivityLog.php';

/**
 * Activity Log Service
 * Handles activity logging operations
 *
 * @package ChweziSM
 * @subpackage Services
 */
class ActivityLogService
{
    private $activityLog;

    public function __construct()
    {
        $this->activityLog = new ActivityLog();
    }

    /**
     * Log an activity
     */
    public function log($action, $description, $metadata = [])
    {
        try {
            // Get current user ID from session if available
            $userId = $_SESSION['user_id'] ?? null;

            if (!$userId) {
                // If no user in session, skip logging or use system user
                return null;
            }

            return $this->activityLog->create([
                'user_id' => $userId,
                'action' => $action,
                'description' => $description,
                'metadata' => !empty($metadata) ? json_encode($metadata) : null,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (Exception $e) {
            // Log errors silently, don't break the app
            error_log('Activity log error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get recent activities
     */
    public function getRecent($limit = 20)
    {
        return $this->activityLog->getRecent($limit);
    }

    /**
     * Get activities by user
     */
    public function getByUser($userId, $limit = 50)
    {
        return $this->activityLog->getByUser($userId, $limit);
    }

    /**
     * Get activities by action
     */
    public function getByAction($action, $limit = 50)
    {
        $sql = "SELECT al.*, u.username
                FROM activity_logs al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE al.action = ?
                ORDER BY al.created_at DESC
                LIMIT ?";

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare($sql);
        $stmt->execute([$action, $limit]);

        return $stmt->fetchAll();
    }
}
