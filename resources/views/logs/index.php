<?php
$pageTitle = 'Log Viewer';
ob_start();
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Log Viewer</h1>
    <p class="text-gray-600 mt-1">View and search system logs (Ubuntu/Debian)</p>
</div>

<?php if (stripos(PHP_OS, 'WIN') === 0): ?>
    <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <div class="flex">
            <svg class="h-5 w-5 text-yellow-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div class="ml-3 text-sm text-yellow-700">
                <p><strong>Development Mode:</strong> Running on Windows. Showing limited logs. Full functionality available on Ubuntu/Debian production servers.</p>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (empty($logs)): ?>
    <div class="bg-white rounded-lg shadow-md p-8 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <h3 class="mt-2 text-lg font-medium text-gray-900">No log files found</h3>
        <p class="mt-1 text-gray-500">No accessible log files in configured directories</p>
    </div>
<?php else: ?>
    <!-- Log Categories Tabs -->
    <div class="mb-6" x-data="{ activeTab: '<?= array_key_first($groupedLogs) ?>' }">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <button @click="activeTab = 'all'" :class="activeTab === 'all' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    All Logs (<?= count($logs) ?>)
                </button>
                <?php foreach ($groupedLogs as $category => $categoryLogs): ?>
                    <button @click="activeTab = '<?= $category ?>'" :class="activeTab === '<?= $category ?>' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                        <?= $categories[$category] ?? ucfirst($category) ?> (<?= count($categoryLogs) ?>)
                    </button>
                <?php endforeach; ?>
            </nav>
        </div>

        <!-- All Logs -->
        <div x-show="activeTab === 'all'" class="mt-6">
            <?php include __DIR__ . '/partials/log-table.php'; $logList = $logs; ?>
            <?php include __DIR__ . '/partials/log-list.php'; ?>
        </div>

        <!-- Category Logs -->
        <?php foreach ($groupedLogs as $category => $categoryLogs): ?>
            <div x-show="activeTab === '<?= $category ?>'" class="mt-6" x-cloak>
                <?php $logList = $categoryLogs; ?>
                <?php include __DIR__ . '/partials/log-list.php'; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Info Panel -->
<div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
    <div class="flex">
        <svg class="h-5 w-5 text-blue-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div class="ml-3 text-sm text-blue-700">
            <p class="font-semibold mb-2">Log Locations on Ubuntu/Debian:</p>
            <ul class="list-disc list-inside space-y-1">
                <li><strong>Apache:</strong> /var/log/apache2/</li>
                <li><strong>Nginx:</strong> /var/log/nginx/</li>
                <li><strong>PHP:</strong> /var/log/php/</li>
                <li><strong>MySQL:</strong> /var/log/mysql/</li>
                <li><strong>Application:</strong> storage/logs/</li>
            </ul>
            <p class="mt-2 text-xs">Note: Some logs may require elevated permissions to access.</p>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
