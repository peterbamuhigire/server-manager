<?php

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../Services/ServerDiscoveryService.php';

/**
 * Discovery Controller
 * Handles server resource discovery and import
 *
 * @package ChweziSM
 * @subpackage Controllers
 */
class DiscoveryController extends Controller
{
    private $discoveryService;

    public function __construct()
    {
        $this->discoveryService = new ServerDiscoveryService();
    }

    /**
     * Show discovery dashboard
     */
    public function index(): void
    {
        $this->requireAuth();
        $this->requireRoot();

        $user = $this->user();

        // Get discovered resources with registration status
        $discovered = $this->discoveryService->discoverAllWithStatus();

        $this->view('discovery.index', [
            'user' => $user,
            'discovered' => $discovered
        ]);
    }

    /**
     * Scan for resources (AJAX)
     */
    public function scan(): void
    {
        $this->requireAuth();
        $this->requireRoot();

        try {
            $discovered = $this->discoveryService->discoverAll();

            json_success('Scan completed successfully', $discovered);
        } catch (Exception $e) {
            json_error('Scan failed: ' . $e->getMessage());
        }
    }

    /**
     * Get discovered directories
     */
    public function directories(): void
    {
        $this->requireAuth();
        $this->requireRoot();

        try {
            $directories = $this->discoveryService->discoverDirectories();
            json_success('Directories discovered', ['directories' => $directories]);
        } catch (Exception $e) {
            json_error('Failed to discover directories: ' . $e->getMessage());
        }
    }

    /**
     * Get discovered domains
     */
    public function domains(): void
    {
        $this->requireAuth();
        $this->requireRoot();

        try {
            $domains = $this->discoveryService->discoverDomains();
            json_success('Domains discovered', ['domains' => $domains]);
        } catch (Exception $e) {
            json_error('Failed to discover domains: ' . $e->getMessage());
        }
    }

    /**
     * Get discovered SSL certificates
     */
    public function ssl(): void
    {
        $this->requireAuth();
        $this->requireRoot();

        try {
            $certificates = $this->discoveryService->discoverSSLCertificates();
            json_success('SSL certificates discovered', ['certificates' => $certificates]);
        } catch (Exception $e) {
            json_error('Failed to discover SSL certificates: ' . $e->getMessage());
        }
    }

    /**
     * Get discovered databases
     */
    public function databases(): void
    {
        $this->requireAuth();
        $this->requireRoot();

        try {
            $databases = $this->discoveryService->discoverDatabases();
            json_success('Databases discovered', $databases);
        } catch (Exception $e) {
            json_error('Failed to discover databases: ' . $e->getMessage());
        }
    }

    /**
     * Import discovered resources
     */
    public function import(): void
    {
        $this->requireAuth();
        $this->requireRoot();
        $this->verifyCsrf();

        try {
            $user = $this->user();

            // Get items to import from POST
            $importDirs = $_POST['directories'] ?? [];
            $importDomains = $_POST['domains'] ?? [];
            $importDatabases = $_POST['databases'] ?? [];
            $importAll = isset($_POST['import_all']);

            // Discover all resources
            $discovered = $this->discoveryService->discoverAll();

            // Filter based on selection if not importing all
            if (!$importAll) {
                if (!empty($importDirs)) {
                    $discovered['directories'] = array_filter(
                        $discovered['directories'],
                        fn($d) => in_array($d['name'], $importDirs)
                    );
                } else {
                    $discovered['directories'] = [];
                }

                if (!empty($importDomains)) {
                    $discovered['domains'] = array_filter(
                        $discovered['domains'],
                        fn($d) => in_array($d['server_name'], $importDomains)
                    );
                } else {
                    $discovered['domains'] = [];
                }

                if (!empty($importDatabases)) {
                    $discovered['databases']['databases'] = array_filter(
                        $discovered['databases']['databases'] ?? [],
                        fn($d) => in_array($d['name'], $importDatabases)
                    );
                } else {
                    $discovered['databases']['databases'] = [];
                }
            }

            // Import selected items
            $results = $this->discoveryService->importDiscoveredItems($discovered, $user['id']);

            flash('success', sprintf(
                'Import complete. Apps: %d imported, %d skipped. Domains: %d imported, %d skipped. Databases: %d imported, %d skipped.',
                $results['apps']['imported'],
                $results['apps']['skipped'],
                $results['domains']['imported'],
                $results['domains']['skipped'],
                $results['databases']['imported'],
                $results['databases']['skipped']
            ));

            redirect('/discovery');

        } catch (Exception $e) {
            flash('error', 'Import failed: ' . $e->getMessage());
            redirect('/discovery');
        }
    }

    /**
     * View Apache config file
     */
    public function viewConfig(): void
    {
        $this->requireAuth();
        $this->requireRoot();

        $file = $_GET['file'] ?? '';

        if (empty($file)) {
            json_error('No file specified');
            return;
        }

        $sitesAvailable = config('paths.apache_sites_available', '/etc/apache2/sites-available');
        $filePath = $sitesAvailable . '/' . basename($file);

        if (!file_exists($filePath)) {
            json_error('File not found');
            return;
        }

        $content = file_get_contents($filePath);
        json_success('Config loaded', ['content' => $content, 'file' => basename($file)]);
    }

    /**
     * Register an app
     */
    public function registerApp(): void
    {
        $this->requireAuth();
        $this->requireRoot();
        $this->verifyCsrf();

        $name = $_POST['name'] ?? '';
        $user = $this->user();

        if (empty($name)) {
            json_error('App name is required');
            return;
        }

        $result = $this->discoveryService->registerApp($name, $user['id']);

        if ($result['success']) {
            json_success($result['message'], ['id' => $result['id'] ?? null]);
        } else {
            json_error($result['message']);
        }
    }

