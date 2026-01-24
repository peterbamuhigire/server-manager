<?php
/**
 * Git Submodules - Show View
 */
ob_start();
?>

<!-- Header -->
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <a href="/submodules" class="text-gray-600 hover:text-gray-900">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-gray-900 flex items-center">
                    <i class="fas fa-code-branch text-blue-600 mr-3"></i>
                    <?= htmlspecialchars($submodule['name']) ?>
                </h1>
                <p class="mt-2 text-gray-600"><?= htmlspecialchars($submodule['description'] ?? 'No description') ?></p>
            </div>
        </div>
        <div class="flex space-x-3">
            <!-- Pull Latest Button -->
            <form method="POST" action="/submodules/<?= $submodule['id'] ?>/update" class="inline">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                    <i class="fas fa-sync-alt mr-2"></i>Pull Latest
                </button>
            </form>
            <!-- Delete Button -->
            <button onclick="confirmDelete()" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">
                <i class="fas fa-trash mr-2"></i>Delete
            </button>
        </div>
    </div>
</div>

<!-- Submodule Details -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Main Info -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Repository Info -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Repository Information</h2>
            </div>
            <div class="p-6">
                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 mb-1">Repository URL</dt>
                        <dd class="text-sm text-gray-900 font-mono bg-gray-50 px-3 py-2 rounded">
                            <?= htmlspecialchars($submodule['repository_url']) ?>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 mb-1">Local Path</dt>
                        <dd class="text-sm text-gray-900 font-mono bg-gray-50 px-3 py-2 rounded">
                            <?= htmlspecialchars($submodule['local_path']) ?>
                        </dd>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 mb-1">Branch</dt>
                            <dd class="text-sm">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    <?= htmlspecialchars($submodule['branch']) ?>
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 mb-1">Status</dt>
                            <dd class="text-sm">
                                <?php
                                $statusColors = [
                                    'active' => 'bg-green-100 text-green-800',
                                    'syncing' => 'bg-yellow-100 text-yellow-800',
                                    'error' => 'bg-red-100 text-red-800'
                                ];
                                $status = $submodule['status'];
                                $colorClass = $statusColors[$status] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $colorClass ?>">
                                    <?= ucfirst($status) ?>
                                </span>
                            </dd>
                        </div>
                    </div>
                    <?php if (!empty($submodule['last_commit_hash'])): ?>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 mb-1">Latest Commit</dt>
                            <dd class="text-sm text-gray-900 font-mono">
                                <?= htmlspecialchars(substr($submodule['last_commit_hash'], 0, 12)) ?>
                            </dd>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($submodule['error_message'])): ?>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 mb-1">Error Message</dt>
                            <dd class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded">
                                <?= htmlspecialchars($submodule['error_message']) ?>
                            </dd>
                        </div>
                    <?php endif; ?>
                </dl>
            </div>
        </div>

        <!-- Linked Applications -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-900">Linked Applications</h2>
                <button onclick="showLinkModal()" class="text-blue-600 hover:text-blue-900 text-sm font-medium">
                    <i class="fas fa-link mr-1"></i>Link to App
                </button>
            </div>

            <?php if (empty($linked_apps)): ?>
                <div class="p-8 text-center">
                    <i class="fas fa-link text-gray-300 text-4xl mb-3"></i>
                    <p class="text-gray-500 mb-4">This submodule is not linked to any applications yet</p>
                    <button onclick="showLinkModal()" class="text-blue-600 hover:text-blue-900 font-medium">
                        <i class="fas fa-link mr-1"></i>Link to Application
                    </button>
                </div>
            <?php else: ?>
                <div class="divide-y divide-gray-200">
                    <?php foreach ($linked_apps as $app): ?>
                        <div class="px-6 py-4 hover:bg-gray-50">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <h3 class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($app['name']) ?>
                                    </h3>
                                    <p class="text-sm text-gray-600 mt-1">
                                        <span class="font-medium">Path:</span>
                                        <code class="bg-gray-100 px-2 py-0.5 rounded text-xs">
                                            <?= htmlspecialchars($app['relative_path']) ?>
                                        </code>
                                    </p>
                                    <div class="mt-2">
                                        <?php if ($app['auto_sync']): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                <i class="fas fa-sync-alt mr-1"></i>Auto-sync enabled
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                                <i class="fas fa-times mr-1"></i>Manual sync only
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <form method="POST" action="/submodules/unlink" class="ml-4" onsubmit="return confirm('Unlink this submodule from <?= htmlspecialchars($app['name']) ?>?')">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                    <input type="hidden" name="submodule_id" value="<?= $submodule['id'] ?>">
                                    <input type="hidden" name="app_id" value="<?= $app['id'] ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-900 text-sm">
                                        <i class="fas fa-unlink"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Stats -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-4">Statistics</h3>
            <dl class="space-y-3">
                <div>
                    <dt class="text-xs text-gray-500">Linked Applications</dt>
                    <dd class="text-2xl font-bold text-gray-900"><?= count($linked_apps) ?></dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Last Synced</dt>
                    <dd class="text-sm text-gray-900">
                        <?php if ($submodule['last_sync_at']): ?>
                            <?= date('M j, Y', strtotime($submodule['last_sync_at'])) ?><br>
                            <span class="text-xs text-gray-500"><?= date('H:i:s', strtotime($submodule['last_sync_at'])) ?></span>
                        <?php else: ?>
                            <span class="text-gray-400">Never</span>
                        <?php endif; ?>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Created</dt>
                    <dd class="text-sm text-gray-900">
                        <?= date('M j, Y', strtotime($submodule['created_at'])) ?>
                    </dd>
                </div>
            </dl>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-4">Quick Actions</h3>
            <div class="space-y-2">
                <button onclick="showLinkModal()" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-lg flex items-center">
                    <i class="fas fa-link text-blue-600 w-6"></i>
                    <span>Link to Application</span>
                </button>
                <form method="POST" action="/submodules/<?= $submodule['id'] ?>/update" class="w-full">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-lg flex items-center">
                        <i class="fas fa-sync-alt text-green-600 w-6"></i>
                        <span>Pull Latest Changes</span>
                    </button>
                </form>
                <button onclick="confirmDelete()" class="w-full text-left px-4 py-2 text-sm text-red-700 hover:bg-red-50 rounded-lg flex items-center">
                    <i class="fas fa-trash text-red-600 w-6"></i>
                    <span>Delete Submodule</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Link to App Modal -->
