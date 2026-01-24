<?php

require_once __DIR__ . '/../Models/TerminalSession.php';
require_once __DIR__ . '/ActivityLogService.php';

/**
 * Web Terminal Service
 * Secure command execution service for web terminal
 *
 * @package ChweziSM
 * @subpackage Services
 */
class WebTerminalService
{
    private $sessionModel;
    private $activityLog;
    private $maxExecutionTime;
    private $workingDirectory;

    // Dangerous commands that require confirmation
    private $dangerousCommands = [
        'rm -rf',
        'mkfs',
        'dd if',
        'shutdown',
        'reboot',
        'halt',
        'init 0',
        'init 6',
        'systemctl poweroff',
        'systemctl reboot',
        '> /dev',
        'chmod -R 777',
        'chown -R',
        ':(){:|:&};:',  // Fork bomb
    ];

    // Commands that are completely blocked
    private $blockedCommands = [
        ':(){:|:&};:',  // Fork bomb
        'wget http',    // Downloading from internet (use git clone instead)
        'curl http',    // Downloading from internet
    ];

    public function __construct()
    {
        $this->sessionModel = new TerminalSession();
        $this->activityLog = new ActivityLogService();
        $this->maxExecutionTime = config('terminal.timeout', 120);

        // Set working directory based on OS
        $defaultDir = stripos(PHP_OS, 'WIN') === 0 ? 'C:/wamp64/www' : '/var/www';
        $this->workingDirectory = config('terminal.working_dir', $defaultDir);
    }

    /**
     * Execute a command in the terminal
     */
    public function executeCommand($command, $userId, $confirmed = false)
    {
        try {
            // Validate command
            $validation = $this->validateCommand($command);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => $validation['message'],
                    'requires_confirmation' => $validation['requires_confirmation'] ?? false
                ];
            }

            // Check if command requires confirmation
            if ($this->isDangerous($command) && !$confirmed) {
                return [
                    'success' => false,
                    'message' => 'This command is potentially dangerous. Please confirm execution.',
                    'requires_confirmation' => true,
                    'command' => $command
                ];
            }

            // Create session record
            $sessionId = $this->sessionModel->create([
                'user_id' => $userId,
                'command' => $command,
                'working_directory' => $this->workingDirectory,
                'status' => 'running',
                'started_at' => date('Y-m-d H:i:s')
            ]);

            // Execute command
            $startTime = microtime(true);
            $output = [];
            $exitCode = 0;

            // Execute command
            // On Windows, skip directory change as it's complex with cmd.exe
            // On Linux, change to working directory first
            if (stripos(PHP_OS, 'WIN') === 0) {
                $fullCommand = $command . " 2>&1";
            } else {
                $fullCommand = "cd " . escapeshellarg($this->workingDirectory) . " && " . $command . " 2>&1";
            }
            exec($fullCommand, $output, $exitCode);

            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 3);

            // Determine status
            $status = $exitCode === 0 ? 'completed' : 'failed';
            $outputText = implode("\n", $output);
            $errorOutput = $exitCode !== 0 ? $outputText : null;

            // Update session
            $this->sessionModel->completeSession(
                $sessionId,
                $status,
                $outputText,
                $errorOutput,
                $exitCode,
                $executionTime
            );

            // Log activity
            $this->activityLog->log(
                'terminal_command',
                'Executed terminal command',
                [
                    'command' => $command,
                    'exit_code' => $exitCode,
                    'execution_time' => $executionTime
                ]
            );

            return [
                'success' => true,
                'output' => $outputText,
                'exit_code' => $exitCode,
                'execution_time' => $executionTime,
                'status' => $status,
                'session_id' => $sessionId
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Command execution failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validate command before execution
     */
    private function validateCommand($command)
    {
        // Check if command is empty
        if (empty(trim($command))) {
            return [
                'valid' => false,
                'message' => 'Command cannot be empty'
            ];
        }

        // Check for blocked commands
        foreach ($this->blockedCommands as $blocked) {
            if (stripos($command, $blocked) !== false) {
                return [
                    'valid' => false,
                    'message' => 'This command is blocked for security reasons'
                ];
            }
        }

        // Check for dangerous commands (requires confirmation)
        foreach ($this->dangerousCommands as $dangerous) {
            if (stripos($command, $dangerous) !== false) {
                return [
                    'valid' => true,
                    'requires_confirmation' => true,
                    'message' => 'This command is potentially dangerous'
                ];
            }
        }

        return [
            'valid' => true,
            'requires_confirmation' => false
        ];
    }

    /**
     * Check if command is dangerous
     */
    private function isDangerous($command)
    {
        foreach ($this->dangerousCommands as $dangerous) {
            if (stripos($command, $dangerous) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get command history for a user
     */
    public function getHistory($userId, $limit = 50)
    {
        return $this->sessionModel->getByUser($userId, $limit);
    }

    /**
     * Search command history
     */
    public function searchHistory($query, $userId, $limit = 50)
    {
        return $this->sessionModel->search($query, $userId, $limit);
    }

    /**
     * Clear command history for a user
     */
    public function clearHistory($userId)
    {
        try {
            $sql = "DELETE FROM terminal_sessions WHERE user_id = ?";
            $stmt = $this->sessionModel->db->prepare($sql);
            $stmt->execute([$userId]);

            return [
                'success' => true,
                'message' => 'Command history cleared successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to clear history: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get terminal statistics for a user
     */
    public function getStats($userId)
    {
        return $this->sessionModel->getStats($userId);
    }

    /**
     * Get common commands
     */
    public function getCommonCommands()
    {
        return [
            'System Information' => [
                'uname -a' => 'System information',
                'hostname' => 'Show hostname',
                'uptime' => 'System uptime',
                'whoami' => 'Current user',
                'id' => 'User ID and groups',
            ],
            'File Operations' => [
                'ls -la' => 'List files (detailed)',
                'pwd' => 'Print working directory',
                'df -h' => 'Disk space usage',
                'du -sh *' => 'Directory sizes',
                'find . -name "*.log"' => 'Find log files',
            ],
            'Process Management' => [
                'ps aux' => 'List all processes',
                'top -n 1' => 'Process snapshot',
                'pgrep apache2' => 'Find Apache processes',
                'pgrep mysql' => 'Find MySQL processes',
            ],
            'Service Management' => [
                'systemctl status apache2' => 'Apache status',
                'systemctl status mysql' => 'MySQL status',
                'systemctl list-units --type=service' => 'List services',
            ],
            'Network' => [
                'ifconfig' => 'Network interfaces',
                'netstat -tulpn' => 'Open ports',
                'ping -c 4 google.com' => 'Ping Google',
                'curl -I localhost' => 'HTTP headers',
            ],
            'Git' => [
                'git status' => 'Git status',
                'git log --oneline -10' => 'Recent commits',
                'git branch' => 'List branches',
                'git diff' => 'Show changes',
            ],
        ];
    }

    /**
     * Get current working directory
     */
    public function getWorkingDirectory()
    {
        return $this->workingDirectory;
    }

    /**
     * Set working directory
     */
    public function setWorkingDirectory($directory)
    {
        if (is_dir($directory)) {
            $this->workingDirectory = $directory;
            return true;
        }
        return false;
    }
}
