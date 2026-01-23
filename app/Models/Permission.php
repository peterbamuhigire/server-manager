<?php

require_once __DIR__ . '/Model.php';

/**
 * Permission Model
 * Chwezi Server Manager
 */

class Permission extends Model
{
    protected string $table = 'permissions';

    /**
     * Find permission by slug
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
     * Get all permissions
     * @return array
     */
    public function all(?string $orderBy = 'category, name', string $direction = 'ASC'): array
    {
        $query = "SELECT * FROM {$this->table} ORDER BY {$orderBy} {$direction}";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll();
    }

    /**
     * Get permissions grouped by category
     * @return array
     */
    public function allGroupedByCategory(): array
    {
        $permissions = $this->all();
        $grouped = [];

        foreach ($permissions as $permission) {
            $category = $permission['category'];
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $permission;
        }

        return $grouped;
    }

    /**
     * Get permissions by category
     * @param string $category
     * @return array
     */
    public function getByCategory(string $category): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE category = ? ORDER BY name ASC");
        $stmt->execute([$category]);
        return $stmt->fetchAll();
    }

    /**
     * Get all categories
     * @return array
     */
    public function getCategories(): array
    {
        $stmt = $this->db->query("SELECT DISTINCT category FROM {$this->table} ORDER BY category ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get permission IDs by slugs
     * @param array $slugs
     * @return array
     */
    public function getIdsBySlugs(array $slugs): array
    {
        if (empty($slugs)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($slugs), '?'));
        $query = "SELECT id FROM {$this->table} WHERE slug IN ({$placeholders})";

        $stmt = $this->db->prepare($query);
        $stmt->execute($slugs);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
