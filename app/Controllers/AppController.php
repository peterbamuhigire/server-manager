<?php

namespace App\Controllers;

use App\Models\App;
use App\Services\AppDeploymentService;
use App\Services\ApacheService;
use Controller;

class AppController extends Controller
{
    private AppDeploymentService $deploymentService;
    private ApacheService $apacheService;

    public function __construct()
    {
        parent::__construct();
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
        $apps = $appModel->all();

        $this->view('apps/index', [
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

        $this->view('apps/create', [
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
            $this->redirect('/apps/create');
            return;
        }

        $appModel = new App();

        // Check if subdomain already exists
        $existing = $appModel->where('subdomain', $_POST['subdomain'])->first();
        if ($existing) {
            $_SESSION['errors'] = ['subdomain' => 'Subdomain already exists'];
            $_SESSION['old_input'] = $_POST;
            $this->redirect('/apps/create');
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
        $this->redirect('/apps/' . $appId);
    }

    /**
     * Show application details
     */
    public function show(int $id): void
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

        $this->view('apps/show', [
            'app' => $app,
            'deploymentInfo' => $deploymentInfo,
            'title' => $app->name
        ]);
    }

    /**
     * Deploy application
     */
    public function deploy(int $id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $appModel = new App();
        $app = $appModel->find($id);

        if (!$app) {
            $this->jsonError('Application not found', 404);
            return;
        }

        // Deploy the application
        $deployResult = $this->deploymentService->deploy($app);

        if (!$deployResult['success']) {
            $this->jsonError($deployResult['message']);
            return;
        }

        // Create Apache virtual host
        $vhostResult = $this->apacheService->createVirtualHost($app);

        if (!$vhostResult['success']) {
            $this->jsonError('Deployment succeeded but vhost creation failed: ' . $vhostResult['message']);
            return;
        }

        $this->jsonSuccess('Application deployed successfully', [
            'app' => $app
        ]);
    }

    /**
     * Redeploy application
     */
    public function redeploy(int $id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $appModel = new App();
        $app = $appModel->find($id);

        if (!$app) {
            $this->jsonError('Application not found', 404);
            return;
        }

        $result = $this->deploymentService->redeploy($app);

        if (!$result['success']) {
            $this->jsonError($result['message']);
            return;
        }

        $this->jsonSuccess('Application redeployed successfully', [
            'app' => $app
        ]);
    }

    /**
     * Update application
     */
    public function update(int $id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $appModel = new App();
        $app = $appModel->find($id);

        if (!$app) {
            $this->jsonError('Application not found', 404);
            return;
        }

        $validation = $this->validate($_POST, [
            'name' => 'required|min:3|max:100',
            'git_branch' => 'required|min:1|max:50',
            'document_root' => 'max:255'
        ]);

        if (!$validation['valid']) {
            $this->jsonError('Validation failed', 422);
            return;
        }

        $app->name = clean($_POST['name']);
        $app->git_branch = clean($_POST['git_branch']);
        $app->document_root = clean($_POST['document_root'] ?? '');
        $app->save();

        // Update virtual host if needed
        if ($app->status === 'active') {
            $this->apacheService->updateVirtualHost($app);
        }

        $this->jsonSuccess('Application updated successfully', [
            'app' => $app
        ]);
    }

    /**
     * Delete application
     */
    public function destroy(int $id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $appModel = new App();
        $app = $appModel->find($id);

        if (!$app) {
            $this->jsonError('Application not found', 404);
            return;
        }

        // Remove virtual host
        $this->apacheService->removeVirtualHost($app->subdomain);

        // Remove application files
        $this->deploymentService->removeAppFiles($app->subdomain);

        // Delete app record
        $app->delete();

        $this->jsonSuccess('Application deleted successfully');
    }

    /**
     * Get application status
     */
    public function status(int $id): void
    {
        $this->requireAuth();

        $appModel = new App();
        $app = $appModel->find($id);

        if (!$app) {
            $this->jsonError('Application not found', 404);
            return;
        }

        $deploymentInfo = $this->deploymentService->getDeploymentInfo($app);

        $this->json([
            'success' => true,
            'app' => $app,
            'deployment' => $deploymentInfo,
            'vhost_exists' => $this->apacheService->vhostExists($app->subdomain)
        ]);
    }
}
