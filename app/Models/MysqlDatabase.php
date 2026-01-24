<?php

require_once __DIR__ . '/Model.php';

/**
 * MySQL Database Model
 * Manages MySQL databases and their metadata
 *
 * @package ChweziSM
 * @subpackage Models
 */
class MysqlDatabase extends Model
{
    protected string $table = 'databases';

    /**
     * Get all databases with related information
     */
    public function getAll()
    {
        $sql = "SELECT d.*,
                       a.name as app_name,
                       u.username as creator_name,
                       (SELECT COUNT(*) FROM database_backups WHERE database_id = d.id) as backup_count,
                       (SELECT created_at FROM database_backups WHERE database_id = d.id ORDER BY created_at DESC LIMIT 1) as last_backup_at
                FROM databases d
                LEFT JOIN apps a ON d.app_id = a.id
                LEFT JOIN users u ON d.user_id = u.id
                ORDER BY d.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Get database by name
     */
    public function getByName($databaseName)
    {
        $sql = "SELECT d.*,
                       a.name as app_name,
                       u.username as creator_name
                FROM databases d
                LEFT JOIN apps a ON d.app_id = a.id
                LEFT JOIN users u ON d.user_id = u.id
                WHERE d.database_name = ?
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$databaseName]);

        return $stmt->fetch();
    }

    /**
     * Get databases by application
     */
    public function getByApp($appId)
    {
        $sql = "SELECT d.*,
                       u.username as creator_name
                FROM databases d
                LEFT JOIN users u ON d.user_id = u.id
                WHERE d.app_id = ?
                ORDER BY d.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$appId]);

        return $stmt->fetchAll();
    }

    /**
     * Get total database size
     */
    public function getTotalSize()
    {
        $sql = "SELECT SUM(database_size) as total_size FROM databases";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        $result = $stmt->fetch();
        return $result ? (int)$result['total_size'] : 0;
    }

    /**
     * Update database size
     */
    public function updateSize($id, $size)
    {
        return $this->update($id, ['database_size' => $size]);
    }

    /**
     * Check if database name exists
     */
    public function nameExists($databaseName, $excludeId = null)
    {
        $sql = "SELECT id FROM databases WHERE database_name = ?";
        $params = [$databaseName];

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch() !== false;
    }

    /**
     * Validate database name
     * MySQL database names must be 1-64 chars, alphanumeric + underscore
     */
    public function isValidDatabaseName($name)
    {
        return preg_match('/^[a-zA-Z0-9_]{1,64}$/', $name) === 1;
    }
}
