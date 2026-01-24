-- ============================================================================
-- Phase 12: Git Submodules
-- Migration: 009_create_git_submodules_table.sql
-- Description: Creates tables for managing Git submodules (shared code)
-- ============================================================================

-- Table: git_submodules
-- Purpose: Track shared Git repositories that can be used across applications
CREATE TABLE IF NOT EXISTS `git_submodules` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Submodule name (e.g., "shared-components")',
    `repository_url` VARCHAR(500) NOT NULL COMMENT 'Git repository URL',
    `local_path` VARCHAR(255) NOT NULL COMMENT 'Local path where submodule is cloned',
    `description` TEXT NULL COMMENT 'Submodule description',
    `branch` VARCHAR(100) DEFAULT 'main' COMMENT 'Git branch to use',
    `status` ENUM('active', 'syncing', 'error') NOT NULL DEFAULT 'active',
    `last_sync_at` TIMESTAMP NULL COMMENT 'Last synchronization time',
    `last_commit_hash` VARCHAR(40) NULL COMMENT 'Latest commit hash',
    `error_message` TEXT NULL COMMENT 'Error message if sync failed',
    `user_id` INT NOT NULL COMMENT 'User who created the submodule',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_name` (`name`),
    INDEX `idx_status` (`status`),
    INDEX `idx_user_id` (`user_id`),

    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Git submodules for shared code';

-- Table: app_submodules
-- Purpose: Link applications to submodules (many-to-many relationship)
CREATE TABLE IF NOT EXISTS `app_submodules` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `app_id` INT UNSIGNED NOT NULL COMMENT 'Application ID',
    `submodule_id` INT UNSIGNED NOT NULL COMMENT 'Submodule ID',
    `relative_path` VARCHAR(255) NOT NULL COMMENT 'Path within app where submodule is linked',
    `auto_sync` TINYINT(1) DEFAULT 1 COMMENT 'Auto-sync submodule on app deployment',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_app_id` (`app_id`),
    INDEX `idx_submodule_id` (`submodule_id`),
    UNIQUE KEY `unique_app_submodule` (`app_id`, `submodule_id`),

    FOREIGN KEY (`app_id`) REFERENCES `apps`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`submodule_id`) REFERENCES `git_submodules`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Application-Submodule relationships';

-- ============================================================================
-- Notes:
-- - Submodules are shared code repositories that can be linked to multiple apps
-- - Each app can have multiple submodules
-- - Submodules are cloned to a central location and symlinked to apps
-- - Auto-sync ensures submodules are updated when apps are deployed
-- - Central submodule location: /var/www/submodules/ (or configured path)
-- ============================================================================
