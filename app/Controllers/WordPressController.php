<?php

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../Models/App.php';
require_once __DIR__ . '/../Services/WordPressInstaller.php';

/**
 * WordPress Controller
 * Handles WordPress installation and management
 *
 * @package ChweziSM
 * @subpackage Controllers
 */
class WordPressController extends Controller
{
    private $installer;

    public function __construct()
    {
        parent::__construct();
        $this->installer = new WordPressInstaller();
    }

    /**
     * Show WordPress installation form
     */
    public function install()
    {
        $this->requireAuth();

        $this->render('wordpress/install', [
            'pageTitle' => 'Install WordPress'
        ]);
    }

    /**
     * Process WordPress installation
     */
    public function store()
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $siteName = $_POST['site_name'] ?? '';
        $subdomain = $_POST['subdomain'] ?? '';
        $adminUser = $_POST['admin_user'] ?? 'admin';
        $adminPassword = $_POST['admin_password'] ?? '';
        $adminEmail = $_POST['admin_email'] ?? '';

        // Validation
        $errors = [];

        if (empty($siteName)) {
            $errors[] = 'Site name is required';
        }

        if (empty($subdomain)) {
            $errors[] = 'Subdomain is required';
        } elseif (!preg_match('/^[a-z0-9-]+$/', $subdomain)) {
            $errors[] = 'Subdomain must contain only lowercase letters, numbers, and hyphens';
        }

        if (empty($adminPassword)) {
            $errors[] = 'Admin password is required';
        } elseif (strlen($adminPassword) < 8) {
            $errors[] = 'Admin password must be at least 8 characters';
        }

        if (empty($adminEmail)) {
            $errors[] = 'Admin email is required';
        } elseif (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address';
        }

        if (!empty($errors)) {
            $_SESSION['error'] = implode(', ', $errors);
            header('Location: /wordpress/install');
            exit;
        }

        // Install WordPress
        $result = $this->installer->install(
            $siteName,
            $subdomain,
            $adminUser,
            $adminPassword,
            $adminEmail,
            $_SESSION['user_id']
        );

        if ($result['success']) {
            // Store installation details in session for display
            $_SESSION['wp_install_success'] = [
                'site_name' => $siteName,
                'url' => $result['url'],
                'admin_url' => $result['admin_url'],
                'admin_user' => $result['admin_user'],
                'admin_password' => $result['admin_password'],
                'db_name' => $result['db_name'],
                'db_user' => $result['db_user'],
                'db_password' => $result['db_password']
            ];

            $_SESSION['success'] = $result['message'];
            header('Location: /wordpress/success');
        } else {
            $_SESSION['error'] = $result['message'];
            header('Location: /wordpress/install');
        }
        exit;
    }

    /**
     * Show successful installation details
     */
    public function success()
    {
        $this->requireAuth();

        if (!isset($_SESSION['wp_install_success'])) {
            header('Location: /apps');
            exit;
        }

        $installDetails = $_SESSION['wp_install_success'];
        unset($_SESSION['wp_install_success']);

        $this->render('wordpress/success', [
            'pageTitle' => 'WordPress Installed Successfully',
            'installDetails' => $installDetails
        ]);
    }

    /**
     * List all WordPress installations
     */
    public function index()
    {
        $this->requireAuth();

        $appModel = new App();
        $allApps = $appModel->getAll();

        // Filter for WordPress sites (those without git_repo)
        $wpSites = array_filter($allApps, function($app) {
            return empty($app['git_repo']);
        });

        $this->render('wordpress/index', [
            'pageTitle' => 'WordPress Sites',
            'sites' => $wpSites
        ]);
    }
}
