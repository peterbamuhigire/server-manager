-- ============================================================================
-- Phase 7: Database Management
-- Migration: 006_create_databases_table.sql
-- Description: Creates table for managing MySQL databases
-- ============================================================================

-- Table: mysql_databases
-- Purpose: Track MySQL databases created through the application
CREATE TABLE IF NOT EXISTS `mysql_databases` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `database_name` VARCHAR(64) NOT NULL UNIQUE COMMENT 'MySQL database name',
    `database_user` VARCHAR(32) NULL COMMENT 'Database username',
    `database_size` BIGINT UNSIGNED NULL COMMENT 'Database size in bytes',
    `app_id` INT UNSIGNED NULL COMMENT 'Associated application',
    `description` TEXT NULL COMMENT 'Database description',
    `status` ENUM('active', 'backing_up', 'error') NOT NULL DEFAULT 'active',
    `last_backup_at` TIMESTAMP NULL COMMENT 'Last backup timestamp',
    `backup_enabled` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Enable automated backups',
    `backup_frequency` ENUM('daily', 'weekly', 'monthly') DEFAULT 'weekly',
    `error_message` TEXT NULL COMMENT 'Error message if failed',
    `user_id` INT NOT NULL COMMENT 'User who created the database',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_database_name` (`database_name`),
    INDEX `idx_app_id` (`app_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_user_id` (`user_id`),

    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`app_id`) REFERENCES `apps`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='MySQL database tracking';

-- Table: database_backups
-- Purpose: Track database backup history
CREATE TABLE IF NOT EXISTS `database_backups` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `database_id` INT UNSIGNED NOT NULL COMMENT 'Associated database',
    `backup_path` VARCHAR(500) NOT NULL COMMENT 'Path to backup file',
    `backup_size` BIGINT UNSIGNED NULL COMMENT 'Backup size in bytes',
    `backup_type` ENUM('manual', 'scheduled') NOT NULL DEFAULT 'manual',
    `status` ENUM('completed', 'failed', 'in_progress') NOT NULL DEFAULT 'in_progress',
    `error_message` TEXT NULL COMMENT 'Error if backup failed',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_database_id` (`database_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`),

    FOREIGN KEY (`database_id`) REFERENCES `mysql_databases`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Database backup history';

-- ============================================================================
-- Notes:
-- - database_name must be valid MySQL database name (max 64 chars)
-- - database_user is optional (some apps may use shared credentials)
-- - backup_enabled: controls automated backups
-- - Backups stored in storage/backups/databases/
-- ============================================================================
