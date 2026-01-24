<?php
/**
 * Apache Virtual Host Configuration Template
 * This template is used to generate vhost files for deployed applications
 */

$appPath = realpath(__DIR__ . '/../../apps/' . $app->subdomain) ?: __DIR__ . '/../../apps/' . $app->subdomain;
$documentRoot = $app->document_root ? $appPath . '/' . trim($app->document_root, '/') : $appPath;
$serverName = $app->subdomain . '.localhost';
?>
<VirtualHost *:80>
    ServerName <?= $serverName ?>

    DocumentRoot "<?= $documentRoot ?>"

    <Directory "<?= $documentRoot ?>">
        Options Indexes FollowSymLinks MultiViews
        AllowOverride All
        Require all granted

        # Enable mod_rewrite if .htaccess exists
        <IfModule mod_rewrite.c>
            RewriteEngine On
        </IfModule>
    </Directory>

    # Logging
    ErrorLog "logs/<?= $app->subdomain ?>-error.log"
    CustomLog "logs/<?= $app->subdomain ?>-access.log" common

    # PHP Configuration
    <IfModule php_module>
        php_flag display_errors On
        php_value error_reporting E_ALL
        php_value max_execution_time 300
        php_value memory_limit 256M
    </IfModule>
</VirtualHost>
