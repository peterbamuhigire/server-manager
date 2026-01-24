<?php

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../Models/MysqlDatabase.php';
require_once __DIR__ . '/../Models/DatabaseBackup.php';
require_once __DIR__ . '/../Models/App.php';
require_once __DIR__ . '/../Services/DatabaseService.php';

/**
 * Database Controller
 * Handles MySQL database management requests
 *
 * @package ChweziSM
 * @subpackage Controllers
 */
class DatabaseController extends Controller
{
    private $databaseService;

    public function __construct()
    {
        parent::__construct();
        $this->databaseService = new DatabaseService();
    }

    /**
     * List all databases
     */
    public function index()
    {
        $this->requireAuth();

        $dbModel = new MysqlDatabase();
        $databases = $dbModel->getAll();

        // Update sizes for active databases
        foreach ($databases as &$db) {
            if ($db['status'] === 'active') {
                $this->databaseService->updateDatabaseSize($db['id']);
            }
        }

        // Refresh data after size updates
        $databases = $dbModel->getAll();

        // Format sizes
        foreach ($databases as &$db) {
            $db['size_formatted'] = $this->formatBytes($db['database_size']);
        }

        $this->render('databases/index', [
            'databases' => $databases,
            'pageTitle' => 'Databases'
        ]);
    }

    /**
     * Show database creation form
     */
    public function create()
    {
        $this->requireAuth();

        $appModel = new App();
        $apps = $appModel->getAll();

        $this->render('databases/create', [
            'apps' => $apps,
            'pageTitle' => 'Create Database'
        ]);
    }

    /**
     * Create new database
     */
    public function store()
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $databaseName = $_POST['database_name'] ?? '';
        $description = $_POST['description'] ?? '';
        $appId = $_POST['app_id'] ?? null;
        $createUser = isset($_POST['create_user']) && $_POST['create_user'] === '1';

        if (empty($databaseName)) {
            $_SESSION['error'] = 'Database name is required';
            header('Location: /databases/create');
            exit;
        }

        $result = $this->databaseService->createDatabase(
            $databaseName,
            $createUser,
            $_SESSION['user_id']
        );

        if ($result['success']) {
            // Update description and app_id if provided
            if ($description || $appId) {
                $dbModel = new MysqlDatabase();
                $dbModel->update($result['database_id'], [
                    'description' => $description,
                    'app_id' => $appId ? (int)$appId : null
                ]);
            }

            // Store credentials in session temporarily for display
            if ($createUser && isset($result['database_password'])) {
                $_SESSION['db_credentials'] = [
                    'database' => $result['database_name'],
                    'username' => $result['database_user'],
                    'password' => $result['database_password']
                ];
            }

            $_SESSION['success'] = $result['message'];
            header('Location: /databases/' . $result['database_id']);
        } else {
            $_SESSION['error'] = $result['message'];
            header('Location: /databases/create');
        }
        exit;
    }

    /**
     * Show database details
     */
    public function show($id)
    {
        $this->requireAuth();

        $dbModel = new MysqlDatabase();
        $database = $dbModel->find($id);

        if (!$database) {
            $_SESSION['error'] = 'Database not found';
            header('Location: /databases');
            exit;
        }

        // Update size
        $this->databaseService->updateDatabaseSize($id);
        $database = $dbModel->find($id);

        // Get app if linked
        if ($database['app_id']) {
            $appModel = new App();
            $database['app'] = $appModel->find($database['app_id']);
        }

        // Get backups
        $backupModel = new DatabaseBackup();
        $backups = $backupModel->getByDatabase($id);

        // Format sizes
        $database['size_formatted'] = $this->formatBytes($database['database_size']);
        foreach ($backups as &$backup) {
            $backup['size_formatted'] = $this->formatBytes($backup['backup_size']);
        }

        // Check for new credentials
        $newCredentials = null;
        if (isset($_SESSION['db_credentials'])) {
            $newCredentials = $_SESSION['db_credentials'];
            unset($_SESSION['db_credentials']);
        }

        $this->render('databases/show', [
            'database' => $database,
            'backups' => $backups,
            'newCredentials' => $newCredentials,
            'pageTitle' => 'Database: ' . $database['database_name']
        ]);
    }

    /**
     * Backup database
     */
    public function backup($id)
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $result = $this->databaseService->backupDatabase($id, 'manual');

        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }

        header('Location: /databases/' . $id);
        exit;
    }

    /**
     * Restore database from backup
     */
    public function restore()
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $backupId = $_POST['backup_id'] ?? null;
        $databaseId = $_POST['database_id'] ?? null;

        if (!$backupId) {
            $_SESSION['error'] = 'Backup ID is required';
            header('Location: /databases/' . $databaseId);
            exit;
        }

        $result = $this->databaseService->restoreDatabase($backupId);

        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }

        header('Location: /databases/' . $databaseId);
        exit;
    }

    /**
     * Delete database
     */
    public function destroy($id)
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $result = $this->databaseService->deleteDatabase($id);

        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }

        header('Location: /databases');
        exit;
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes)
    {
        if ($bytes == 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);

        return round($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
    }
}
