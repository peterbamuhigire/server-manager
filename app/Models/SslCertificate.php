<?php

require_once __DIR__ . '/Model.php';

/**
 * SSL Certificate Model
 * Manages SSL certificates for domains
 *
 * @package ChweziSM
 * @subpackage Models
 */
class SslCertificate extends Model
{
    protected string $table = 'ssl_certificates';

    /**
     * Get all certificates
     */
    public function getAll()
    {
        $sql = "SELECT s.*, d.domain_name, u.username as creator_name
                FROM ssl_certificates s
                LEFT JOIN domains d ON s.domain_id = d.id
                LEFT JOIN users u ON s.user_id = u.id
                ORDER BY s.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Get certificates expiring soon
     */
    public function getExpiringSoon($days = 30)
    {
        $sql = "SELECT s.*, d.domain_name
                FROM ssl_certificates s
                LEFT JOIN domains d ON s.domain_id = d.id
                WHERE s.expires_at <= DATE_ADD(NOW(), INTERVAL ? DAY)
                AND s.status = 'active'
                AND s.auto_renew = 1
                ORDER BY s.expires_at ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days]);

        return $stmt->fetchAll();
    }

    /**
     * Get certificate by domain
     */
    public function getByDomain($domainId)
    {
        $sql = "SELECT s.*, d.domain_name
                FROM ssl_certificates s
                LEFT JOIN domains d ON s.domain_id = d.id
                WHERE s.domain_id = ?
                AND s.status = 'active'
                ORDER BY s.created_at DESC
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$domainId]);

        return $stmt->fetch();
    }

    /**
     * Check if certificate is expiring
     */
    public function isExpiring($certificateId, $days = 30)
    {
        $cert = $this->find($certificateId);
        if (!$cert || !$cert['expires_at']) {
            return false;
        }

        $expiryDate = strtotime($cert['expires_at']);
        $threshold = strtotime("+{$days} days");

        return $expiryDate <= $threshold;
    }
}
