<?php

$basePath = dirname(__DIR__);

return [
    'base' => $basePath,
    'public' => $basePath . '/public',
    'app' => $basePath . '/app',
    'config' => $basePath . '/config',
    'storage' => $basePath . '/storage',
    'logs' => $basePath . '/storage/logs',
    'backups' => $basePath . '/storage/backups',
    'cache' => $basePath . '/storage/cache',
    'views' => $basePath . '/resources/views',
    'templates' => $basePath . '/resources/templates',
    'database' => $basePath . '/database',
    'migrations' => $basePath . '/database/migrations',
    'seeds' => $basePath . '/database/seeds',
    'scripts' => $basePath . '/scripts',

    // System paths (Linux)
    'apache_sites_available' => $_ENV['APACHE_SITES_AVAILABLE'] ?? '/etc/apache2/sites-available',
    'apache_sites_enabled' => $_ENV['APACHE_SITES_ENABLED'] ?? '/etc/apache2/sites-enabled',
    'web_root' => $_ENV['WEB_ROOT'] ?? '/var/www',
    'certbot' => $_ENV['CERTBOT_BIN'] ?? '/usr/bin/certbot',
];