<div id="linkModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Link to Application</h3>
            <button onclick="hideLinkModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form method="POST" action="/submodules/link">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="submodule_id" value="<?= $submodule['id'] ?>">

            <div class="mb-4">
                <label for="app_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Application <span class="text-red-500">*</span>
                </label>
                <select
                    id="app_id"
                    name="app_id"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    required
                >
                    <option value="">Select an application...</option>
                    <?php foreach ($apps as $app): ?>
                        <option value="<?= $app['id'] ?>"><?= htmlspecialchars($app['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-4">
                <label for="relative_path" class="block text-sm font-medium text-gray-700 mb-2">
                    Relative Path <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="relative_path"
                    name="relative_path"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono text-sm"
                    placeholder="shared/components"
                    required
                >
                <p class="mt-1 text-xs text-gray-500">Path within the app where submodule will be linked</p>
            </div>

            <div class="mb-6">
                <label class="flex items-center">
                    <input type="checkbox" name="auto_sync" value="1" checked class="rounded text-blue-600 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-700">Auto-sync on deployment</span>
                </label>
            </div>

            <div class="flex justify-end space-x-3">
                <button type="button" onclick="hideLinkModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-link mr-2"></i>Link
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Form -->
<form id="deleteForm" method="POST" action="/submodules/<?= $submodule['id'] ?>/delete" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
</form>

<script>
function showLinkModal() {
    document.getElementById('linkModal').classList.remove('hidden');
}

function hideLinkModal() {
    document.getElementById('linkModal').classList.add('hidden');
}

function confirmDelete() {
    if (confirm('Are you sure you want to delete this submodule?\n\nThis will remove the local repository and all database records. Linked applications will need to be manually cleaned up.\n\nThis action cannot be undone.')) {
        document.getElementById('deleteForm').submit();
    }
}

// Close modal when clicking outside
document.getElementById('linkModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        hideLinkModal();
    }
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
