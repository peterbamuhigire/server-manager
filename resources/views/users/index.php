<?php
$pageTitle = 'User Management';
ob_start();
?>

<div class="mb-6 flex justify-between items-center">
    <div>
        <p class="text-gray-600">Manage system users and their roles</p>
    </div>
    <a href="/users/create" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium inline-flex items-center transition">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        Create User
    </a>
</div>

<!-- Users Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Login</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php foreach ($users as $user): ?>
            <tr class="hover:bg-gray-50" x-data="{ showDeleteConfirm: false }">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10 bg-blue-600 rounded-full flex items-center justify-center">
                            <span class="text-white font-bold"><?= strtoupper(substr($user['username'], 0, 1)) ?></span>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900">
                                <?= e($user['username']) ?>
                                <?php if ($user['is_root']): ?>
                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                    Root
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900"><?= e($user['email']) ?></div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
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
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?= $user['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                        <?= ucfirst($user['status']) ?>
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <?= $user['last_login'] ? format_date($user['last_login']) : 'Never' ?>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                    <a href="/users/edit?id=<?= $user['id'] ?>" class="text-blue-600 hover:text-blue-900">Edit</a>

                    <?php if ($user['id'] != auth_id()): ?>
                    <button
                        onclick="toggleUserStatus(<?= $user['id'] ?>, '<?= $user['status'] ?>')"
                        class="text-yellow-600 hover:text-yellow-900"
                    >
                        <?= $user['status'] === 'active' ? 'Disable' : 'Enable' ?>
                    </button>

                    <?php if (!$user['is_root'] || (new User())->countRootUsers() > 1): ?>
                    <button
                        @click="showDeleteConfirm = true"
                        class="text-red-600 hover:text-red-900"
                    >
                        Delete
                    </button>

                    <!-- Delete Confirmation Modal -->
                    <div x-show="showDeleteConfirm" x-cloak class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                        <div class="bg-white rounded-lg p-6 max-w-md mx-4">
                            <h3 class="text-lg font-bold text-gray-900 mb-2">Delete User</h3>
                            <p class="text-gray-600 mb-4">
                                Are you sure you want to delete <strong><?= e($user['username']) ?></strong>?
                                This action cannot be undone.
                            </p>
                            <div class="flex justify-end space-x-3">
                                <button
                                    @click="showDeleteConfirm = false"
                                    class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300"
                                >
                                    Cancel
                                </button>
                                <button
                                    onclick="deleteUser(<?= $user['id'] ?>)"
                                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>

            <?php if (empty($users)): ?>
            <tr>
                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                    No users found.
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function toggleUserStatus(userId, currentStatus) {
    if (!confirm(`Are you sure you want to ${currentStatus === 'active' ? 'disable' : 'enable'} this user?`)) {
        return;
    }

    const formData = new FormData();
    formData.append('id', userId);
    formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);

    fetch('/users/toggle-status', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Failed to update user status');
        }
    })
    .catch(error => {
        alert('An error occurred');
        console.error(error);
    });
}

function deleteUser(userId) {
    const formData = new FormData();
    formData.append('id', userId);
    formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);

    fetch('/users/delete', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Failed to delete user');
        }
    })
    .catch(error => {
        alert('An error occurred');
        console.error(error);
    });
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
