<?php

require_once __DIR__ . '/Model.php';

/**
 * Database Backup Model
 * Manages database backup history
 *
 * @package ChweziSM
 * @subpackage Models
 */
class DatabaseBackup extends Model
{
    protected string $table = 'database_backups';

    /**
     * Get backups for a specific database
     */
    public function getByDatabase($databaseId)
    {
        $sql = "SELECT * FROM database_backups
                WHERE database_id = ?
                ORDER BY created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$databaseId]);

        return $stmt->fetchAll();
    }

    /**
     * Get recent backups
     */
    public function getRecent($limit = 10)
    {
        $sql = "SELECT b.*, d.database_name
                FROM database_backups b
                LEFT JOIN databases d ON b.database_id = d.id
                WHERE b.status = 'completed'
                ORDER BY b.created_at DESC
                LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);

        return $stmt->fetchAll();
    }

    /**
     * Get failed backups
     */
    public function getFailed()
    {
        $sql = "SELECT b.*, d.database_name
                FROM database_backups b
                LEFT JOIN databases d ON b.database_id = d.id
                WHERE b.status = 'failed'
                ORDER BY b.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Get total backup size
     */
    public function getTotalSize()
    {
        $sql = "SELECT SUM(backup_size) as total_size
                FROM database_backups
                WHERE status = 'completed'";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        $result = $stmt->fetch();
        return $result ? (int)$result['total_size'] : 0;
    }

    /**
     * Clean up old backups (keep only N most recent per database)
     */
    public function cleanupOld($databaseId, $keepCount = 5)
    {
        // Get IDs of backups to keep
        $sql = "SELECT id FROM database_backups
                WHERE database_id = ? AND status = 'completed'
                ORDER BY created_at DESC
                LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$databaseId, $keepCount]);
        $keep = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        if (empty($keep)) {
            return 0;
        }

        // Delete old backups
        $placeholders = implode(',', array_fill(0, count($keep), '?'));
        $sql = "DELETE FROM database_backups
                WHERE database_id = ?
                AND status = 'completed'
                AND id NOT IN ($placeholders)";

        $params = array_merge([$databaseId], $keep);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }
}
