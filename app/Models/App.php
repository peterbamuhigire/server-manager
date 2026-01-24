<?php

require_once __DIR__ . '/Model.php';

/**
 * App Model
 * Manages deployed applications
 *
 * @package ChweziSM
 * @subpackage Models
 */
class App extends Model
{
    protected string $table = 'apps';

    /**
     * Get all apps
     */
    public function getAll()
    {
        $sql = "SELECT a.*, u.username as creator_name
                FROM apps a
                LEFT JOIN users u ON a.user_id = u.id
                ORDER BY a.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Get apps by status
     */
    public function getByStatus($status)
    {
        $sql = "SELECT a.*, u.username as creator_name
                FROM apps a
                LEFT JOIN users u ON a.user_id = u.id
                WHERE a.status = ?
                ORDER BY a.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$status]);

        return $stmt->fetchAll();
    }

    /**
     * Get apps by user
     */
    public function getByUser($userId)
    {
        $sql = "SELECT a.*, u.username as creator_name
                FROM apps a
                LEFT JOIN users u ON a.user_id = u.id
                WHERE a.user_id = ?
                ORDER BY a.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    /**
     * Get app by subdomain
     */
    public function getBySubdomain($subdomain)
    {
        return $this->where('subdomain', $subdomain)->first();
    }

    /**
     * Validate app data
     */
    public function validate($data)
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors['name'] = 'Name is required';
        }

        if (empty($data['subdomain'])) {
            $errors['subdomain'] = 'Subdomain is required';
        } elseif (!preg_match('/^[a-z0-9\-]+$/', $data['subdomain'])) {
            $errors['subdomain'] = 'Subdomain must contain only lowercase letters, numbers, and hyphens';
        }

        if (empty($data['git_repo'])) {
            $errors['git_repo'] = 'Git repository URL is required';
        } elseif (!filter_var($data['git_repo'], FILTER_VALIDATE_URL)) {
            $errors['git_repo'] = 'Git repository URL must be valid';
        }

        if (empty($data['git_branch'])) {
            $errors['git_branch'] = 'Git branch is required';
        }

        return $errors;
    }
}
