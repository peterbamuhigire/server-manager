<?php

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../Models/App.php';
require_once __DIR__ . '/../Services/AppDeploymentService.php';
require_once __DIR__ . '/../Services/ApacheService.php';

/**
 * App Controller
 * Manages application deployments
 */
class AppController extends Controller
{
    private $deploymentService;
    private $apacheService;

    public function __construct()
    {
        $this->deploymentService = new AppDeploymentService();
        $this->apacheService = new ApacheService();
    }

    /**
     * List all applications
     */
    public function index(): void
    {
        $this->requireAuth();

        $appModel = new App();
        $apps = $appModel->getAll();

        $this->view('apps.index', [
            'apps' => $apps,
            'title' => 'Applications'
        ]);
    }

    /**
     * Show create application form
     */
    public function create(): void
    {
        $this->requireAuth();

        $this->view('apps.create', [
            'title' => 'Create Application'
        ]);
    }

    /**
     * Store new application
     */
    public function store(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $validation = $this->validate($_POST, [
            'name' => 'required|min:3|max:100',
            'subdomain' => 'required|min:3|max:50|alpha_dash',
            'git_repo' => 'required|url',
            'git_branch' => 'required|min:1|max:50',
            'document_root' => 'max:255'
        ]);

        if (!$validation['valid']) {
            $_SESSION['errors'] = $validation['errors'];
            $_SESSION['old_input'] = $_POST;
            redirect('/apps/create');
            return;
        }

        $appModel = new App();

        // Check if subdomain already exists
        $existing = $appModel->getBySubdomain($_POST['subdomain']);
        if ($existing) {
            $_SESSION['errors'] = ['subdomain' => 'Subdomain already exists'];
            $_SESSION['old_input'] = $_POST;
            redirect('/apps/create');
            return;
        }

        // Create app record
        $appId = $appModel->create([
            'name' => clean($_POST['name']),
            'subdomain' => clean($_POST['subdomain']),
            'git_repo' => clean($_POST['git_repo']),
            'git_branch' => clean($_POST['git_branch']),
            'document_root' => clean($_POST['document_root'] ?? ''),
            'status' => 'pending',
            'user_id' => $this->user()['id']
        ]);

        $_SESSION['success'] = 'Application created successfully';
        redirect('/apps/' . $appId);
    }

    /**
     * Show application details
     */
    public function show($id): void
    {
        $this->requireAuth();

        $appModel = new App();
        $app = $appModel->find($id);

        if (!$app) {
            http_response_code(404);
            die('Application not found');
        }

        // Get deployment info
        $deploymentInfo = $this->deploymentService->getDeploymentInfo($app);

        $this->view('apps.show', [
            'app' => $app,
            'deploymentInfo' => $deploymentInfo,
            'title' => $app['name']
        ]);
    }

    /**
     * Deploy application
     */
    public function deploy($id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $appModel = new App();
        $app = $appModel->find($id);

        if (!$app) {
            json_error('Application not found', 404);
            return;
        }

        // Deploy the application
        $deployResult = $this->deploymentService->deploy($app);

        if (!$deployResult['success']) {
            json_error($deployResult['message']);
            return;
        }

        // Create Apache virtual host
        $vhostResult = $this->apacheService->createVirtualHost($app);

        if (!$vhostResult['success']) {
            json_error('Deployment succeeded but vhost creation failed: ' . $vhostResult['message']);
            return;
        }

        json_success('Application deployed successfully', [
            'app' => $app
        ]);
    }

    /**
     * Redeploy application
     */
    public function redeploy($id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $appModel = new App();
        $app = $appModel->find($id);

        if (!$app) {
            json_error('Application not found', 404);
            return;
        }

        $result = $this->deploymentService->redeploy($app);

        if (!$result['success']) {
            json_error($result['message']);
            return;
        }

        json_success('Application redeployed successfully', [
            'app' => $app
        ]);
    }

    /**
     * Update application
     */
    public function update($id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $appModel = new App();
        $app = $appModel->find($id);

        if (!$app) {
            json_error('Application not found', 404);
            return;
        }

        $validation = $this->validate($_POST, [
            'name' => 'required|min:3|max:100',
            'git_branch' => 'required|min:1|max:50',
            'document_root' => 'max:255'
        ]);

        if (!$validation['valid']) {
            json_error('Validation failed', 422);
            return;
        }

        $appModel->update($app['id'], [
            'name' => clean($_POST['name']),
            'git_branch' => clean($_POST['git_branch']),
            'document_root' => clean($_POST['document_root'] ?? '')
        ]);

        // Update virtual host if needed
        if ($app['status'] === 'active') {
            $updatedApp = $appModel->find($id);
            $this->apacheService->updateVirtualHost($updatedApp);
        }

        json_success('Application updated successfully');
    }

    /**
     * Delete application
     */
    public function destroy($id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $appModel = new App();
        $app = $appModel->find($id);

        if (!$app) {
            json_error('Application not found', 404);
            return;
        }

        // Remove virtual host
        $this->apacheService->removeVirtualHost($app['subdomain']);

        // Remove application files
        $this->deploymentService->removeAppFiles($app['subdomain']);

        // Delete app record
        $appModel->delete($app['id']);

        json_success('Application deleted successfully');
    }

    /**
     * Get application status
     */
    public function status($id): void
    {
        $this->requireAuth();

        $appModel = new App();
        $app = $appModel->find($id);

        if (!$app) {
            json_error('Application not found', 404);
            return;
        }

        $deploymentInfo = $this->deploymentService->getDeploymentInfo($app);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'app' => $app,
            'deployment' => $deploymentInfo,
            'vhost_exists' => $this->apacheService->vhostExists($app['subdomain'])
        ]);
    }
}
