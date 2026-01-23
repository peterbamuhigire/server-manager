<?php
$pageTitle = 'Edit User';
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
        <?php if ($user['is_root'] && $user['id'] == auth_id()): ?>
        <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <p class="text-sm text-yellow-800">
                <strong>Note:</strong> You cannot change your own root status or role.
            </p>
        </div>
        <?php endif; ?>

        <form method="POST" action="/users/update" class="space-y-6">
            <?= CsrfMiddleware::field() ?>
            <input type="hidden" name="id" value="<?= $user['id'] ?>">

            <!-- Username -->
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                    Username *
                </label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    value="<?= e($old['username'] ?? $user['username']) ?>"
                    required
                    class="w-full px-4 py-3 border <?= isset($errors['username']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                <?php if (isset($errors['username'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= implode(', ', $errors['username']) ?></p>
                <?php endif; ?>
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
                    value="<?= e($old['email'] ?? $user['email']) ?>"
                    required
                    class="w-full px-4 py-3 border <?= isset($errors['email']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                <?php if (isset($errors['email'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= implode(', ', $errors['email']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                    New Password (leave blank to keep current)
                </label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="w-full px-4 py-3 border <?= isset($errors['password']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Enter new password to change"
                >
                <?php if (isset($errors['password'])): ?>
                <div class="mt-1 text-sm text-red-600">
                    <?php foreach ($errors['password'] as $error): ?>
                    <p><?= $error ?></p>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
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
                    <?= ($user['is_root'] && $user['id'] == auth_id()) ? 'disabled' : '' ?>
                    class="w-full px-4 py-3 border <?= isset($errors['role_id']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent <?= ($user['is_root'] && $user['id'] == auth_id()) ? 'bg-gray-100' : '' ?>"
                >
                    <?php foreach ($roles as $role): ?>
                    <option value="<?= $role['id'] ?>" <?= ($old['role_id'] ?? $user['role_id']) == $role['id'] ? 'selected' : '' ?>>
                        <?= e($role['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['role_id'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= implode(', ', $errors['role_id']) ?></p>
                <?php endif; ?>
            </div>

            <!-- User Info -->
            <div class="bg-gray-50 rounded-lg p-4 space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600">Created:</span>
                    <span class="font-medium"><?= format_date($user['created_at']) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Last Updated:</span>
                    <span class="font-medium"><?= format_date($user['updated_at']) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Last Login:</span>
                    <span class="font-medium"><?= $user['last_login'] ? format_date($user['last_login']) : 'Never' ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Status:</span>
                    <span class="font-medium <?= $user['status'] === 'active' ? 'text-green-600' : 'text-red-600' ?>">
                        <?= ucfirst($user['status']) ?>
                    </span>
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
                    Update User
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
