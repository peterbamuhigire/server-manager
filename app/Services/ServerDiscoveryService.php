<?php

/**
 * Server Discovery Service
 * Automatically discovers existing apps, domains, SSL certificates, and databases
 *
 * @package ChweziSM
 * @subpackage Services
 */
class ServerDiscoveryService
{
    private $webRoot;
    private $sitesAvailable;
    private $sitesEnabled;
    private $sslCertsPath;
    private $activityLog;

    public function __construct()
    {
        $this->webRoot = config('paths.web_root', '/var/www') . '/html';
        $this->sitesAvailable = config('paths.apache_sites_available', '/etc/apache2/sites-available');
        $this->sitesEnabled = config('paths.apache_sites_enabled', '/etc/apache2/sites-enabled');
        $this->sslCertsPath = '/etc/letsencrypt/live';

        // Load ActivityLogService if available
        if (class_exists('ActivityLogService')) {
            $this->activityLog = new ActivityLogService();
        } else {
            $this->activityLog = null;
        }
    }

    /**
     * Discover all server resources
     */
    public function discoverAll()
    {
        return [
            'directories' => $this->discoverDirectories(),
            'domains' => $this->discoverDomains(),
            'ssl_certificates' => $this->discoverSSLCertificates(),
            'databases' => $this->discoverDatabases(),
            'discovered_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Discover all with registration status
     */
    public function discoverAllWithStatus()
    {
        $discovered = $this->discoverAll();

        // Check registration status for directories (apps)
        $registeredApps = $this->getRegisteredApps();
        foreach ($discovered['directories'] as &$dir) {
            $dir['registered'] = isset($registeredApps[$dir['name']]);
            $dir['db_id'] = $registeredApps[$dir['name']]['id'] ?? null;
            $dir['db_record'] = $registeredApps[$dir['name']] ?? null;
        }

        // Check registration status for domains
        $registeredDomains = $this->getRegisteredDomains();
        foreach ($discovered['domains'] as &$domain) {
            $serverName = $domain['server_name'] ?? '';
            $domain['registered'] = isset($registeredDomains[$serverName]);
            $domain['db_id'] = $registeredDomains[$serverName]['id'] ?? null;
            $domain['db_record'] = $registeredDomains[$serverName] ?? null;
        }

        // Check registration status for databases
        if (isset($discovered['databases']['databases'])) {
            $registeredDbs = $this->getRegisteredDatabases();
            foreach ($discovered['databases']['databases'] as &$db) {
                $db['registered'] = isset($registeredDbs[$db['name']]);
                $db['db_id'] = $registeredDbs[$db['name']]['id'] ?? null;
                $db['db_record'] = $registeredDbs[$db['name']] ?? null;
            }
        }

        // Check registration status for SSL certificates
        $registeredSSL = $this->getRegisteredSSLCertificates();
        foreach ($discovered['ssl_certificates'] as &$cert) {
            $domain = $cert['domain'] ?? '';
            $cert['registered'] = isset($registeredSSL[$domain]);
            $cert['db_id'] = $registeredSSL[$domain]['id'] ?? null;
            $cert['db_record'] = $registeredSSL[$domain] ?? null;
        }

        return $discovered;
    }

    /**
     * Get registered SSL certificates indexed by domain
     */
    private function getRegisteredSSLCertificates()
    {
        try {
            require_once __DIR__ . '/../Models/SslCertificate.php';
            $sslModel = new SslCertificate();
            $certs = $sslModel->all();

            $indexed = [];
            foreach ($certs as $cert) {
                // Index by domain name stored in the certificate
                $domainName = $cert['domain_name'] ?? $cert['common_name'] ?? '';
                if ($domainName) {
                    $indexed[$domainName] = $cert;
                }
            }
            return $indexed;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get registered apps indexed by subdomain
     */
    private function getRegisteredApps()
    {
        try {
            require_once __DIR__ . '/../Models/App.php';
            $appModel = new App();
            $apps = $appModel->all();

            $indexed = [];
            foreach ($apps as $app) {
                $indexed[$app['subdomain']] = $app;
            }
            return $indexed;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get registered domains indexed by domain_name
     */
    private function getRegisteredDomains()
    {
        try {
            require_once __DIR__ . '/../Models/Domain.php';
            $domainModel = new Domain();
            $domains = $domainModel->all();

            $indexed = [];
            foreach ($domains as $domain) {
                $indexed[$domain['domain_name']] = $domain;
            }
            return $indexed;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get registered databases indexed by database_name
     */
    private function getRegisteredDatabases()
    {
        try {
            require_once __DIR__ . '/../Models/MysqlDatabase.php';
            $dbModel = new MysqlDatabase();
            $databases = $dbModel->all();

            $indexed = [];
            foreach ($databases as $db) {
                $indexed[$db['database_name']] = $db;
            }
            return $indexed;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Register an app (directory) in the database
     */
    public function registerApp($name, $userId)
    {
        try {
            require_once __DIR__ . '/../Models/App.php';
            $appModel = new App();

            // Check if already exists
            $existing = $appModel->where(['subdomain' => $name]);
            if ($existing) {
                return ['success' => false, 'message' => 'App already registered'];
            }

            // Get directory info
            $path = $this->webRoot . '/' . $name;
            $hasPublicDir = is_dir($path . '/public');

            $appId = $appModel->create([
                'name' => ucfirst(str_replace(['-', '_'], ' ', $name)),
                'subdomain' => $name,
                'git_repo' => null,
                'git_branch' => null,
                'document_root' => $hasPublicDir ? 'public' : '',
                'status' => 'active',
                'user_id' => $userId
            ]);

            if ($this->activityLog) {
                $this->activityLog->log('app_registered', 'App registered from discovery: ' . $name, ['app_id' => $appId]);
            }

            return ['success' => true, 'message' => 'App registered successfully', 'id' => $appId];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Unregister an app (remove from database only)
     */
    public function unregisterApp($id)
    {
        try {
            require_once __DIR__ . '/../Models/App.php';
            $appModel = new App();

            $app = $appModel->find($id);
            if (!$app) {
                return ['success' => false, 'message' => 'App not found'];
            }

            $appModel->delete($id);

            if ($this->activityLog) {
                $this->activityLog->log('app_unregistered', 'App unregistered: ' . $app['subdomain'], ['app_id' => $id]);
            }

            return ['success' => true, 'message' => 'App unregistered successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Register a domain in the database
     */
    public function registerDomain($serverName, $documentRoot, $sslEnabled, $userId)
    {
        try {
            require_once __DIR__ . '/../Models/Domain.php';
            $domainModel = new Domain();

            // Check if already exists
            $existing = $domainModel->where(['domain_name' => $serverName]);
            if ($existing) {
                return ['success' => false, 'message' => 'Domain already registered'];
            }

            $domainId = $domainModel->create([
                'domain_name' => $serverName,
                'document_root' => $documentRoot,
                'ssl_enabled' => $sslEnabled ? 1 : 0,
                'vhost_configured' => 1,
                'dns_configured' => 1,
                'status' => 'active',
                'user_id' => $userId
            ]);

            if ($this->activityLog) {
                $this->activityLog->log('domain_registered', 'Domain registered from discovery: ' . $serverName, ['domain_id' => $domainId]);
            }

            return ['success' => true, 'message' => 'Domain registered successfully', 'id' => $domainId];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Unregister a domain (remove from database only)
     */
    public function unregisterDomain($id)
    {
        try {
            require_once __DIR__ . '/../Models/Domain.php';
            $domainModel = new Domain();

            $domain = $domainModel->find($id);
            if (!$domain) {
                return ['success' => false, 'message' => 'Domain not found'];
            }

            $domainModel->delete($id);

            if ($this->activityLog) {
                $this->activityLog->log('domain_unregistered', 'Domain unregistered: ' . $domain['domain_name'], ['domain_id' => $id]);
            }

            return ['success' => true, 'message' => 'Domain unregistered successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Register a database in the database
     */
    public function registerDatabase($dbName, $userId)
    {
        try {
            require_once __DIR__ . '/../Models/MysqlDatabase.php';
            $dbModel = new MysqlDatabase();

            // Check if already exists
            $existing = $dbModel->where(['database_name' => $dbName]);
            if ($existing) {
                return ['success' => false, 'message' => 'Database already registered'];
            }

            // Get database info
            $dbInfo = $this->getDatabaseInfo($dbName);

            $dbId = $dbModel->create([
                'database_name' => $dbName,
                'database_user' => null,
                'database_size' => $dbInfo['size'] ?? 0,
                'status' => 'active',
                'user_id' => $userId
            ]);

            if ($this->activityLog) {
                $this->activityLog->log('database_registered', 'Database registered from discovery: ' . $dbName, ['database_id' => $dbId]);
            }

            return ['success' => true, 'message' => 'Database registered successfully', 'id' => $dbId];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Unregister a database (remove from our database only, not the actual MySQL database)
     */
    public function unregisterDatabase($id)
    {
        try {
            require_once __DIR__ . '/../Models/MysqlDatabase.php';
            $dbModel = new MysqlDatabase();

            $db = $dbModel->find($id);
            if (!$db) {
                return ['success' => false, 'message' => 'Database record not found'];
            }

            $dbModel->delete($id);

            if ($this->activityLog) {
                $this->activityLog->log('database_unregistered', 'Database unregistered: ' . $db['database_name'], ['database_id' => $id]);
            }

            return ['success' => true, 'message' => 'Database unregistered successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get info about a specific database
     */
    private function getDatabaseInfo($dbName)
    {
        try {
            $dbConfig = config('database');

            $pdo = new PDO(
                "mysql:host={$dbConfig['host']}",
                $dbConfig['username'],
                $dbConfig['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            $sizeQuery = "SELECT SUM(data_length + index_length) as size FROM information_schema.TABLES WHERE table_schema = ?";
            $stmt = $pdo->prepare($sizeQuery);
            $stmt->execute([$dbName]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return ['size' => (int)($result['size'] ?? 0)];
        } catch (Exception $e) {
            return ['size' => 0];
        }
    }

    /**
     * Discover directories in web root
     */
    public function discoverDirectories()
    {
        $directories = [];

        if (!is_dir($this->webRoot)) {
            return $directories;
        }

        $items = scandir($this->webRoot);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $this->webRoot . '/' . $item;

            if (!is_dir($fullPath)) {
                continue;
            }

            $dirInfo = [
                'name' => $item,
                'path' => $fullPath,
                'size' => $this->getDirectorySize($fullPath),
                'modified' => filemtime($fullPath),
                'is_git_repo' => is_dir($fullPath . '/.git'),
                'has_composer' => file_exists($fullPath . '/composer.json'),
                'has_package_json' => file_exists($fullPath . '/package.json'),
                'is_wordpress' => file_exists($fullPath . '/wp-config.php') || file_exists($fullPath . '/wp-config-sample.php'),
                'is_laravel' => file_exists($fullPath . '/artisan'),
                'has_public_dir' => is_dir($fullPath . '/public'),
                'framework' => $this->detectFramework($fullPath),
                'linked_domain' => null
            ];

            $directories[] = $dirInfo;
        }

        return $directories;
    }

    /**
     * Discover Apache virtual host configurations
     */
    public function discoverDomains()
    {
        $domains = [];

        if (!is_dir($this->sitesAvailable)) {
            return $domains;
        }

        $files = scandir($this->sitesAvailable);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || !preg_match('/\.conf$/', $file)) {
                continue;
            }

            $confPath = $this->sitesAvailable . '/' . $file;
            $parsed = $this->parseApacheConfig($confPath);

            if (!empty($parsed)) {
                // Check if site is enabled
                $enabledPath = $this->sitesEnabled . '/' . $file;
                $parsed['enabled'] = file_exists($enabledPath) || is_link($enabledPath);
                $parsed['config_file'] = $file;
                $parsed['config_path'] = $confPath;

                $domains[] = $parsed;
            }
        }

        return $domains;
    }

    /**
     * Parse Apache virtual host configuration file
     */
    private function parseApacheConfig($filePath)
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return null;
        }

        $content = file_get_contents($filePath);

        $config = [
            'server_name' => null,
            'server_aliases' => [],
            'document_root' => null,
            'port' => 80,
            'ssl_enabled' => false,
            'ssl_certificate' => null,
            'ssl_certificate_key' => null,
            'error_log' => null,
            'access_log' => null,
            'directory_options' => [],
            'raw_config' => $content
        ];

        // Parse VirtualHost port
        if (preg_match('/<VirtualHost\s+\*:(\d+)>/i', $content, $matches)) {
            $config['port'] = (int)$matches[1];
            if ($config['port'] === 443) {
                $config['ssl_enabled'] = true;
            }
        }

        // Parse ServerName (ignore comments)
        if (preg_match('/^\s*ServerName\s+(\S+)/im', $content, $matches)) {
            $serverName = trim($matches[1]);
            // Ignore if it looks like a comment or placeholder
            if (!preg_match('/^(#|www\.example|example\.com|directive|the\s)/i', $serverName)) {
                $config['server_name'] = $serverName;
            }
        }

        // Parse ServerAlias
        if (preg_match_all('/ServerAlias\s+(.+)/i', $content, $matches)) {
            foreach ($matches[1] as $alias) {
                $aliases = preg_split('/\s+/', trim($alias));
                $config['server_aliases'] = array_merge($config['server_aliases'], $aliases);
            }
        }

        // Parse DocumentRoot
        if (preg_match('/DocumentRoot\s+["\']?([^"\'>\n]+)["\']?/i', $content, $matches)) {
            $config['document_root'] = trim($matches[1]);
        }

        // Parse SSL settings
        if (preg_match('/SSLEngine\s+on/i', $content)) {
            $config['ssl_enabled'] = true;
        }

        if (preg_match('/SSLCertificateFile\s+(.+)/i', $content, $matches)) {
            $config['ssl_certificate'] = trim($matches[1]);
        }

        if (preg_match('/SSLCertificateKeyFile\s+(.+)/i', $content, $matches)) {
            $config['ssl_certificate_key'] = trim($matches[1]);
        }

        // Parse log files
        if (preg_match('/ErrorLog\s+(.+)/i', $content, $matches)) {
            $config['error_log'] = trim($matches[1]);
        }

        if (preg_match('/CustomLog\s+(\S+)/i', $content, $matches)) {
            $config['access_log'] = trim($matches[1]);
        }

        // Only return if we found meaningful configuration
        if ($config['server_name'] || $config['document_root']) {
            return $config;
        }

        return null;
    }

    /**
     * Discover SSL certificates
     */
    public function discoverSSLCertificates()
    {
        $certificates = [];

        // Check Let's Encrypt certificates
        if (is_dir($this->sslCertsPath)) {
            $domains = scandir($this->sslCertsPath);

            foreach ($domains as $domain) {
                if ($domain === '.' || $domain === '..' || $domain === 'README') {
                    continue;
                }

                $certPath = $this->sslCertsPath . '/' . $domain;

                if (is_dir($certPath)) {
                    $certInfo = $this->parseCertificate($certPath . '/fullchain.pem');
                    $certificates[] = [
                        'domain' => $domain,
                        'provider' => 'letsencrypt',
                        'path' => $certPath,
                        'cert_file' => $certPath . '/fullchain.pem',
                        'key_file' => $certPath . '/privkey.pem',
                        'chain_file' => $certPath . '/chain.pem',
                        'valid' => file_exists($certPath . '/fullchain.pem'),
                        'expires_at' => $certInfo['expires_at'] ?? null,
                        'issued_at' => $certInfo['issued_at'] ?? null,
                        'issuer' => $certInfo['issuer'] ?? null
                    ];
                }
            }
        }

        // Check Apache SSL directory
        $apacheSSLPath = '/etc/apache2/ssl';
        if (is_dir($apacheSSLPath)) {
            $files = scandir($apacheSSLPath);

            foreach ($files as $file) {
                if (preg_match('/\.crt$/', $file)) {
                    $certFile = $apacheSSLPath . '/' . $file;
                    $keyFile = $apacheSSLPath . '/' . str_replace('.crt', '.key', $file);
                    $certInfo = $this->parseCertificate($certFile);

                    $certificates[] = [
                        'domain' => str_replace('.crt', '', $file),
                        'provider' => 'self-signed',
                        'path' => $apacheSSLPath,
                        'cert_file' => $certFile,
                        'key_file' => file_exists($keyFile) ? $keyFile : null,
                        'chain_file' => null,
                        'valid' => file_exists($certFile),
                        'expires_at' => $certInfo['expires_at'] ?? null,
                        'issued_at' => $certInfo['issued_at'] ?? null,
                        'issuer' => $certInfo['issuer'] ?? null
                    ];
                }
            }
        }

        // Also scan from Apache configs for SSL certificates
        $domains = $this->discoverDomains();
        foreach ($domains as $domain) {
            if ($domain['ssl_enabled'] && $domain['ssl_certificate']) {
                $certPath = $domain['ssl_certificate'];
                if (file_exists($certPath)) {
                    $certInfo = $this->parseCertificate($certPath);

                    // Check if already in list
                    $exists = false;
                    foreach ($certificates as $cert) {
                        if ($cert['cert_file'] === $certPath) {
                            $exists = true;
                            break;
                        }
                    }

                    if (!$exists) {
                        $certificates[] = [
                            'domain' => $domain['server_name'],
                            'provider' => 'custom',
                            'path' => dirname($certPath),
                            'cert_file' => $certPath,
                            'key_file' => $domain['ssl_certificate_key'],
                            'chain_file' => null,
                            'valid' => true,
                            'expires_at' => $certInfo['expires_at'] ?? null,
                            'issued_at' => $certInfo['issued_at'] ?? null,
                            'issuer' => $certInfo['issuer'] ?? null
                        ];
                    }
                }
            }
        }

        return $certificates;
    }

    /**
     * Parse SSL certificate to get expiry and other info
     */
    private function parseCertificate($certPath)
    {
        if (!file_exists($certPath)) {
            return [];
        }

        $certData = @file_get_contents($certPath);
        if (!$certData) {
            return [];
        }

        $cert = @openssl_x509_parse($certData);
        if (!$cert) {
            return [];
        }

        return [
            'expires_at' => isset($cert['validTo_time_t']) ? date('Y-m-d H:i:s', $cert['validTo_time_t']) : null,
            'issued_at' => isset($cert['validFrom_time_t']) ? date('Y-m-d H:i:s', $cert['validFrom_time_t']) : null,
            'issuer' => $cert['issuer']['O'] ?? $cert['issuer']['CN'] ?? 'Unknown',
            'subject' => $cert['subject']['CN'] ?? null
        ];
    }

    /**
     * Discover MySQL databases
     */
    public function discoverDatabases()
    {
        $databases = [];

        try {
            $dbConfig = config('database');

            $pdo = new PDO(
                "mysql:host={$dbConfig['host']}",
                $dbConfig['username'],
                $dbConfig['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            // Get all databases
            $stmt = $pdo->query("SHOW DATABASES");
            $allDatabases = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // System databases to exclude from main list
            $systemDbs = ['information_schema', 'mysql', 'performance_schema', 'sys'];

            foreach ($allDatabases as $dbName) {
                $isSystem = in_array($dbName, $systemDbs);

                // Get database size
                $sizeQuery = "SELECT
                    SUM(data_length + index_length) as size,
                    COUNT(*) as tables_count
                    FROM information_schema.TABLES
                    WHERE table_schema = ?";
                $sizeStmt = $pdo->prepare($sizeQuery);
                $sizeStmt->execute([$dbName]);
                $sizeInfo = $sizeStmt->fetch(PDO::FETCH_ASSOC);

                // Get character set
                $charsetQuery = "SELECT DEFAULT_CHARACTER_SET_NAME, DEFAULT_COLLATION_NAME
                                FROM information_schema.SCHEMATA
                                WHERE SCHEMA_NAME = ?";
                $charsetStmt = $pdo->prepare($charsetQuery);
                $charsetStmt->execute([$dbName]);
                $charsetInfo = $charsetStmt->fetch(PDO::FETCH_ASSOC);

                $databases[] = [
                    'name' => $dbName,
                    'size' => (int)($sizeInfo['size'] ?? 0),
                    'size_formatted' => $this->formatBytes((int)($sizeInfo['size'] ?? 0)),
                    'tables_count' => (int)($sizeInfo['tables_count'] ?? 0),
                    'charset' => $charsetInfo['DEFAULT_CHARACTER_SET_NAME'] ?? 'unknown',
                    'collation' => $charsetInfo['DEFAULT_COLLATION_NAME'] ?? 'unknown',
                    'is_system' => $isSystem,
                    'is_wordpress' => $this->isWordPressDatabase($pdo, $dbName),
                    'linked_app' => $this->guessLinkedApp($dbName)
                ];
            }

            // Get database users
            $usersQuery = "SELECT User, Host FROM mysql.user WHERE User != '' ORDER BY User";
            $usersStmt = $pdo->query($usersQuery);
            $users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            return [
                'error' => 'Database connection failed: ' . $e->getMessage(),
                'databases' => [],
                'users' => []
            ];
        }

        return [
            'databases' => $databases,
            'users' => $users ?? [],
            'total_count' => count($databases),
            'user_count' => count($systemDbs) > 0 ? count($databases) - count(array_filter($databases, fn($d) => $d['is_system'])) : count($databases)
        ];
    }

    /**
     * Check if database is a WordPress database
     */
    private function isWordPressDatabase($pdo, $dbName)
    {
        try {
            $query = "SELECT COUNT(*) FROM information_schema.TABLES
                      WHERE table_schema = ?
                      AND (table_name LIKE '%_options' OR table_name LIKE '%_posts' OR table_name LIKE '%_users')";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$dbName]);
            return $stmt->fetchColumn() >= 3;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Guess linked application from database name
     */
    private function guessLinkedApp($dbName)
    {
        // Common patterns: wp_sitename, sitename_db, sitename_wordpress
        $patterns = [
            '/^wp_(.+)$/' => '$1',
            '/^(.+)_db$/' => '$1',
            '/^(.+)_wordpress$/' => '$1',
            '/^(.+)_wp$/' => '$1',
        ];

        foreach ($patterns as $pattern => $replacement) {
            if (preg_match($pattern, $dbName, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Detect framework used in directory
     */
    private function detectFramework($path)
    {
        if (file_exists($path . '/artisan')) {
            return 'Laravel';
        }
        if (file_exists($path . '/wp-config.php') || file_exists($path . '/wp-config-sample.php')) {
            return 'WordPress';
        }
        if (file_exists($path . '/bin/console') && file_exists($path . '/symfony.lock')) {
            return 'Symfony';
        }
        if (file_exists($path . '/yii')) {
            return 'Yii';
        }
        if (file_exists($path . '/craft')) {
            return 'Craft CMS';
        }
        if (file_exists($path . '/package.json')) {
            $pkg = json_decode(file_get_contents($path . '/package.json'), true);
            if (isset($pkg['dependencies']['next'])) {
                return 'Next.js';
            }
            if (isset($pkg['dependencies']['nuxt'])) {
                return 'Nuxt.js';
            }
            if (isset($pkg['dependencies']['react'])) {
                return 'React';
            }
            if (isset($pkg['dependencies']['vue'])) {
                return 'Vue.js';
            }
            if (isset($pkg['dependencies']['express'])) {
                return 'Express.js';
            }
            return 'Node.js';
        }
        if (is_dir($path . '/app') && is_dir($path . '/public') && file_exists($path . '/public/index.php')) {
            return 'PHP MVC';
        }
        if (file_exists($path . '/index.php')) {
            return 'PHP';
        }
        if (file_exists($path . '/index.html')) {
            return 'Static HTML';
        }

        return 'Unknown';
    }

    /**
     * Get directory size (approximate for performance)
     */
    private function getDirectorySize($path)
    {
        $size = 0;

        // Use du command for better performance on Linux
        if (stripos(PHP_OS, 'WIN') !== 0) {
            exec("du -sb " . escapeshellarg($path) . " 2>/dev/null | cut -f1", $output);
            if (!empty($output[0])) {
                return (int)$output[0];
            }
        }

        // Fallback to PHP
        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
        } catch (Exception $e) {
            // Ignore permission errors
        }

        return $size;
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Import discovered items into database
     */
    public function importDiscoveredItems($items, $userId)
    {
        $results = [
            'apps' => ['imported' => 0, 'skipped' => 0, 'errors' => []],
            'domains' => ['imported' => 0, 'skipped' => 0, 'errors' => []],
            'databases' => ['imported' => 0, 'skipped' => 0, 'errors' => []],
            'ssl' => ['imported' => 0, 'skipped' => 0, 'errors' => []]
        ];

        // Import apps (directories)
        if (!empty($items['directories'])) {
            require_once __DIR__ . '/../Models/App.php';
            $appModel = new App();

            foreach ($items['directories'] as $dir) {
                if ($dir['name'] === 'server-manager') {
                    continue; // Skip self
                }

                // Check if already exists
                $existing = $appModel->where(['subdomain' => $dir['name']]);
                if ($existing) {
                    $results['apps']['skipped']++;
                    continue;
                }

                try {
                    $appModel->create([
                        'name' => ucfirst(str_replace(['-', '_'], ' ', $dir['name'])),
                        'subdomain' => $dir['name'],
                        'git_repo' => null,
                        'git_branch' => null,
                        'document_root' => $dir['has_public_dir'] ? 'public' : '',
                        'status' => 'active',
                        'user_id' => $userId
                    ]);
                    $results['apps']['imported']++;
                } catch (Exception $e) {
                    $results['apps']['errors'][] = $dir['name'] . ': ' . $e->getMessage();
                }
            }
        }

        // Import domains
        if (!empty($items['domains'])) {
            require_once __DIR__ . '/../Models/Domain.php';
            $domainModel = new Domain();

            foreach ($items['domains'] as $domain) {
                if (empty($domain['server_name'])) {
                    continue;
                }

                // Check if already exists
                $existing = $domainModel->where(['domain_name' => $domain['server_name']]);
                if ($existing) {
                    $results['domains']['skipped']++;
                    continue;
                }

                try {
                    $domainModel->create([
                        'domain_name' => $domain['server_name'],
                        'document_root' => $domain['document_root'],
                        'ssl_enabled' => $domain['ssl_enabled'] ? 1 : 0,
                        'vhost_configured' => 1,
                        'dns_configured' => 1,
                        'status' => $domain['enabled'] ? 'active' : 'inactive',
                        'user_id' => $userId
                    ]);
                    $results['domains']['imported']++;
                } catch (Exception $e) {
                    $results['domains']['errors'][] = $domain['server_name'] . ': ' . $e->getMessage();
                }
            }
        }

        // Import databases
        if (!empty($items['databases']['databases'])) {
            require_once __DIR__ . '/../Models/MysqlDatabase.php';
            $dbModel = new MysqlDatabase();

            foreach ($items['databases']['databases'] as $db) {
                if ($db['is_system']) {
                    continue; // Skip system databases
                }

                // Check if already exists
                $existing = $dbModel->where(['database_name' => $db['name']]);
                if ($existing) {
                    $results['databases']['skipped']++;
                    continue;
                }

                try {
                    $dbModel->create([
                        'database_name' => $db['name'],
                        'database_user' => null,
                        'database_size' => $db['size'],
                        'status' => 'active',
                        'user_id' => $userId
                    ]);
                    $results['databases']['imported']++;
                } catch (Exception $e) {
                    $results['databases']['errors'][] = $db['name'] . ': ' . $e->getMessage();
                }
            }
        }

        // Log activity
        if ($this->activityLog) {
            $this->activityLog->log(
                'server_discovery_import',
                'Imported discovered server resources',
                $results
            );
        }

        return $results;
    }

    /**
     * Link discovered resources together
     */
    public function linkResources()
    {
        $linked = [
            'apps_to_domains' => 0,
            'apps_to_databases' => 0
        ];

        // This would analyze and link apps to their domains and databases
        // Based on document root matching, database naming patterns, etc.

        return $linked;
    }

    /**
     * Register an SSL certificate in the database
     */
    public function registerSSLCertificate($certData, $userId)
    {
        try {
            require_once __DIR__ . '/../Models/SslCertificate.php';
            $sslModel = new SslCertificate();

            // Check if already exists
            $existing = $sslModel->where('domain_name', $certData['domain']);
            if ($existing) {
                return [
                    'success' => false,
                    'message' => 'SSL certificate for this domain is already registered'
                ];
            }

            // Create the certificate record
            $id = $sslModel->create([
                'domain_id' => null, // Can be linked to domain later
                'domain_name' => $certData['domain'],
                'provider' => $certData['provider'] ?? 'unknown',
                'certificate_path' => $certData['cert_file'] ?? null,
                'private_key_path' => $certData['key_file'] ?? null,
                'chain_path' => $certData['chain_file'] ?? null,
                'issued_at' => $certData['issued_at'] ?? null,
                'expires_at' => $certData['expires_at'] ?? null,
                'auto_renew' => ($certData['provider'] === 'letsencrypt') ? 1 : 0,
                'status' => 'active',
                'user_id' => $userId
            ]);

            if ($this->activityLog) {
                $this->activityLog->log(
                    'ssl_registered',
                    'SSL certificate registered: ' . $certData['domain'],
                    ['ssl_id' => $id]
                );
            }

            return [
                'success' => true,
                'message' => 'SSL certificate registered successfully',
                'id' => $id
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to register SSL certificate: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Unregister an SSL certificate from the database
     */
    public function unregisterSSLCertificate($id)
    {
        try {
            require_once __DIR__ . '/../Models/SslCertificate.php';
            $sslModel = new SslCertificate();

            $cert = $sslModel->find($id);
            if (!$cert) {
                return [
                    'success' => false,
                    'message' => 'SSL certificate not found'
                ];
            }

            $sslModel->delete($id);

            if ($this->activityLog) {
                $this->activityLog->log(
                    'ssl_unregistered',
                    'SSL certificate unregistered: ' . ($cert['domain_name'] ?? 'ID: ' . $id),
                    ['ssl_id' => $id]
                );
            }

            return [
                'success' => true,
                'message' => 'SSL certificate unregistered successfully (files remain intact)'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to unregister SSL certificate: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Clean up orphaned database entries (entities that no longer exist on server)
     */
    public function cleanupOrphanedEntries()
    {
        $results = [
            'apps' => ['removed' => 0, 'items' => []],
            'domains' => ['removed' => 0, 'items' => []],
            'databases' => ['removed' => 0, 'items' => []],
            'ssl' => ['removed' => 0, 'items' => []]
        ];

        // Get current server state
        $discovered = $this->discoverAll();

        // Build lookup arrays for what exists on server
        $existingDirs = [];
        foreach ($discovered['directories'] as $dir) {
            $existingDirs[$dir['name']] = true;
        }

        $existingDomains = [];
        foreach ($discovered['domains'] as $domain) {
            if (!empty($domain['server_name'])) {
                $existingDomains[$domain['server_name']] = true;
            }
        }

        $existingDatabases = [];
        if (isset($discovered['databases']['databases'])) {
            foreach ($discovered['databases']['databases'] as $db) {
                $existingDatabases[$db['name']] = true;
            }
        }

        $existingSSL = [];
        foreach ($discovered['ssl_certificates'] as $cert) {
            $existingSSL[$cert['domain']] = true;
        }

        // Clean up orphaned apps
        try {
            require_once __DIR__ . '/../Models/App.php';
            $appModel = new App();
            $apps = $appModel->all();

            foreach ($apps as $app) {
                // Check if the app directory exists
                $appPath = $this->webRoot . '/' . $app['subdomain'];
                if (!is_dir($appPath) && !isset($existingDirs[$app['subdomain']])) {
                    $appModel->delete($app['id']);
                    $results['apps']['removed']++;
                    $results['apps']['items'][] = $app['name'] . ' (' . $app['subdomain'] . ')';
                }
            }
        } catch (Exception $e) {
            $results['apps']['error'] = $e->getMessage();
        }

        // Clean up orphaned domains
        try {
            require_once __DIR__ . '/../Models/Domain.php';
            $domainModel = new Domain();
            $domains = $domainModel->all();

            foreach ($domains as $domain) {
                // Check if domain config exists
                if (!isset($existingDomains[$domain['domain_name']])) {
                    $domainModel->delete($domain['id']);
                    $results['domains']['removed']++;
                    $results['domains']['items'][] = $domain['domain_name'];
                }
            }
        } catch (Exception $e) {
            $results['domains']['error'] = $e->getMessage();
        }

        // Clean up orphaned databases
        try {
            require_once __DIR__ . '/../Models/MysqlDatabase.php';
            $dbModel = new MysqlDatabase();
            $databases = $dbModel->all();

            foreach ($databases as $db) {
                if (!isset($existingDatabases[$db['database_name']])) {
                    $dbModel->delete($db['id']);
                    $results['databases']['removed']++;
                    $results['databases']['items'][] = $db['database_name'];
                }
            }
        } catch (Exception $e) {
            $results['databases']['error'] = $e->getMessage();
        }

        // Clean up orphaned SSL certificates
        try {
            require_once __DIR__ . '/../Models/SslCertificate.php';
            $sslModel = new SslCertificate();
            $certs = $sslModel->all();

            foreach ($certs as $cert) {
                $domainName = $cert['domain_name'] ?? '';
                $certPath = $cert['certificate_path'] ?? '';

                // Check if cert file exists or domain is in discovered list
                $certExists = !empty($certPath) && file_exists($certPath);
                $inDiscovered = isset($existingSSL[$domainName]);

                if (!$certExists && !$inDiscovered) {
                    $sslModel->delete($cert['id']);
                    $results['ssl']['removed']++;
                    $results['ssl']['items'][] = $domainName ?: 'ID: ' . $cert['id'];
                }
            }
        } catch (Exception $e) {
            $results['ssl']['error'] = $e->getMessage();
        }

        if ($this->activityLog) {
            $totalRemoved = $results['apps']['removed'] + $results['domains']['removed'] +
                           $results['databases']['removed'] + $results['ssl']['removed'];
            if ($totalRemoved > 0) {
                $this->activityLog->log(
                    'cleanup_orphaned',
                    "Cleaned up {$totalRemoved} orphaned entries",
                    $results
                );
            }
        }

        return $results;
    }

    /**
     * Restart Apache service
     */
    public function restartApache()
    {
        try {
            // Test Apache configuration first
            exec('sudo apache2ctl configtest 2>&1', $testOutput, $testCode);

            if ($testCode !== 0) {
                return [
                    'success' => false,
                    'message' => "Apache configuration test failed:\n" . implode("\n", $testOutput)
                ];
            }

            // Restart Apache
            exec('sudo systemctl restart apache2 2>&1', $output, $returnCode);

            if ($returnCode !== 0) {
                return [
                    'success' => false,
                    'message' => "Failed to restart Apache:\n" . implode("\n", $output)
                ];
            }

            if ($this->activityLog) {
                $this->activityLog->log('apache_restart', 'Apache service restarted');
            }

            return [
                'success' => true,
                'message' => 'Apache restarted successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to restart Apache: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Reload Apache service (graceful)
     */
    public function reloadApache()
    {
        try {
            // Test Apache configuration first
            exec('sudo apache2ctl configtest 2>&1', $testOutput, $testCode);

            if ($testCode !== 0) {
                return [
                    'success' => false,
                    'message' => "Apache configuration test failed:\n" . implode("\n", $testOutput)
                ];
            }

            // Reload Apache (graceful)
            exec('sudo systemctl reload apache2 2>&1', $output, $returnCode);

            if ($returnCode !== 0) {
                return [
                    'success' => false,
                    'message' => "Failed to reload Apache:\n" . implode("\n", $output)
                ];
            }

            if ($this->activityLog) {
                $this->activityLog->log('apache_reload', 'Apache service reloaded');
            }

            return [
                'success' => true,
                'message' => 'Apache reloaded successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to reload Apache: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get Apache service status
     */
    public function getApacheStatus()
    {
        try {
            exec('systemctl is-active apache2 2>&1', $statusOutput, $statusCode);
            $isRunning = ($statusCode === 0 && trim($statusOutput[0] ?? '') === 'active');

            exec('systemctl status apache2 2>&1', $detailOutput, $detailCode);

            return [
                'running' => $isRunning,
                'status' => $isRunning ? 'active' : 'inactive',
                'details' => implode("\n", $detailOutput)
            ];
        } catch (Exception $e) {
            return [
                'running' => false,
                'status' => 'unknown',
                'error' => $e->getMessage()
            ];
        }
    }
}
