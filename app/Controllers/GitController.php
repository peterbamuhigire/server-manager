<?php

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../Services/GitService.php';

/**
 * Git Controller
 * Handles Git integration and SSH key management
 *
 * @package ChweziSM
 * @subpackage Controllers
 */

class GitController extends Controller
{
    private GitService $gitService;

    public function __construct()
    {
        parent::__construct();
        $this->gitService = new GitService();
    }

    /**
     * Show Git integration page
     */
    public function index(): void
    {
        $this->requireAuth();
        $this->requirePermission('git.view');

        $user = $this->user();

        // Get Git configuration
        $gitConfig = $this->gitService->getGitConfig();
        $gitVersion = $this->gitService->getGitVersion();
        $gitInstalled = $this->gitService->isGitInstalled();

        // Get SSH key info
        $sshKeyExists = $this->gitService->sshKeyExists();
        $sshPublicKey = $sshKeyExists ? $this->gitService->getSshPublicKey() : null;
        $sshFingerprint = $sshKeyExists ? $this->gitService->getSshKeyFingerprint() : null;

        $this->view('git.index', [
            'user' => $user,
            'gitConfig' => $gitConfig,
            'gitVersion' => $gitVersion,
            'gitInstalled' => $gitInstalled,
            'sshKeyExists' => $sshKeyExists,
            'sshPublicKey' => $sshPublicKey,
            'sshFingerprint' => $sshFingerprint
        ]);
    }

    /**
     * Generate SSH key pair
     */
    public function generateKey(): void
    {
        $this->requireAuth();
        $this->requirePermission('git.manage');
        $this->verifyCsrf();

        $email = input('email', auth_user()['email']);

        $result = $this->gitService->generateSshKey($email);

        if ($result['success']) {
            flash('success', $result['message']);
        } else {
            flash('error', $result['message']);
        }

        $this->redirect('/git');
    }

    /**
     * Delete SSH key pair
     */
    public function deleteKey(): void
    {
        $this->requireAuth();
        $this->requirePermission('git.manage');
        $this->verifyCsrf();

        if ($this->gitService->deleteSshKey()) {
            flash('success', 'SSH key deleted successfully');
        } else {
            flash('error', 'No SSH key found to delete');
        }

        $this->redirect('/git');
    }

    /**
     * Test GitHub connection
     */
    public function testConnection(): void
    {
        $this->requireAuth();
        $this->requirePermission('git.manage');
        $this->verifyCsrf();

        $result = $this->gitService->testGitHubConnection();

        $this->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'output' => $result['output'] ?? null
        ]);
    }

    /**
     * Update Git configuration
     */
    public function updateConfig(): void
    {
        $this->requireAuth();
        $this->requirePermission('git.manage');
        $this->verifyCsrf();

        $validation = validate_input([
            'git_name' => 'required|min:2',
            'git_email' => 'required|email'
        ]);

        if (!$validation['valid']) {
            flash('error', 'Please provide valid name and email');
            $this->back();
            return;
        }

        $result = $this->gitService->setGitConfig(
            $validation['data']['git_name'],
            $validation['data']['git_email']
        );

        if ($result['success']) {
            flash('success', $result['message']);
        } else {
            flash('error', $result['message']);
        }

        $this->redirect('/git');
    }
}
