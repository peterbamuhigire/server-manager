<?php
$pageTitle = 'Tail Log: ' . htmlspecialchars($file['name']);
ob_start();
?>

<div class="mb-6">
    <div class="flex items-center gap-2 text-sm text-gray-600 mb-4">
        <a href="/logs" class="hover:text-gray-900">Logs</a>
        <span>/</span>
        <a href="/logs/view?file=<?= base64_encode($file['path']) ?>" class="hover:text-gray-900"><?= htmlspecialchars($file['name']) ?></a>
        <span>/</span>
        <span>Tail</span>
    </div>
    <div class="flex justify-between items-start">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                <?= htmlspecialchars($file['name']) ?>
                <span id="live-indicator" class="px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800">
                    <span class="inline-block w-2 h-2 bg-green-500 rounded-full mr-1 animate-pulse"></span>
                    Live
                </span>
            </h1>
            <p class="text-gray-600 mt-1">Real-time log monitoring</p>
        </div>
        <div class="flex gap-2">
            <button onclick="toggleAutoRefresh()" id="toggle-btn" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Pause
            </button>
            <a href="/logs/view?file=<?= base64_encode($file['path']) ?>" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                Static View
            </a>
        </div>
    </div>
</div>

<!-- Controls -->
<div class="bg-white rounded-lg shadow-md p-4 mb-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <label class="text-sm text-gray-600">Show lines:</label>
            <select id="lines-select" class="px-3 py-1 border border-gray-300 rounded">
                <option value="25">25</option>
                <option value="50" selected>50</option>
                <option value="100">100</option>
                <option value="200">200</option>
            </select>
            <label class="text-sm text-gray-600 ml-4">Refresh interval:</label>
            <select id="interval-select" class="px-3 py-1 border border-gray-300 rounded">
                <option value="1000">1 second</option>
                <option value="2000" selected>2 seconds</option>
                <option value="5000">5 seconds</option>
                <option value="10000">10 seconds</option>
            </select>
        </div>
        <div class="text-sm text-gray-600">
            Last update: <span id="last-update">Never</span>
        </div>
    </div>
</div>

<!-- Log Content -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="bg-gray-800 px-4 py-2">
        <span class="text-sm font-mono text-gray-300">Live log output</span>
    </div>
    <div class="p-0">
        <pre id="log-content" class="p-6 bg-gray-900 text-gray-100 text-sm font-mono overflow-x-auto" style="max-height: 600px; overflow-y: auto;">Loading...</pre>
    </div>
</div>

<script>
let autoRefresh = true;
let refreshInterval = 2000;
let refreshTimer = null;
let lines = 50;

const filepath = '<?= base64_encode($file['path']) ?>';

function toggleAutoRefresh() {
    autoRefresh = !autoRefresh;
    const btn = document.getElementById('toggle-btn');
    const indicator = document.getElementById('live-indicator');

    if (autoRefresh) {
        btn.textContent = 'Pause';
        btn.className = 'px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700';
        indicator.innerHTML = '<span class="inline-block w-2 h-2 bg-green-500 rounded-full mr-1 animate-pulse"></span> Live';
        indicator.className = 'px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800';
        startAutoRefresh();
    } else {
        btn.textContent = 'Resume';
        btn.className = 'px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700';
        indicator.innerHTML = '<span class="inline-block w-2 h-2 bg-gray-500 rounded-full mr-1"></span> Paused';
        indicator.className = 'px-3 py-1 text-sm font-semibold rounded-full bg-gray-100 text-gray-800';
        stopAutoRefresh();
    }
}

function startAutoRefresh() {
    if (refreshTimer) {
        clearInterval(refreshTimer);
    }
    refreshTimer = setInterval(loadLogContent, refreshInterval);
    loadLogContent(); // Load immediately
}

function stopAutoRefresh() {
    if (refreshTimer) {
        clearInterval(refreshTimer);
        refreshTimer = null;
    }
}

function loadLogContent() {
    fetch(`/logs/tail?file=${filepath}&ajax=1&lines=${lines}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const contentEl = document.getElementById('log-content');
                const wasAtBottom = contentEl.scrollHeight - contentEl.scrollTop === contentEl.clientHeight;

                // Use innerHTML since content is already escaped
                contentEl.textContent = '';
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = data.content;
                contentEl.textContent = tempDiv.textContent;

                // Auto-scroll to bottom if user was already at bottom
                if (wasAtBottom || contentEl.scrollTop === 0) {
                    contentEl.scrollTop = contentEl.scrollHeight;
                }

                document.getElementById('last-update').textContent = new Date().toLocaleTimeString();
            }
        })
        .catch(error => {
            console.error('Error loading log:', error);
        });
}

// Event listeners
document.getElementById('lines-select').addEventListener('change', function() {
    lines = parseInt(this.value);
    if (autoRefresh) {
        loadLogContent();
    }
});

document.getElementById('interval-select').addEventListener('change', function() {
    refreshInterval = parseInt(this.value);
    if (autoRefresh) {
        stopAutoRefresh();
        startAutoRefresh();
    }
});

// Start auto-refresh on load
startAutoRefresh();

// Stop refresh when page is hidden
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        stopAutoRefresh();
    } else if (autoRefresh) {
        startAutoRefresh();
    }
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
