<?php
$pageTitle = 'Create User';
$errors = $_SESSION['errors'] ?? [];
$old = $_SESSION['old'] ?? [];
unset($_SESSION['errors'], $_SESSION['old']);
ob_start();
?>

<div class="max-w-2xl">
    <div class="mb-6">
        <a href="/users" class="text-blue-600 hover:text-blue-800 inline-flex items-center">
            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back to Users
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="/users/create" class="space-y-6">
            <?= CsrfMiddleware::field() ?>

            <!-- Username -->
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                    Username *
                </label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    value="<?= e($old['username'] ?? '') ?>"
                    required
                    class="w-full px-4 py-3 border <?= isset($errors['username']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Enter username (3-50 alphanumeric characters)"
                >
                <?php if (isset($errors['username'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= implode(', ', $errors['username']) ?></p>
                <?php endif; ?>
                <p class="mt-1 text-xs text-gray-500">Must be 3-50 characters, alphanumeric with underscore or hyphen</p>
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                    Email Address *
                </label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= e($old['email'] ?? '') ?>"
                    required
                    class="w-full px-4 py-3 border <?= isset($errors['email']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="user@example.com"
                >
                <?php if (isset($errors['email'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= implode(', ', $errors['email']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                    Password *
                </label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    class="w-full px-4 py-3 border <?= isset($errors['password']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Enter a strong password"
                >
                <?php if (isset($errors['password'])): ?>
                <div class="mt-1 text-sm text-red-600">
                    <?php foreach ($errors['password'] as $error): ?>
                    <p><?= $error ?></p>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <p class="mt-1 text-xs text-gray-500">
                    Password must be at least 8 characters with uppercase, lowercase, and numbers
                </p>
            </div>

            <!-- Role -->
            <div>
                <label for="role_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Role *
                </label>
                <select
                    id="role_id"
                    name="role_id"
                    required
                    class="w-full px-4 py-3 border <?= isset($errors['role_id']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                    <option value="">Select a role</option>
                    <?php foreach ($roles as $role): ?>
                    <option value="<?= $role['id'] ?>" <?= ($old['role_id'] ?? '') == $role['id'] ? 'selected' : '' ?>>
                        <?= e($role['name']) ?>
                        <?php if ($role['description']): ?>
                        - <?= e($role['description']) ?>
                        <?php endif; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['role_id'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= implode(', ', $errors['role_id']) ?></p>
                <?php endif; ?>
                <div class="mt-2 text-xs text-gray-600 space-y-1">
                    <p><strong>Root:</strong> Full system access (cannot be assigned)</p>
                    <p><strong>Admin:</strong> All permissions except user management</p>
                    <p><strong>Developer:</strong> Apps, domains, SSL, databases, git, logs</p>
                    <p><strong>Viewer:</strong> Read-only access</p>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="flex justify-end space-x-3 pt-4">
                <a href="/users" class="px-6 py-3 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 font-medium">
                    Cancel
                </a>
                <button
                    type="submit"
                    class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium"
                >
                    Create User
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
