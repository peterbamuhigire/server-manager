<?php

/**
 * Root User Middleware
 * Chwezi Server Manager
 */

class RootMiddleware
{
    /**
     * Handle root access check
     */
    public static function handle(): void
    {
        // Check if user is authenticated
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            die('Forbidden: Authentication required');
        }

        // Check if user is root
        if (!isset($_SESSION['is_root']) || !$_SESSION['is_root']) {
            http_response_code(403);
            log_message("Unauthorized root access attempt by user ID: " . $_SESSION['user_id'], 'warning');
            die('Forbidden: Root access required');
        }
    }
}
