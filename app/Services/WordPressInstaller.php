<?php

require_once __DIR__ . '/../Models/App.php';
require_once __DIR__ . '/../Models/MysqlDatabase.php';
require_once __DIR__ . '/../Models/Domain.php';
require_once __DIR__ . '/DatabaseService.php';
require_once __DIR__ . '/ActivityLogService.php';

/**
 * WordPress Installer Service
 * Handles WordPress download, configuration, and installation
 *
 * @package ChweziSM
 * @subpackage Services
 */
class WordPressInstaller
{
    private $appsPath;
    private $templatePath;
    private $databaseService;
    private $activityLog;

    public function __construct()
    {
        $this->appsPath = realpath(__DIR__ . '/../../apps') ?: __DIR__ . '/../../apps';
        $this->templatePath = realpath(__DIR__ . '/../../resources/templates/wordpress') ?: __DIR__ . '/../../resources/templates/wordpress';
        $this->databaseService = new DatabaseService();
        $this->activityLog = new ActivityLogService();

        // Ensure apps directory exists
        if (!is_dir($this->appsPath)) {
            mkdir($this->appsPath, 0755, true);
        }
    }

    /**
     * Install WordPress site
     */
    public function install($siteName, $subdomain, $adminUser, $adminPassword, $adminEmail, $userId)
    {
        try {
            $installPath = $this->appsPath . '/' . $subdomain;

            // Check if subdomain already exists
            if (is_dir($installPath)) {
                throw new Exception('Subdomain already exists');
            }

            // Download WordPress
            $downloadResult = $this->downloadWordPress($installPath);
            if (!$downloadResult['success']) {
                throw new Exception($downloadResult['message']);
            }

            // Create database
            $dbName = str_replace(['-', '.'], '_', 'wp_' . $subdomain);
            $dbResult = $this->databaseService->createDatabase($dbName, true, $userId);
            if (!$dbResult['success']) {
                $this->cleanup($installPath);
                throw new Exception('Database creation failed: ' . $dbResult['message']);
            }

            // Create wp-config.php
            $configResult = $this->createWpConfig(
                $installPath,
                $dbName,
                $dbResult['database_user'],
                $dbResult['database_password']
            );
            if (!$configResult['success']) {
                $this->databaseService->deleteDatabase($dbResult['database_id']);
                $this->cleanup($installPath);
                throw new Exception($configResult['message']);
            }

            // Create app record
            $appModel = new App();
            $appId = $appModel->create([
                'name' => $siteName,
                'subdomain' => $subdomain,
                'git_repo' => null,
                'git_branch' => null,
                'document_root' => '',
                'status' => 'pending',
                'user_id' => $userId
            ]);

            // Link database to app
            $dbModel = new MysqlDatabase();
            $dbModel->update($dbResult['database_id'], [
                'app_id' => $appId,
                'description' => 'WordPress database for ' . $siteName
            ]);

            // Configure Apache vhost
            $vhostResult = $this->createVhost($subdomain, $installPath);
            if (!$vhostResult['success']) {
                log_message('WordPress vhost creation warning: ' . $vhostResult['message'], 'warning');
            }

            // Run WordPress installation
            $wpInstallResult = $this->runWordPressInstall(
                $subdomain,
                $siteName,
                $adminUser,
                $adminPassword,
                $adminEmail
            );

            if (!$wpInstallResult['success']) {
                log_message('WordPress auto-install failed: ' . $wpInstallResult['message'], 'warning');
            }

            // Update app status
            $appModel->update($appId, ['status' => 'active']);

            $this->activityLog->log(
                'wordpress_installed',
                'WordPress installed: ' . $siteName,
                ['app_id' => $appId, 'subdomain' => $subdomain]
            );

            return [
                'success' => true,
                'message' => 'WordPress installed successfully',
                'app_id' => $appId,
                'database_id' => $dbResult['database_id'],
                'url' => 'http://' . $subdomain . '.localhost',
                'admin_url' => 'http://' . $subdomain . '.localhost/wp-admin',
                'admin_user' => $adminUser,
                'admin_password' => $adminPassword,
                'db_name' => $dbName,
                'db_user' => $dbResult['database_user'],
                'db_password' => $dbResult['database_password']
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Download WordPress from wordpress.org
     */
    private function downloadWordPress($installPath)
    {
        try {
            $wpZipUrl = 'https://wordpress.org/latest.zip';
            $tempZip = sys_get_temp_dir() . '/wordpress-' . time() . '.zip';

            // Download WordPress
            $downloaded = @file_put_contents($tempZip, @file_get_contents($wpZipUrl));
            if ($downloaded === false) {
                throw new Exception('Failed to download WordPress');
            }

            // Extract
            $zip = new ZipArchive();
            if ($zip->open($tempZip) !== true) {
                unlink($tempZip);
                throw new Exception('Failed to open WordPress zip file');
            }

            // Extract to temp directory first
            $tempExtract = sys_get_temp_dir() . '/wp-extract-' . time();
            if (!$zip->extractTo($tempExtract)) {
                $zip->close();
                unlink($tempZip);
                throw new Exception('Failed to extract WordPress');
            }
            $zip->close();
            unlink($tempZip);

            // Move from wordpress subdirectory to install path
            $wpSource = $tempExtract . '/wordpress';
            if (!is_dir($wpSource)) {
                $this->cleanup($tempExtract);
                throw new Exception('WordPress directory not found after extraction');
            }

            // Move files
            if (!rename($wpSource, $installPath)) {
                $this->cleanup($tempExtract);
                throw new Exception('Failed to move WordPress files');
            }

            // Cleanup temp directory
            $this->cleanup($tempExtract);

            return ['success' => true];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Create wp-config.php file
     */
    private function createWpConfig($installPath, $dbName, $dbUser, $dbPassword)
    {
        try {
            $templateFile = $this->templatePath . '/wp-config.php.tpl';

            // Use default template if custom doesn't exist
            if (!file_exists($templateFile)) {
                $wpConfig = $this->getDefaultWpConfigTemplate();
            } else {
                $wpConfig = file_get_contents($templateFile);
            }

            // Generate salt keys
            $salts = $this->generateSaltKeys();

            // Replace placeholders
            $wpConfig = str_replace('{{DB_NAME}}', $dbName, $wpConfig);
            $wpConfig = str_replace('{{DB_USER}}', $dbUser, $wpConfig);
            $wpConfig = str_replace('{{DB_PASSWORD}}', $dbPassword, $wpConfig);
            $wpConfig = str_replace('{{DB_HOST}}', 'localhost', $wpConfig);
            $wpConfig = str_replace('{{TABLE_PREFIX}}', 'wp_', $wpConfig);
            $wpConfig = str_replace('{{SALT_KEYS}}', $salts, $wpConfig);

            // Write wp-config.php
            $configPath = $installPath . '/wp-config.php';
            if (file_put_contents($configPath, $wpConfig) === false) {
                throw new Exception('Failed to write wp-config.php');
            }

            return ['success' => true];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Run WordPress installation via WP-CLI or HTTP
     */
    private function runWordPressInstall($subdomain, $siteName, $adminUser, $adminPassword, $adminEmail)
    {
        try {
            $siteUrl = 'http://' . $subdomain . '.localhost';

            // Try WP-CLI first (if available)
            $wpCliResult = $this->installViaWpCli(
                $this->appsPath . '/' . $subdomain,
                $siteUrl,
                $siteName,
                $adminUser,
                $adminPassword,
                $adminEmail
            );

            if ($wpCliResult['success']) {
                return $wpCliResult;
            }

            // Fallback: Manual installation note
            return [
                'success' => false,
                'message' => 'Automatic installation not available. Complete setup at: ' . $siteUrl . '/wp-admin/install.php'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Install WordPress via WP-CLI
     */
    private function installViaWpCli($wpPath, $url, $title, $adminUser, $adminPassword, $adminEmail)
    {
        $command = sprintf(
            'wp core install --path=%s --url=%s --title=%s --admin_user=%s --admin_password=%s --admin_email=%s 2>&1',
            escapeshellarg($wpPath),
            escapeshellarg($url),
            escapeshellarg($title),
            escapeshellarg($adminUser),
            escapeshellarg($adminPassword),
            escapeshellarg($adminEmail)
        );

        exec($command, $output, $returnCode);

        if ($returnCode === 0) {
            return [
                'success' => true,
                'message' => 'WordPress installed via WP-CLI'
            ];
        }

        return [
            'success' => false,
            'message' => 'WP-CLI not available: ' . implode("\n", $output)
        ];
    }

    /**
     * Create Apache virtual host
     */
    private function createVhost($subdomain, $documentRoot)
    {
        // This would create Apache vhost - for now just return success
        // In production, integrate with ApacheService
        return ['success' => true];
    }

    /**
     * Generate WordPress salt keys
     */
    private function generateSaltKeys()
    {
        $keys = [
            'AUTH_KEY',
            'SECURE_AUTH_KEY',
            'LOGGED_IN_KEY',
            'NONCE_KEY',
            'AUTH_SALT',
            'SECURE_AUTH_SALT',
            'LOGGED_IN_SALT',
            'NONCE_SALT'
        ];

        $salts = '';
        foreach ($keys as $key) {
            $salt = $this->generateRandomString(64);
            $salts .= "define('{$key}', '{$salt}');\n";
        }

        return $salts;
    }

    /**
     * Generate random string for salts
     */
    private function generateRandomString($length)
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+{}[]|;:,.<>?/';
        $string = '';
        $max = strlen($characters) - 1;

        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[random_int(0, $max)];
        }

        return $string;
    }

    /**
     * Get default wp-config.php template
     */
    private function getDefaultWpConfigTemplate()
    {
        return <<<'EOT'
<?php
/**
 * WordPress Configuration
 * Generated by Chwezi Server Manager
 */

// Database settings
define('DB_NAME', '{{DB_NAME}}');
define('DB_USER', '{{DB_USER}}');
define('DB_PASSWORD', '{{DB_PASSWORD}}');
define('DB_HOST', '{{DB_HOST}}');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');

// Security keys
{{SALT_KEYS}}

// Database table prefix
$table_prefix = '{{TABLE_PREFIX}}';

// WordPress debugging
define('WP_DEBUG', false);

// Absolute path to WordPress directory
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

// Sets up WordPress vars and included files
require_once ABSPATH . 'wp-settings.php';
EOT;
    }

    /**
     * Cleanup directory
     */
    private function cleanup($path)
    {
        if (!is_dir($path)) {
            return;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        rmdir($path);
    }
}
