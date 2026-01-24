<?php

/**
 * CSRF Protection Middleware
 * Chwezi Server Manager
 */

class CsrfMiddleware
{
    /**
     * Generate CSRF token
     * @return string
     */
    public static function generate(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Get CSRF token
     * @return string|null
     */
    public static function getToken(): ?string
    {
        return $_SESSION['csrf_token'] ?? null;
    }

    /**
     * Verify CSRF token
     */
    public static function verify(): void
    {
        // Skip CSRF check if disabled in config
        if (!config('app.csrf_enabled', true)) {
            return;
        }

        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $sessionToken = $_SESSION['csrf_token'] ?? '';

        if (!hash_equals($sessionToken, $token)) {
            http_response_code(403);
            log_message("CSRF validation failed. IP: " . get_client_ip(), 'warning');
            die('CSRF validation failed');
        }
    }

    /**
     * Validate CSRF token (returns bool instead of dying)
     * @param string $token
     * @return bool
     */
    public static function validate(string $token): bool
    {
        // Skip CSRF check if disabled in config
        if (!config('app.csrf_enabled', true)) {
            return true;
        }

        $sessionToken = $_SESSION['csrf_token'] ?? '';

        return hash_equals($sessionToken, $token);
    }

    /**
     * Generate CSRF token input field
     * @return string
     */
    public static function field(): string
    {
        $token = self::generate();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * Generate CSRF meta tag
     * @return string
     */
    public static function meta(): string
    {
        $token = self::generate();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token) . '">';
    }
}
