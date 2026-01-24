<?php
$pageTitle = 'View Log: ' . htmlspecialchars($file['name']);
ob_start();
?>

<div class="mb-6">
    <div class="flex items-center gap-2 text-sm text-gray-600 mb-4">
        <a href="/logs" class="hover:text-gray-900">Logs</a>
        <span>/</span>
        <span><?= htmlspecialchars($file['name']) ?></span>
    </div>
    <div class="flex justify-between items-start">
        <div>
            <h1 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($file['name']) ?></h1>
            <p class="text-gray-600 mt-1"><?= htmlspecialchars($file['path']) ?></p>
        </div>
        <div class="flex gap-2">
            <a href="/logs/tail?file=<?= base64_encode($file['path']) ?>" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                Live Tail
            </a>
            <a href="/logs/download?file=<?= base64_encode($file['path']) ?>" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Download
            </a>
        </div>
    </div>
</div>

<!-- File Info -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow-md p-4">
        <p class="text-sm text-gray-500">File Size</p>
        <p class="text-lg font-semibold text-gray-900"><?= $file['size_formatted'] ?></p>
    </div>
    <div class="bg-white rounded-lg shadow-md p-4">
        <p class="text-sm text-gray-500">Total Lines</p>
        <p class="text-lg font-semibold text-gray-900"><?= number_format($file['lines']) ?></p>
    </div>
    <div class="bg-white rounded-lg shadow-md p-4">
        <p class="text-sm text-gray-500">Last Modified</p>
        <p class="text-lg font-semibold text-gray-900"><?= date('M j, H:i', $file['modified']) ?></p>
    </div>
    <div class="bg-white rounded-lg shadow-md p-4">
        <p class="text-sm text-gray-500">Status</p>
        <p class="text-lg font-semibold <?= $file['readable'] ? 'text-green-600' : 'text-red-600' ?>">
            <?= $file['readable'] ? 'Readable' : 'No Access' ?>
        </p>
    </div>
</div>

<!-- Search Form -->
<div class="bg-white rounded-lg shadow-md p-4 mb-6">
    <form method="POST" action="/logs/search" class="flex gap-4">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="file" value="<?= base64_encode($file['path']) ?>">
        <input
            type="text"
            name="query"
            placeholder="Search in log file..."
            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            required
        >
        <label class="flex items-center gap-2">
            <input type="checkbox" name="case_sensitive" class="rounded">
            <span class="text-sm text-gray-600">Case Sensitive</span>
        </label>
        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            Search
        </button>
    </form>
</div>

<!-- View Options -->
<div class="bg-white rounded-lg shadow-md p-4 mb-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <label class="text-sm text-gray-600">Show last:</label>
            <select onchange="window.location.href='/logs/view?file=<?= base64_encode($file['path']) ?>&lines=' + this.value" class="px-3 py-1 border border-gray-300 rounded">
                <option value="50" <?= $lines == 50 ? 'selected' : '' ?>>50 lines</option>
                <option value="100" <?= $lines == 100 ? 'selected' : '' ?>>100 lines</option>
                <option value="200" <?= $lines == 200 ? 'selected' : '' ?>>200 lines</option>
                <option value="500" <?= $lines == 500 ? 'selected' : '' ?>>500 lines</option>
                <option value="1000" <?= $lines == 1000 ? 'selected' : '' ?>>1000 lines</option>
            </select>
        </div>
        <?php if ($file['writable']): ?>
            <form method="POST" action="/logs/clear" id="clear-log-form" class="inline">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="file" value="<?= base64_encode($file['path']) ?>">
                <button type="button" onclick="confirmClearLog()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm">
                    Clear Log
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- Log Content -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="bg-gray-800 px-4 py-2 flex justify-between items-center">
        <span class="text-sm font-mono text-gray-300">Last <?= $lines ?> lines</span>
        <button onclick="copyContent()" class="px-3 py-1 bg-gray-700 text-gray-300 rounded hover:bg-gray-600 text-sm">
            Copy
        </button>
    </div>
    <div class="p-0">
        <pre id="log-content" class="p-6 bg-gray-900 text-gray-100 text-sm font-mono overflow-x-auto" style="max-height: 600px; overflow-y: auto;"><?= htmlspecialchars($content) ?></pre>
    </div>
</div>

<script>
function copyContent() {
    const content = document.getElementById('log-content').textContent;
    navigator.clipboard.writeText(content).then(() => {
        SwalUtil.success('Log content copied to clipboard!');
    }).catch(err => {
        SwalUtil.error('Failed to copy to clipboard');
        console.error('Failed to copy:', err);
    });
}

function confirmClearLog() {
    SwalUtil.confirm({
        title: 'Clear Log File?',
        text: 'This action cannot be undone. All log entries will be permanently deleted.',
        icon: 'warning',
        confirmText: 'Yes, clear it',
        confirmColor: '#dc2626'
    }).then((result) => {
        if (result.isConfirmed) {
            SwalUtil.loading('Clearing log file...');
            document.getElementById('clear-log-form').submit();
        }
    });
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
