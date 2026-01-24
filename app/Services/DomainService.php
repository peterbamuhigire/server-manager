<?php

require_once __DIR__ . '/../Models/Domain.php';
require_once __DIR__ . '/../Models/App.php';
require_once __DIR__ . '/ActivityLogService.php';

/**
 * Domain Service
 * Handles custom domain DNS checking and Apache configuration
 *
 * @package ChweziSM
 * @subpackage Services
 */

class DomainService
{
    private $vhostPath;
    private $templatePath;
    private $activityLog;

    public function __construct()
    {
        // Windows WAMP vhost path
        $this->vhostPath = 'C:/wamp64/bin/apache/apache2.4.59/conf/extra';
        $this->templatePath = realpath(__DIR__ . '/../Templates') ?: __DIR__ . '/../Templates';
        $this->activityLog = new ActivityLogService();
    }

    /**
     * Configure domain (create vhost)
     */
    public function configureDomain($domain)
    {
        try {
            // Check DNS configuration
            $dnsCheck = $this->checkDNS($domain['domain_name']);

            // Create Apache vhost
            $vhostResult = $this->createDomainVhost($domain);

            if (!$vhostResult['success']) {
                throw new Exception($vhostResult['message']);
            }

            $domainModel = new Domain();
            $domainModel->update($domain['id'], [
                'vhost_configured' => 1,
                'dns_configured' => $dnsCheck['configured'] ? 1 : 0,
                'status' => $dnsCheck['configured'] ? 'active' : 'pending'
            ]);

            $this->activityLog->log(
                'domain_configured',
                'Domain configured: ' . $domain['domain_name'],
                ['domain_id' => $domain['id']]
            );

            return [
                'success' => true,
                'message' => 'Domain configured successfully',
                'dns_configured' => $dnsCheck['configured']
            ];

        } catch (Exception $e) {
            $domainModel = new Domain();
            $domainModel->update($domain['id'], [
                'status' => 'error',
                'error_message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to configure domain: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create Apache virtual host for domain
     */
    private function createDomainVhost($domain)
    {
        try {
            $vhostFile = $this->getVhostFilePath($domain['domain_name']);
            $vhostContent = $this->generateDomainVhostContent($domain);

            // Write vhost file
            if (file_put_contents($vhostFile, $vhostContent) === false) {
                throw new Exception('Failed to write vhost file');
            }

            // Add include to httpd-vhosts.conf
            $this->addVhostInclude($domain['domain_name']);

            // Reload Apache
            $this->reloadApache();

            return [
                'success' => true,
                'message' => 'Virtual host created successfully'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate vhost content for domain
     */
    private function generateDomainVhostContent($domain)
    {
        $domainName = $domain['domain_name'];
        $documentRoot = $this->getDocumentRoot($domain);

        $vhost = "<VirtualHost *:80>\n";
        $vhost .= "    ServerName {$domainName}\n";
        $vhost .= "    ServerAlias www.{$domainName}\n";
        $vhost .= "    DocumentRoot \"{$documentRoot}\"\n\n";
        $vhost .= "    <Directory \"{$documentRoot}\">\n";
        $vhost .= "        Options Indexes FollowSymLinks MultiViews\n";
        $vhost .= "        AllowOverride All\n";
        $vhost .= "        Require all granted\n";
        $vhost .= "    </Directory>\n\n";
        $vhost .= "    ErrorLog \"logs/{$domainName}-error.log\"\n";
        $vhost .= "    CustomLog \"logs/{$domainName}-access.log\" common\n";
        $vhost .= "</VirtualHost>\n";

        return $vhost;
    }

    /**
     * Get document root for domain
     */
    private function getDocumentRoot($domain)
    {
        if (!empty($domain['document_root'])) {
            return $domain['document_root'];
        }

        // If linked to an app, use app's path
        if (!empty($domain['app_id'])) {
            $appModel = new App();
            $app = $appModel->find($domain['app_id']);
            if ($app) {
                $appPath = realpath(__DIR__ . '/../../apps/' . $app['subdomain']);
                if ($app['document_root']) {
                    return $appPath . '/' . $app['document_root'];
                }
                return $appPath;
            }
        }

        // Default
        return realpath(__DIR__ . '/../../public');
    }

    /**
     * Check DNS configuration
     */
    public function checkDNS($domainName)
    {
        $serverIp = $this->getServerIP();

        $dnsRecords = dns_get_record($domainName, DNS_A);

        if ($dnsRecords === false || empty($dnsRecords)) {
            return [
                'configured' => false,
                'message' => 'No DNS records found',
                'expected_ip' => $serverIp
            ];
        }

        foreach ($dnsRecords as $record) {
            if ($record['ip'] === $serverIp) {
                return [
                    'configured' => true,
                    'message' => 'DNS correctly configured',
                    'current_ip' => $record['ip']
                ];
            }
        }

        return [
            'configured' => false,
            'message' => 'DNS points to different IP',
            'current_ip' => $dnsRecords[0]['ip'] ?? 'unknown',
            'expected_ip' => $serverIp
        ];
    }

    /**
     * Get server IP address
     */
    private function getServerIP()
    {
        // For development, return localhost
        if (config('app.env') === 'dev') {
            return '127.0.0.1';
        }

        // Try to get public IP
        $ip = file_get_contents('https://api.ipify.org');
        return $ip ?: '127.0.0.1';
    }

    /**
     * Remove domain configuration
     */
    public function removeDomain($domainName)
    {
        try {
            $vhostFile = $this->getVhostFilePath($domainName);

            // Delete vhost file
            if (file_exists($vhostFile)) {
                unlink($vhostFile);
            }

            // Remove from httpd-vhosts.conf
            $this->removeVhostInclude($domainName);

            // Reload Apache
            $this->reloadApache();

            $this->activityLog->log(
                'domain_removed',
                'Domain removed: ' . $domainName,
                ['domain_name' => $domainName]
            );

            return [
                'success' => true,
                'message' => 'Domain removed successfully'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to remove domain: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get vhost file path
     */
    private function getVhostFilePath($domainName)
    {
        $safeName = str_replace('.', '_', $domainName);
        return $this->vhostPath . '/vhost-' . $safeName . '.conf';
    }

    /**
     * Add include directive
     */
    private function addVhostInclude($domainName)
    {
        $mainVhostFile = $this->vhostPath . '/httpd-vhosts.conf';
        $safeName = str_replace('.', '_', $domainName);
        $includeDirective = 'Include conf/extra/vhost-' . $safeName . '.conf';

        if (!file_exists($mainVhostFile)) {
            file_put_contents($mainVhostFile, '');
        }

        $content = file_get_contents($mainVhostFile);

        if (strpos($content, $includeDirective) !== false) {
            return;
        }

        $content .= "\n" . $includeDirective . "\n";
        file_put_contents($mainVhostFile, $content);
    }

    /**
     * Remove include directive
     */
    private function removeVhostInclude($domainName)
    {
        $mainVhostFile = $this->vhostPath . '/httpd-vhosts.conf';

        if (!file_exists($mainVhostFile)) {
            return;
        }

        $content = file_get_contents($mainVhostFile);
        $safeName = str_replace('.', '_', $domainName);
        $includeDirective = 'Include conf/extra/vhost-' . $safeName . '.conf';

        $content = str_replace($includeDirective . "\n", '', $content);
        $content = str_replace($includeDirective, '', $content);

        file_put_contents($mainVhostFile, $content);
    }

    /**
     * Reload Apache
     */
    private function reloadApache()
    {
        $command = 'net stop wampapache64 && net start wampapache64 2>&1';
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            error_log('Apache reload failed: ' . implode("\n", $output));
        }
    }
}
