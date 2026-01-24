<?php

/**
 * MySQL Database Model Test
 * Tests for MysqlDatabase model methods
 */

require_once __DIR__ . '/../app/Models/MysqlDatabase.php';
require_once __DIR__ . '/../app/Helpers/functions.php';

class MysqlDatabaseModelTest
{
    private $dbModel;
    private $testUserId = 1; // Assuming user ID 1 exists
    private $testDbId;

    public function __construct()
    {
        $this->dbModel = new MysqlDatabase();
    }

    public function run()
    {
        echo "Running MySQL Database Model Tests...\n";

        try {
            $this->testCreateDatabase();
            $this->testFindDatabase();
            $this->testGetAllDatabases();
            $this->testGetByName();
            $this->testUpdateSize();
            $this->testValidation();
            $this->testUpdateDatabase();
            $this->cleanup();

            echo "✓ All MySQL Database Model tests passed!\n\n";
            return true;
        } catch (Exception $e) {
            echo "✗ Test failed: " . $e->getMessage() . "\n";
            echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
            $this->cleanup();
            return false;
        }
    }

    private function testCreateDatabase()
    {
        echo "  Testing database creation...\n";

        $this->testDbId = $this->dbModel->create([
            'database_name' => 'test_db_' . time(),
            'database_user' => 'test_user',
            'database_size' => 0,
            'app_id' => null,
            'description' => 'Test database',
            'status' => 'active',
            'backup_enabled' => 1,
            'backup_frequency' => 'weekly',
            'user_id' => $this->testUserId
        ]);

        if (!$this->testDbId) {
            throw new Exception('Failed to create database record');
        }
    }

    private function testFindDatabase()
    {
        echo "  Testing find database...\n";

        $db = $this->dbModel->find($this->testDbId);

        if (!$db) {
            throw new Exception('Database not found');
        }

        if ($db['status'] !== 'active') {
            throw new Exception('Status mismatch');
        }

        if ($db['backup_enabled'] != 1) {
            throw new Exception('Backup enabled mismatch');
        }

        if ($db['backup_frequency'] !== 'weekly') {
            throw new Exception('Backup frequency mismatch');
        }
    }

    private function testGetAllDatabases()
    {
        echo "  Testing get all databases...\n";

        $databases = $this->dbModel->getAll();

        if (!is_array($databases)) {
            throw new Exception('getAll should return an array');
        }

        $found = false;
        foreach ($databases as $db) {
            if ($db['id'] == $this->testDbId) {
                $found = true;
                if (!isset($db['creator_name'])) {
                    throw new Exception('creator_name should be joined in getAll');
                }
                if (!isset($db['backup_count'])) {
                    throw new Exception('backup_count should be calculated in getAll');
                }
                break;
            }
        }

        if (!$found) {
            throw new Exception('Test database not found in getAll results');
        }
    }

    private function testGetByName()
    {
        echo "  Testing get database by name...\n";

        $db = $this->dbModel->find($this->testDbId);
        $foundDb = $this->dbModel->getByName($db['database_name']);

        if (!$foundDb) {
            throw new Exception('Database not found by name');
        }

        if ($foundDb['id'] != $this->testDbId) {
            throw new Exception('Wrong database returned');
        }
    }

    private function testUpdateSize()
    {
        echo "  Testing update database size...\n";

        $updated = $this->dbModel->updateSize($this->testDbId, 1048576); // 1MB

        if (!$updated) {
            throw new Exception('Failed to update size');
        }

        $db = $this->dbModel->find($this->testDbId);

        if ($db['database_size'] != 1048576) {
            throw new Exception('Size was not updated correctly');
        }
    }

    private function testValidation()
    {
        echo "  Testing database name validation...\n";

        // Valid names
        $validNames = ['my_database', 'test123', 'db_name_with_123'];
        foreach ($validNames as $name) {
            if (!$this->dbModel->isValidDatabaseName($name)) {
                throw new Exception("Valid name '$name' was rejected");
            }
        }

        // Invalid names
        $invalidNames = ['my-database', 'test.db', 'db with spaces', '', str_repeat('a', 65)];
        foreach ($invalidNames as $name) {
            if ($this->dbModel->isValidDatabaseName($name)) {
                throw new Exception("Invalid name '$name' was accepted");
            }
        }
    }

    private function testUpdateDatabase()
    {
        echo "  Testing database update...\n";

        $updated = $this->dbModel->update($this->testDbId, [
            'description' => 'Updated description',
            'status' => 'backing_up'
        ]);

        if (!$updated) {
            throw new Exception('Failed to update database');
        }

        $db = $this->dbModel->find($this->testDbId);

        if ($db['description'] !== 'Updated description') {
            throw new Exception('Description was not updated');
        }

        if ($db['status'] !== 'backing_up') {
            throw new Exception('Status was not updated');
        }
    }

    private function cleanup()
    {
        echo "  Cleaning up test data...\n";

        if ($this->testDbId) {
            $this->dbModel->delete($this->testDbId);
        }
    }
}

// Run test if executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $test = new MysqlDatabaseModelTest();
    exit($test->run() ? 0 : 1);
}
