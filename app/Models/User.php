<?php

require_once __DIR__ . '/Model.php';

/**
 * User Model
 * Chwezi Server Manager
 */

class User extends Model
{
    protected string $table = 'users';

    /**
     * Find user by username
     * @param string $username
     * @return array|false
     */
    public function findByUsername(string $username)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }

    /**
     * Find user by email
     * @param string $email
     * @return array|false
     */
    public function findByEmail(string $email)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    /**
     * Find user with role information
     * @param int $id
     * @return array|false
     */
    public function findWithRole(int $id)
    {
        $query = "
            SELECT u.*, r.name as role_name, r.slug as role_slug
            FROM {$this->table} u
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE u.id = ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Get all users with their roles
     * @return array
     */
    public function allWithRoles(): array
    {
        $query = "
            SELECT u.*, r.name as role_name, r.slug as role_slug
            FROM {$this->table} u
            LEFT JOIN roles r ON u.role_id = r.id
            ORDER BY u.created_at DESC
        ";

        $stmt = $this->db->query($query);
        return $stmt->fetchAll();
    }

    /**
     * Create new user
     * @param array $data
     * @return int|false
     */
    public function create(array $data)
    {
        // Hash password if provided
        if (isset($data['password'])) {
            $data['password'] = hash_password($data['password']);
        }

        return parent::create($data);
    }

    /**
     * Update user
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        // Hash password if being updated
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = hash_password($data['password']);
        } else {
            // Remove password field if empty (not updating)
            unset($data['password']);
        }

        return parent::update($id, $data);
    }

    /**
     * Check if user has specific permission
     * @param int $userId
     * @param string $permissionSlug
     * @return bool
     */
    public function hasPermission(int $userId, string $permissionSlug): bool
    {
        $user = $this->find($userId);

        if (!$user) {
            return false;
        }

        // Root user has all permissions
        if ($user['is_root']) {
            return true;
        }

        // Check if user's role has the permission
        $query = "
            SELECT COUNT(*) FROM role_permissions rp
            JOIN permissions p ON rp.permission_id = p.id
            WHERE rp.role_id = ? AND p.slug = ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$user['role_id'], $permissionSlug]);

        return $stmt->fetchColumn() > 0;
    }

    /**
     * Check if user has any of the given permissions
     * @param int $userId
     * @param array $permissionSlugs
     * @return bool
     */
    public function hasAnyPermission(int $userId, array $permissionSlugs): bool
    {
        foreach ($permissionSlugs as $slug) {
            if ($this->hasPermission($userId, $slug)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has all of the given permissions
     * @param int $userId
     * @param array $permissionSlugs
     * @return bool
     */
    public function hasAllPermissions(int $userId, array $permissionSlugs): bool
    {
        foreach ($permissionSlugs as $slug) {
            if (!$this->hasPermission($userId, $slug)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if user has specific role
     * @param int $userId
     * @param string $roleSlug
     * @return bool
     */
    public function hasRole(int $userId, string $roleSlug): bool
    {
        $query = "
            SELECT COUNT(*) FROM {$this->table} u
            JOIN roles r ON u.role_id = r.id
            WHERE u.id = ? AND r.slug = ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId, $roleSlug]);

        return $stmt->fetchColumn() > 0;
    }

    /**
     * Check if user is root
     * @param int $userId
     * @return bool
     */
    public function isRoot(int $userId): bool
    {
        $user = $this->find($userId);
        return $user && $user['is_root'];
    }

    /**
     * Get user's permissions
     * @param int $userId
     * @return array
     */
    public function getPermissions(int $userId): array
    {
        $user = $this->find($userId);

        if (!$user) {
            return [];
        }

        // Root user gets all permissions
        if ($user['is_root']) {
            $stmt = $this->db->query("SELECT * FROM permissions ORDER BY category, name");
            return $stmt->fetchAll();
        }

        // Get permissions for user's role
        $query = "
            SELECT p.* FROM permissions p
            JOIN role_permissions rp ON p.id = rp.permission_id
            WHERE rp.role_id = ?
            ORDER BY p.category, p.name
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$user['role_id']]);

        return $stmt->fetchAll();
    }

    /**
     * Update last login timestamp
     * @param int $userId
     * @return bool
     */
    public function updateLastLogin(int $userId): bool
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET last_login = NOW() WHERE id = ?");
        return $stmt->execute([$userId]);
    }

    /**
     * Set remember token
     * @param int $userId
     * @param string $token
     * @return bool
     */
    public function setRememberToken(int $userId, string $token): bool
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET remember_token = ? WHERE id = ?");
        return $stmt->execute([$token, $userId]);
    }

    /**
     * Find user by remember token
     * @param string $token
     * @return array|false
     */
    public function findByRememberToken(string $token)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE remember_token = ?");
        $stmt->execute([$token]);
        return $stmt->fetch();
    }

    /**
     * Toggle user status (active/disabled)
     * @param int $userId
     * @return bool
     */
    public function toggleStatus(int $userId): bool
    {
        $query = "
            UPDATE {$this->table}
            SET status = CASE
                WHEN status = 'active' THEN 'disabled'
                ELSE 'active'
            END
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($query);
        return $stmt->execute([$userId]);
    }

    /**
     * Check if username is unique
     * @param string $username
     * @param int|null $excludeId
     * @return bool
     */
    public function isUsernameUnique(string $username, ?int $excludeId = null): bool
    {
        if ($excludeId) {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE username = ? AND id != ?");
            $stmt->execute([$username, $excludeId]);
        } else {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE username = ?");
            $stmt->execute([$username]);
        }

        return $stmt->fetchColumn() == 0;
    }

    /**
     * Check if email is unique
     * @param string $email
     * @param int|null $excludeId
     * @return bool
     */
    public function isEmailUnique(string $email, ?int $excludeId = null): bool
    {
        if ($excludeId) {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE email = ? AND id != ?");
            $stmt->execute([$email, $excludeId]);
        } else {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE email = ?");
            $stmt->execute([$email]);
        }

        return $stmt->fetchColumn() == 0;
    }

    /**
     * Count root users
     * @return int
     */
    public function countRootUsers(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table} WHERE is_root = 1");
        return (int)$stmt->fetchColumn();
    }
}
