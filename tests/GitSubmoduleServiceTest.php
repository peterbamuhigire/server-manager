<?php

/**
 * Git Submodule Service Tests
 * Tests for Phase 12: Git Submodules
 */

require_once __DIR__ . '/../app/Services/GitSubmoduleService.php';
require_once __DIR__ . '/../app/Models/GitSubmodule.php';
require_once __DIR__ . '/../app/Models/App.php';

class GitSubmoduleServiceTest
{
    private $service;
    private $results = [];

    public function __construct()
    {
        $this->service = new GitSubmoduleService();
    }

    /**
     * Run all tests
     */
    public function runAll()
    {
        echo "====================================\n";
        echo "Git Submodule Service Tests\n";
        echo "====================================\n\n";

        $this->testAddSubmodule();
        $this->testAddSubmoduleWithInvalidName();
        $this->testUpdateSubmodule();
        $this->testGetStatus();
        $this->testRemoveSubmodule();

        $this->printResults();
    }

    /**
     * Test adding a new submodule
     */
    private function testAddSubmodule()
    {
        echo "Testing: Add Submodule...\n";

        $name = 'test-submodule-' . time();
        $repositoryUrl = 'https://github.com/test/test-repo.git';
        $description = 'Test submodule for unit testing';
        $branch = 'main';
        $userId = 1;

        $result = $this->service->addSubmodule($name, $repositoryUrl, $description, $branch, $userId);

        if ($result['success'] && !empty($result['submodule_id'])) {
            $this->pass("Add Submodule");

            // Clean up - remove the test submodule
            $this->service->removeSubmodule($result['submodule_id']);
        } else {
            $this->fail("Add Submodule", $result['message'] ?? 'Unknown error');
        }
    }

    /**
     * Test adding a submodule with invalid name
     */
    private function testAddSubmoduleWithInvalidName()
    {
        echo "Testing: Add Submodule with Invalid Name...\n";

        $name = 'Test Submodule With Spaces';  // Invalid: contains spaces and uppercase
        $repositoryUrl = 'https://github.com/test/test-repo.git';
        $description = 'Test submodule';
        $branch = 'main';
        $userId = 1;

        $result = $this->service->addSubmodule($name, $repositoryUrl, $description, $branch, $userId);

        // Should fail because name is invalid
        if (!$result['success'] && strpos($result['message'], 'lowercase') !== false) {
            $this->pass("Add Submodule with Invalid Name");
        } else {
            $this->fail("Add Submodule with Invalid Name", "Should reject invalid submodule names");
        }
    }

    /**
     * Test updating a submodule
     */
    private function testUpdateSubmodule()
    {
        echo "Testing: Update Submodule...\n";

        // First, create a test submodule
        $name = 'test-update-' . time();
        $repositoryUrl = 'https://github.com/test/test-repo.git';
        $description = 'Test submodule for update testing';
        $branch = 'main';
        $userId = 1;

        $createResult = $this->service->addSubmodule($name, $repositoryUrl, $description, $branch, $userId);

        if (!$createResult['success']) {
            $this->fail("Update Submodule", "Failed to create test submodule");
            return;
        }

        $submoduleId = $createResult['submodule_id'];

        // Now try to update it
        $updateResult = $this->service->updateSubmodule($submoduleId);

        if ($updateResult['success']) {
            $this->pass("Update Submodule");
        } else {
            $this->fail("Update Submodule", $updateResult['message'] ?? 'Unknown error');
        }

        // Clean up
        $this->service->removeSubmodule($submoduleId);
    }

    /**
     * Test getting submodule status
     */
    private function testGetStatus()
    {
        echo "Testing: Get Submodule Status...\n";

        // First, create a test submodule
        $name = 'test-status-' . time();
        $repositoryUrl = 'https://github.com/test/test-repo.git';
        $description = 'Test submodule for status testing';
        $branch = 'main';
        $userId = 1;

        $createResult = $this->service->addSubmodule($name, $repositoryUrl, $description, $branch, $userId);

        if (!$createResult['success']) {
            $this->fail("Get Submodule Status", "Failed to create test submodule");
            return;
        }

        $submoduleId = $createResult['submodule_id'];

        // Get status
        $statusResult = $this->service->getStatus($submoduleId);

        if ($statusResult['success'] &&
            !empty($statusResult['submodule']) &&
            $statusResult['submodule']['name'] === $name) {
            $this->pass("Get Submodule Status");
        } else {
            $this->fail("Get Submodule Status", "Failed to retrieve submodule status");
        }

        // Clean up
        $this->service->removeSubmodule($submoduleId);
    }

    /**
     * Test removing a submodule
     */
    private function testRemoveSubmodule()
    {
        echo "Testing: Remove Submodule...\n";

        // First, create a test submodule
        $name = 'test-remove-' . time();
        $repositoryUrl = 'https://github.com/test/test-repo.git';
        $description = 'Test submodule for removal testing';
        $branch = 'main';
        $userId = 1;

        $createResult = $this->service->addSubmodule($name, $repositoryUrl, $description, $branch, $userId);

        if (!$createResult['success']) {
            $this->fail("Remove Submodule", "Failed to create test submodule");
            return;
        }

        $submoduleId = $createResult['submodule_id'];

        // Remove the submodule
        $removeResult = $this->service->removeSubmodule($submoduleId);

        if ($removeResult['success']) {
            $this->pass("Remove Submodule");
        } else {
            $this->fail("Remove Submodule", $removeResult['message'] ?? 'Unknown error');
        }
    }

    /**
     * Record a passing test
     */
    private function pass($testName)
    {
        $this->results[] = [
            'name' => $testName,
            'status' => 'PASS'
        ];
        echo "  ✓ PASS\n\n";
    }

    /**
     * Record a failing test
     */
    private function fail($testName, $message = '')
    {
        $this->results[] = [
            'name' => $testName,
            'status' => 'FAIL',
            'message' => $message
        ];
        echo "  ✗ FAIL" . ($message ? " - $message" : "") . "\n\n";
    }

    /**
     * Print test results summary
     */
    private function printResults()
    {
        echo "====================================\n";
        echo "Test Results\n";
        echo "====================================\n\n";

        $passed = 0;
        $failed = 0;

        foreach ($this->results as $result) {
            $status = $result['status'] === 'PASS' ? '✓' : '✗';
            echo "{$status} {$result['name']}: {$result['status']}\n";

            if ($result['status'] === 'PASS') {
                $passed++;
            } else {
                $failed++;
                if (!empty($result['message'])) {
                    echo "  Error: {$result['message']}\n";
                }
            }
        }

        echo "\n====================================\n";
        echo "Total: " . count($this->results) . " | ";
        echo "Passed: $passed | ";
        echo "Failed: $failed\n";
        echo "====================================\n";

        if ($failed > 0) {
            exit(1);
        }
    }
}

// Run tests if executed directly
if (php_sapi_name() === 'cli') {
    $test = new GitSubmoduleServiceTest();
    $test->runAll();
}
