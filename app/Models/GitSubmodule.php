<?php

require_once __DIR__ . '/Model.php';

/**
 * Git Submodule Model
 * Manages Git submodules (shared code repositories)
 *
 * @package ChweziSM
 * @subpackage Models
 */
class GitSubmodule extends Model
{
    protected string $table = 'git_submodules';

    /**
     * Get all submodules with user information
     */
    public function getAll()
    {
        $sql = "SELECT s.*, u.username as creator_name
                FROM git_submodules s
                LEFT JOIN users u ON s.user_id = u.id
                ORDER BY s.name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Get submodules linked to a specific app
     */
    public function getByApp($appId)
    {
        $sql = "SELECT s.*, as.relative_path, as.auto_sync
                FROM git_submodules s
                INNER JOIN app_submodules as ON s.id = as.submodule_id
                WHERE as.app_id = ?
                ORDER BY s.name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$appId]);

        return $stmt->fetchAll();
    }

    /**
     * Get apps using a specific submodule
     */
    public function getLinkedApps($submoduleId)
    {
        $sql = "SELECT a.*, as.relative_path, as.auto_sync
                FROM apps a
                INNER JOIN app_submodules as ON a.id = as.app_id
                WHERE as.submodule_id = ?
                ORDER BY a.name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$submoduleId]);

        return $stmt->fetchAll();
    }

    /**
     * Link a submodule to an application
     */
    public function linkToApp($submoduleId, $appId, $relativePath, $autoSync = true)
    {
        $sql = "INSERT INTO app_submodules (app_id, submodule_id, relative_path, auto_sync)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE relative_path = ?, auto_sync = ?";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $appId,
            $submoduleId,
            $relativePath,
            $autoSync ? 1 : 0,
            $relativePath,
            $autoSync ? 1 : 0
        ]);
    }

    /**
     * Unlink a submodule from an application
     */
    public function unlinkFromApp($submoduleId, $appId)
    {
        $sql = "DELETE FROM app_submodules
                WHERE submodule_id = ? AND app_id = ?";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$submoduleId, $appId]);
    }

    /**
     * Update submodule sync status
     */
    public function updateSyncStatus($id, $status, $commitHash = null, $errorMessage = null)
    {
        $data = [
            'status' => $status,
            'last_sync_at' => date('Y-m-d H:i:s')
        ];

        if ($commitHash !== null) {
            $data['last_commit_hash'] = $commitHash;
        }

        if ($errorMessage !== null) {
            $data['error_message'] = $errorMessage;
        }

        return $this->update($id, $data);
    }

    /**
     * Get submodule by name
     */
    public function getByName($name)
    {
        $sql = "SELECT * FROM git_submodules WHERE name = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$name]);

        return $stmt->fetch();
    }

    /**
     * Get statistics
     */
    public function getStats()
    {
        $sql = "SELECT
                    COUNT(*) as total_submodules,
                    COUNT(CASE WHEN status = 'active' THEN 1 END) as active,
                    COUNT(CASE WHEN status = 'error' THEN 1 END) as errors,
                    (SELECT COUNT(DISTINCT app_id) FROM app_submodules) as apps_using_submodules
                FROM git_submodules";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetch();
    }
}
