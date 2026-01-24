<?php

/**
 * App Model Tests
 * Tests for the App model CRUD operations
 */

require_once __DIR__ . '/../app/Models/App.php';
require_once __DIR__ . '/../app/Helpers/functions.php';

class AppModelTest
{
    private $testApp;
    private $testUserId = 1; // Assuming user ID 1 exists

    public function run()
    {
        echo "Running App Model Tests...\n";

        $this->testCreate();
        $this->testFind();
        $this->testUpdate();
        $this->testDelete();

        echo "All App Model tests passed!\n\n";
    }

    private function testCreate()
    {
        echo "Test: Create App... ";

        $appModel = new App();
        $appId = $appModel->create([
            'name' => 'Test Application',
            'subdomain' => 'test-app-' . time(),
            'git_repo' => 'https://github.com/test/repo.git',
            'git_branch' => 'main',
            'document_root' => 'public',
            'status' => 'pending',
            'user_id' => $this->testUserId
        ]);

        if (!$appId) {
            throw new Exception('Failed to create app');
        }

        $this->testApp = $appModel->find($appId);
        if (!$this->testApp) {
            throw new Exception('Failed to find created app');
        }

        echo "PASSED\n";
    }

    private function testFind()
    {
        echo "Test: Find App... ";

        if ($this->testApp['name'] !== 'Test Application') {
            throw new Exception('App name mismatch');
        }

        if ($this->testApp['status'] !== 'pending') {
            throw new Exception('App status mismatch');
        }

        echo "PASSED\n";
    }

    private function testUpdate()
    {
        echo "Test: Update App... ";

        $appModel = new App();
        $app = $appModel->find($this->testApp['id']);
        $app['status'] = 'active';
        $app['deployed_at'] = date('Y-m-d H:i:s');

        $result = $appModel->update($this->testApp['id'], [
            'status' => 'active',
            'deployed_at' => date('Y-m-d H:i:s')
        ]);

        if (!$result) {
            throw new Exception('Failed to update app');
        }

        $updated = $appModel->find($this->testApp['id']);

        if ($updated['status'] !== 'active') {
            throw new Exception('App status not updated');
        }

        echo "PASSED\n";
    }

    private function testDelete()
    {
        echo "Test: Delete App... ";

        $appModel = new App();
        $result = $appModel->delete($this->testApp['id']);

        if (!$result) {
            throw new Exception('Failed to delete app');
        }

        $deleted = $appModel->find($this->testApp['id']);

        if ($deleted) {
            throw new Exception('App still exists after deletion');
        }

        echo "PASSED\n";
    }
}

// Run tests if executed directly
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    try {
        $test = new AppModelTest();
        $test->run();
    } catch (Exception $e) {
        echo "FAILED: " . $e->getMessage() . "\n";
        exit(1);
    }
}
