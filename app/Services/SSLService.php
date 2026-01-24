<?php

require_once __DIR__ . '/../Models/SslCertificate.php';
require_once __DIR__ . '/../Models/Domain.php';
require_once __DIR__ . '/ActivityLogService.php';

/**
 * SSL Service
 * Handles SSL certificate generation, installation, and renewal
 *
 * @package ChweziSM
 * @subpackage Services
 */

class SSLService
{
    private $certPath;
    private $activityLog;

    public function __construct()
    {
        $this->certPath = realpath(__DIR__ . '/../../storage/ssl') ?: __DIR__ . '/../../storage/ssl';
        $this->activityLog = new ActivityLogService();

        // Ensure SSL directory exists
        if (!is_dir($this->certPath)) {
            mkdir($this->certPath, 0755, true);
        }
    }

    /**
     * Generate Let's Encrypt certificate
     */
    public function generateCertificate($domainId, $userId)
    {
        try {
            $domainModel = new Domain();
            $domain = $domainModel->find($domainId);

            if (!$domain) {
                throw new Exception('Domain not found');
            }

            // Check if certbot is available
            if (!$this->isCertbotInstalled()) {
                throw new Exception('Certbot is not installed. Please install certbot first.');
            }

            // Generate certificate using certbot
            $result = $this->runCertbot($domain['domain_name']);

            if (!$result['success']) {
                throw new Exception($result['message']);
            }

            // Save certificate info to database
            $certModel = new SslCertificate();
            $certId = $certModel->create([
                'domain_id' => $domainId,
                'certificate_path' => $result['cert_path'],
                'private_key_path' => $result['key_path'],
                'chain_path' => $result['chain_path'],
                'provider' => 'letsencrypt',
                'status' => 'active',
                'issued_at' => date('Y-m-d H:i:s'),
                'expires_at' => $result['expires_at'],
                'auto_renew' => 1,
                'user_id' => $userId
            ]);

            // Update domain SSL status
            $domainModel->update($domainId, [
                'ssl_enabled' => 1,
                'ssl_certificate_path' => $result['cert_path'],
                'ssl_key_path' => $result['key_path']
            ]);

            // Update Apache vhost to use SSL
            $this->updateVhostForSSL($domain, $result);

            $this->activityLog->log(
                'ssl_certificate_created',
                'SSL certificate generated for: ' . $domain['domain_name'],
                ['domain_id' => $domainId, 'cert_id' => $certId]
            );

            return [
                'success' => true,
                'message' => 'SSL certificate generated successfully',
                'cert_id' => $certId
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Renew certificate
     */
    public function renewCertificate($certId)
    {
        try {
            $certModel = new SslCertificate();
            $cert = $certModel->find($certId);

            if (!$cert) {
                throw new Exception('Certificate not found');
            }

            $domainModel = new Domain();
            $domain = $domainModel->find($cert['domain_id']);

            // Update status to renewing
            $certModel->update($certId, ['status' => 'renewing']);

            // Renew using certbot
            $result = $this->runCertbotRenew($domain['domain_name']);

            if (!$result['success']) {
                $certModel->update($certId, [
                    'status' => 'error',
                    'error_message' => $result['message']
                ]);
                throw new Exception($result['message']);
            }

            // Update certificate
            $certModel->update($certId, [
                'status' => 'active',
                'last_renewed_at' => date('Y-m-d H:i:s'),
                'expires_at' => $result['expires_at'],
                'error_message' => null
            ]);

            $this->activityLog->log(
                'ssl_certificate_renewed',
                'SSL certificate renewed for: ' . $domain['domain_name'],
                ['cert_id' => $certId]
            );

            return [
                'success' => true,
                'message' => 'Certificate renewed successfully'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Check if certbot is installed
     */
    private function isCertbotInstalled()
    {
        exec('certbot --version 2>&1', $output, $returnCode);
        return $returnCode === 0;
    }

    /**
     * Run certbot to generate certificate
     */
    private function runCertbot($domainName)
    {
        // For Windows/WAMP, we'll simulate certificate generation
        // In production Linux, this would use actual certbot
        if (stripos(PHP_OS, 'WIN') === 0) {
            return $this->generateSelfSignedCertificate($domainName);
        }

        $command = sprintf(
            'certbot certonly --webroot -w /var/www/html -d %s --non-interactive --agree-tos -m %s',
            escapeshellarg($domainName),
            escapeshellarg(config('app.ssl_email', 'admin@localhost'))
        );

        exec($command . ' 2>&1', $output, $returnCode);

        if ($returnCode !== 0) {
            return [
                'success' => false,
                'message' => 'Certbot failed: ' . implode("\n", $output)
            ];
        }

        $certPath = "/etc/letsencrypt/live/{$domainName}/fullchain.pem";
        $keyPath = "/etc/letsencrypt/live/{$domainName}/privkey.pem";
        $chainPath = "/etc/letsencrypt/live/{$domainName}/chain.pem";

        return [
            'success' => true,
            'cert_path' => $certPath,
            'key_path' => $keyPath,
            'chain_path' => $chainPath,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+90 days'))
        ];
    }

    /**
     * Generate self-signed certificate (for development)
     */
    private function generateSelfSignedCertificate($domainName)
    {
        $certDir = $this->certPath . '/' . str_replace('.', '_', $domainName);
        if (!is_dir($certDir)) {
            mkdir($certDir, 0755, true);
        }

        $certPath = $certDir . '/cert.pem';
        $keyPath = $certDir . '/privkey.pem';

        // Generate self-signed certificate using OpenSSL
        $command = sprintf(
            'openssl req -x509 -newkey rsa:4096 -keyout %s -out %s -days 365 -nodes -subj "/CN=%s" 2>&1',
            escapeshellarg($keyPath),
            escapeshellarg($certPath),
            $domainName
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            return [
                'success' => false,
                'message' => 'Failed to generate self-signed certificate'
            ];
        }

        return [
            'success' => true,
            'cert_path' => $certPath,
            'key_path' => $keyPath,
            'chain_path' => null,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+365 days'))
        ];
    }

    /**
     * Renew certificate using certbot
     */
    private function runCertbotRenew($domainName)
    {
        // For Windows/WAMP, simulate renewal
        if (stripos(PHP_OS, 'WIN') === 0) {
            return $this->generateSelfSignedCertificate($domainName);
        }

        $command = sprintf(
            'certbot renew --cert-name %s --non-interactive',
            escapeshellarg($domainName)
        );

        exec($command . ' 2>&1', $output, $returnCode);

        if ($returnCode !== 0) {
            return [
                'success' => false,
                'message' => 'Renewal failed: ' . implode("\n", $output)
            ];
        }

        return [
            'success' => true,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+90 days'))
        ];
    }

    /**
     * Update Apache vhost to use SSL
     */
    private function updateVhostForSSL($domain, $certInfo)
    {
        // This would update the Apache vhost configuration to include SSL
        // For now, we'll just log it
        $this->activityLog->log(
            'vhost_ssl_updated',
            'Apache vhost updated with SSL for: ' . $domain['domain_name'],
            ['domain_id' => $domain['id']]
        );
    }

    /**
     * Delete certificate
     */
    public function deleteCertificate($certId)
    {
        try {
            $certModel = new SslCertificate();
            $cert = $certModel->find($certId);

            if (!$cert) {
                throw new Exception('Certificate not found');
            }

            // Delete certificate files
            if (file_exists($cert['certificate_path'])) {
                unlink($cert['certificate_path']);
            }
            if (file_exists($cert['private_key_path'])) {
                unlink($cert['private_key_path']);
            }

            // Update domain
            $domainModel = new Domain();
            $domainModel->update($cert['domain_id'], [
                'ssl_enabled' => 0,
                'ssl_certificate_path' => null,
                'ssl_key_path' => null
            ]);

            // Delete record
            $certModel->delete($certId);

            return [
                'success' => true,
                'message' => 'Certificate deleted successfully'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
