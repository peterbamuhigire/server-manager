-- ============================================================================
-- Phase 6: SSL Management
-- Migration: 005_create_ssl_certificates_table.sql
-- Description: Creates table for managing SSL certificates
-- ============================================================================

-- Table: ssl_certificates
-- Purpose: Track SSL certificates for domains
CREATE TABLE IF NOT EXISTS `ssl_certificates` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `domain_id` INT UNSIGNED NOT NULL COMMENT 'Associated domain',
    `certificate_path` VARCHAR(500) NOT NULL COMMENT 'Path to certificate file',
    `private_key_path` VARCHAR(500) NOT NULL COMMENT 'Path to private key',
    `chain_path` VARCHAR(500) NULL COMMENT 'Path to certificate chain',
    `provider` ENUM('letsencrypt', 'custom', 'self-signed') NOT NULL DEFAULT 'letsencrypt',
    `status` ENUM('active', 'expired', 'renewing', 'error') NOT NULL DEFAULT 'active',
    `issued_at` TIMESTAMP NULL COMMENT 'When certificate was issued',
    `expires_at` TIMESTAMP NULL COMMENT 'When certificate expires',
    `auto_renew` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Auto-renew before expiry',
    `last_renewed_at` TIMESTAMP NULL COMMENT 'Last renewal date',
    `error_message` TEXT NULL COMMENT 'Error message if failed',
    `user_id` INT NOT NULL COMMENT 'User who created the certificate',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_domain_id` (`domain_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_expires_at` (`expires_at`),
    INDEX `idx_user_id` (`user_id`),

    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`domain_id`) REFERENCES `domains`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SSL certificates';

-- ============================================================================
-- Notes:
-- - provider: letsencrypt (auto), custom (uploaded), self-signed (dev)
-- - auto_renew: automatically renew 30 days before expiry
-- - One active certificate per domain
-- ============================================================================
