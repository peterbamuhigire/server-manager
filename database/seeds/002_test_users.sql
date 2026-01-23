-- ============================================================================
-- Test Users Seed Data
-- Creates test users for each role with known passwords
-- ============================================================================

-- First, let's update the root user password to ensure it works
-- Password: Wirtz2025@
UPDATE users
SET password = '$2y$12$psNLTtZ1dl1Zlmz668hjMubgb1vlAJJuPFlsP2ysK2vq8F5KxXrB2'
WHERE username = 'peter';

-- Get role IDs
SET @root_role_id = (SELECT id FROM roles WHERE slug = 'root' LIMIT 1);
SET @admin_role_id = (SELECT id FROM roles WHERE slug = 'admin' LIMIT 1);
SET @developer_role_id = (SELECT id FROM roles WHERE slug = 'developer' LIMIT 1);
SET @viewer_role_id = (SELECT id FROM roles WHERE slug = 'viewer' LIMIT 1);

-- Delete existing test users if they exist
DELETE FROM users WHERE username IN ('admin', 'developer', 'viewer');

-- Admin User (Password: Admin123!)
INSERT INTO users (username, email, password, role_id, is_root, status, created_at, updated_at)
VALUES (
    'admin',
    'admin@test.com',
    '$2y$12$1KKcmws6BU28heb219RkCewTcK3EvofmtKXFGzkLPWSthb.0XgMRW',
    @admin_role_id,
    0,
    'active',
    NOW(),
    NOW()
);

-- Developer User (Password: Dev123!)
INSERT INTO users (username, email, password, role_id, is_root, status, created_at, updated_at)
VALUES (
    'developer',
    'developer@test.com',
    '$2y$12$SLnx2hrqfMSJU9/bXrMmi.xzGLZilR6K3xiMi44s.N/.q378UKhTq',
    @developer_role_id,
    0,
    'active',
    NOW(),
    NOW()
);

-- Viewer User (Password: View123!)
INSERT INTO users (username, email, password, role_id, is_root, status, created_at, updated_at)
VALUES (
    'viewer',
    'viewer@test.com',
    '$2y$12$AUm6bkZI0YgyaUKtkMACx.znd37dR7/BpDFokJ8QyFJ7yAV5A82eW',
    @viewer_role_id,
    0,
    'active',
    NOW(),
    NOW()
);

-- ============================================================================
-- Test Users Created
-- ============================================================================
-- Root User:      peter / Wirtz2025@
-- Admin User:     admin / Admin123!
-- Developer User: developer / Dev123!
-- Viewer User:    viewer / View123!
-- ============================================================================
