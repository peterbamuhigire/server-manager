-- ============================================================================
-- Phase 5: Domain Management
-- Migration: 004_create_domains_table.sql
-- Description: Creates table for managing custom domains
-- ============================================================================

-- Table: domains
-- Purpose: Store custom domain configurations
CREATE TABLE IF NOT EXISTS `domains` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `domain_name` VARCHAR(255) NOT NULL COMMENT 'Full domain name (e.g., example.com)',
    `app_id` INT UNSIGNED NULL COMMENT 'Associated application (optional)',
    `document_root` VARCHAR(500) NULL COMMENT 'Custom document root path',
    `status` ENUM('pending', 'active', 'error') NOT NULL DEFAULT 'pending' COMMENT 'Domain status',
    `dns_configured` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Whether DNS is properly configured',
    `vhost_configured` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Whether vhost is configured',
    `ssl_enabled` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Whether SSL is enabled',
    `ssl_certificate_path` VARCHAR(500) NULL COMMENT 'Path to SSL certificate',
    `ssl_key_path` VARCHAR(500) NULL COMMENT 'Path to SSL private key',
    `error_message` TEXT NULL COMMENT 'Error message if configuration failed',
    `user_id` INT NOT NULL COMMENT 'User who created the domain',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE INDEX `idx_domain_name` (`domain_name`),
    INDEX `idx_app_id` (`app_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_user_id` (`user_id`),

    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`app_id`) REFERENCES `apps`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Custom domain configurations';

-- ============================================================================
-- Notes:
-- - domain_name must be unique (one domain per entry)
-- - app_id is optional (domain can exist without an app)
-- - dns_configured: true when DNS points to the server
-- - vhost_configured: true when Apache vhost is set up
-- - ssl_enabled: true when SSL certificate is installed
-- ============================================================================
