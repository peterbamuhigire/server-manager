<?php
$pageTitle = 'Search Log: ' . htmlspecialchars($file['name']);
ob_start();
?>

<div class="mb-6">
    <div class="flex items-center gap-2 text-sm text-gray-600 mb-4">
        <a href="/logs" class="hover:text-gray-900">Logs</a>
        <span>/</span>
        <a href="/logs/view?file=<?= base64_encode($file['path']) ?>" class="hover:text-gray-900"><?= htmlspecialchars($file['name']) ?></a>
        <span>/</span>
        <span>Search</span>
    </div>
    <div class="flex justify-between items-start">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Search Results</h1>
            <p class="text-gray-600 mt-1">
                <?= $count ?> matches found for "<?= htmlspecialchars($query) ?>"
                <?= $caseSensitive ? '(case sensitive)' : '(case insensitive)' ?>
            </p>
        </div>
        <a href="/logs/view?file=<?= base64_encode($file['path']) ?>" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
            Back to Log
        </a>
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
            value="<?= htmlspecialchars($query) ?>"
            placeholder="Search in log file..."
            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            required
        >
        <label class="flex items-center gap-2">
            <input type="checkbox" name="case_sensitive" class="rounded" <?= $caseSensitive ? 'checked' : '' ?>>
            <span class="text-sm text-gray-600">Case Sensitive</span>
        </label>
        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            Search
        </button>
    </form>
</div>

<!-- Results -->
<?php if (empty($matches)): ?>
    <div class="bg-white rounded-lg shadow-md p-8 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <h3 class="mt-2 text-lg font-medium text-gray-900">No matches found</h3>
        <p class="mt-1 text-gray-500">Try a different search query</p>
    </div>
<?php else: ?>
    <!-- Results Summary -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="flex items-center">
            <svg class="h-5 w-5 text-blue-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-sm text-blue-700">
                Found <strong><?= number_format($count) ?></strong> matching lines.
                <?php if ($count >= 1000): ?>
                    <strong>Note:</strong> Results limited to first 1000 matches.
                <?php endif; ?>
            </p>
        </div>
    </div>

    <!-- Matches List -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="bg-gray-800 px-4 py-2 flex justify-between items-center">
            <span class="text-sm font-mono text-gray-300">Matching lines</span>
            <button onclick="copyMatches()" class="px-3 py-1 bg-gray-700 text-gray-300 rounded hover:bg-gray-600 text-sm">
                Copy All
            </button>
        </div>
        <div class="divide-y divide-gray-200" style="max-height: 700px; overflow-y: auto;">
            <?php
            $highlightedQuery = $caseSensitive ? $query : null;
            foreach ($matches as $index => $line):
                $lineNumber = $index + 1;

                // Highlight search term
                if ($caseSensitive) {
                    $highlighted = str_replace(
                        $query,
                        '<mark class="bg-yellow-200">' . htmlspecialchars($query) . '</mark>',
                        htmlspecialchars($line)
                    );
                } else {
                    $highlighted = preg_replace(
                        '/(' . preg_quote($query, '/') . ')/i',
                        '<mark class="bg-yellow-200">$1</mark>',
                        htmlspecialchars($line)
                    );
                }
            ?>
                <div class="p-4 hover:bg-gray-50">
                    <div class="flex gap-4">
                        <span class="text-xs text-gray-500 font-mono w-12 flex-shrink-0">#<?= $lineNumber ?></span>
                        <pre class="flex-1 text-sm font-mono text-gray-900 whitespace-pre-wrap break-all"><?= $highlighted ?></pre>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<script>
function copyMatches() {
    const matches = <?= json_encode($matches) ?>;
    const content = matches.join('');
    navigator.clipboard.writeText(content).then(() => {
        SwalUtil.success('Matching lines copied to clipboard!');
    }).catch(err => {
        SwalUtil.error('Failed to copy to clipboard');
        console.error('Failed to copy:', err);
    });
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
