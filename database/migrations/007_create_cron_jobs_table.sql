-- ============================================================================
-- Phase 9: Cron Jobs
-- Migration: 007_create_cron_jobs_table.sql
-- Description: Creates table for managing scheduled cron jobs
-- ============================================================================

-- Table: cron_jobs
-- Purpose: Track scheduled cron jobs and their execution
CREATE TABLE IF NOT EXISTS `cron_jobs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL COMMENT 'Human-readable job name',
    `command` TEXT NOT NULL COMMENT 'Command to execute',
    `schedule` VARCHAR(100) NOT NULL COMMENT 'Cron schedule (e.g., "0 2 * * *")',
    `description` TEXT NULL COMMENT 'Job description',
    `enabled` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Whether job is active',
    `last_run_at` TIMESTAMP NULL COMMENT 'Last execution timestamp',
    `last_run_status` ENUM('success', 'failed', 'running') NULL COMMENT 'Last execution status',
    `last_run_output` TEXT NULL COMMENT 'Output from last run',
    `next_run_at` TIMESTAMP NULL COMMENT 'Calculated next run time',
    `run_count` INT UNSIGNED DEFAULT 0 COMMENT 'Total execution count',
    `user_id` INT NOT NULL COMMENT 'User who created the job',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_enabled` (`enabled`),
    INDEX `idx_schedule` (`schedule`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_next_run` (`next_run_at`),

    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Scheduled cron jobs';

-- Table: cron_job_logs
-- Purpose: Track execution history of cron jobs
CREATE TABLE IF NOT EXISTS `cron_job_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `cron_job_id` INT UNSIGNED NOT NULL COMMENT 'Associated cron job',
    `status` ENUM('success', 'failed') NOT NULL COMMENT 'Execution status',
    `output` TEXT NULL COMMENT 'Command output',
    `error_output` TEXT NULL COMMENT 'Error output if failed',
    `execution_time` DECIMAL(10,3) NULL COMMENT 'Execution time in seconds',
    `started_at` TIMESTAMP NOT NULL COMMENT 'When execution started',
    `completed_at` TIMESTAMP NULL COMMENT 'When execution completed',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_cron_job_id` (`cron_job_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_started_at` (`started_at`),

    FOREIGN KEY (`cron_job_id`) REFERENCES `cron_jobs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cron job execution logs';

-- ============================================================================
-- Notes:
-- - Cron schedule format: minute hour day month weekday
-- - Common schedules: "0 2 * * *" (daily at 2am), "*/5 * * * *" (every 5 min)
-- - Jobs are synced to system crontab on Ubuntu/Debian
-- - Execution logs kept for monitoring and debugging
-- ============================================================================
