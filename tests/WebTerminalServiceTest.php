<?php

/**
 * Web Terminal Service Test
 * Tests for web terminal command execution
 */

require_once __DIR__ . '/../app/Services/WebTerminalService.php';
require_once __DIR__ . '/../app/Models/TerminalSession.php';
require_once __DIR__ . '/../app/Helpers/functions.php';

class WebTerminalServiceTest
{
    private $terminalService;
    private $sessionModel;
    private $testSessionIds = [];

    public function __construct()
    {
        $this->terminalService = new WebTerminalService();
        $this->sessionModel = new TerminalSession();
    }

    public function run()
    {
        echo "Running Web Terminal Service Tests...\n";

        try {
            $this->testCommandValidation();
            $this->testDangerousCommandDetection();
            $this->testCommandExecution();
            $this->testCommandHistory();
            $this->testCommandStats();
            $this->testCommonCommands();
            $this->cleanup();

            echo "✓ All Web Terminal Service tests passed!\n\n";
            return true;
        } catch (Exception $e) {
            echo "✗ Test failed: " . $e->getMessage() . "\n";
            echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
            $this->cleanup();
            return false;
        }
    }

    private function testCommandValidation()
    {
        echo "  Testing command validation...\n";

        // Use reflection to test private method
        $reflection = new ReflectionClass($this->terminalService);
        $method = $reflection->getMethod('validateCommand');
        $method->setAccessible(true);

        // Test empty command
        $result = $method->invoke($this->terminalService, '');
        if ($result['valid']) {
            throw new Exception("Empty command should be invalid");
        }

        // Test blocked command (fork bomb)
        $result = $method->invoke($this->terminalService, ':(){:|:&};:');
        if ($result['valid']) {
            throw new Exception("Fork bomb should be blocked");
        }

        // Test dangerous command (requires confirmation)
        $result = $method->invoke($this->terminalService, 'rm -rf /tmp/test');
        if (!$result['valid']) {
            throw new Exception("Dangerous command should be valid but require confirmation");
        }
        if (!$result['requires_confirmation']) {
            throw new Exception("Dangerous command should require confirmation");
        }

        // Test safe command
        $result = $method->invoke($this->terminalService, 'ls -la');
        if (!$result['valid']) {
            throw new Exception("Safe command should be valid");
        }
        if ($result['requires_confirmation']) {
            throw new Exception("Safe command should not require confirmation");
        }

        echo "    ✓ Command validation works correctly\n";
    }

    private function testDangerousCommandDetection()
    {
        echo "  Testing dangerous command detection...\n";

        // Use reflection to test private method
        $reflection = new ReflectionClass($this->terminalService);
        $method = $reflection->getMethod('isDangerous');
        $method->setAccessible(true);

        // Test dangerous commands
        $dangerousCommands = [
            'rm -rf /var/www',
            'shutdown now',
            'reboot',
            'mkfs.ext4 /dev/sda1',
        ];

        foreach ($dangerousCommands as $command) {
            $isDangerous = $method->invoke($this->terminalService, $command);
            if (!$isDangerous) {
                throw new Exception("Command should be detected as dangerous: $command");
            }
        }

        // Test safe commands
        $safeCommands = [
            'ls -la',
            'pwd',
            'whoami',
            'df -h',
        ];

        foreach ($safeCommands as $command) {
            $isDangerous = $method->invoke($this->terminalService, $command);
            if ($isDangerous) {
                throw new Exception("Command should NOT be detected as dangerous: $command");
            }
        }

        echo "    ✓ Dangerous command detection works correctly\n";
    }

