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
}
