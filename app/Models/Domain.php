<?php

require_once __DIR__ . '/Model.php';

/**
 * Domain Model
 * Manages custom domain configurations
 *
 * @package ChweziSM
 * @subpackage Models
 */
class Domain extends Model
{
    protected string $table = 'domains';

    /**
     * Get all domains
     */
    public function getAll()
    {
        $sql = "SELECT d.*, u.username as creator_name, a.name as app_name
                FROM domains d
                LEFT JOIN users u ON d.user_id = u.id
                LEFT JOIN apps a ON d.app_id = a.id
                ORDER BY d.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Get domains by status
     */
    public function getByStatus($status)
    {
        $sql = "SELECT d.*, u.username as creator_name, a.name as app_name
                FROM domains d
                LEFT JOIN users u ON d.user_id = u.id
                LEFT JOIN apps a ON d.app_id = a.id
                WHERE d.status = ?
                ORDER BY d.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$status]);

        return $stmt->fetchAll();
    }

    /**
     * Get domains by user
     */
    public function getByUser($userId)
    {
        $sql = "SELECT d.*, u.username as creator_name, a.name as app_name
                FROM domains d
                LEFT JOIN users u ON d.user_id = u.id
                LEFT JOIN apps a ON d.app_id = a.id
                WHERE d.user_id = ?
                ORDER BY d.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    /**
     * Get domain by name
     */
    public function getByDomainName($domainName)
    {
        return $this->where('domain_name', $domainName)->first();
    }

    /**
     * Get domains by app
     */
    public function getByApp($appId)
    {
        $sql = "SELECT d.*, u.username as creator_name
                FROM domains d
                LEFT JOIN users u ON d.user_id = u.id
                WHERE d.app_id = ?
                ORDER BY d.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$appId]);

        return $stmt->fetchAll();
    }

    /**
     * Validate domain data
     */
    public function validate($data)
    {
        $errors = [];

        if (empty($data['domain_name'])) {
            $errors['domain_name'] = 'Domain name is required';
        } elseif (!$this->isValidDomain($data['domain_name'])) {
            $errors['domain_name'] = 'Invalid domain name format';
        }

        return $errors;
    }

    /**
     * Validate domain name format
     */
    private function isValidDomain($domain)
    {
        return preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9][a-z0-9-]{0,61}[a-z0-9]$/i', $domain);
    }
}