    private function testCommandExecution()
    {
        echo "  Testing command execution...\n";

        // Execute a safe command (use Windows-compatible command)
        $command = stripos(PHP_OS, 'WIN') === 0 ? 'echo Hello' : 'echo "Hello World"';
        $result = $this->terminalService->executeCommand($command, 1, false);

        if (!$result['success']) {
            throw new Exception("Safe command execution should succeed");
        }

        if (!isset($result['output'])) {
            throw new Exception("Execution result should include output");
        }

        if (!isset($result['exit_code'])) {
            throw new Exception("Execution result should include exit code");
        }

        if ($result['exit_code'] !== 0) {
            throw new Exception("Echo command should have exit code 0");
        }

        if (!isset($result['session_id'])) {
            throw new Exception("Execution result should include session ID");
        }

        $this->testSessionIds[] = $result['session_id'];

        // Verify session was created
        $session = $this->sessionModel->find($result['session_id']);
        if (!$session) {
            throw new Exception("Session should be created in database");
        }

        if ($session['command'] !== $command) {
            throw new Exception("Session should store the command");
        }

        if ($session['status'] !== 'completed') {
            throw new Exception("Session status should be 'completed'");
        }

        // Test dangerous command without confirmation
        $result = $this->terminalService->executeCommand('rm -rf /tmp/test', 1, false);

        if ($result['success']) {
            throw new Exception("Dangerous command should require confirmation");
        }

        if (!isset($result['requires_confirmation'])) {
            throw new Exception("Result should indicate confirmation requirement");
        }

        if (!$result['requires_confirmation']) {
            throw new Exception("requires_confirmation should be true");
        }

        echo "    ✓ Command execution works correctly\n";
    }

    private function testCommandHistory()
    {
        echo "  Testing command history...\n";

        // Execute a few commands to build history
        $this->terminalService->executeCommand('pwd', 1, false);
        $this->terminalService->executeCommand('whoami', 1, false);
        $this->terminalService->executeCommand('hostname', 1, false);

        // Get history
        $history = $this->terminalService->getHistory(1, 10);

        if (!is_array($history)) {
            throw new Exception("History should return an array");
        }

        if (count($history) < 3) {
            throw new Exception("History should include at least 3 commands");
        }

        // Verify most recent command is first
        if ($history[0]['command'] !== 'hostname') {
            throw new Exception("History should be ordered by most recent first");
        }

        // Test search
        $results = $this->terminalService->searchHistory('who', 1, 10);

        if (!is_array($results)) {
            throw new Exception("Search should return an array");
        }

        // Should find "whoami" and "hostname"
        if (count($results) < 1) {
            throw new Exception("Search should find matching commands");
        }

        // Store session IDs for cleanup
        foreach ($history as $item) {
            $this->testSessionIds[] = $item['id'];
        }

        echo "    ✓ Command history works correctly\n";
    }

    private function testCommandStats()
    {
        echo "  Testing command statistics...\n";

        $stats = $this->terminalService->getStats(1);

        if (!is_array($stats)) {
            throw new Exception("Stats should return an array");
        }

        // Verify required fields
        $requiredFields = ['total_commands', 'successful', 'failed', 'avg_execution_time', 'last_command_at'];

        foreach ($requiredFields as $field) {
            if (!isset($stats[$field])) {
                throw new Exception("Stats missing field: $field");
            }
        }

        // Verify counts
        if ($stats['total_commands'] < 4) {
            throw new Exception("Total commands should be at least 4");
        }

        if ($stats['successful'] < 4) {
            throw new Exception("Successful commands should be at least 4");
        }

        echo "    ✓ Command statistics work correctly\n";
    }

    private function testCommonCommands()
    {
        echo "  Testing common commands...\n";

        $commands = $this->terminalService->getCommonCommands();

        if (!is_array($commands)) {
            throw new Exception("Common commands should return an array");
        }

        // Verify expected categories
        $expectedCategories = ['System Information', 'File Operations', 'Process Management', 'Service Management', 'Network', 'Git'];

        foreach ($expectedCategories as $category) {
            if (!isset($commands[$category])) {
                throw new Exception("Missing common commands category: $category");
            }

            if (!is_array($commands[$category])) {
                throw new Exception("Category should be an array: $category");
            }

            if (empty($commands[$category])) {
                throw new Exception("Category should not be empty: $category");
            }
        }

        echo "    ✓ Common commands are correct\n";
    }

    private function cleanup()
    {
        echo "  Cleaning up test data...\n";

        // Delete test terminal sessions
        foreach ($this->testSessionIds as $id) {
            $this->sessionModel->delete($id);
        }

        echo "    ✓ Cleanup complete\n";
    }
}

// Run tests if executed directly
if (php_sapi_name() === 'cli') {
    $test = new WebTerminalServiceTest();
    $result = $test->run();
    exit($result ? 0 : 1);
}
