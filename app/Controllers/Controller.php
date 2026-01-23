<?php

/**
 * Base Controller Class
 * Chwezi Server Manager
 */

class Controller
{
    /**
     * Render a view
     * @param string $view
     * @param array $data
     */
    protected function view(string $view, array $data = []): void
    {
        view($view, $data);
    }

    /**
     * Redirect to URL
     * @param string $url
     * @param int $code
     */
    protected function redirect(string $url, int $code = 302): void
    {
        redirect($url, $code);
    }

    /**
     * Redirect back
     */
    protected function back(): void
    {
        back();
    }

    /**
     * Return JSON response
     * @param mixed $data
     * @param int $status
     */
    protected function json($data, int $status = 200): void
    {
        json($data, $status);
    }

    /**
     * Return JSON error
     * @param string $message
     * @param int $status
     */
    protected function jsonError(string $message, int $status = 400): void
    {
        json_error($message, $status);
    }

    /**
     * Return JSON success
     * @param string $message
     * @param mixed $data
     */
    protected function jsonSuccess(string $message, $data = null): void
    {
        json_success($message, $data);
    }

    /**
     * Validate input data
     * @param array $data
     * @param array $rules
     * @return array
     */
    protected function validate(array $data, array $rules): array
    {
        return validate_input($data, $rules);
    }

    /**
     * Get authenticated user
     * @return array|null
     */
    protected function user(): ?array
    {
        return auth_user();
    }

    /**
     * Check if user is authenticated
     * @return bool
     */
    protected function isAuthenticated(): bool
    {
        return auth_check();
    }

    /**
     * Check if user is root
     * @return bool
     */
    protected function isRoot(): bool
    {
        return is_root();
    }

    /**
     * Require authentication
     */
    protected function requireAuth(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('/login');
        }
    }

    /**
     * Require root access
     */
    protected function requireRoot(): void
    {
        if (!$this->isRoot()) {
            http_response_code(403);
            die('Forbidden: Root access required');
        }
    }

    /**
     * Check permission
     * @param string $permission
     * @return bool
     */
    protected function can(string $permission): bool
    {
        if (!$this->isAuthenticated()) {
            return false;
        }

        $user = $this->user();
        $userModel = new User();

        return $userModel->hasPermission($user['id'], $permission);
    }

    /**
     * Require permission
     * @param string $permission
     */
    protected function requirePermission(string $permission): void
    {
        if (!$this->can($permission)) {
            http_response_code(403);
            die('Forbidden: Insufficient permissions');
        }
    }
}
