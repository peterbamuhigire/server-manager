<?php
$pageTitle = $app['name'];
ob_start();
?>

<div class="mb-6">
    <a href="/apps" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
        ← Back to Applications
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Application Details -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-bold text-gray-800">Application Details</h2>
                    <span class="px-3 py-1 text-sm font-medium rounded-full
                        <?php if ($app['status'] === 'active'): ?>bg-green-100 text-green-800
                        <?php elseif ($app['status'] === 'deploying'): ?>bg-yellow-100 text-yellow-800
                        <?php elseif ($app['status'] === 'error'): ?>bg-red-100 text-red-800
                        <?php else: ?>bg-gray-100 text-gray-800<?php endif; ?>">
                        <?= ucfirst($app['status']) ?>
                    </span>
                </div>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-3 gap-4">
                    <div class="font-medium text-gray-700">Name:</div>
                    <div class="col-span-2 text-gray-900"><?= e($app['name']) ?></div>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div class="font-medium text-gray-700">Subdomain:</div>
                    <div class="col-span-2">
                        <?php if ($app['status'] === 'active'): ?>
                            <a href="http://<?= e($app['subdomain']) ?>.localhost" target="_blank" class="text-blue-600 hover:text-blue-800">
                                <?= e($app['subdomain']) ?>.localhost
                                <svg class="inline-block w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                </svg>
                            </a>
                        <?php else: ?>
                            <span class="text-gray-600"><?= e($app['subdomain']) ?>.localhost</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div class="font-medium text-gray-700">Git Repository:</div>
                    <div class="col-span-2">
                        <a href="<?= e($app['git_repo']) ?>" target="_blank" class="text-blue-600 hover:text-blue-800 break-all">
                            <?= e($app['git_repo']) ?>
                        </a>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div class="font-medium text-gray-700">Branch:</div>
                    <div class="col-span-2">
                        <code class="bg-gray-100 px-2 py-1 rounded text-sm"><?= e($app['git_branch']) ?></code>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div class="font-medium text-gray-700">Document Root:</div>
                    <div class="col-span-2">
                        <code class="bg-gray-100 px-2 py-1 rounded text-sm"><?= e($app['document_root'] ?: '/') ?></code>
                    </div>
                </div>
                <?php if ($app['deployed_at']): ?>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="font-medium text-gray-700">Deployed At:</div>
                        <div class="col-span-2 text-gray-900"><?= format_date($app['deployed_at']) ?></div>
                    </div>
                <?php endif; ?>
                <div class="grid grid-cols-3 gap-4">
                    <div class="font-medium text-gray-700">Created At:</div>
                    <div class="col-span-2 text-gray-900"><?= format_date($app['created_at']) ?></div>
                </div>

                <?php if ($app['status'] === 'error' && $app['error_message']): ?>
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <p class="text-sm text-red-800">
                            <strong>Error:</strong> <?= e($app['error_message']) ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Deployment Information -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-800">Deployment Information</h2>
            </div>
            <div class="p-6 space-y-3">
                <div class="grid grid-cols-3 gap-4">
                    <div class="font-medium text-gray-700">Path:</div>
                    <div class="col-span-2">
                        <code class="bg-gray-100 px-2 py-1 rounded text-xs break-all"><?= e($deploymentInfo['path']) ?></code>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div class="font-medium text-gray-700">Deployed:</div>
                    <div class="col-span-2">
                        <span class="px-2 py-1 text-xs font-medium rounded <?= $deploymentInfo['exists'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                            <?= $deploymentInfo['exists'] ? 'Yes' : 'No' ?>
                        </span>
                    </div>
                </div>
                <?php if ($deploymentInfo['exists']): ?>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="font-medium text-gray-700">Git Repository:</div>
                        <div class="col-span-2">
                            <span class="px-2 py-1 text-xs font-medium rounded <?= $deploymentInfo['is_git_repo'] ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                <?= $deploymentInfo['is_git_repo'] ? 'Yes' : 'No' ?>
                            </span>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="font-medium text-gray-700">Composer:</div>
                        <div class="col-span-2">
                            <span class="px-2 py-1 text-xs font-medium rounded <?= $deploymentInfo['has_composer'] ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' ?>">
                                <?= $deploymentInfo['has_composer'] ? 'Yes' : 'No' ?>
                            </span>
                        </div>
                    </div>
                    <?php if (isset($deploymentInfo['current_branch'])): ?>
                        <div class="grid grid-cols-3 gap-4">
                            <div class="font-medium text-gray-700">Current Branch:</div>
                            <div class="col-span-2">
                                <code class="bg-gray-100 px-2 py-1 rounded text-sm"><?= e($deploymentInfo['current_branch']) ?></code>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($deploymentInfo['last_commit'])): ?>
                        <div class="grid grid-cols-3 gap-4">
                            <div class="font-medium text-gray-700">Last Commit:</div>
                            <div class="col-span-2">
                                <code class="bg-gray-100 px-2 py-1 rounded text-xs"><?= e($deploymentInfo['last_commit']) ?></code>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Sidebar Actions -->
    <div class="space-y-6">
        <!-- Actions Card -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-800">Actions</h3>
            </div>
            <div class="p-6 space-y-3">
                <?php if ($app['status'] === 'pending' || $app['status'] === 'error'): ?>
                    <button
                        onclick="deployApp(<?= $app['id'] ?>)"
                        class="w-full bg-green-600 hover:bg-green-700 text-white py-3 px-4 rounded-lg font-medium transition">
                        <svg class="inline-block w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        Deploy Application
                    </button>
                <?php endif; ?>

                <?php if ($app['status'] === 'active'): ?>
                    <button
                        onclick="redeployApp(<?= $app['id'] ?>)"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 px-4 rounded-lg font-medium transition">
                        <svg class="inline-block w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Redeploy (Pull Latest)
                    </button>
                <?php endif; ?>

                <div class="border-t border-gray-200 my-3"></div>

                <button
                    onclick="deleteApp(<?= $app['id'] ?>)"
                    class="w-full bg-red-600 hover:bg-red-700 text-white py-3 px-4 rounded-lg font-medium transition">
                    <svg class="inline-block w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Delete Application
                </button>
            </div>
        </div>

        <!-- Quick Links Card -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-800">Quick Links</h3>
            </div>
            <div class="p-6 space-y-3">
                <?php if ($app['status'] === 'active'): ?>
                    <a
                        href="http://<?= e($app['subdomain']) ?>.localhost"
                        target="_blank"
                        class="block w-full bg-blue-600 hover:bg-blue-700 text-white py-3 px-4 rounded-lg font-medium text-center transition">
                        <svg class="inline-block w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                        </svg>
                        Open Application
                    </a>
                <?php endif; ?>
                <a
                    href="<?= e($app['git_repo']) ?>"
                    target="_blank"
                    class="block w-full bg-gray-700 hover:bg-gray-800 text-white py-3 px-4 rounded-lg font-medium text-center transition">
                    <svg class="inline-block w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                    </svg>
                    View Repository
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function deployApp(id) {
    SwalUtil.confirm({
        title: 'Deploy Application?',
        text: 'This will clone the repository and configure Apache.',
        icon: 'question',
        confirmText: 'Yes, deploy',
        confirmColor: '#10b981'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Deploying...',
                html: 'Please wait while the application is being deployed.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => { Swal.showLoading(); }
            });

            fetch(`/apps/${id}/deploy`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deployed!',
                        text: 'Application has been deployed successfully.',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => window.location.reload());
                } else {
                    SwalUtil.error('Deployment failed: ' + data.message);
                }
            })
            .catch(error => {
                SwalUtil.error('An error occurred: ' + error.message);
            });
        }
    });
}

function redeployApp(id) {
    SwalUtil.confirm({
        title: 'Redeploy Application?',
        text: 'This will pull the latest changes from the repository.',
        icon: 'question',
        confirmText: 'Yes, redeploy',
        confirmColor: '#3b82f6'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Redeploying...',
                html: 'Please wait while the application is being updated.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => { Swal.showLoading(); }
            });

            fetch(`/apps/${id}/redeploy`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Redeployed!',
                        text: 'Application has been updated successfully.',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => window.location.reload());
                } else {
                    SwalUtil.error('Redeploy failed: ' + data.message);
                }
            })
            .catch(error => {
                SwalUtil.error('An error occurred: ' + error.message);
            });
        }
    });
}

function deleteApp(id) {
    SwalUtil.confirmDelete('application').then((result) => {
        if (result.isConfirmed) {
            SwalUtil.loading('Deleting application...');

            fetch(`/apps/${id}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: 'Application has been deleted.',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => window.location.href = '/apps');
                } else {
                    SwalUtil.error('Delete failed: ' + data.message);
                }
            })
            .catch(error => {
                SwalUtil.error('An error occurred: ' + error.message);
            });
        }
    });
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
