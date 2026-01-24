-- ============================================================================
-- Phase 4: Application Deployment
-- Migration: 003_create_apps_table.sql
-- Description: Creates table for managing deployed applications
-- ============================================================================

-- Table: apps
-- Purpose: Store information about deployed applications
CREATE TABLE IF NOT EXISTS `apps` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL COMMENT 'Application name',
    `subdomain` VARCHAR(100) NOT NULL COMMENT 'Subdomain (e.g., myapp for myapp.localhost)',
    `git_repo` VARCHAR(500) NOT NULL COMMENT 'GitHub repository URL',
    `git_branch` VARCHAR(100) NOT NULL DEFAULT 'main' COMMENT 'Git branch',
    `document_root` VARCHAR(255) NULL COMMENT 'Relative document root path (e.g., public)',
    `status` ENUM('pending', 'deploying', 'active', 'error') NOT NULL DEFAULT 'pending' COMMENT 'Deployment status',
    `error_message` TEXT NULL COMMENT 'Error message if deployment failed',
    `deployed_at` TIMESTAMP NULL COMMENT 'Last successful deployment time',
    `user_id` INT NOT NULL COMMENT 'User who created the app',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE INDEX `idx_subdomain` (`subdomain`),
    INDEX `idx_name` (`name`),
    INDEX `idx_status` (`status`),
    INDEX `idx_user_id` (`user_id`),

    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Deployed applications';

-- ============================================================================
-- Notes:
-- - subdomain must be unique (e.g., "myapp" becomes "myapp.localhost")
-- - document_root: Relative path like "public" for Laravel/Symfony apps
-- - status: pending → deploying → active (or error if deployment fails)
-- - error_message: Stores deployment error details
-- ============================================================================
