<?php

require_once __DIR__ . '/../Models/MysqlDatabase.php';
require_once __DIR__ . '/../Models/DatabaseBackup.php';
require_once __DIR__ . '/ActivityLogService.php';
require_once __DIR__ . '/../../config/database.php';

/**
 * Database Service
 * Handles MySQL database creation, backup, and restoration
 *
 * @package ChweziSM
 * @subpackage Services
 */
class DatabaseService
{
    private $backupPath;
    private $activityLog;
    private $dbConfig;

    public function __construct()
    {
        $this->backupPath = realpath(__DIR__ . '/../../storage/backups/databases') ?: __DIR__ . '/../../storage/backups/databases';
        $this->activityLog = new ActivityLogService();

        // Ensure backup directory exists
        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }

        // Get database config
        $this->dbConfig = [
            'host' => config('db.host', 'localhost'),
            'username' => config('db.root_username', 'root'),
            'password' => config('db.root_password', ''),
        ];
    }

    /**
     * Create new MySQL database
     */
    public function createDatabase($databaseName, $createUser = true, $userId = null)
    {
        try {
            $dbModel = new MysqlDatabase();

            // Validate database name
            if (!$dbModel->isValidDatabaseName($databaseName)) {
                throw new Exception('Invalid database name. Use only alphanumeric characters and underscores (1-64 chars).');
            }

            // Check if already exists
            if ($dbModel->nameExists($databaseName)) {
                throw new Exception('Database already exists in tracking system.');
            }

            // Create database
            $pdo = new PDO(
                "mysql:host={$this->dbConfig['host']}",
                $this->dbConfig['username'],
                $this->dbConfig['password']
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERR_EXCEPTION);

            $sql = "CREATE DATABASE IF NOT EXISTS `{$databaseName}`
                    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            $pdo->exec($sql);

            // Create database user if requested
            $dbUser = null;
            if ($createUser) {
                $dbUser = $databaseName . '_user';
                $dbPassword = $this->generatePassword();

                $sql = "CREATE USER IF NOT EXISTS '{$dbUser}'@'localhost' IDENTIFIED BY '{$dbPassword}'";
                $pdo->exec($sql);

                $sql = "GRANT ALL PRIVILEGES ON `{$databaseName}`.* TO '{$dbUser}'@'localhost'";
                $pdo->exec($sql);

                $sql = "FLUSH PRIVILEGES";
                $pdo->exec($sql);
            }

            // Save to tracking database
            $dbId = $dbModel->create([
                'database_name' => $databaseName,
                'database_user' => $dbUser,
                'database_size' => 0,
                'app_id' => null,
                'description' => null,
                'status' => 'active',
                'backup_enabled' => 1,
                'backup_frequency' => 'weekly',
                'user_id' => $userId ?? 1
            ]);

            $this->activityLog->log(
                'database_created',
                'Database created: ' . $databaseName,
                ['database_id' => $dbId, 'database_name' => $databaseName]
            );

            return [
                'success' => true,
                'message' => 'Database created successfully',
                'database_id' => $dbId,
                'database_name' => $databaseName,
                'database_user' => $dbUser,
                'database_password' => $createUser ? $dbPassword : null
            ];

        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'MySQL error: ' . $e->getMessage()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Backup database to SQL file
     */
    public function backupDatabase($databaseId, $backupType = 'manual')
    {
        try {
            $dbModel = new MysqlDatabase();
            $database = $dbModel->find($databaseId);

            if (!$database) {
                throw new Exception('Database not found');
            }

            $backupModel = new DatabaseBackup();

            // Create backup record
            $backupId = $backupModel->create([
                'database_id' => $databaseId,
                'backup_path' => '',
                'backup_size' => null,
                'backup_type' => $backupType,
                'status' => 'in_progress'
            ]);

            // Update database status
            $dbModel->update($databaseId, ['status' => 'backing_up']);

            // Generate backup filename
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "{$database['database_name']}_{$timestamp}.sql";
            $backupFile = $this->backupPath . '/' . $filename;

            // Run mysqldump
            $result = $this->runMysqldump($database['database_name'], $backupFile);

            if (!$result['success']) {
                $backupModel->update($backupId, [
                    'status' => 'failed',
                    'error_message' => $result['message']
                ]);
                $dbModel->update($databaseId, ['status' => 'active']);
                throw new Exception($result['message']);
            }

            // Get backup file size
            $backupSize = file_exists($backupFile) ? filesize($backupFile) : 0;

            // Update backup record
            $backupModel->update($backupId, [
                'backup_path' => $backupFile,
                'backup_size' => $backupSize,
                'status' => 'completed'
            ]);

            // Update database
            $dbModel->update($databaseId, [
                'status' => 'active',
                'last_backup_at' => date('Y-m-d H:i:s')
            ]);

            // Update database size
            $this->updateDatabaseSize($databaseId);

            $this->activityLog->log(
                'database_backup',
                'Database backed up: ' . $database['database_name'],
                ['database_id' => $databaseId, 'backup_id' => $backupId]
            );

            return [
                'success' => true,
                'message' => 'Database backed up successfully',
                'backup_id' => $backupId,
                'backup_file' => $filename,
                'backup_size' => $backupSize
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Restore database from backup
     */
    public function restoreDatabase($backupId)
    {
        try {
            $backupModel = new DatabaseBackup();
            $backup = $backupModel->find($backupId);

            if (!$backup) {
                throw new Exception('Backup not found');
            }

            if (!file_exists($backup['backup_path'])) {
                throw new Exception('Backup file not found');
            }

            $dbModel = new MysqlDatabase();
            $database = $dbModel->find($backup['database_id']);

            if (!$database) {
                throw new Exception('Database not found');
            }

            // Run mysql restore
            $result = $this->runMysqlRestore($database['database_name'], $backup['backup_path']);

            if (!$result['success']) {
                throw new Exception($result['message']);
            }

            $this->activityLog->log(
                'database_restore',
                'Database restored: ' . $database['database_name'],
                ['database_id' => $database['id'], 'backup_id' => $backupId]
            );

            return [
                'success' => true,
                'message' => 'Database restored successfully'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete database
     */
    public function deleteDatabase($databaseId)
    {
        try {
            $dbModel = new MysqlDatabase();
            $database = $dbModel->find($databaseId);

            if (!$database) {
                throw new Exception('Database not found');
            }

            // Drop database
            $pdo = new PDO(
                "mysql:host={$this->dbConfig['host']}",
                $this->dbConfig['username'],
                $this->dbConfig['password']
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERR_EXCEPTION);

            $sql = "DROP DATABASE IF EXISTS `{$database['database_name']}`";
            $pdo->exec($sql);

            // Drop user if exists
            if ($database['database_user']) {
                $sql = "DROP USER IF EXISTS '{$database['database_user']}'@'localhost'";
                $pdo->exec($sql);
            }

            // Delete from tracking
            $dbModel->delete($databaseId);

            $this->activityLog->log(
                'database_deleted',
                'Database deleted: ' . $database['database_name'],
                ['database_name' => $database['database_name']]
            );

            return [
                'success' => true,
                'message' => 'Database deleted successfully'
            ];

        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'MySQL error: ' . $e->getMessage()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Update database size
     */
    public function updateDatabaseSize($databaseId)
    {
        try {
            $dbModel = new MysqlDatabase();
            $database = $dbModel->find($databaseId);

            if (!$database) {
                return false;
            }

            $pdo = new PDO(
                "mysql:host={$this->dbConfig['host']}",
                $this->dbConfig['username'],
                $this->dbConfig['password']
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERR_EXCEPTION);

            $sql = "SELECT SUM(data_length + index_length) as size
                    FROM information_schema.TABLES
                    WHERE table_schema = ?";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$database['database_name']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $size = $result ? (int)$result['size'] : 0;
            $dbModel->updateSize($databaseId, $size);

            return true;

        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Run mysqldump command
     */
    private function runMysqldump($databaseName, $outputFile)
    {
        // For Windows WAMP
        if (stripos(PHP_OS, 'WIN') === 0) {
            $mysqldump = 'C:/wamp64/bin/mysql/mysql8.4.7/bin/mysqldump.exe';
        } else {
            $mysqldump = 'mysqldump';
        }

        $command = sprintf(
            '%s --user=%s --password=%s --host=%s %s > %s 2>&1',
            escapeshellarg($mysqldump),
            escapeshellarg($this->dbConfig['username']),
            escapeshellarg($this->dbConfig['password']),
            escapeshellarg($this->dbConfig['host']),
            escapeshellarg($databaseName),
            escapeshellarg($outputFile)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            return [
                'success' => false,
                'message' => 'Backup failed: ' . implode("\n", $output)
            ];
        }

        return ['success' => true];
    }

    /**
     * Run mysql restore command
     */
    private function runMysqlRestore($databaseName, $inputFile)
    {
        // For Windows WAMP
        if (stripos(PHP_OS, 'WIN') === 0) {
            $mysql = 'C:/wamp64/bin/mysql/mysql8.4.7/bin/mysql.exe';
        } else {
            $mysql = 'mysql';
        }

        $command = sprintf(
            '%s --user=%s --password=%s --host=%s %s < %s 2>&1',
            escapeshellarg($mysql),
            escapeshellarg($this->dbConfig['username']),
            escapeshellarg($this->dbConfig['password']),
            escapeshellarg($this->dbConfig['host']),
            escapeshellarg($databaseName),
            escapeshellarg($inputFile)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            return [
                'success' => false,
                'message' => 'Restore failed: ' . implode("\n", $output)
            ];
        }

        return ['success' => true];
    }

    /**
     * Generate random password
     */
    private function generatePassword($length = 16)
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        $max = strlen($characters) - 1;

        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[random_int(0, $max)];
        }

        return $password;
    }
}
