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

        // Get discovered resources
        $discovered = $this->discoveryService->discoverAll();

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
        $this->validateCsrf();

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
}
