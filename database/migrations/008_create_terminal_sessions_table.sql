-- ============================================================================
-- Phase 11: Web Terminal
-- Migration: 008_create_terminal_sessions_table.sql
-- Description: Creates table for tracking terminal command history
-- ============================================================================

-- Table: terminal_sessions
-- Purpose: Track web terminal command execution history
CREATE TABLE IF NOT EXISTS `terminal_sessions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL COMMENT 'User who executed the command',
    `command` TEXT NOT NULL COMMENT 'Command that was executed',
    `output` MEDIUMTEXT NULL COMMENT 'Command output',
    `error_output` TEXT NULL COMMENT 'Error output if command failed',
    `exit_code` INT NULL COMMENT 'Command exit code',
    `execution_time` DECIMAL(10,3) NULL COMMENT 'Execution time in seconds',
    `working_directory` VARCHAR(500) NULL COMMENT 'Working directory when command was executed',
    `environment` TEXT NULL COMMENT 'Environment variables (JSON)',
    `status` ENUM('running', 'completed', 'failed', 'timeout') NOT NULL DEFAULT 'running',
    `started_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `completed_at` TIMESTAMP NULL COMMENT 'When command finished',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_started_at` (`started_at`),

    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Web terminal command history';

-- ============================================================================
-- Notes:
-- - All commands are logged for security and audit purposes
-- - Exit code 0 = success, non-zero = error
-- - Commands timeout after configured duration (default 120 seconds)
-- - Output stored as MEDIUMTEXT to handle large outputs (up to 16MB)
-- ============================================================================
