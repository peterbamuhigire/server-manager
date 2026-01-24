<?php

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../Models/SslCertificate.php';
require_once __DIR__ . '/../Models/Domain.php';
require_once __DIR__ . '/../Services/SSLService.php';
require_once __DIR__ . '/../Services/ActivityLogService.php';

/**
 * SSL Controller
 * Handles SSL certificate management requests
 *
 * @package ChweziSM
 * @subpackage Controllers
 */
class SSLController extends Controller
{
    private $sslService;
    private $activityLog;

    public function __construct()
    {
        parent::__construct();
        $this->sslService = new SSLService();
        $this->activityLog = new ActivityLogService();
    }

    /**
     * List all SSL certificates
     */
    public function index()
    {
        $this->requireAuth();

        $certModel = new SslCertificate();
        $certificates = $certModel->getAll();

        // Check expiry status for each certificate
        foreach ($certificates as &$cert) {
            $cert['is_expiring'] = $certModel->isExpiring($cert['id'], 30);
            $cert['days_until_expiry'] = $this->getDaysUntilExpiry($cert['expires_at']);
        }

        $this->render('ssl/index', [
            'certificates' => $certificates,
            'pageTitle' => 'SSL Certificates'
        ]);
    }

    /**
     * Show certificate generation form
     */
    public function create()
    {
        $this->requireAuth();

        // Get domains without SSL
        $domainModel = new Domain();
        $domains = $domainModel->getAll();

        $availableDomains = array_filter($domains, function($domain) {
            return !$domain['ssl_enabled'];
        });

        $this->render('ssl/create', [
            'domains' => $availableDomains,
            'pageTitle' => 'Generate SSL Certificate'
        ]);
    }

    /**
     * Generate new SSL certificate
     */
    public function store()
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $domainId = $_POST['domain_id'] ?? null;

        if (!$domainId) {
            $_SESSION['error'] = 'Domain is required';
            header('Location: /ssl/create');
            exit;
        }

        $domainModel = new Domain();
        $domain = $domainModel->find($domainId);

        if (!$domain) {
            $_SESSION['error'] = 'Domain not found';
            header('Location: /ssl/create');
            exit;
        }

        if ($domain['ssl_enabled']) {
            $_SESSION['error'] = 'Domain already has SSL certificate';
            header('Location: /ssl');
            exit;
        }

        $result = $this->sslService->generateCertificate($domainId, $_SESSION['user_id']);

        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
            header('Location: /ssl');
        } else {
            $_SESSION['error'] = $result['message'];
            header('Location: /ssl/create');
        }
        exit;
    }

    /**
     * Show certificate details
     */
    public function show($id)
    {
        $this->requireAuth();

        $certModel = new SslCertificate();
        $cert = $certModel->find($id);

        if (!$cert) {
            $_SESSION['error'] = 'Certificate not found';
            header('Location: /ssl');
            exit;
        }

        // Get domain info
        $domainModel = new Domain();
        $domain = $domainModel->find($cert['domain_id']);

        $cert['domain_name'] = $domain['domain_name'] ?? 'Unknown';
        $cert['is_expiring'] = $certModel->isExpiring($id, 30);
        $cert['days_until_expiry'] = $this->getDaysUntilExpiry($cert['expires_at']);

        $this->render('ssl/show', [
            'certificate' => $cert,
            'pageTitle' => 'Certificate: ' . $cert['domain_name']
        ]);
    }

    /**
     * Renew SSL certificate
     */
    public function renew($id)
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $certModel = new SslCertificate();
        $cert = $certModel->find($id);

        if (!$cert) {
            $_SESSION['error'] = 'Certificate not found';
            header('Location: /ssl');
            exit;
        }

        $result = $this->sslService->renewCertificate($id);

        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }

        header('Location: /ssl/' . $id);
        exit;
    }

    /**
     * Delete SSL certificate
     */
    public function destroy($id)
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $certModel = new SslCertificate();
        $cert = $certModel->find($id);

        if (!$cert) {
            $_SESSION['error'] = 'Certificate not found';
            header('Location: /ssl');
            exit;
        }

        $result = $this->sslService->deleteCertificate($id);

        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }

        header('Location: /ssl');
        exit;
    }

    /**
     * Calculate days until expiry
     */
    private function getDaysUntilExpiry($expiresAt)
    {
        if (!$expiresAt) {
            return null;
        }

        $expiryDate = strtotime($expiresAt);
        $now = time();
        $diff = $expiryDate - $now;

        return max(0, floor($diff / (60 * 60 * 24)));
    }
}
