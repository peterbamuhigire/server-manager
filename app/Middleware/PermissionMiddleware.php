<?php

require_once __DIR__ . '/../Models/User.php';

/**
 * Permission Middleware
 * Chwezi Server Manager
 */

class PermissionMiddleware
{
    /**
     * Check if user has permission
     * @param string $permission Permission slug (e.g., 'apps.create')
     */
    public static function check(string $permission): void
    {
        // Check if user is authenticated
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            die('Forbidden: Authentication required');
        }

        // Root users have all permissions
        if (isset($_SESSION['is_root']) && $_SESSION['is_root']) {
            return;
        }

        // Check permission
        $userModel = new User();
        $hasPermission = $userModel->hasPermission($_SESSION['user_id'], $permission);

        if (!$hasPermission) {
            http_response_code(403);
            log_message("Permission denied: {$permission} for user ID: " . $_SESSION['user_id'], 'warning');
            die('Forbidden: Insufficient permissions');
        }
    }

    /**
     * Check if user has any of the given permissions
     * @param array $permissions Array of permission slugs
     */
    public static function checkAny(array $permissions): void
    {
        // Check if user is authenticated
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            die('Forbidden: Authentication required');
        }

        // Root users have all permissions
        if (isset($_SESSION['is_root']) && $_SESSION['is_root']) {
            return;
        }

        // Check if user has any of the permissions
        $userModel = new User();

        foreach ($permissions as $permission) {
            if ($userModel->hasPermission($_SESSION['user_id'], $permission)) {
                return;
            }
        }

        http_response_code(403);
        log_message("Permission denied (any): " . implode(', ', $permissions) . " for user ID: " . $_SESSION['user_id'], 'warning');
        die('Forbidden: Insufficient permissions');
    }

    /**
     * Check if user has all of the given permissions
     * @param array $permissions Array of permission slugs
     */
    public static function checkAll(array $permissions): void
    {
        // Check if user is authenticated
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            die('Forbidden: Authentication required');
        }

        // Root users have all permissions
        if (isset($_SESSION['is_root']) && $_SESSION['is_root']) {
            return;
        }

        // Check if user has all permissions
        $userModel = new User();

        foreach ($permissions as $permission) {
            if (!$userModel->hasPermission($_SESSION['user_id'], $permission)) {
                http_response_code(403);
                log_message("Permission denied (all): " . implode(', ', $permissions) . " for user ID: " . $_SESSION['user_id'], 'warning');
                die('Forbidden: Insufficient permissions');
            }
        }
    }
}
