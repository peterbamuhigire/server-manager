<?php

/**
 * ActivityLog Model
 *
 * Handles user activity logging for audit trail and security monitoring.
 * Tracks all user actions with timestamps and context.
 *
 * @package ChweziSM
 * @subpackage Models
 * @version 1.0
 */

class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    /**
     * Log a user activity
     *
     * @param int $userId User ID
     * @param string $action Action performed (login, create_user, etc.)
     * @param string|null $entityType Entity type affected (user, app, domain)
     * @param int|null $entityId Entity ID
     * @param array|null $details Additional details
     * @param string|null $ipAddress IP address
     * @param string|null $userAgent User agent
     * @return bool Success status
     */
    public static function log(
        $userId,
        $action,
        $entityType = null,
        $entityId = null,
        $details = null,
        $ipAddress = null,
        $userAgent = null
    ) {
        $db = db();
        $stmt = $db->prepare("
            INSERT INTO activity_logs (
                user_id, action, entity_type, entity_id,
                details, ip_address, user_agent, created_at
            ) VALUES (
                :user_id, :action, :entity_type, :entity_id,
                :details, :ip_address, :user_agent, NOW()
            )
        ");

        return $stmt->execute([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'details' => $details ? json_encode($details) : null,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent
        ]);
    }

    /**
     * Get recent activities
     *
     * @param int $limit Number of records to retrieve
     * @return array Array of activity logs with user info
     */
    public static function getRecent($limit = 20)
    {
        $db = db();
        $stmt = $db->prepare("
            SELECT
                al.*,
                u.username,
                u.email
            FROM activity_logs al
            INNER JOIN users u ON al.user_id = u.id
            ORDER BY al.created_at DESC
            LIMIT :limit
        ");

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get activities by user
     *
     * @param int $userId User ID
     * @param int $limit Number of records
     * @return array Array of activity logs
     */
    public static function getByUser($userId, $limit = 50)
    {
        $db = db();
        $stmt = $db->prepare("
            SELECT *
            FROM activity_logs
            WHERE user_id = :user_id
            ORDER BY created_at DESC
            LIMIT :limit
        ");

        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get activities by action type
     *
     * @param string $action Action type
     * @param int $limit Number of records
     * @return array Array of activity logs
     */
    public static function getByAction($action, $limit = 50)
    {
        $db = db();
        $stmt = $db->prepare("
            SELECT
                al.*,
                u.username
            FROM activity_logs al
            INNER JOIN users u ON al.user_id = u.id
            WHERE al.action = :action
            ORDER BY al.created_at DESC
            LIMIT :limit
        ");

        $stmt->bindValue(':action', $action, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get activities by entity
     *
     * @param string $entityType Entity type
     * @param int $entityId Entity ID
     * @return array Array of activity logs
     */
    public static function getByEntity($entityType, $entityId)
    {
        $db = db();
        $stmt = $db->prepare("
            SELECT
                al.*,
                u.username
            FROM activity_logs al
            INNER JOIN users u ON al.user_id = u.id
            WHERE al.entity_type = :entity_type
            AND al.entity_id = :entity_id
            ORDER BY al.created_at DESC
        ");

        $stmt->execute([
            'entity_type' => $entityType,
            'entity_id' => $entityId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get activity statistics
     *
     * @param string $period Period (24h, 7d, 30d)
     * @return array Activity counts by action
     */
    public static function getStats($period = '24h')
    {
        $intervals = [
            '24h' => 'INTERVAL 24 HOUR',
            '7d' => 'INTERVAL 7 DAY',
            '30d' => 'INTERVAL 30 DAY'
        ];

        $interval = $intervals[$period] ?? $intervals['24h'];

        $db = db();
        $stmt = $db->prepare("
            SELECT
                action,
                COUNT(*) as count
            FROM activity_logs
            WHERE created_at >= NOW() - $interval
            GROUP BY action
            ORDER BY count DESC
        ");

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get user login history
     *
     * @param int $userId User ID
     * @param int $limit Number of records
     * @return array Array of login activities
     */
    public static function getLoginHistory($userId, $limit = 10)
    {
        $db = db();
        $stmt = $db->prepare("
            SELECT
                ip_address,
                user_agent,
                created_at
            FROM activity_logs
            WHERE user_id = :user_id
            AND action = 'login'
            ORDER BY created_at DESC
            LIMIT :limit
        ");

        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Clean old activity logs
     *
     * @param int $days Number of days to keep
     * @return int Number of deleted rows
     */
    public static function cleanOld($days = 90)
    {
        $db = db();
        $stmt = $db->prepare("
            DELETE FROM activity_logs
            WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)
        ");

        $stmt->execute(['days' => $days]);
        return $stmt->rowCount();
    }

    /**
     * Get total activity count
     *
     * @param string|null $action Optional action filter
     * @return int Total count
     */
    public static function getTotalCount($action = null)
    {
        $db = db();

        if ($action) {
            $stmt = $db->prepare("
                SELECT COUNT(*) as total
                FROM activity_logs
                WHERE action = :action
            ");
            $stmt->execute(['action' => $action]);
        } else {
            $stmt = $db->query("SELECT COUNT(*) as total FROM activity_logs");
        }

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'];
    }

    /**
     * Search activity logs
     *
     * @param array $filters Search filters
     * @param int $limit Number of records
     * @return array Array of matching logs
     */
    public static function search($filters = [], $limit = 50)
    {
        $db = db();
        $where = [];
        $params = [];

        if (isset($filters['user_id'])) {
            $where[] = "al.user_id = :user_id";
            $params['user_id'] = $filters['user_id'];
        }

        if (isset($filters['action'])) {
            $where[] = "al.action = :action";
            $params['action'] = $filters['action'];
        }

        if (isset($filters['entity_type'])) {
            $where[] = "al.entity_type = :entity_type";
            $params['entity_type'] = $filters['entity_type'];
        }

        if (isset($filters['from_date'])) {
            $where[] = "al.created_at >= :from_date";
            $params['from_date'] = $filters['from_date'];
        }

        if (isset($filters['to_date'])) {
            $where[] = "al.created_at <= :to_date";
            $params['to_date'] = $filters['to_date'];
        }

        $whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "
            SELECT
                al.*,
                u.username
            FROM activity_logs al
            INNER JOIN users u ON al.user_id = u.id
            $whereClause
            ORDER BY al.created_at DESC
            LIMIT :limit
        ";

        $stmt = $db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
