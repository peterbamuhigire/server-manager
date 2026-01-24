<?php
$pageTitle = 'Cron Job: ' . htmlspecialchars($job['name']);
ob_start();
?>

<div class="mb-6">
    <div class="flex items-center gap-2 text-sm text-gray-600 mb-4">
        <a href="/cron" class="hover:text-gray-900">Cron Jobs</a>
        <span>/</span>
        <span><?= htmlspecialchars($job['name']) ?></span>
    </div>
    <div class="flex justify-between items-start">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                <?= htmlspecialchars($job['name']) ?>
                <?php if ($job['enabled']): ?>
                    <span class="px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800">Enabled</span>
                <?php else: ?>
                    <span class="px-3 py-1 text-sm font-semibold rounded-full bg-gray-100 text-gray-800">Disabled</span>
                <?php endif; ?>
            </h1>
            <?php if ($job['description']): ?>
                <p class="text-gray-600 mt-1"><?= htmlspecialchars($job['description']) ?></p>
            <?php endif; ?>
        </div>
        <div class="flex gap-2">
            <form method="POST" action="/cron/<?= $job['id'] ?>/execute" class="inline">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Run Now
                </button>
            </form>
            <form method="POST" action="/cron/<?= $job['id'] ?>/toggle" class="inline">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                    <?= $job['enabled'] ? 'Disable' : 'Enable' ?>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Job Details Card -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Main Details -->
    <div class="lg:col-span-2 bg-white rounded-lg shadow-md p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Job Configuration</h2>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-500">Command</label>
                <code class="block mt-1 px-3 py-2 bg-gray-50 border border-gray-200 rounded text-sm"><?= htmlspecialchars($job['command']) ?></code>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-500">Schedule</label>
                    <code class="block mt-1 px-3 py-2 bg-gray-50 border border-gray-200 rounded text-sm"><?= htmlspecialchars($job['schedule']) ?></code>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500">Next Run</label>
                    <p class="mt-1 text-sm text-gray-900">
                        <?= $job['next_run_at'] ? date('M j, Y H:i', strtotime($job['next_run_at'])) : 'Not scheduled' ?>
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-500">Last Run</label>
                    <p class="mt-1 text-sm text-gray-900">
                        <?= $job['last_run_at'] ? date('M j, Y H:i:s', strtotime($job['last_run_at'])) : 'Never' ?>
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500">Last Status</label>
                    <div class="mt-1">
                        <?php if ($job['last_run_status'] === 'success'): ?>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Success</span>
                        <?php elseif ($job['last_run_status'] === 'failed'): ?>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Failed</span>
                        <?php elseif ($job['last_run_status'] === 'running'): ?>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Running</span>
                        <?php else: ?>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Never Run</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if ($job['last_run_output']): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-2">Last Output</label>
                    <pre class="p-3 bg-gray-900 text-gray-100 rounded text-xs overflow-x-auto"><?= htmlspecialchars($job['last_run_output']) ?></pre>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stats Sidebar -->
    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Statistics</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Total Executions</span>
                    <span class="text-sm font-semibold text-gray-900"><?= number_format($job['run_count']) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Created By</span>
                    <span class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($job['creator_name'] ?? 'Unknown') ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Created At</span>
                    <span class="text-sm font-semibold text-gray-900"><?= date('M j, Y', strtotime($job['created_at'])) ?></span>
                </div>
            </div>
        </div>

        <!-- Danger Zone -->
        <div class="bg-white rounded-lg shadow-md p-6 border-2 border-red-200">
            <h3 class="text-lg font-semibold text-red-900 mb-4">Danger Zone</h3>
            <form method="POST" action="/cron/<?= $job['id'] ?>/destroy" onsubmit="return confirm('Are you sure you want to delete this cron job? This action cannot be undone.');">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <button type="submit" class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    Delete Cron Job
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Execution Logs -->
<div class="bg-white rounded-lg shadow-md p-6">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Execution History</h2>

    <?php if (empty($logs)): ?>
        <div class="text-center py-8 text-gray-500">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="mt-2">No execution history yet</p>
        </div>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($logs as $log): ?>
                <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                    <div class="flex justify-between items-start mb-2">
                        <div class="flex items-center gap-3">
                            <?php if ($log['status'] === 'success'): ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Success</span>
                            <?php else: ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Failed</span>
                            <?php endif; ?>
                            <span class="text-sm text-gray-600">
                                <?= date('M j, Y H:i:s', strtotime($log['started_at'])) ?>
                            </span>
                            <?php if ($log['execution_time']): ?>
                                <span class="text-sm text-gray-500">
                                    (<?= number_format($log['execution_time'], 3) ?>s)
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($log['output']): ?>
                        <details class="mt-2">
                            <summary class="cursor-pointer text-sm text-blue-600 hover:text-blue-800">View Output</summary>
                            <pre class="mt-2 p-3 bg-gray-900 text-gray-100 rounded text-xs overflow-x-auto"><?= htmlspecialchars($log['output']) ?></pre>
                        </details>
                    <?php endif; ?>

                    <?php if ($log['error_output']): ?>
                        <details class="mt-2">
                            <summary class="cursor-pointer text-sm text-red-600 hover:text-red-800">View Error</summary>
                            <pre class="mt-2 p-3 bg-red-50 text-red-900 rounded text-xs overflow-x-auto"><?= htmlspecialchars($log['error_output']) ?></pre>
                        </details>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
