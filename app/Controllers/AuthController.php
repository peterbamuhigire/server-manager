<?php

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../Models/User.php';

/**
 * Authentication Controller
 * Chwezi Server Manager
 */

class AuthController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Show login form
     */
    public function showLogin(): void
    {
        // Redirect if already logged in
        if ($this->isAuthenticated()) {
            $this->redirect('/');
        }

        $this->view('auth.login');
    }

    /**
     * Handle login request
     */
    public function login(): void
    {
        // Validate input
        $validation = $this->validate(all_input(), [
            'username' => 'required',
            'password' => 'required',
        ]);

        if (!$validation['valid']) {
            flash('error', 'Please provide both username and password');
            $this->back();
            return;
        }

        $username = $validation['data']['username'];
        $password = input('password');
        $remember = input('remember') === '1';

        // Find user
        $user = $this->userModel->findByUsername($username);

        if (!$user) {
            flash('error', 'Invalid username or password');
            log_message("Failed login attempt for username: {$username}", 'warning');
            $this->back();
            return;
        }

        // Check if user is disabled
        if ($user['status'] !== 'active') {
            flash('error', 'Your account has been disabled. Please contact the administrator.');
            log_message("Disabled user login attempt: {$username}", 'warning');
            $this->back();
            return;
        }

        // Verify password
        if (!verify_password($password, $user['password'])) {
            flash('error', 'Invalid username or password');
            log_message("Failed login attempt for username: {$username}", 'warning');
            $this->back();
            return;
        }

        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);

        // Set session data
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_root'] = (bool)$user['is_root'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['logged_in_at'] = time();

        // Handle remember me
        if ($remember) {
            $token = generate_token(32);
            $this->userModel->setRememberToken($user['id'], $token);

            // Set cookie for 30 days
            setcookie(
                'remember_token',
                $token,
                time() + (86400 * 30),
                '/',
                '',
                config('app.session.secure'),
                true
            );
        }

        // Update last login
        $this->userModel->updateLastLogin($user['id']);

        // Create session record
        $this->createSessionRecord($user['id']);

        // Log successful login
        log_message("User logged in: {$username}", 'info');

        // Redirect to dashboard
        flash('success', 'Welcome back, ' . $user['username'] . '!');
        $this->redirect('/');
    }

    /**
     * Handle logout request
     */
    public function logout(): void
    {
        $username = session('username', 'Unknown');

        // Delete session record
        $this->deleteSessionRecord();

        // Clear remember me cookie
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', config('app.session.secure'), true);
        }

        // Destroy session
        session_destroy();

        // Log logout
        log_message("User logged out: {$username}", 'info');

        // Redirect to login
        flash('success', 'You have been logged out successfully');
        $this->redirect('/login');
    }

    /**
     * Show user profile
     */
    public function showProfile(): void
    {
        $this->requireAuth();

        $user = $this->userModel->findWithRole($this->user()['id']);

        $this->view('auth.profile', [
            'user' => $user
        ]);
    }

    /**
     * Update user password
     */
    public function updatePassword(): void
    {
        $this->requireAuth();

        $validation = $this->validate(all_input(), [
            'current_password' => 'required',
            'new_password' => 'required|min:8',
            'confirm_password' => 'required',
        ]);

        if (!$validation['valid']) {
            flash('error', 'Please fill all required fields');
            $this->back();
            return;
        }

        $userId = $this->user()['id'];
        $user = $this->userModel->find($userId);

        $currentPassword = input('current_password');
        $newPassword = input('new_password');
        $confirmPassword = input('confirm_password');

        // Verify current password
        if (!verify_password($currentPassword, $user['password'])) {
            flash('error', 'Current password is incorrect');
            $this->back();
            return;
        }

        // Check if new passwords match
        if ($newPassword !== $confirmPassword) {
            flash('error', 'New passwords do not match');
            $this->back();
            return;
        }

        // Validate password strength
        $passwordValidation = validate_password($newPassword);
        if (!$passwordValidation['valid']) {
            flash('error', implode('<br>', $passwordValidation['errors']));
            $this->back();
            return;
        }

        // Update password
        $this->userModel->update($userId, [
            'password' => $newPassword
        ]);

        log_message("User changed password: {$user['username']}", 'info');

        flash('success', 'Password updated successfully');
        $this->back();
    }

    /**
     * Create session record in database
     * @param int $userId
     */
    private function createSessionRecord(int $userId): void
    {
        $db = db();
        $sessionId = session_id();
        $ipAddress = get_client_ip();
        $userAgent = get_user_agent();

        $stmt = $db->prepare("
            INSERT INTO sessions (id, user_id, ip_address, user_agent, last_activity)
            VALUES (?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE last_activity = NOW()
        ");

        $stmt->execute([$sessionId, $userId, $ipAddress, $userAgent]);
    }

    /**
     * Delete session record from database
     */
    private function deleteSessionRecord(): void
    {
        $db = db();
        $sessionId = session_id();

        $stmt = $db->prepare("DELETE FROM sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
    }

    /**
     * Check remember me token and auto-login
     */
    public static function checkRememberMe(): void
    {
        if (auth_check()) {
            return;
        }

        if (!isset($_COOKIE['remember_token'])) {
            return;
        }

        $token = $_COOKIE['remember_token'];
        $userModel = new User();
        $user = $userModel->findByRememberToken($token);

        if ($user && $user['status'] === 'active') {
            // Set session data
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_root'] = (bool)$user['is_root'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['logged_in_at'] = time();

            log_message("User auto-logged in via remember token: {$user['username']}", 'info');
        } else {
            // Invalid token, clear cookie
            setcookie('remember_token', '', time() - 3600, '/', '', config('app.session.secure'), true);
        }
    }
}
