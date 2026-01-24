<?php

/**
 * Domain Model Tests
 */

require_once __DIR__ . '/../app/Models/Domain.php';

class DomainModelTest
{
    private $testDomain;
    private $testUserId = 1;

    public function run()
    {
        echo "Running Domain Model Tests...\n";

        $this->testCreate();
        $this->testFind();
        $this->testUpdate();
        $this->testDelete();

        echo "All Domain Model tests passed!\n\n";
    }

    private function testCreate()
    {
        echo "Test: Create Domain... ";

        $domainModel = new Domain();
        $domainId = $domainModel->create([
            'domain_name' => 'test' . time() . '.com',
            'app_id' => null,
            'document_root' => '',
            'status' => 'pending',
            'user_id' => $this->testUserId
        ]);

        if (!$domainId) {
            throw new Exception('Failed to create domain');
        }

        $this->testDomain = $domainModel->find($domainId);
        if (!$this->testDomain) {
            throw new Exception('Failed to find created domain');
        }

        echo "PASSED\n";
    }

    private function testFind()
    {
        echo "Test: Find Domain... ";

        if ($this->testDomain['status'] !== 'pending') {
            throw new Exception('Domain status mismatch');
        }

        echo "PASSED\n";
    }

    private function testUpdate()
    {
        echo "Test: Update Domain... ";

        $domainModel = new Domain();
        $result = $domainModel->update($this->testDomain['id'], [
            'status' => 'active',
            'dns_configured' => 1,
            'vhost_configured' => 1
        ]);

        if (!$result) {
            throw new Exception('Failed to update domain');
        }

        $updated = $domainModel->find($this->testDomain['id']);

        if ($updated['status'] !== 'active') {
            throw new Exception('Domain status not updated');
        }

        echo "PASSED\n";
    }

    private function testDelete()
    {
        echo "Test: Delete Domain... ";

        $domainModel = new Domain();
        $result = $domainModel->delete($this->testDomain['id']);

        if (!$result) {
            throw new Exception('Failed to delete domain');
        }

        $deleted = $domainModel->find($this->testDomain['id']);

        if ($deleted) {
            throw new Exception('Domain still exists after deletion');
        }

        echo "PASSED\n";
    }
}
