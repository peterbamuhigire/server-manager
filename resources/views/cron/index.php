<?php
$pageTitle = 'Cron Jobs';
ob_start();
?>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Cron Jobs</h1>
        <p class="text-gray-600 mt-1">Manage scheduled tasks on your server</p>
    </div>
    <div class="flex gap-3">
        <a href="/cron/crontab" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
            View System Crontab
        </a>
        <a href="/cron/create" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            Create Cron Job
        </a>
    </div>
</div>

<?php if (empty($jobs)): ?>
    <div class="bg-white rounded-lg shadow-md p-8 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <h3 class="mt-2 text-lg font-medium text-gray-900">No cron jobs</h3>
        <p class="mt-1 text-gray-500">Get started by creating a new scheduled task</p>
        <div class="mt-6">
            <a href="/cron/create" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Create Cron Job
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Schedule</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Command</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Run</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Next Run</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($jobs as $job): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <?php if ($job['enabled']): ?>
                                    <span class="h-2 w-2 bg-green-500 rounded-full mr-2"></span>
                                <?php else: ?>
                                    <span class="h-2 w-2 bg-gray-400 rounded-full mr-2"></span>
                                <?php endif; ?>
                                <a href="/cron/<?= $job['id'] ?>" class="text-blue-600 hover:underline font-medium">
                                    <?= htmlspecialchars($job['name']) ?>
                                </a>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <code class="text-sm bg-gray-100 px-2 py-1 rounded"><?= htmlspecialchars($job['schedule']) ?></code>
                        </td>
                        <td class="px-6 py-4">
                            <code class="text-sm text-gray-600 truncate block max-w-xs"><?= htmlspecialchars($job['command']) ?></code>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($job['last_run_status'] === 'success'): ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Success</span>
                            <?php elseif ($job['last_run_status'] === 'failed'): ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Failed</span>
                            <?php elseif ($job['last_run_status'] === 'running'): ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Running</span>
                            <?php else: ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Never Run</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            <?= $job['last_run_at'] ? date('M j, Y H:i', strtotime($job['last_run_at'])) : '-' ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            <?= $job['next_run_at'] ? date('M j, Y H:i', strtotime($job['next_run_at'])) : '-' ?>
                        </td>
                        <td class="px-6 py-4 text-right text-sm">
                            <a href="/cron/<?= $job['id'] ?>" class="text-blue-600 hover:text-blue-900 mr-3">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex">
            <svg class="h-5 w-5 text-blue-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="ml-3">
                <p class="text-sm text-blue-700">
                    <strong>Ubuntu/Debian System:</strong> Cron jobs are synced to the system crontab for the www-data user.
                    Ensure your server has proper sudo permissions configured for crontab management.
                </p>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
