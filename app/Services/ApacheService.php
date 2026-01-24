<?php

namespace App\Services;

use App\Models\App;
use Exception;

class ApacheService
{
    private string $vhostPath;
    private string $templatePath;
    private ActivityLogService $activityLog;

    public function __construct()
    {
        // Windows WAMP vhost path
        $this->vhostPath = 'C:/wamp64/bin/apache/apache2.4.59/conf/extra';
        $this->templatePath = realpath(__DIR__ . '/../Templates') ?: __DIR__ . '/../Templates';
        $this->activityLog = new ActivityLogService();
    }

    /**
     * Create Apache virtual host configuration for an app
     */
    public function createVirtualHost(App $app): array
    {
        try {
            $vhostFile = $this->getVhostFilePath($app->subdomain);
            $vhostContent = $this->generateVhostContent($app);

            // Write vhost file
            if (file_put_contents($vhostFile, $vhostContent) === false) {
                throw new Exception('Failed to write vhost file');
            }

            // Update httpd-vhosts.conf to include this file
            $this->addVhostInclude($app->subdomain);

            // Reload Apache
            $this->reloadApache();

            $this->activityLog->log(
                'vhost_created',
                'Virtual host created for: ' . $app->subdomain,
                ['app_id' => $app->id, 'subdomain' => $app->subdomain]
            );

            return [
                'success' => true,
                'message' => 'Virtual host created successfully'
            ];

        } catch (Exception $e) {
            $this->activityLog->log(
                'vhost_creation_failed',
                'Virtual host creation failed: ' . $e->getMessage(),
                ['app_id' => $app->id, 'error' => $e->getMessage()]
            );

            return [
                'success' => false,
                'message' => 'Failed to create virtual host: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update existing virtual host configuration
     */
    public function updateVirtualHost(App $app): array
    {
        try {
            $vhostFile = $this->getVhostFilePath($app->subdomain);
            $vhostContent = $this->generateVhostContent($app);

            if (file_put_contents($vhostFile, $vhostContent) === false) {
                throw new Exception('Failed to update vhost file');
            }

            $this->reloadApache();

            $this->activityLog->log(
                'vhost_updated',
                'Virtual host updated for: ' . $app->subdomain,
                ['app_id' => $app->id]
            );

            return [
                'success' => true,
                'message' => 'Virtual host updated successfully'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update virtual host: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Remove virtual host configuration
     */
    public function removeVirtualHost(string $subdomain): array
    {
        try {
            $vhostFile = $this->getVhostFilePath($subdomain);

            // Delete vhost file
            if (file_exists($vhostFile)) {
                unlink($vhostFile);
            }

            // Remove from httpd-vhosts.conf
            $this->removeVhostInclude($subdomain);

            // Reload Apache
            $this->reloadApache();

            $this->activityLog->log(
                'vhost_removed',
                'Virtual host removed for: ' . $subdomain,
                ['subdomain' => $subdomain]
            );

            return [
                'success' => true,
                'message' => 'Virtual host removed successfully'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to remove virtual host: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate virtual host configuration content
     */
    private function generateVhostContent(App $app): string
    {
        $templateFile = $this->templatePath . '/vhost.conf.php';

        if (!file_exists($templateFile)) {
            // Fallback template if file doesn't exist yet
            return $this->getDefaultTemplate($app);
        }

        ob_start();
        include $templateFile;
        return ob_get_clean();
    }

    /**
     * Default vhost template
     */
    private function getDefaultTemplate(App $app): string
    {
        $appPath = realpath(__DIR__ . '/../../apps/' . $app->subdomain) ?: __DIR__ . '/../../apps/' . $app->subdomain;
        $documentRoot = $app->document_root ? $appPath . '/' . $app->document_root : $appPath;
        $serverName = $app->subdomain . '.localhost';

        return <<<VHOST
<VirtualHost *:80>
    ServerName {$serverName}
    DocumentRoot "{$documentRoot}"

    <Directory "{$documentRoot}">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog "logs/{$app->subdomain}-error.log"
    CustomLog "logs/{$app->subdomain}-access.log" common
</VirtualHost>
VHOST;
    }

    /**
     * Get vhost file path
     */
    private function getVhostFilePath(string $subdomain): string
    {
        return $this->vhostPath . '/vhost-' . $subdomain . '.conf';
    }

    /**
     * Add include directive to httpd-vhosts.conf
     */
    private function addVhostInclude(string $subdomain): void
    {
        $mainVhostFile = $this->vhostPath . '/httpd-vhosts.conf';
        $includeDirective = 'Include conf/extra/vhost-' . $subdomain . '.conf';

        if (!file_exists($mainVhostFile)) {
            file_put_contents($mainVhostFile, '');
        }

        $content = file_get_contents($mainVhostFile);

        // Check if already included
        if (strpos($content, $includeDirective) !== false) {
            return;
        }

        // Add include directive
        $content .= "\n" . $includeDirective . "\n";
        file_put_contents($mainVhostFile, $content);
    }

    /**
     * Remove include directive from httpd-vhosts.conf
     */
    private function removeVhostInclude(string $subdomain): void
    {
        $mainVhostFile = $this->vhostPath . '/httpd-vhosts.conf';

        if (!file_exists($mainVhostFile)) {
            return;
        }

        $content = file_get_contents($mainVhostFile);
        $includeDirective = 'Include conf/extra/vhost-' . $subdomain . '.conf';

        // Remove the include directive
        $content = str_replace($includeDirective . "\n", '', $content);
        $content = str_replace($includeDirective, '', $content);

        file_put_contents($mainVhostFile, $content);
    }

    /**
     * Reload Apache configuration
     */
    private function reloadApache(): void
    {
        // On Windows WAMP, we need to restart Apache
        // This assumes the Apache service name is wampapache64
        $command = 'net stop wampapache64 && net start wampapache64 2>&1';

        exec($command, $output, $returnCode);

        // Note: This may fail if not running as administrator
        // In production, you might want to handle this differently
        if ($returnCode !== 0) {
            // Log but don't throw exception - manual restart may be needed
            error_log('Apache reload failed: ' . implode("\n", $output));
        }
    }

    /**
     * Test Apache configuration
     */
    public function testConfiguration(): array
    {
        $command = 'httpd -t 2>&1';
        exec($command, $output, $returnCode);

        return [
            'valid' => $returnCode === 0,
            'output' => implode("\n", $output)
        ];
    }

    /**
     * Check if virtual host exists
     */
    public function vhostExists(string $subdomain): bool
    {
        return file_exists($this->getVhostFilePath($subdomain));
    }

    /**
     * Get virtual host content
     */
    public function getVhostContent(string $subdomain): ?string
    {
        $vhostFile = $this->getVhostFilePath($subdomain);

        if (!file_exists($vhostFile)) {
            return null;
        }

        return file_get_contents($vhostFile);
    }
}
