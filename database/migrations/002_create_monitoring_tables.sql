-- ============================================================================
-- Phase 2: Dashboard & Server Monitoring
-- Migration: 002_create_monitoring_tables.sql
-- Description: Creates tables for system metrics and activity logging
-- ============================================================================

-- Table: system_metrics
-- Purpose: Store server performance metrics (CPU, RAM, Disk)
CREATE TABLE IF NOT EXISTS `system_metrics` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `metric_type` VARCHAR(50) NOT NULL COMMENT 'cpu, ram, disk, etc.',
    `metric_value` DECIMAL(5,2) NOT NULL COMMENT 'Percentage or numeric value',
    `recorded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_metric_type` (`metric_type`),
    INDEX `idx_recorded_at` (`recorded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='System performance metrics';

-- Table: activity_logs
-- Purpose: Track all user activities for auditing
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `action` VARCHAR(100) NOT NULL COMMENT 'login, logout, create_user, etc.',
    `entity_type` VARCHAR(50) NULL COMMENT 'user, app, domain, ssl, etc.',
    `entity_id` INT UNSIGNED NULL COMMENT 'ID of the affected entity',
    `details` TEXT NULL COMMENT 'Additional details in JSON format',
    `ip_address` VARCHAR(45) NULL COMMENT 'IPv4 or IPv6',
    `user_agent` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_entity_type` (`entity_type`),
    INDEX `idx_created_at` (`created_at`),

    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='User activity audit log';

-- ============================================================================
-- Initial data / seeds
-- ============================================================================

-- No seed data required for these tables

-- ============================================================================
-- Notes:
-- - system_metrics: Used for dashboard charts and historical monitoring
-- - activity_logs: Used for audit trail and security monitoring
-- - Both tables will grow over time; consider cleanup/archival strategy
-- ============================================================================
