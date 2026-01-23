<?php

require_once __DIR__ . '/Model.php';

/**
 * Role Model
 * Chwezi Server Manager
 */

class Role extends Model
{
    protected string $table = 'roles';

    /**
     * Find role by slug
     * @param string $slug
     * @return array|false
     */
    public function findBySlug(string $slug)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE slug = ?");
        $stmt->execute([$slug]);
        return $stmt->fetch();
    }

    /**
     * Get all roles
     * @return array
     */
    public function all(?string $orderBy = 'name', string $direction = 'ASC'): array
    {
        return parent::all($orderBy, $direction);
    }

    /**
     * Get role's permissions
     * @param int $roleId
     * @return array
     */
    public function getPermissions(int $roleId): array
    {
        $query = "
            SELECT p.* FROM permissions p
            JOIN role_permissions rp ON p.id = rp.permission_id
            WHERE rp.role_id = ?
            ORDER BY p.category, p.name
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$roleId]);

        return $stmt->fetchAll();
    }

    /**
     * Get role with permissions
     * @param int $roleId
     * @return array|false
     */
    public function findWithPermissions(int $roleId)
    {
        $role = $this->find($roleId);

        if (!$role) {
            return false;
        }

        $role['permissions'] = $this->getPermissions($roleId);

        return $role;
    }

    /**
     * Assign permission to role
     * @param int $roleId
     * @param int $permissionId
     * @return bool
     */
    public function assignPermission(int $roleId, int $permissionId): bool
    {
        try {
            $stmt = $this->db->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
            return $stmt->execute([$roleId, $permissionId]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Remove permission from role
     * @param int $roleId
     * @param int $permissionId
     * @return bool
     */
    public function removePermission(int $roleId, int $permissionId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM role_permissions WHERE role_id = ? AND permission_id = ?");
        return $stmt->execute([$roleId, $permissionId]);
    }

    /**
     * Sync role permissions (replace all permissions)
     * @param int $roleId
     * @param array $permissionIds
     * @return bool
     */
    public function syncPermissions(int $roleId, array $permissionIds): bool
    {
        try {
            $this->db->beginTransaction();

            // Remove all existing permissions
            $stmt = $this->db->prepare("DELETE FROM role_permissions WHERE role_id = ?");
            $stmt->execute([$roleId]);

            // Add new permissions
            if (!empty($permissionIds)) {
                $stmt = $this->db->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
                foreach ($permissionIds as $permissionId) {
                    $stmt->execute([$roleId, $permissionId]);
                }
            }

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Check if role has permission
     * @param int $roleId
     * @param string $permissionSlug
     * @return bool
     */
    public function hasPermission(int $roleId, string $permissionSlug): bool
    {
        $query = "
            SELECT COUNT(*) FROM role_permissions rp
            JOIN permissions p ON rp.permission_id = p.id
            WHERE rp.role_id = ? AND p.slug = ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$roleId, $permissionSlug]);

        return $stmt->fetchColumn() > 0;
    }

    /**
     * Get users count for a role
     * @param int $roleId
     * @return int
     */
    public function getUsersCount(int $roleId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE role_id = ?");
        $stmt->execute([$roleId]);
        return (int)$stmt->fetchColumn();
    }
}
