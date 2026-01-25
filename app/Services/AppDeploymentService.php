<?php

require_once __DIR__ . '/../Models/App.php';
require_once __DIR__ . '/ActivityLogService.php';

class AppDeploymentService
{
    private $appsBasePath;
    private $activityLog;

    public function __construct()
    {
        $this->appsBasePath = realpath(__DIR__ . '/../../apps') ?: __DIR__ . '/../../apps';
        $this->activityLog = new ActivityLogService();

        // Ensure apps directory exists
        if (!is_dir($this->appsBasePath)) {
            mkdir($this->appsBasePath, 0755, true);
        }
    }

    /**
     * Deploy an application from a git repository
     */
    public function deploy(array $app): array
    {
        try {
            $appModel = new App();
            $appModel->update($app['id'], ['status' => 'deploying']);

            $appPath = $this->getAppPath($app['subdomain']);

            // Clone repository
            $this->cloneRepository($app['git_repo'], $appPath, $app['git_branch']);

            // Run composer install if composer.json exists
            if (file_exists($appPath . '/composer.json')) {
                $this->runComposerInstall($appPath);
            }

            // Update app status
            $appModel->update($app['id'], [
                'status' => 'active',
                'deployed_at' => date('Y-m-d H:i:s'),
                'error_message' => null
            ]);

            $this->activityLog->log(
                'app_deployed',
                'Application deployed successfully: ' . $app['name'],
                ['app_id' => $app['id'], 'subdomain' => $app['subdomain']]
            );

            return [
                'success' => true,
                'message' => 'Application deployed successfully'
            ];

        } catch (Exception $e) {
            $appModel = new App();
            $appModel->update($app['id'], [
                'status' => 'error',
                'error_message' => $e->getMessage()
            ]);

            $this->activityLog->log(
                'app_deploy_failed',
                'Application deployment failed: ' . $e->getMessage(),
                ['app_id' => $app['id'], 'error' => $e->getMessage()]
            );

            return [
                'success' => false,
                'message' => 'Deployment failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Redeploy an application (pull latest changes)
     */
    public function redeploy(array $app): array
    {
        try {
            $appModel = new App();
            $appModel->update($app['id'], ['status' => 'deploying']);

            $appPath = $this->getAppPath($app['subdomain']);

            if (!is_dir($appPath . '/.git')) {
                throw new Exception('Application not deployed or .git directory missing');
            }

            // Pull latest changes
            $this->pullLatestChanges($appPath, $app['git_branch']);

            // Run composer install if composer.json exists
            if (file_exists($appPath . '/composer.json')) {
                $this->runComposerInstall($appPath);
            }

            $appModel->update($app['id'], [
                'status' => 'active',
                'deployed_at' => date('Y-m-d H:i:s'),
                'error_message' => null
            ]);

            $this->activityLog->log(
                'app_redeployed',
                'Application redeployed successfully: ' . $app['name'],
                ['app_id' => $app['id']]
            );

            return [
                'success' => true,
                'message' => 'Application redeployed successfully'
            ];

        } catch (Exception $e) {
            $appModel = new App();
            $appModel->update($app['id'], [
                'status' => 'error',
                'error_message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Redeploy failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Remove an application's files
     */
    public function removeAppFiles(string $subdomain): bool
    {
        $appPath = $this->getAppPath($subdomain);

        if (!is_dir($appPath)) {
            return true;
        }

        return $this->deleteDirectory($appPath);
    }

    /**
     * Clone a git repository
     */
    private function cloneRepository(string $repoUrl, string $destination, string $branch = 'main'): void
    {
        // Remove directory if it exists
        if (is_dir($destination)) {
            $this->deleteDirectory($destination);
        }

        $command = sprintf(
            'git clone --branch %s %s %s 2>&1',
            escapeshellarg($branch),
            escapeshellarg($repoUrl),
            escapeshellarg($destination)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new Exception('Git clone failed: ' . implode("\n", $output));
        }
    }

    /**
     * Pull latest changes from repository
     */
    private function pullLatestChanges(string $appPath, string $branch = 'main'): void
    {
        $commands = [
            sprintf('cd %s', escapeshellarg($appPath)),
            sprintf('git checkout %s 2>&1', escapeshellarg($branch)),
            'git pull origin ' . escapeshellarg($branch) . ' 2>&1'
        ];

        $command = implode(' && ', $commands);
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new Exception('Git pull failed: ' . implode("\n", $output));
        }
    }

    /**
     * Run composer install
     */
    private function runComposerInstall(string $appPath): void
    {
        $commands = [
            sprintf('cd %s', escapeshellarg($appPath)),
            'composer install --no-dev --optimize-autoloader 2>&1'
        ];

        $command = implode(' && ', $commands);
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new Exception('Composer install failed: ' . implode("\n", $output));
        }
    }

    /**
     * Get the full path for an app
     */
    private function getAppPath(string $subdomain): string
    {
        return $this->appsBasePath . '/' . $subdomain;
    }

    /**
     * Recursively delete a directory
     */
    private function deleteDirectory(string $dir): bool
    {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dir);
    }

    /**
     * Get deployment status and information
     */
    public function getDeploymentInfo(array $app): array
    {
        $appPath = $this->getAppPath($app['subdomain']);
        $info = [
            'path' => $appPath,
            'exists' => is_dir($appPath),
            'is_git_repo' => is_dir($appPath . '/.git'),
            'has_composer' => file_exists($appPath . '/composer.json')
        ];

        if ($info['is_git_repo']) {
            // Get current branch and last commit
            $command = sprintf('cd %s && git branch --show-current 2>&1', escapeshellarg($appPath));
            exec($command, $branchOutput);
            $info['current_branch'] = $branchOutput[0] ?? 'unknown';

            $command = sprintf('cd %s && git log -1 --format="%%h - %%s (%%cr)" 2>&1', escapeshellarg($appPath));
            exec($command, $commitOutput);
            $info['last_commit'] = $commitOutput[0] ?? 'unknown';
        }

        return $info;
    }
}
