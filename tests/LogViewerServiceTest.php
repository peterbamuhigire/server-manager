<?php

/**
 * Log Viewer Service Test
 * Tests for log viewing functionality
 */

require_once __DIR__ . '/../app/Services/LogViewerService.php';
require_once __DIR__ . '/../app/Helpers/functions.php';

class LogViewerServiceTest
{
    private $logService;
    private $testLogDir;
    private $testLogFile;

    public function __construct()
    {
        $this->logService = new LogViewerService();
        $this->testLogDir = __DIR__ . '/../storage/logs/test';
        $this->testLogFile = $this->testLogDir . '/test.log';
    }

    public function run()
    {
        echo "Running Log Viewer Service Tests...\n";

        try {
            $this->setup();
            $this->testLogFileDetection();
            $this->testReadLog();
            $this->testTailFile();
            $this->testSearchLog();
            $this->testLogInfo();
            $this->testClearLog();
            $this->testCategories();
            $this->cleanup();

            echo "✓ All Log Viewer Service tests passed!\n\n";
            return true;
        } catch (Exception $e) {
            echo "✗ Test failed: " . $e->getMessage() . "\n";
            echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
            $this->cleanup();
            return false;
        }
    }

    private function setup()
    {
        echo "  Setting up test environment...\n";

        // Create test log directory
        if (!is_dir($this->testLogDir)) {
            mkdir($this->testLogDir, 0755, true);
        }

        // Create test log file with sample content
        $logContent = "[2026-01-24 10:00:00] INFO: Application started\n";
        $logContent .= "[2026-01-24 10:01:00] WARNING: Low memory\n";
        $logContent .= "[2026-01-24 10:02:00] ERROR: Database connection failed\n";
        $logContent .= "[2026-01-24 10:03:00] INFO: User logged in\n";
        $logContent .= "[2026-01-24 10:04:00] DEBUG: Processing request\n";
        $logContent .= "[2026-01-24 10:05:00] ERROR: File not found\n";
        $logContent .= "[2026-01-24 10:06:00] INFO: Request completed\n";
        $logContent .= "[2026-01-24 10:07:00] WARNING: Slow query detected\n";
        $logContent .= "[2026-01-24 10:08:00] INFO: Cache cleared\n";
        $logContent .= "[2026-01-24 10:09:00] ERROR: Permission denied\n";

        file_put_contents($this->testLogFile, $logContent);

        echo "    ✓ Test environment ready\n";
    }

    private function testLogFileDetection()
    {
        echo "  Testing log file detection...\n";

        // Use reflection to test private method
        $reflection = new ReflectionClass($this->logService);
        $method = $reflection->getMethod('isLogFile');
        $method->setAccessible(true);

        // Test valid log files
        $validLogFiles = [
            'error.log',
            'access.log',
            'app.log',
            'test.txt',
            'debug.err',
            'output.out',
            'apache_error.log',
        ];

        foreach ($validLogFiles as $filename) {
            $isLog = $method->invoke($this->logService, $filename);
            if (!$isLog) {
                throw new Exception("Valid log file rejected: $filename");
            }
        }

        // Test invalid log files
        $invalidLogFiles = [
            'script.php',
            'image.jpg',
            'config.json',
            'data.xml',
            'README.md',
        ];

        foreach ($invalidLogFiles as $filename) {
            $isLog = $method->invoke($this->logService, $filename);
            if ($isLog) {
                throw new Exception("Invalid log file accepted: $filename");
            }
        }

        echo "    ✓ Log file detection works correctly\n";
    }

    private function testReadLog()
    {
        echo "  Testing log file reading...\n";

        $result = $this->logService->readLog($this->testLogFile, 100);

        if (!$result['success']) {
            throw new Exception("Failed to read log file: " . ($result['message'] ?? 'Unknown error'));
        }

        if (empty($result['content'])) {
            throw new Exception("Log content should not be empty");
        }

        if (!isset($result['size'])) {
            throw new Exception("Result should include file size");
        }

        if (!isset($result['lines'])) {
            throw new Exception("Result should include line count");
        }

        // Verify content contains expected text
        if (strpos($result['content'], 'Application started') === false) {
            throw new Exception("Log content missing expected text");
        }

        // Test reading non-existent file
        $result = $this->logService->readLog('/path/to/nonexistent.log', 100);
        if ($result['success']) {
            throw new Exception("Reading non-existent file should fail");
        }

        echo "    ✓ Log reading works correctly\n";
    }

    private function testTailFile()
    {
        echo "  Testing log file tailing...\n";

        // Tail last 5 lines
        $content = $this->logService->tailFile($this->testLogFile, 5);

        if (empty($content)) {
            throw new Exception("Tail content should not be empty");
        }

        // Count lines
        $lines = explode("\n", trim($content));
        if (count($lines) > 5) {
            throw new Exception("Tail should return at most 5 lines, got " . count($lines));
        }

        // Verify last line is the most recent log entry
        if (strpos($content, 'Permission denied') === false) {
            throw new Exception("Tail should include the last log entry");
        }

        // Test with offset
        $content = $this->logService->tailFile($this->testLogFile, 3, 2);
        if (empty($content)) {
            throw new Exception("Tail with offset should return content");
        }

        echo "    ✓ Log tailing works correctly\n";
    }

