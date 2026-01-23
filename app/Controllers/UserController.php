<?php

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/Role.php';

/**
 * User Management Controller (Root Only)
 * Chwezi Server Manager
 */

class UserController extends Controller
{
    private User $userModel;
    private Role $roleModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->roleModel = new Role();
    }

    /**
     * List all users
     */
    public function index(): void
    {
        $this->requireRoot();

        $users = $this->userModel->allWithRoles();

        $this->view('users.index', [
            'users' => $users
        ]);
    }

    /**
     * Show create user form
     */
    public function create(): void
    {
        $this->requireRoot();

        $roles = $this->roleModel->all();

        $this->view('users.create', [
            'roles' => $roles
        ]);
    }

    /**
     * Store new user
     */
    public function store(): void
    {
        $this->requireRoot();

        // Validate input
        $validation = $this->validate(all_input(), [
            'username' => 'required|username|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'role_id' => 'required|exists:roles,id',
        ]);

        if (!$validation['valid']) {
            $_SESSION['errors'] = $validation['errors'];
            $_SESSION['old'] = all_input();
            $this->back();
            return;
        }

        // Validate password strength
        $password = input('password');
        $passwordValidation = validate_password($password);

        if (!$passwordValidation['valid']) {
            $_SESSION['errors'] = ['password' => $passwordValidation['errors']];
            $_SESSION['old'] = all_input();
            $this->back();
            return;
        }

        // Create user
        $userId = $this->userModel->create([
            'username' => $validation['data']['username'],
            'email' => $validation['data']['email'],
            'password' => $password,
            'role_id' => $validation['data']['role_id'],
            'status' => 'active',
        ]);

        if ($userId) {
            $role = $this->roleModel->find($validation['data']['role_id']);
            log_message("User created: {$validation['data']['username']} (Role: {$role['name']}) by " . $this->user()['username'], 'info');
            log_activity('create_user', 'user', $userId, [
                'username' => $validation['data']['username'],
                'role' => $role['name']
            ]);

            flash('success', 'User created successfully');
            $this->redirect('/users');
        } else {
            flash('error', 'Failed to create user');
            $this->back();
        }
    }

    /**
     * Show edit user form
     */
    public function edit(): void
    {
        $this->requireRoot();

        $userId = (int)input('id');
        $user = $this->userModel->findWithRole($userId);

        if (!$user) {
            flash('error', 'User not found');
            $this->redirect('/users');
            return;
        }

        $roles = $this->roleModel->all();

        $this->view('users.edit', [
            'user' => $user,
            'roles' => $roles
        ]);
    }

    /**
     * Update user
     */
    public function update(): void
    {
        $this->requireRoot();

        $userId = (int)input('id');
        $user = $this->userModel->find($userId);

        if (!$user) {
            flash('error', 'User not found');
            $this->redirect('/users');
            return;
        }

        // Validate input
        $validation = $this->validate(all_input(), [
            'username' => 'required|username',
            'email' => 'required|email',
            'role_id' => 'required|exists:roles,id',
        ]);

        if (!$validation['valid']) {
            $_SESSION['errors'] = $validation['errors'];
            $_SESSION['old'] = all_input();
            $this->back();
            return;
        }

        // Check username uniqueness
        if (!$this->userModel->isUsernameUnique($validation['data']['username'], $userId)) {
            $_SESSION['errors'] = ['username' => ['Username already exists']];
            $_SESSION['old'] = all_input();
            $this->back();
            return;
        }

        // Check email uniqueness
        if (!$this->userModel->isEmailUnique($validation['data']['email'], $userId)) {
            $_SESSION['errors'] = ['email' => ['Email already exists']];
            $_SESSION['old'] = all_input();
            $this->back();
            return;
        }

        // Prepare update data
        $updateData = [
            'username' => $validation['data']['username'],
            'email' => $validation['data']['email'],
            'role_id' => $validation['data']['role_id'],
        ];

        // Update password if provided
        $newPassword = input('password');
        if (!empty($newPassword)) {
            $passwordValidation = validate_password($newPassword);

            if (!$passwordValidation['valid']) {
                $_SESSION['errors'] = ['password' => $passwordValidation['errors']];
                $_SESSION['old'] = all_input();
                $this->back();
                return;
            }

            $updateData['password'] = $newPassword;
        }

        // Prevent removing own root status
        if ($user['id'] === $this->user()['id'] && $user['is_root']) {
            // Don't allow changing own role
            unset($updateData['role_id']);
        }

        // Update user
        if ($this->userModel->update($userId, $updateData)) {
            log_message("User updated: {$validation['data']['username']} by " . $this->user()['username'], 'info');
            log_activity('update_user', 'user', $userId, [
                'username' => $validation['data']['username']
            ]);

            flash('success', 'User updated successfully');
            $this->redirect('/users');
        } else {
            flash('error', 'Failed to update user');
            $this->back();
        }
    }

    /**
     * Delete user
     */
    public function delete(): void
    {
        $this->requireRoot();

        $userId = (int)input('id');
        $user = $this->userModel->find($userId);

        if (!$user) {
            $this->jsonError('User not found', 404);
            return;
        }

        // Prevent deleting self
        if ($user['id'] === $this->user()['id']) {
            $this->jsonError('You cannot delete your own account', 400);
            return;
        }

        // Prevent deleting last root user
        if ($user['is_root']) {
            $rootCount = $this->userModel->countRootUsers();
            if ($rootCount <= 1) {
                $this->jsonError('Cannot delete the last root user', 400);
                return;
            }
        }

        // Delete user
        if ($this->userModel->delete($userId)) {
            log_message("User deleted: {$user['username']} by " . $this->user()['username'], 'warning');
            log_activity('delete_user', 'user', $userId, [
                'username' => $user['username']
            ]);

            $this->jsonSuccess('User deleted successfully');
        } else {
            $this->jsonError('Failed to delete user');
        }
    }

    /**
     * Toggle user status (active/disabled)
     */
    public function toggleStatus(): void
    {
        $this->requireRoot();

        $userId = (int)input('id');
        $user = $this->userModel->find($userId);

        if (!$user) {
            $this->jsonError('User not found', 404);
            return;
        }

        // Prevent disabling self
        if ($user['id'] === $this->user()['id']) {
            $this->jsonError('You cannot disable your own account', 400);
            return;
        }

        // Prevent disabling last root user
        if ($user['is_root'] && $user['status'] === 'active') {
            $rootCount = $this->userModel->countRootUsers();
            if ($rootCount <= 1) {
                $this->jsonError('Cannot disable the last root user', 400);
                return;
            }
        }

        // Toggle status
        if ($this->userModel->toggleStatus($userId)) {
            $newStatus = $user['status'] === 'active' ? 'disabled' : 'active';
            log_message("User status changed: {$user['username']} -> {$newStatus} by " . $this->user()['username'], 'info');
            log_activity('toggle_user_status', 'user', $userId, [
                'username' => $user['username'],
                'new_status' => $newStatus
            ]);

            $this->jsonSuccess('User status updated successfully', ['status' => $newStatus]);
        } else {
            $this->jsonError('Failed to update user status');
        }
    }
}
