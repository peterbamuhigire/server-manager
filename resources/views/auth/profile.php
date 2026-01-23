<?php
$pageTitle = 'My Profile';
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['errors']);
ob_start();
?>

<div class="max-w-2xl">
    <!-- Profile Info -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Profile Information</h3>
        <div class="space-y-4">
            <div>
                <label class="block text-sm text-gray-600 mb-1">Username</label>
                <p class="text-lg font-medium text-gray-800"><?= e($user['username']) ?></p>
            </div>
            <div>
                <label class="block text-sm text-gray-600 mb-1">Email</label>
                <p class="text-lg font-medium text-gray-800"><?= e($user['email']) ?></p>
            </div>
            <div>
                <label class="block text-sm text-gray-600 mb-1">Role</label>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php
                    echo match($user['role_slug'] ?? 'viewer') {
                        'root' => 'bg-red-100 text-red-800',
                        'admin' => 'bg-purple-100 text-purple-800',
                        'developer' => 'bg-blue-100 text-blue-800',
                        default => 'bg-gray-100 text-gray-800'
                    };
                ?>">
                    <?= e($user['role_name'] ?? 'Unknown') ?>
                </span>
            </div>
            <div>
                <label class="block text-sm text-gray-600 mb-1">Last Login</label>
                <p class="text-gray-800"><?= $user['last_login'] ? format_date($user['last_login']) : 'Never' ?></p>
            </div>
            <div>
                <label class="block text-sm text-gray-600 mb-1">Member Since</label>
                <p class="text-gray-800"><?= format_date($user['created_at']) ?></p>
            </div>
        </div>
    </div>

    <!-- Change Password -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Change Password</h3>
        <form method="POST" action="/profile/password" class="space-y-4">
            <?= CsrfMiddleware::field() ?>

            <div>
                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">
                    Current Password *
                </label>
                <input
                    type="password"
                    id="current_password"
                    name="current_password"
                    required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
            </div>

            <div>
                <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">
                    New Password *
                </label>
                <input
                    type="password"
                    id="new_password"
                    name="new_password"
                    required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                <p class="mt-1 text-xs text-gray-500">
                    Must be at least 8 characters with uppercase, lowercase, and numbers
                </p>
            </div>

            <div>
                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                    Confirm New Password *
                </label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
            </div>

            <div class="flex justify-end pt-2">
                <button
                    type="submit"
                    class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium"
                >
                    Update Password
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
