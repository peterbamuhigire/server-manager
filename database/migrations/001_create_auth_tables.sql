-- ============================================
-- Chwezi Server Manager - Auth Tables
-- Migration: 001_create_auth_tables
-- ============================================

-- Roles table
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    slug VARCHAR(50) NOT NULL UNIQUE,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Permissions table
CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    category VARCHAR(50) NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    is_root BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'disabled') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id),
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Role-Permission pivot table
CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sessions table
CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Insert Default Roles
-- ============================================

INSERT INTO roles (name, slug, description) VALUES
('Root', 'root', 'Root user with full system access'),
('Admin', 'admin', 'Administrator with all permissions except user management'),
('Developer', 'developer', 'Developer with access to apps, domains, SSL, databases, git, and logs'),
('Viewer', 'viewer', 'Read-only access to view system information')
ON DUPLICATE KEY UPDATE name=name;

-- ============================================
-- Insert Permissions
-- ============================================

-- Apps permissions
INSERT INTO permissions (name, slug, category, description) VALUES
('View Apps', 'apps.view', 'apps', 'View application list and details'),
('Create Apps', 'apps.create', 'apps', 'Deploy new applications'),
('Update Apps', 'apps.update', 'apps', 'Update and redeploy applications'),
('Delete Apps', 'apps.delete', 'apps', 'Delete applications'),

-- Domains permissions
('View Domains', 'domains.view', 'domains', 'View domain list and configuration'),
('Create Domains', 'domains.create', 'domains', 'Add new domains'),
('Update Domains', 'domains.update', 'domains', 'Modify domain configuration'),
('Delete Domains', 'domains.delete', 'domains', 'Remove domains'),

-- SSL permissions
('View SSL', 'ssl.view', 'ssl', 'View SSL certificates'),
('Install SSL', 'ssl.install', 'ssl', 'Install SSL certificates'),
('Renew SSL', 'ssl.renew', 'ssl', 'Renew SSL certificates'),
('Delete SSL', 'ssl.delete', 'ssl', 'Remove SSL certificates'),

-- Databases permissions
('View Databases', 'databases.view', 'databases', 'View database list'),
('Create Databases', 'databases.create', 'databases', 'Create databases'),
('Backup Databases', 'databases.backup', 'databases', 'Backup and restore databases'),
('Delete Databases', 'databases.delete', 'databases', 'Delete databases'),

-- WordPress permissions
('Install WordPress', 'wordpress.install', 'wordpress', 'Install new WordPress sites'),
('Manage WordPress', 'wordpress.manage', 'wordpress', 'Manage WordPress installations'),

-- Git permissions
('View Git', 'git.view', 'git', 'View git configuration'),
('Manage Git', 'git.manage', 'git', 'Manage git settings and SSH keys'),

-- Submodules permissions
('View Submodules', 'submodules.view', 'submodules', 'View git submodules'),
('Manage Submodules', 'submodules.manage', 'submodules', 'Add, update, remove submodules'),

-- Cron permissions
('View Cron Jobs', 'cron.view', 'cron', 'View scheduled jobs'),
('Create Cron Jobs', 'cron.create', 'cron', 'Create scheduled jobs'),
('Update Cron Jobs', 'cron.update', 'cron', 'Modify scheduled jobs'),
('Delete Cron Jobs', 'cron.delete', 'cron', 'Remove scheduled jobs'),

-- Logs permissions
('View Logs', 'logs.view', 'logs', 'View system and application logs'),
('Download Logs', 'logs.download', 'logs', 'Download log files'),

-- Terminal permissions
('Access Terminal', 'terminal.access', 'terminal', 'Access web terminal'),

-- Users permissions (ROOT ONLY)
('View Users', 'users.view', 'users', 'View user list'),
('Create Users', 'users.create', 'users', 'Create new users'),
('Update Users', 'users.update', 'users', 'Modify user accounts'),
('Delete Users', 'users.delete', 'users', 'Delete user accounts'),

-- Settings permissions
('View Settings', 'settings.view', 'settings', 'View system settings'),
('Update Settings', 'settings.update', 'settings', 'Modify system settings')
ON DUPLICATE KEY UPDATE name=name;

-- ============================================
-- Assign Permissions to Roles
-- ============================================

-- Root role gets ALL permissions (handled in code via is_root flag)
-- No need to assign individual permissions to root

-- Admin role (all except user management)
INSERT INTO role_permissions (role_id, permission_id)
SELECT
    (SELECT id FROM roles WHERE slug = 'admin'),
    id
FROM permissions
WHERE category != 'users'
ON DUPLICATE KEY UPDATE role_id=role_id;

-- Developer role (apps, domains, ssl, databases, git, submodules, logs)
INSERT INTO role_permissions (role_id, permission_id)
SELECT
    (SELECT id FROM roles WHERE slug = 'developer'),
    id
FROM permissions
WHERE category IN ('apps', 'domains', 'ssl', 'databases', 'git', 'submodules', 'logs')
ON DUPLICATE KEY UPDATE role_id=role_id;

-- Viewer role (view permissions only)
INSERT INTO role_permissions (role_id, permission_id)
SELECT
    (SELECT id FROM roles WHERE slug = 'viewer'),
    id
FROM permissions
WHERE slug LIKE '%.view'
ON DUPLICATE KEY UPDATE role_id=role_id;
