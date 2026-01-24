<?php
$pageTitle = 'Database Details';
ob_start();
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <a href="/databases" class="text-sm text-indigo-600 hover:text-indigo-800">
            &larr; Back to Databases
        </a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="mb-4 bg-green-50 border-l-4 border-green-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-700"><?= htmlspecialchars($_SESSION['success']) ?></p>
                </div>
            </div>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="mb-4 bg-red-50 border-l-4 border-red-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700"><?= htmlspecialchars($_SESSION['error']) ?></p>
                </div>
            </div>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if ($newCredentials): ?>
        <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <h3 class="text-sm font-medium text-yellow-800">Save These Credentials!</h3>
                    <p class="mt-2 text-sm text-yellow-700">This is the only time these credentials will be displayed. Save them now.</p>
                    <div class="mt-3 bg-white p-3 rounded font-mono text-xs">
                        <div><strong>Database:</strong> <?= htmlspecialchars($newCredentials['database']) ?></div>
                        <div><strong>Username:</strong> <?= htmlspecialchars($newCredentials['username']) ?></div>
                        <div><strong>Password:</strong> <?= htmlspecialchars($newCredentials['password']) ?></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
        <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
            <div>
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Database Information
                </h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">
                    <?= htmlspecialchars($database['database_name']) ?>
                </p>
            </div>
            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                <?= ucfirst($database['status']) ?>
            </span>
        </div>
        <div class="border-t border-gray-200 px-4 py-5 sm:p-0">
            <dl class="sm:divide-y sm:divide-gray-200">
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Database Name</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 font-mono">
                        <?= htmlspecialchars($database['database_name']) ?>
                    </dd>
                </div>
                <?php if ($database['database_user']): ?>
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Database User</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 font-mono">
                            <?= htmlspecialchars($database['database_user']) ?>
                        </dd>
                    </div>
                <?php endif; ?>
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Size</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <?= $database['size_formatted'] ?>
                    </dd>
                </div>
                <?php if ($database['description']): ?>
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Description</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <?= htmlspecialchars($database['description']) ?>
                        </dd>
                    </div>
                <?php endif; ?>
                <?php if (isset($database['app'])): ?>
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Linked Application</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <a href="/apps/<?= $database['app']['id'] ?>" class="text-indigo-600 hover:text-indigo-800">
                                <?= htmlspecialchars($database['app']['name']) ?>
                            </a>
                        </dd>
                    </div>
                <?php endif; ?>
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Backup Settings</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <?php if ($database['backup_enabled']): ?>
                            Enabled (<?= ucfirst($database['backup_frequency']) ?>)
                        <?php else: ?>
                            Disabled
                        <?php endif; ?>
                    </dd>
                </div>
                <?php if ($database['last_backup_at']): ?>
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Last Backup</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <?= date('F j, Y g:i A', strtotime($database['last_backup_at'])) ?>
                        </dd>
                    </div>
                <?php endif; ?>
            </dl>
        </div>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
        <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Backups
            </h3>
            <button type="button" onclick="createBackup(<?= $database['id'] ?>)"
                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="-ml-0.5 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                </svg>
                Create Backup
            </button>
        </div>
        <div class="border-t border-gray-200">
            <?php if (empty($backups)): ?>
                <div class="text-center py-8">
                    <p class="text-sm text-gray-500">No backups yet</p>
                </div>
            <?php else: ?>
                <ul class="divide-y divide-gray-200">
                    <?php foreach ($backups as $backup): ?>
                        <li class="px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900">
                                        <?= date('F j, Y g:i A', strtotime($backup['created_at'])) ?>
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        <?= $backup['size_formatted'] ?> • <?= ucfirst($backup['backup_type']) ?>
                                    </p>
                                </div>
                                <?php if ($backup['status'] === 'completed'): ?>
                                    <form method="POST" action="/databases/restore" class="inline" id="restore-form-<?= $backup['id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <input type="hidden" name="backup_id" value="<?= $backup['id'] ?>">
                                        <input type="hidden" name="database_id" value="<?= $database['id'] ?>">
                                        <button type="button"
                                                onclick="confirmRestore(<?= $backup['id'] ?>)"
                                                class="inline-flex items-center px-3 py-1 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                            Restore
                                        </button>
                                    </form>
                                <?php elseif ($backup['status'] === 'failed'): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        Failed
                                    </span>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <div class="flex justify-end">
        <form method="POST" action="/databases/<?= $database['id'] ?>/delete" id="delete-db-form">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <button type="button"
                    onclick="confirmDeleteDatabase()"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Delete Database
            </button>
        </form>
    </div>
</div>

<script>
const csrfToken = '<?= $_SESSION['csrf_token'] ?>';

function createBackup(databaseId) {
    SwalUtil.confirm({
        title: 'Create Backup?',
        text: 'This will create a new backup of the database.',
        icon: 'question',
        confirmText: 'Yes, create backup',
        confirmColor: '#4f46e5'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Creating Backup...',
                html: 'Please wait while the database is being backed up.<br><small>This may take a while for large databases.</small>',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => { Swal.showLoading(); }
            });

            fetch(`/databases/${databaseId}/backup`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `csrf_token=${encodeURIComponent(csrfToken)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Backup Created!',
                        text: data.message,
                        confirmButtonColor: '#4f46e5'
                    }).then(() => location.reload());
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Backup Failed',
                        html: `<pre style="text-align: left; font-size: 12px; max-height: 300px; overflow-y: auto; background: #f3f4f6; padding: 12px; border-radius: 6px; white-space: pre-wrap; word-wrap: break-word;">${escapeHtml(data.message)}</pre>`,
                        confirmButtonColor: '#dc2626',
                        width: '600px'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Request Failed',
                    text: 'An error occurred while creating the backup: ' + error.message,
                    confirmButtonColor: '#dc2626'
                });
            });
        }
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function confirmRestore(backupId) {
    SwalUtil.confirm({
        title: 'Restore Database?',
        text: 'This will overwrite the current database with the backup data. This action cannot be undone.',
        icon: 'warning',
        confirmText: 'Yes, restore',
        confirmColor: '#f59e0b'
    }).then((result) => {
        if (result.isConfirmed) {
            SwalUtil.loading('Restoring database...');
            document.getElementById('restore-form-' + backupId).submit();
        }
    });
}

function confirmDeleteDatabase() {
    SwalUtil.confirm({
        title: 'Delete Database?',
        text: 'This action cannot be undone and will permanently delete all data!',
        icon: 'error',
        confirmText: 'Yes, delete permanently',
        confirmColor: '#dc2626'
    }).then((result) => {
        if (result.isConfirmed) {
            SwalUtil.loading('Deleting database...');
            document.getElementById('delete-db-form').submit();
        }
    });
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
