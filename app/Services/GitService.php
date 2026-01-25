<?php

/**
 * Git Service
 *
 * Handles Git operations including SSH key management and GitHub integration.
 *
 * @package ChweziSM
 * @subpackage Services
 * @version 1.0
 */

class GitService
{
    private string $sshDir;
    private string $privateKeyPath;
    private string $publicKeyPath;

    public function __construct()
    {
        $this->sshDir = __DIR__ . '/../../storage/ssh';
        $this->privateKeyPath = $this->sshDir . '/id_rsa';
        $this->publicKeyPath = $this->sshDir . '/id_rsa.pub';
    }

    /**
     * Check if SSH key pair exists
     *
     * @return bool
     */
    public function sshKeyExists(): bool
    {
        return file_exists($this->privateKeyPath) && file_exists($this->publicKeyPath);
    }

    /**
     * Generate SSH key pair
     *
     * @param string $email Email for key comment
     * @return array Result with success status and message
     */
    public function generateSshKey(string $email = 'chwezi-sm@server'): array
    {
        try {
            // Check if key already exists
            if ($this->sshKeyExists()) {
                return [
                    'success' => false,
                    'message' => 'SSH key already exists. Delete existing key first if you want to regenerate.'
                ];
            }

            // Ensure SSH directory exists
            if (!is_dir($this->sshDir)) {
                mkdir($this->sshDir, 0700, true);
            }

            // Generate SSH key using ssh-keygen
            $command = sprintf(
                'ssh-keygen -t rsa -b 4096 -C "%s" -f "%s" -N ""',
                escapeshellarg($email),
                escapeshellarg($this->privateKeyPath)
            );

            exec($command . ' 2>&1', $output, $returnCode);

            if ($returnCode !== 0) {
                throw new Exception('Failed to generate SSH key: ' . implode("\n", $output));
            }

            // Set proper permissions
            chmod($this->privateKeyPath, 0600);
            chmod($this->publicKeyPath, 0644);

            // Log activity
            log_activity('generate_ssh_key', 'git', null, ['email' => $email]);

            return [
                'success' => true,
                'message' => 'SSH key generated successfully',
                'public_key' => $this->getSshPublicKey()
            ];
        } catch (Exception $e) {
            log_message('error', 'SSH key generation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get SSH public key content
     *
     * @return string|null
     */
    public function getSshPublicKey(): ?string
    {
        if (!file_exists($this->publicKeyPath)) {
            return null;
        }

        return trim(file_get_contents($this->publicKeyPath));
    }

    /**
     * Get SSH private key path
     *
     * @return string|null
     */
    public function getSshPrivateKeyPath(): ?string
    {
        if (!file_exists($this->privateKeyPath)) {
            return null;
        }

        return $this->privateKeyPath;
    }

    /**
     * Delete SSH key pair
     *
     * @return bool
     */
    public function deleteSshKey(): bool
    {
        $deleted = false;

        if (file_exists($this->privateKeyPath)) {
            unlink($this->privateKeyPath);
            $deleted = true;
        }

        if (file_exists($this->publicKeyPath)) {
            unlink($this->publicKeyPath);
            $deleted = true;
        }

        if ($deleted) {
            log_activity('delete_ssh_key', 'git');
        }

        return $deleted;
    }

    /**
     * Test GitHub connection using SSH
     *
     * @return array Result with success status and message
     */
    public function testGitHubConnection(): array
    {
        try {
            if (!$this->sshKeyExists()) {
                return [
                    'success' => false,
                    'message' => 'SSH key not found. Please generate an SSH key first.'
                ];
            }

            // Set GIT_SSH_COMMAND to use our private key
            $sshCommand = sprintf(
                'ssh -i "%s" -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -o IdentitiesOnly=yes',
                $this->privateKeyPath
            );

            // Test connection to GitHub
            $command = sprintf(
                'GIT_SSH_COMMAND="%s" ssh -T git@github.com 2>&1',
                $sshCommand
            );

            // On Windows, use different syntax
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $command = sprintf(
                    'ssh -i "%s" -o StrictHostKeyChecking=no -T git@github.com 2>&1',
                    $this->privateKeyPath
                );
            }

            exec($command, $output, $returnCode);
            $outputText = implode("\n", $output);

            // GitHub returns 1 even on successful authentication
            // Success message contains "successfully authenticated"
            if (stripos($outputText, 'successfully authenticated') !== false) {
                log_activity('test_github_connection', 'git', null, ['status' => 'success']);
                return [
                    'success' => true,
                    'message' => 'GitHub connection successful! SSH key is properly configured.',
                    'output' => $outputText
                ];
            }

            // Check for "Permission denied"
            if (stripos($outputText, 'permission denied') !== false) {
                return [
                    'success' => false,
                    'message' => 'Permission denied. Please add the SSH public key to your GitHub account.',
                    'output' => $outputText
                ];
            }

            // Other errors
            return [
                'success' => false,
                'message' => 'Connection failed. Please check your SSH key configuration.',
                'output' => $outputText
            ];
        } catch (Exception $e) {
            log_message('error', 'GitHub connection test failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get Git global configuration
     *
     * @return array Git config (user.name, user.email)
     */
    public function getGitConfig(): array
    {
        $config = [
            'user.name' => '',
            'user.email' => ''
        ];

        try {
            // Get user.name
            exec('git config --global user.name 2>&1', $nameOutput, $nameCode);
            if ($nameCode === 0 && !empty($nameOutput[0])) {
                $config['user.name'] = trim($nameOutput[0]);
            }

            // Get user.email
            exec('git config --global user.email 2>&1', $emailOutput, $emailCode);
            if ($emailCode === 0 && !empty($emailOutput[0])) {
                $config['user.email'] = trim($emailOutput[0]);
            }
        } catch (Exception $e) {
            log_message('error', 'Failed to get git config: ' . $e->getMessage());
        }

        return $config;
    }

    /**
     * Set Git global configuration
     *
     * @param string $name User name
     * @param string $email User email
     * @return array Result with success status and message
     */
    public function setGitConfig(string $name, string $email): array
    {
        try {
            // Set user.name
            exec(sprintf('git config --global user.name "%s" 2>&1', escapeshellarg($name)), $nameOutput, $nameCode);
            if ($nameCode !== 0) {
                throw new Exception('Failed to set user.name: ' . implode("\n", $nameOutput));
            }

            // Set user.email
            exec(sprintf('git config --global user.email "%s" 2>&1', escapeshellarg($email)), $emailOutput, $emailCode);
            if ($emailCode !== 0) {
                throw new Exception('Failed to set user.email: ' . implode("\n", $emailOutput));
            }

            log_activity('update_git_config', 'git', null, [
                'name' => $name,
                'email' => $email
            ]);

            return [
                'success' => true,
                'message' => 'Git configuration updated successfully'
            ];
        } catch (Exception $e) {
            log_message('error', 'Failed to set git config: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get Git version
     *
     * @return string|null
     */
    public function getGitVersion(): ?string
    {
        try {
            exec('git --version 2>&1', $output, $returnCode);
            if ($returnCode === 0 && !empty($output[0])) {
                return trim($output[0]);
            }
        } catch (Exception $e) {
            log_message('error', 'Failed to get git version: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Check if Git is installed
     *
     * @return bool
     */
    public function isGitInstalled(): bool
    {
        return $this->getGitVersion() !== null;
    }

    /**
     * Get SSH key fingerprint
     *
     * @return string|null
     */
    public function getSshKeyFingerprint(): ?string
    {
        if (!$this->sshKeyExists()) {
            return null;
        }

        try {
            exec(sprintf('ssh-keygen -lf "%s" 2>&1', escapeshellarg($this->publicKeyPath)), $output, $returnCode);
            if ($returnCode === 0 && !empty($output[0])) {
                return trim($output[0]);
            }
        } catch (Exception $e) {
            log_message('error', 'Failed to get SSH fingerprint: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Get the SSH command for Git operations
     *
     * @return string
     */
    private function getSshCommand(): string
    {
        if ($this->sshKeyExists()) {
            return sprintf(
                'ssh -i "%s" -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -o IdentitiesOnly=yes',
                $this->privateKeyPath
            );
        }
        return 'ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null';
    }

    /**
     * Clone a repository into a target directory
     *
     * @param string $repoUrl The repository URL (HTTPS or SSH)
     * @param string $targetDir The target directory path
     * @param string|null $branch Optional branch to clone
     * @param string|null $username GitHub username for HTTPS auth
     * @param string|null $token GitHub personal access token for HTTPS auth
     * @return array Result with success status and message
     */
    public function cloneRepository(string $repoUrl, string $targetDir, ?string $branch = null, ?string $username = null, ?string $token = null): array
    {
        try {
            // Check if target directory already exists and is not empty
            if (is_dir($targetDir)) {
                $files = scandir($targetDir);
                $files = array_diff($files, ['.', '..']);
                if (!empty($files)) {
                    return [
                        'success' => false,
                        'message' => 'Target directory is not empty. Please use pull to update existing repository.'
                    ];
                }
            } else {
                // Create the directory
                if (!mkdir($targetDir, 0755, true)) {
                    return [
                        'success' => false,
                        'message' => 'Failed to create target directory.'
                    ];
                }
            }

            // Build the git clone command
            $branchOption = $branch ? sprintf(' -b %s', escapeshellarg($branch)) : '';

            // Determine if using SSH or HTTPS
            $isSSH = (strpos($repoUrl, 'git@') === 0 || strpos($repoUrl, 'ssh://') === 0);
            $isHTTPS = (strpos($repoUrl, 'https://') === 0);

            // Build authenticated URL if credentials provided
            $cloneUrl = $repoUrl;
            if ($isHTTPS && $username && $token) {
                $cloneUrl = $this->buildAuthUrl($repoUrl, $username, $token);
            }

            if ($isSSH && $this->sshKeyExists()) {
                $command = sprintf(
                    'GIT_SSH_COMMAND="%s" git clone%s %s %s 2>&1',
                    $this->getSshCommand(),
                    $branchOption,
                    escapeshellarg($repoUrl),
                    escapeshellarg($targetDir)
                );
            } else {
                $command = sprintf(
                    'git clone%s %s %s 2>&1',
                    $branchOption,
                    escapeshellarg($cloneUrl),
                    escapeshellarg($targetDir)
                );
            }

            exec($command, $output, $returnCode);
            $outputText = implode("\n", $output);

            // Remove any credentials from output for security
            if ($token) {
                $outputText = str_replace($token, '***TOKEN***', $outputText);
            }

            if ($returnCode !== 0) {
                // Clean up empty directory on failure
                if (is_dir($targetDir) && count(scandir($targetDir)) <= 2) {
                    rmdir($targetDir);
                }

                return [
                    'success' => false,
                    'message' => 'Git clone failed: ' . $outputText
                ];
            }

            // If we used authenticated URL, reset remote to original URL (without credentials)
            if ($isHTTPS && $username && $token) {
                exec(sprintf('cd %s && git remote set-url origin %s 2>&1', escapeshellarg($targetDir), escapeshellarg($repoUrl)));
            }

            // Set proper ownership (www-data)
            exec(sprintf('chown -R www-data:www-data %s 2>&1', escapeshellarg($targetDir)));

            return [
                'success' => true,
                'message' => 'Repository cloned successfully',
                'output' => $outputText,
                'path' => $targetDir
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Pull updates for an existing repository
     *
     * @param string $repoDir The repository directory path
     * @param string|null $branch Optional branch to pull (defaults to current branch)
     * @param string|null $username GitHub username for HTTPS auth
     * @param string|null $token GitHub personal access token for HTTPS auth
     * @return array Result with success status and message
     */
    public function pullRepository(string $repoDir, ?string $branch = null, ?string $username = null, ?string $token = null): array
    {
        try {
            // Check if directory exists
            if (!is_dir($repoDir)) {
                return [
                    'success' => false,
                    'message' => 'Directory does not exist.'
                ];
            }

            // Check if it's a git repository
            if (!is_dir($repoDir . '/.git')) {
                return [
                    'success' => false,
                    'message' => 'Directory is not a git repository.'
                ];
            }

            // Get current branch if not specified
            if (!$branch) {
                exec(sprintf('cd %s && git rev-parse --abbrev-ref HEAD 2>&1', escapeshellarg($repoDir)), $branchOutput, $branchCode);
                $branch = ($branchCode === 0 && !empty($branchOutput[0])) ? trim($branchOutput[0]) : 'main';
            }

            // Check for remote URL to determine SSH vs HTTPS
            exec(sprintf('cd %s && git remote get-url origin 2>&1', escapeshellarg($repoDir)), $remoteOutput, $remoteCode);
            $remoteUrl = ($remoteCode === 0 && !empty($remoteOutput[0])) ? trim($remoteOutput[0]) : '';
            $isSSH = (strpos($remoteUrl, 'git@') === 0 || strpos($remoteUrl, 'ssh://') === 0);
            $isHTTPS = (strpos($remoteUrl, 'https://') === 0);

            // Build the git pull command
            if ($isSSH && $this->sshKeyExists()) {
                // Use SSH key
                $command = sprintf(
                    'cd %s && GIT_SSH_COMMAND="%s" git pull origin %s 2>&1',
                    escapeshellarg($repoDir),
                    $this->getSshCommand(),
                    escapeshellarg($branch)
                );
            } elseif ($isHTTPS && $username && $token) {
                // Use HTTPS with username/token authentication
                // Temporarily set the remote URL with credentials
                $authUrl = $this->buildAuthUrl($remoteUrl, $username, $token);
                $command = sprintf(
                    'cd %s && git remote set-url origin %s && git pull origin %s 2>&1; git remote set-url origin %s',
                    escapeshellarg($repoDir),
                    escapeshellarg($authUrl),
                    escapeshellarg($branch),
                    escapeshellarg($remoteUrl) // Restore original URL without credentials
                );
            } else {
                // No authentication
                $command = sprintf(
                    'cd %s && git pull origin %s 2>&1',
                    escapeshellarg($repoDir),
                    escapeshellarg($branch)
                );
            }

            exec($command, $output, $returnCode);
            $outputText = implode("\n", $output);

            // Remove any credentials from output for security
            if ($token) {
                $outputText = str_replace($token, '***TOKEN***', $outputText);
            }

            if ($returnCode !== 0) {
                return [
                    'success' => false,
                    'message' => 'Git pull failed: ' . $outputText
                ];
            }

            return [
                'success' => true,
                'message' => 'Repository updated successfully',
                'output' => $outputText,
                'branch' => $branch
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Build authenticated HTTPS URL
     *
     * @param string $url Original HTTPS URL
     * @param string $username GitHub username
     * @param string $token GitHub personal access token
     * @return string Authenticated URL
     */
    private function buildAuthUrl(string $url, string $username, string $token): string
    {
        // Convert https://github.com/user/repo.git to https://username:token@github.com/user/repo.git
        if (preg_match('#^https://([^/]+)/(.+)$#', $url, $matches)) {
            return sprintf('https://%s:%s@%s/%s',
                urlencode($username),
                urlencode($token),
                $matches[1],
                $matches[2]
            );
        }
        return $url;
    }

    /**
     * Get repository status
     *
     * @param string $repoDir The repository directory path
     * @return array Repository status info
     */
    public function getRepoStatus(string $repoDir): array
    {
        $status = [
            'is_git_repo' => false,
            'branch' => null,
            'remote_url' => null,
            'has_changes' => false,
            'ahead' => 0,
            'behind' => 0,
            'last_commit' => null
        ];

        if (!is_dir($repoDir . '/.git')) {
            return $status;
        }

        $status['is_git_repo'] = true;

        // Get current branch
        exec(sprintf('cd %s && git rev-parse --abbrev-ref HEAD 2>&1', escapeshellarg($repoDir)), $branchOutput, $branchCode);
        if ($branchCode === 0 && !empty($branchOutput[0])) {
            $status['branch'] = trim($branchOutput[0]);
        }

        // Get remote URL
        exec(sprintf('cd %s && git remote get-url origin 2>&1', escapeshellarg($repoDir)), $remoteOutput, $remoteCode);
        if ($remoteCode === 0 && !empty($remoteOutput[0])) {
            $status['remote_url'] = trim($remoteOutput[0]);
        }

        // Check for uncommitted changes
        exec(sprintf('cd %s && git status --porcelain 2>&1', escapeshellarg($repoDir)), $statusOutput, $statusCode);
        $status['has_changes'] = ($statusCode === 0 && !empty($statusOutput));

        // Get last commit info
        exec(sprintf('cd %s && git log -1 --format="%%h|%%s|%%ar" 2>&1', escapeshellarg($repoDir)), $logOutput, $logCode);
        if ($logCode === 0 && !empty($logOutput[0])) {
            $parts = explode('|', $logOutput[0]);
            if (count($parts) >= 3) {
                $status['last_commit'] = [
                    'hash' => $parts[0],
                    'message' => $parts[1],
                    'time_ago' => $parts[2]
                ];
            }
        }

        return $status;
    }
}
