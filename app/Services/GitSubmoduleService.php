<?php

require_once __DIR__ . '/../Models/GitSubmodule.php';
require_once __DIR__ . '/../Models/App.php';
require_once __DIR__ . '/ActivityLogService.php';

/**
 * Git Submodule Service
 * Manages Git submodules (shared code repositories)
 *
 * @package ChweziSM
 * @subpackage Services
 */
class GitSubmoduleService
{
    private $submoduleModel;
    private $appModel;
    private $activityLog;
    private $submodulesBasePath;

    public function __construct()
    {
        $this->submoduleModel = new GitSubmodule();
        $this->appModel = new App();
        $this->activityLog = new ActivityLogService();

        // Base path for storing submodules
        $this->submodulesBasePath = config('git.submodules_path', '/var/www/submodules');

        // Create submodules directory if it doesn't exist
        if (!is_dir($this->submodulesBasePath)) {
            mkdir($this->submodulesBasePath, 0755, true);
        }
    }

    /**
     * Add a new submodule
     */
    public function addSubmodule($name, $repositoryUrl, $description, $branch, $userId)
    {
        try {
            // Validate submodule name
            if (!preg_match('/^[a-z0-9-_]+$/', $name)) {
                throw new Exception('Submodule name must contain only lowercase letters, numbers, hyphens, and underscores');
            }

            // Check if submodule already exists
            $existing = $this->submoduleModel->getByName($name);
            if ($existing) {
                throw new Exception('A submodule with this name already exists');
            }

            // Create local path
            $localPath = $this->submodulesBasePath . '/' . $name;

            // Clone the repository
            $cloneResult = $this->cloneRepository($repositoryUrl, $localPath, $branch);

            if (!$cloneResult['success']) {
                throw new Exception($cloneResult['message']);
            }

            // Get latest commit hash
            $commitHash = $this->getLatestCommitHash($localPath);

            // Create submodule record
            $submoduleId = $this->submoduleModel->create([
                'name' => $name,
                'repository_url' => $repositoryUrl,
                'local_path' => $localPath,
                'description' => $description,
                'branch' => $branch,
                'status' => 'active',
                'last_sync_at' => date('Y-m-d H:i:s'),
                'last_commit_hash' => $commitHash,
                'user_id' => $userId
            ]);

            $this->activityLog->log(
                'submodule_created',
                'Git submodule created: ' . $name,
                ['submodule_id' => $submoduleId, 'repository_url' => $repositoryUrl]
            );

            return [
                'success' => true,
                'message' => 'Submodule added successfully',
                'submodule_id' => $submoduleId
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Clone a Git repository
     */
    private function cloneRepository($repositoryUrl, $localPath, $branch = 'main')
    {
        try {
            // Check if running on Windows (development)
            if (stripos(PHP_OS, 'WIN') === 0) {
                // On Windows, simulate clone (for development)
                if (!is_dir($localPath)) {
                    mkdir($localPath, 0755, true);
                }
                file_put_contents($localPath . '/README.md', "# Simulated submodule\nRepository: $repositoryUrl");

                return [
                    'success' => true,
                    'message' => 'Repository cloned (simulated on Windows)'
                ];
            }

            // On Linux, use actual git clone
            $command = "git clone -b " . escapeshellarg($branch) . " " . escapeshellarg($repositoryUrl) . " " . escapeshellarg($localPath) . " 2>&1";
            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new Exception('Git clone failed: ' . implode("\n", $output));
            }

            return [
                'success' => true,
                'message' => 'Repository cloned successfully'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Update (pull) a submodule
     */
    public function updateSubmodule($submoduleId)
    {
        try {
            $submodule = $this->submoduleModel->find($submoduleId);

            if (!$submodule) {
                throw new Exception('Submodule not found');
            }

            // Update status to syncing
            $this->submoduleModel->updateSyncStatus($submoduleId, 'syncing');

            // Pull latest changes
            $pullResult = $this->pullRepository($submodule['local_path'], $submodule['branch']);

            if (!$pullResult['success']) {
                $this->submoduleModel->updateSyncStatus($submoduleId, 'error', null, $pullResult['message']);
                throw new Exception($pullResult['message']);
            }

            // Get latest commit hash
            $commitHash = $this->getLatestCommitHash($submodule['local_path']);

            // Update status
            $this->submoduleModel->updateSyncStatus($submoduleId, 'active', $commitHash);

            // Sync to linked apps if needed
            $this->syncToLinkedApps($submoduleId);

            $this->activityLog->log(
                'submodule_updated',
                'Git submodule updated: ' . $submodule['name'],
                ['submodule_id' => $submoduleId, 'commit_hash' => $commitHash]
            );

            return [
                'success' => true,
                'message' => 'Submodule updated successfully',
                'commit_hash' => $commitHash
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Pull latest changes from repository
     */
    private function pullRepository($localPath, $branch)
    {
        try {
            // Check if running on Windows (development)
            if (stripos(PHP_OS, 'WIN') === 0) {
                // On Windows, simulate pull
                touch($localPath . '/README.md');
                return [
                    'success' => true,
                    'message' => 'Repository pulled (simulated on Windows)'
                ];
            }

            // On Linux, use actual git pull
            $command = "cd " . escapeshellarg($localPath) . " && git pull origin " . escapeshellarg($branch) . " 2>&1";
            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new Exception('Git pull failed: ' . implode("\n", $output));
            }

            return [
                'success' => true,
                'message' => 'Repository pulled successfully'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get latest commit hash
     */
    private function getLatestCommitHash($localPath)
    {
        // Check if running on Windows (development)
        if (stripos(PHP_OS, 'WIN') === 0) {
            return 'simulated-' . time();
        }

        $command = "cd " . escapeshellarg($localPath) . " && git rev-parse HEAD 2>&1";
        exec($command, $output, $returnCode);

        if ($returnCode === 0 && !empty($output)) {
            return trim($output[0]);
        }

        return null;
    }

    /**
     * Sync submodule to all linked apps
     */
    private function syncToLinkedApps($submoduleId)
    {
        $submodule = $this->submoduleModel->find($submoduleId);
        $apps = $this->submoduleModel->getLinkedApps($submoduleId);

        foreach ($apps as $app) {
            if ($app['auto_sync']) {
                $this->linkSubmoduleToApp($submoduleId, $app['id'], $app['relative_path']);
            }
        }
    }

    /**
     * Link a submodule to an application
     */
    public function linkSubmoduleToApp($submoduleId, $appId, $relativePath)
    {
        try {
            $submodule = $this->submoduleModel->find($submoduleId);
            $app = $this->appModel->find($appId);

            if (!$submodule) {
                throw new Exception('Submodule not found');
            }

            if (!$app) {
                throw new Exception('Application not found');
            }

            // Create symlink or copy based on OS
            $targetPath = $app['deploy_path'] . '/' . $relativePath;

            // On Windows, copy directory; On Linux, create symlink
            if (stripos(PHP_OS, 'WIN') === 0) {
                // Windows: Copy directory (simulated)
                if (!is_dir(dirname($targetPath))) {
                    mkdir(dirname($targetPath), 0755, true);
                }
            } else {
                // Linux: Create symlink
                if (!is_dir(dirname($targetPath))) {
                    mkdir(dirname($targetPath), 0755, true);
                }

                // Remove existing symlink if it exists
                if (is_link($targetPath)) {
                    unlink($targetPath);
                }

                symlink($submodule['local_path'], $targetPath);
            }

            return [
                'success' => true,
                'message' => 'Submodule linked to application successfully'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Remove a submodule
     */
    public function removeSubmodule($submoduleId)
    {
        try {
            $submodule = $this->submoduleModel->find($submoduleId);

            if (!$submodule) {
                throw new Exception('Submodule not found');
            }

            // Check if submodule is linked to any apps
            $linkedApps = $this->submoduleModel->getLinkedApps($submoduleId);

            if (!empty($linkedApps)) {
                throw new Exception('Cannot remove submodule. It is linked to ' . count($linkedApps) . ' application(s). Unlink it first.');
            }

            // Delete local repository
            if (is_dir($submodule['local_path'])) {
                $this->deleteDirectory($submodule['local_path']);
            }

            // Delete database record
            $this->submoduleModel->delete($submoduleId);

            $this->activityLog->log(
                'submodule_deleted',
                'Git submodule deleted: ' . $submodule['name'],
                ['submodule_id' => $submoduleId]
            );

            return [
                'success' => true,
                'message' => 'Submodule removed successfully'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete directory recursively
     */
    private function deleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }

        rmdir($dir);
    }

    /**
     * Get submodule status
     */
    public function getStatus($submoduleId)
    {
        try {
            $submodule = $this->submoduleModel->find($submoduleId);

            if (!$submodule) {
                throw new Exception('Submodule not found');
            }

            $linkedApps = $this->submoduleModel->getLinkedApps($submoduleId);

            return [
                'success' => true,
                'submodule' => $submodule,
                'linked_apps' => $linkedApps,
                'linked_apps_count' => count($linkedApps)
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