    /**
     * Unregister an app
     */
    public function unregisterApp(): void
    {
        $this->requireAuth();
        $this->requireRoot();
        $this->verifyCsrf();

        $id = $_POST['id'] ?? '';

        if (empty($id)) {
            json_error('App ID is required');
            return;
        }

        $result = $this->discoveryService->unregisterApp($id);

        if ($result['success']) {
            json_success($result['message']);
        } else {
            json_error($result['message']);
        }
    }

    /**
     * Register a domain
     */
    public function registerDomain(): void
    {
        $this->requireAuth();
        $this->requireRoot();
        $this->verifyCsrf();

        $serverName = $_POST['server_name'] ?? '';
        $documentRoot = $_POST['document_root'] ?? '';
        $sslEnabled = isset($_POST['ssl_enabled']) && $_POST['ssl_enabled'] === '1';
        $user = $this->user();

        if (empty($serverName)) {
            json_error('Domain name is required');
            return;
        }

        $result = $this->discoveryService->registerDomain($serverName, $documentRoot, $sslEnabled, $user['id']);

        if ($result['success']) {
            json_success($result['message'], ['id' => $result['id'] ?? null]);
        } else {
            json_error($result['message']);
        }
    }

    /**
     * Unregister a domain
     */
    public function unregisterDomain(): void
    {
        $this->requireAuth();
        $this->requireRoot();
        $this->verifyCsrf();

        $id = $_POST['id'] ?? '';

        if (empty($id)) {
            json_error('Domain ID is required');
            return;
        }

        $result = $this->discoveryService->unregisterDomain($id);

        if ($result['success']) {
            json_success($result['message']);
        } else {
            json_error($result['message']);
        }
    }

    /**
     * Register a database
     */
    public function registerDatabase(): void
    {
        $this->requireAuth();
        $this->requireRoot();
        $this->verifyCsrf();

        $dbName = $_POST['name'] ?? '';
        $user = $this->user();

        if (empty($dbName)) {
            json_error('Database name is required');
            return;
        }

        $result = $this->discoveryService->registerDatabase($dbName, $user['id']);

        if ($result['success']) {
            json_success($result['message'], ['id' => $result['id'] ?? null]);
        } else {
            json_error($result['message']);
        }
    }

    /**
     * Unregister a database
     */
    public function unregisterDatabase(): void
    {
        $this->requireAuth();
        $this->requireRoot();
        $this->verifyCsrf();

        $id = $_POST['id'] ?? '';

        if (empty($id)) {
            json_error('Database ID is required');
            return;
        }

        $result = $this->discoveryService->unregisterDatabase($id);

        if ($result['success']) {
            json_success($result['message']);
        } else {
            json_error($result['message']);
        }
    }

    /**
     * Register an SSL certificate
     */
    public function registerSSL(): void
    {
        $this->requireAuth();
        $this->requireRoot();
        $this->verifyCsrf();

        $user = $this->user();

        $certData = [
            'domain' => $_POST['domain'] ?? '',
            'provider' => $_POST['provider'] ?? 'unknown',
            'cert_file' => $_POST['cert_file'] ?? null,
            'key_file' => $_POST['key_file'] ?? null,
            'chain_file' => $_POST['chain_file'] ?? null,
            'issued_at' => $_POST['issued_at'] ?? null,
            'expires_at' => $_POST['expires_at'] ?? null
        ];

        if (empty($certData['domain'])) {
            json_error('Domain name is required');
            return;
        }

        $result = $this->discoveryService->registerSSLCertificate($certData, $user['id']);

        if ($result['success']) {
            json_success($result['message'], ['id' => $result['id'] ?? null]);
        } else {
            json_error($result['message']);
        }
    }

    /**
     * Unregister an SSL certificate
     */
    public function unregisterSSL(): void
    {
        $this->requireAuth();
        $this->requireRoot();
        $this->verifyCsrf();

        $id = $_POST['id'] ?? '';

        if (empty($id)) {
            json_error('SSL certificate ID is required');
            return;
        }

        $result = $this->discoveryService->unregisterSSLCertificate($id);

        if ($result['success']) {
            json_success($result['message']);
        } else {
            json_error($result['message']);
        }
    }

    /**
     * Clean up orphaned database entries
     */
    public function cleanup(): void
    {
        $this->requireAuth();
        $this->requireRoot();
        $this->verifyCsrf();

        $result = $this->discoveryService->cleanupOrphanedEntries();

        $totalRemoved = $result['apps']['removed'] + $result['domains']['removed'] +
                       $result['databases']['removed'] + $result['ssl']['removed'];

        if ($totalRemoved === 0) {
            json_success('No orphaned entries found. Database is clean.', $result);
        } else {
            json_success("Cleaned up {$totalRemoved} orphaned entries", $result);
        }
    }

    /**
     * Restart Apache service
     */
    public function restartApache(): void
    {
        $this->requireAuth();
        $this->requireRoot();
        $this->verifyCsrf();

        $result = $this->discoveryService->restartApache();

        if ($result['success']) {
            json_success($result['message']);
        } else {
            json_error($result['message']);
        }
    }

    /**
     * Reload Apache service (graceful)
     */
    public function reloadApache(): void
    {
        $this->requireAuth();
        $this->requireRoot();
        $this->verifyCsrf();

        $result = $this->discoveryService->reloadApache();

        if ($result['success']) {
            json_success($result['message']);
        } else {
            json_error($result['message']);
        }
    }

    /**
     * Get Apache status
     */
    public function apacheStatus(): void
    {
        $this->requireAuth();
        $this->requireRoot();

        $status = $this->discoveryService->getApacheStatus();

        json_success('Apache status retrieved', $status);
    }
}
