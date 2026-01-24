<?php
$pageTitle = 'Command History';
ob_start();
?>

<div class="mb-6">
    <div class="flex items-center gap-2 text-sm text-gray-600 mb-4">
        <a href="/terminal" class="hover:text-gray-900">Terminal</a>
        <span>/</span>
        <span>History</span>
    </div>
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Command History</h1>
            <p class="text-gray-600 mt-1">Your terminal command execution history</p>
        </div>
        <div class="flex gap-2">
            <form method="POST" action="/terminal/clear" onsubmit="return confirm('Are you sure you want to clear your command history? This action cannot be undone.');">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    Clear History
                </button>
            </form>
            <a href="/terminal" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Back to Terminal
            </a>
        </div>
    </div>
</div>

<!-- Statistics -->
<?php if ($stats && $stats['total_commands'] > 0): ?>
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow-md p-4">
        <p class="text-sm text-gray-500">Total Commands</p>
        <p class="text-2xl font-semibold text-gray-900"><?= number_format($stats['total_commands']) ?></p>
    </div>
    <div class="bg-white rounded-lg shadow-md p-4">
        <p class="text-sm text-gray-500">Successful</p>
        <p class="text-2xl font-semibold text-green-600"><?= number_format($stats['successful']) ?></p>
    </div>
    <div class="bg-white rounded-lg shadow-md p-4">
        <p class="text-sm text-gray-500">Failed</p>
        <p class="text-2xl font-semibold text-red-600"><?= number_format($stats['failed']) ?></p>
    </div>
    <div class="bg-white rounded-lg shadow-md p-4">
        <p class="text-sm text-gray-500">Last Command</p>
        <p class="text-sm font-semibold text-gray-900"><?= $stats['last_command_at'] ? date('M j, H:i', strtotime($stats['last_command_at'])) : 'Never' ?></p>
    </div>
</div>
<?php endif; ?>

<!-- Command History -->
<?php if (empty($history)): ?>
    <div class="bg-white rounded-lg shadow-md p-8 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <h3 class="mt-2 text-lg font-medium text-gray-900">No command history</h3>
        <p class="mt-1 text-gray-500">Commands you execute will appear here</p>
        <div class="mt-6">
            <a href="/terminal" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Go to Terminal
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Command</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Exit Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Execution Time</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Executed At</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($history as $item): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <code class="text-sm bg-gray-100 px-2 py-1 rounded text-gray-900"><?= htmlspecialchars($item['command']) ?></code>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($item['status'] === 'completed'): ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Completed</span>
                            <?php elseif ($item['status'] === 'failed'): ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Failed</span>
                            <?php elseif ($item['status'] === 'timeout'): ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Timeout</span>
                            <?php else: ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Running</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-sm <?= $item['exit_code'] == 0 ? 'text-green-600' : 'text-red-600' ?>">
                            <?= $item['exit_code'] !== null ? $item['exit_code'] : '-' ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            <?= $item['execution_time'] !== null ? number_format($item['execution_time'], 3) . 's' : '-' ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            <?= date('M j, Y H:i:s', strtotime($item['started_at'])) ?>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button
                                onclick="showOutput(<?= $item['id'] ?>)"
                                class="text-blue-600 hover:text-blue-900 text-sm"
                            >
                                View Output
                            </button>
                        </td>
                    </tr>
                    <tr id="output-<?= $item['id'] ?>" class="hidden bg-gray-50">
                        <td colspan="6" class="px-6 py-4">
                            <div class="bg-gray-900 p-4 rounded">
                                <?php if ($item['output']): ?>
                                    <pre class="text-gray-100 text-sm font-mono whitespace-pre-wrap"><?= htmlspecialchars($item['output']) ?></pre>
                                <?php elseif ($item['error_output']): ?>
                                    <pre class="text-red-400 text-sm font-mono whitespace-pre-wrap"><?= htmlspecialchars($item['error_output']) ?></pre>
                                <?php else: ?>
                                    <p class="text-gray-500 text-sm">(no output)</p>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<script>
function showOutput(id) {
    const row = document.getElementById('output-' + id);
    row.classList.toggle('hidden');
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
