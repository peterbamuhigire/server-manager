<?php

/**
 * Authentication Middleware
 * Chwezi Server Manager
 */

class AuthMiddleware
{
    /**
     * Handle authentication check
     */
    public static function handle(): void
    {
        // Check if user is authenticated
        if (!isset($_SESSION['user_id'])) {
            // Store intended URL for redirect after login
            $_SESSION['intended_url'] = current_url();

            flash('error', 'Please login to continue');
            redirect('/login');
            exit;
        }

        // Check session timeout (default 30 minutes)
        $sessionLifetime = config('app.session.lifetime', 30) * 60; // Convert to seconds
        $lastActivity = $_SESSION['logged_in_at'] ?? 0;

        if (time() - $lastActivity > $sessionLifetime) {
            // Session expired
            session_destroy();
            flash('error', 'Your session has expired. Please login again.');
            redirect('/login');
            exit;
        }

        // Update last activity timestamp
        $_SESSION['logged_in_at'] = time();

        // Update session record in database
        self::updateSessionActivity();
    }

    /**
     * Update session activity in database
     */
    private static function updateSessionActivity(): void
    {
        try {
            $db = db();
            $sessionId = session_id();

            $stmt = $db->prepare("UPDATE sessions SET last_activity = NOW() WHERE id = ?");
            $stmt->execute([$sessionId]);
        } catch (Exception $e) {
            // Silently fail - don't interrupt user experience
            log_message("Failed to update session activity: " . $e->getMessage(), 'error');
        }
    }

    /**
     * Clean expired sessions
     */
    public static function cleanExpiredSessions(): void
    {
        try {
            $db = db();
            $sessionLifetime = config('app.session.lifetime', 30);

            $stmt = $db->prepare("
                DELETE FROM sessions
                WHERE last_activity < DATE_SUB(NOW(), INTERVAL ? MINUTE)
            ");

            $stmt->execute([$sessionLifetime]);
        } catch (Exception $e) {
            log_message("Failed to clean expired sessions: " . $e->getMessage(), 'error');
        }
    }
}