    private function testSearchLog()
    {
        echo "  Testing log file search...\n";

        // Search for "ERROR" (case insensitive)
        $result = $this->logService->searchLog($this->testLogFile, 'ERROR', false);

        if (!$result['success']) {
            throw new Exception("Search failed: " . ($result['message'] ?? 'Unknown error'));
        }

        if (!isset($result['matches'])) {
            throw new Exception("Search result should include matches");
        }

        if (!isset($result['count'])) {
            throw new Exception("Search result should include count");
        }

        // Verify we found the expected ERROR lines
        if ($result['count'] < 3) {
            throw new Exception("Should find at least 3 ERROR lines, found " . $result['count']);
        }

        // Search case sensitive
        $result = $this->logService->searchLog($this->testLogFile, 'error', true);
        if ($result['count'] > 0) {
            throw new Exception("Case sensitive search for 'error' should return 0 (log uses 'ERROR')");
        }

        // Search for specific text
        $result = $this->logService->searchLog($this->testLogFile, 'Database connection', false);
        if ($result['count'] !== 1) {
            throw new Exception("Should find exactly 1 'Database connection' entry");
        }

        // Test searching non-existent file
        $result = $this->logService->searchLog('/path/to/nonexistent.log', 'test', false);
        if ($result['success']) {
            throw new Exception("Searching non-existent file should fail");
        }

        echo "    ✓ Log search works correctly\n";
    }

    private function testLogInfo()
    {
        echo "  Testing log file info retrieval...\n";

        $info = $this->logService->getLogInfo($this->testLogFile);

        if (empty($info)) {
            throw new Exception("getLogInfo should return array for existing file");
        }

        // Verify required fields
        $requiredFields = ['path', 'name', 'size', 'size_formatted', 'modified',
                          'modified_formatted', 'lines', 'readable', 'writable'];

        foreach ($requiredFields as $field) {
            if (!isset($info[$field])) {
                throw new Exception("Log info missing field: $field");
            }
        }

        // Verify values
        if ($info['name'] !== 'test.log') {
            throw new Exception("Log name should be 'test.log'");
        }

        if ($info['lines'] !== 10) {
            throw new Exception("Log should have 10 lines, got " . $info['lines']);
        }

        if (!$info['readable']) {
            throw new Exception("Test log should be readable");
        }

        // Test non-existent file
        $info = $this->logService->getLogInfo('/path/to/nonexistent.log');
        if ($info !== null) {
            throw new Exception("getLogInfo should return null for non-existent file");
        }

        echo "    ✓ Log info retrieval works correctly\n";
    }

    private function testClearLog()
    {
        echo "  Testing log file clearing...\n";

        // Get original size
        $originalSize = filesize($this->testLogFile);
        if ($originalSize === 0) {
            throw new Exception("Test log should have content before clearing");
        }

        // Clear the log
        $result = $this->logService->clearLog($this->testLogFile);

        if (!$result['success']) {
            throw new Exception("Failed to clear log: " . ($result['message'] ?? 'Unknown error'));
        }

        // Verify file is empty
        $newSize = filesize($this->testLogFile);
        if ($newSize !== 0) {
            throw new Exception("Log file should be empty after clearing");
        }

        // Restore content for other tests
        $this->setup();

        // Test clearing non-existent file
        $result = $this->logService->clearLog('/path/to/nonexistent.log');
        if ($result['success']) {
            throw new Exception("Clearing non-existent file should fail");
        }

        echo "    ✓ Log clearing works correctly\n";
    }

    private function testCategories()
    {
        echo "  Testing log categories...\n";

        $categories = $this->logService->getCategories();

        if (!is_array($categories)) {
            throw new Exception("getCategories should return an array");
        }

        // Verify expected categories
        $expectedCategories = ['apache', 'nginx', 'php', 'mysql', 'application', 'system'];

        foreach ($expectedCategories as $category) {
            if (!isset($categories[$category])) {
                throw new Exception("Missing category: $category");
            }

            if (empty($categories[$category])) {
                throw new Exception("Category label should not be empty: $category");
            }
        }

        echo "    ✓ Log categories are correct\n";
    }

    private function cleanup()
    {
        echo "  Cleaning up test data...\n";

        // Remove test log file
        if (file_exists($this->testLogFile)) {
            unlink($this->testLogFile);
        }

        // Remove test log directory
        if (is_dir($this->testLogDir)) {
            rmdir($this->testLogDir);
        }

        echo "    ✓ Cleanup complete\n";
    }
}

// Run tests if executed directly
if (php_sapi_name() === 'cli') {
    $test = new LogViewerServiceTest();
    $result = $test->run();
    exit($result ? 0 : 1);
}
