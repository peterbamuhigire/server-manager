<?php

require_once __DIR__ . '/Model.php';

/**
 * App Model
 *
 * Manages deployed applications
 *
 * @package ChweziSM
 * @subpackage Models
 */
class App extends Model
{
    protected string $table = 'apps';

    /**
     * Get all apps with optional filters
     *
     * @param array $filters Optional filters (status, app_type, created_by)
     * @return array
     */
    public function getAll(array $filters = []): array
    {
        $sql = "SELECT a.*, u.username as creator_name
                FROM apps a
                LEFT JOIN users u ON a.created_by = u.id
                WHERE 1=1";

        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND a.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['app_type'])) {
            $sql .= " AND a.app_type = ?";
            $params[] = $filters['app_type'];
        }

        if (!empty($filters['created_by'])) {
            $sql .= " AND a.created_by = ?";
            $params[] = $filters['created_by'];
        }

        $sql .= " ORDER BY a.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Get app by ID
     *
     * @param int $id
     * @return array|null
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT a.*, u.username as creator_name
                FROM apps a
                LEFT JOIN users u ON a.created_by = u.id
                WHERE a.id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);

        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Get app by domain
     *
     * @param string $domain
     * @return array|null
     */
    public function getByDomain(string $domain): ?array
    {
        $sql = "SELECT * FROM apps WHERE domain = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$domain]);

        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Create new app
     *
     * @param array $data App data
     * @return int|false App ID or false on failure
     */
    public function create(array $data): int|false
    {
        $sql = "INSERT INTO apps (
                    name, domain, repo_url, branch, app_path, document_root,
                    app_type, status, deployment_strategy, auto_deploy, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);

        $success = $stmt->execute([
            $data['name'],
            $data['domain'],
            $data['repo_url'],
            $data['branch'] ?? 'main',
            $data['app_path'],
            $data['document_root'],
            $data['app_type'] ?? 'php',
            $data['status'] ?? 'deploying',
            $data['deployment_strategy'] ?? 'clone',
            $data['auto_deploy'] ?? 0,
            $data['created_by']
        ]);

        return $success ? (int)$this->db->lastInsertId() : false;
    }

    /**
     * Update app
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [];

        $allowedFields = [
            'name', 'domain', 'repo_url', 'branch', 'app_path', 'document_root',
            'app_type', 'status', 'deployment_strategy', 'auto_deploy',
            'last_deployed', 'last_commit', 'deployment_log'
        ];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $id;
        $sql = "UPDATE apps SET " . implode(', ', $fields) . " WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Update deployment status
     *
     * @param int $id
     * @param string $status
     * @param string|null $commitHash
     * @param string|null $log
     * @return bool
     */
    public function updateDeploymentStatus(
        int $id,
        string $status,
        ?string $commitHash = null,
        ?string $log = null
    ): bool {
        $sql = "UPDATE apps SET
                status = ?,
                last_deployed = CURRENT_TIMESTAMP,
                last_commit = ?,
                deployment_log = ?
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$status, $commitHash, $log, $id]);
    }

    /**
     * Delete app
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM apps WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Check if domain exists
     *
     * @param string $domain
     * @param int|null $excludeId Exclude this ID from check (for updates)
     * @return bool
     */
    public function domainExists(string $domain, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM apps WHERE domain = ?";
        $params = [$domain];

        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn() > 0;
    }

    /**
     * Get apps count by status
     *
     * @return array
     */
    public function getCountByStatus(): array
    {
        $sql = "SELECT status, COUNT(*) as count
                FROM apps
                GROUP BY status";

        $stmt = $this->db->query($sql);
        $results = $stmt->fetchAll();

        $counts = [
            'active' => 0,
            'deploying' => 0,
            'failed' => 0,
            'disabled' => 0
        ];

        foreach ($results as $row) {
            $counts[$row['status']] = (int)$row['count'];
        }

        return $counts;
    }

    /**
     * Get apps count by type
     *
     * @return array
     */
    public function getCountByType(): array
    {
        $sql = "SELECT app_type, COUNT(*) as count
                FROM apps
                GROUP BY app_type";

        $stmt = $this->db->query($sql);
        $results = $stmt->fetchAll();

        $counts = [];
        foreach ($results as $row) {
            $counts[$row['app_type']] = (int)$row['count'];
        }

        return $counts;
    }

    /**
     * Get recently deployed apps
     *
     * @param int $limit
     * @return array
     */
    public function getRecentlyDeployed(int $limit = 5): array
    {
        $sql = "SELECT a.*, u.username as creator_name
                FROM apps a
                LEFT JOIN users u ON a.created_by = u.id
                WHERE a.last_deployed IS NOT NULL
                ORDER BY a.last_deployed DESC
                LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);

        return $stmt->fetchAll();
    }

    /**
     * Get apps with failed deployments
     *
     * @return array
     */
    public function getFailedDeployments(): array
    {
        return $this->getAll(['status' => 'failed']);
    }

    /**
     * Search apps
     *
     * @param string $query
     * @return array
     */
    public function search(string $query): array
    {
        $sql = "SELECT a.*, u.username as creator_name
                FROM apps a
                LEFT JOIN users u ON a.created_by = u.id
                WHERE a.name LIKE ?
                   OR a.domain LIKE ?
                   OR a.repo_url LIKE ?
                ORDER BY a.created_at DESC";

        $searchTerm = "%$query%";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);

        return $stmt->fetchAll();
    }
}
