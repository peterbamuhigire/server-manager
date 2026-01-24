<?php

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../Services/GitSubmoduleService.php';
require_once __DIR__ . '/../Models/GitSubmodule.php';
require_once __DIR__ . '/../Models/App.php';

/**
 * Submodule Controller
 * Manages Git submodules (shared code repositories)
 *
 * @package ChweziSM
 * @subpackage Controllers
 */
class SubmoduleController extends Controller
{
    private $submoduleService;
    private $appModel;

    public function __construct()
    {
        parent::__construct();
        $this->submoduleService = new GitSubmoduleService();
        $this->appModel = new App();
    }

    /**
     * Display all submodules
     */
    public function index()
    {
        $this->requireAuth();

        $submoduleModel = new GitSubmodule();
        $submodules = $submoduleModel->getAll();
        $stats = $submoduleModel->getStats();

        $data = [
            'pageTitle' => 'Git Submodules',
            'submodules' => $submodules,
            'stats' => $stats
        ];

        $this->view('submodules/index', $data);
    }

    /**
     * Show form to create new submodule
     */
    public function create()
    {
        $this->requireAuth();

        $data = [
            'pageTitle' => 'Add Git Submodule',
            'csrf_token' => $this->generateCsrfToken()
        ];

        $this->render('submodules/create', $data);
    }

    /**
     * Store new submodule
     */
    public function store()
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/submodules');
            return;
        }

        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Invalid CSRF token';
            $this->redirect('/submodules/create');
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $repositoryUrl = trim($_POST['repository_url'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $branch = trim($_POST['branch'] ?? 'main');

        // Validate input
        if (empty($name) || empty($repositoryUrl)) {
            $_SESSION['error'] = 'Name and repository URL are required';
            $_SESSION['form_data'] = $_POST;
            $this->redirect('/submodules/create');
            return;
        }

        // Add submodule
        $result = $this->submoduleService->addSubmodule(
            $name,
            $repositoryUrl,
            $description,
            $branch,
            $_SESSION['user_id']
        );

        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
            $this->redirect('/submodules/' . $result['submodule_id']);
        } else {
            $_SESSION['error'] = $result['message'];
            $_SESSION['form_data'] = $_POST;
            $this->redirect('/submodules/create');
        }
    }

    /**
     * Show submodule details
     */
    public function show($id)
    {
        $this->requireAuth();

        $result = $this->submoduleService->getStatus($id);

        if (!$result['success']) {
            $_SESSION['error'] = $result['message'];
            $this->redirect('/submodules');
            return;
        }

        // Get all apps for linking dropdown
        $apps = $this->appModel->getAll();

        $data = [
            'pageTitle' => 'Submodule: ' . $result['submodule']['name'],
            'submodule' => $result['submodule'],
            'linked_apps' => $result['linked_apps'],
            'apps' => $apps,
            'csrf_token' => $this->generateCsrfToken()
        ];

        $this->render('submodules/show', $data);
    }

    /**
     * Update (pull) submodule
     */
    public function update($id)
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/submodules/' . $id);
            return;
        }

        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Invalid CSRF token';
            $this->redirect('/submodules/' . $id);
            return;
        }

        $result = $this->submoduleService->updateSubmodule($id);

        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }

        $this->redirect('/submodules/' . $id);
    }

    /**
     * Link submodule to an application
     */
    public function link()
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/submodules');
            return;
        }

        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Invalid CSRF token';
            $this->redirect('/submodules');
            return;
        }

        $submoduleId = (int)($_POST['submodule_id'] ?? 0);
        $appId = (int)($_POST['app_id'] ?? 0);
        $relativePath = trim($_POST['relative_path'] ?? '');
        $autoSync = isset($_POST['auto_sync']) ? true : false;

        if (empty($submoduleId) || empty($appId) || empty($relativePath)) {
            $_SESSION['error'] = 'All fields are required';
            $this->redirect('/submodules/' . $submoduleId);
            return;
        }

        // Link in database
        $submoduleModel = new GitSubmodule();
        $linkResult = $submoduleModel->linkToApp($submoduleId, $appId, $relativePath, $autoSync);

        if ($linkResult) {
            // Create physical link (symlink/copy)
            $result = $this->submoduleService->linkSubmoduleToApp($submoduleId, $appId, $relativePath);

            if ($result['success']) {
                $_SESSION['success'] = 'Submodule linked to application successfully';
            } else {
                $_SESSION['error'] = 'Database link created but physical link failed: ' . $result['message'];
            }
        } else {
            $_SESSION['error'] = 'Failed to link submodule to application';
        }

        $this->redirect('/submodules/' . $submoduleId);
    }

    /**
     * Unlink submodule from an application
     */
    public function unlink()
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/submodules');
            return;
        }

        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Invalid CSRF token';
            $this->redirect('/submodules');
            return;
        }

        $submoduleId = (int)($_POST['submodule_id'] ?? 0);
        $appId = (int)($_POST['app_id'] ?? 0);

        if (empty($submoduleId) || empty($appId)) {
            $_SESSION['error'] = 'Invalid request';
            $this->redirect('/submodules/' . $submoduleId);
            return;
        }

        $submoduleModel = new GitSubmodule();
        $result = $submoduleModel->unlinkFromApp($submoduleId, $appId);

        if ($result) {
            $_SESSION['success'] = 'Submodule unlinked from application successfully';
        } else {
            $_SESSION['error'] = 'Failed to unlink submodule';
        }

        $this->redirect('/submodules/' . $submoduleId);
    }

    /**
     * Delete submodule
     */
    public function destroy($id)
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/submodules');
            return;
        }

        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Invalid CSRF token';
            $this->redirect('/submodules/' . $id);
            return;
        }

        $result = $this->submoduleService->removeSubmodule($id);

        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
            $this->redirect('/submodules');
        } else {
            $_SESSION['error'] = $result['message'];
            $this->redirect('/submodules/' . $id);
        }
    }
}
