<?php

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../Models/Domain.php';
require_once __DIR__ . '/../Models/App.php';
require_once __DIR__ . '/../Services/DomainService.php';

/**
 * Domain Controller
 * Handles custom domain management
 *
 * @package ChweziSM
 * @subpackage Controllers
 */

class DomainController extends Controller
{
    private $domainService;

    public function __construct()
    {
        parent::__construct();
        $this->domainService = new DomainService();
    }

    /**
     * List all domains
     */
    public function index()
    {
        $this->requireAuth();

        $domainModel = new Domain();
        $domains = $domainModel->all();

        $this->view('domains/index', [
            'domains' => $domains,
            'title' => 'Domains'
        ]);
    }

    /**
     * Show create domain form
     */
    public function create()
    {
        $this->requireAuth();

        // Get all apps for dropdown
        $appModel = new App();
        $apps = $appModel->all();

        $this->view('domains/create', [
            'apps' => $apps,
            'title' => 'Add Domain'
        ]);
    }

    /**
     * Store new domain
     */
    public function store()
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $validation = $this->validate($_POST, [
            'domain_name' => 'required|min:3|max:255',
            'document_root' => 'max:500'
        ]);

        if (!$validation['valid']) {
            $_SESSION['errors'] = $validation['errors'];
            $_SESSION['old_input'] = $_POST;
            $this->redirect('/domains/create');
            return;
        }

        $domainModel = new Domain();

        // Check if domain already exists
        $existing = $domainModel->where('domain_name', $_POST['domain_name'])->first();
        if ($existing) {
            $_SESSION['errors'] = ['domain_name' => 'Domain already exists'];
            $_SESSION['old_input'] = $_POST;
            $this->redirect('/domains/create');
            return;
        }

        // Create domain record
        $domainId = $domainModel->create([
            'domain_name' => clean($_POST['domain_name']),
            'app_id' => !empty($_POST['app_id']) ? (int)$_POST['app_id'] : null,
            'document_root' => clean($_POST['document_root'] ?? ''),
            'status' => 'pending',
            'user_id' => $this->user()['id']
        ]);

        $_SESSION['success'] = 'Domain created successfully';
        $this->redirect('/domains/' . $domainId);
    }

    /**
     * Show domain details
     */
    public function show($id)
    {
        $this->requireAuth();

        $domainModel = new Domain();
        $domain = $domainModel->find($id);

        if (!$domain) {
            http_response_code(404);
            die('Domain not found');
        }

        // Check DNS status
        $dnsCheck = $this->domainService->checkDNS($domain['domain_name']);

        $this->view('domains/show', [
            'domain' => $domain,
            'dnsCheck' => $dnsCheck,
            'title' => $domain['domain_name']
        ]);
    }

    /**
     * Configure domain (create vhost)
     */
    public function configure($id)
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $domainModel = new Domain();
        $domain = $domainModel->find($id);

        if (!$domain) {
            $this->jsonError('Domain not found', 404);
            return;
        }

        $result = $this->domainService->configureDomain($domain);

        if (!$result['success']) {
            $this->jsonError($result['message']);
            return;
        }

        $this->jsonSuccess('Domain configured successfully', [
            'dns_configured' => $result['dns_configured']
        ]);
    }

    /**
     * Check DNS status
     */
    public function checkDns($id)
    {
        $this->requireAuth();

        $domainModel = new Domain();
        $domain = $domainModel->find($id);

        if (!$domain) {
            $this->jsonError('Domain not found', 404);
            return;
        }

        $dnsCheck = $this->domainService->checkDNS($domain['domain_name']);

        // Update DNS status in database
        $domainModel->update($id, [
            'dns_configured' => $dnsCheck['configured'] ? 1 : 0
        ]);

        $this->json([
            'success' => true,
            'dns_check' => $dnsCheck
        ]);
    }

    /**
     * Delete domain
     */
    public function destroy($id)
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $domainModel = new Domain();
        $domain = $domainModel->find($id);

        if (!$domain) {
            $this->jsonError('Domain not found', 404);
            return;
        }

        // Remove vhost
        $this->domainService->removeDomain($domain['domain_name']);

        // Delete domain record
        $domainModel->delete($id);

        $this->jsonSuccess('Domain deleted successfully');
    }
}
