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
        // Set vhost path based on OS
        if (stripos(PHP_OS, 'WIN') === 0) {
            // Windows WAMP vhost path
            $this->vhostPath = 'C:/wamp64/bin/apache/apache2.4.59/conf/extra';
        } else {
            // Linux (Ubuntu/Debian) Apache sites-available path
            $this->vhostPath = config('paths.apache_sites_available', '/etc/apache2/sites-available');
        }
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

            // Update httpd-vhosts.conf to include this file (Windows)
            // or enable site (Linux)
            $this->addVhostInclude($app->subdomain);
            $this->enableSite($app->subdomain);

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

            // Remove from httpd-vhosts.conf (Windows) or disable site (Linux)
            $this->removeVhostInclude($subdomain);
            $this->disableSite($subdomain);

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
        if (stripos(PHP_OS, 'WIN') === 0) {
            // On Windows WAMP, we need to restart Apache
            $command = 'net stop wampapache64 && net start wampapache64 2>&1';
        } else {
            // On Linux (Ubuntu/Debian), use systemctl
            $command = 'sudo systemctl reload apache2 2>&1';
        }

        exec($command, $output, $returnCode);

        // Note: This may fail if not running with proper permissions
        if ($returnCode !== 0) {
            // Log but don't throw exception - manual restart may be needed
            error_log('Apache reload failed: ' . implode("\n", $output));
        }
    }

    /**
     * Enable site on Linux
     */
    private function enableSite(string $subdomain): void
    {
        if (stripos(PHP_OS, 'WIN') === 0) {
            return; // Not needed on Windows
        }

        $command = sprintf('sudo a2ensite vhost-%s.conf 2>&1', escapeshellarg($subdomain));
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            error_log('Failed to enable site: ' . implode("\n", $output));
        }
    }

    /**
     * Disable site on Linux
     */
    private function disableSite(string $subdomain): void
    {
        if (stripos(PHP_OS, 'WIN') === 0) {
            return; // Not needed on Windows
        }

        $command = sprintf('sudo a2dissite vhost-%s.conf 2>&1', escapeshellarg($subdomain));
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            error_log('Failed to disable site: ' . implode("\n", $output));
        }
    }

    /**
     * Test Apache configuration
     */
    public function testConfiguration(): array
    {
        if (stripos(PHP_OS, 'WIN') === 0) {
            $command = 'httpd -t 2>&1';
        } else {
            // Linux (Ubuntu/Debian)
            $command = 'sudo apache2ctl -t 2>&1';
        }

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
