<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">File Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Size</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Modified</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php foreach ($logList as $log): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <svg class="h-5 w-5 text-gray-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span class="font-mono text-sm text-gray-900"><?= htmlspecialchars($log['name']) ?></span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full
                            <?php
                            switch($log['category']) {
                                case 'apache':
                                case 'nginx':
                                    echo 'bg-blue-100 text-blue-800';
                                    break;
                                case 'php':
                                    echo 'bg-purple-100 text-purple-800';
                                    break;
                                case 'mysql':
                                    echo 'bg-orange-100 text-orange-800';
                                    break;
                                case 'application':
                                    echo 'bg-green-100 text-green-800';
                                    break;
                                default:
                                    echo 'bg-gray-100 text-gray-800';
                            }
                            ?>">
                            <?= ucfirst($log['category']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        <?php
                        $size = $log['size'];
                        $units = ['B', 'KB', 'MB', 'GB'];
                        $pow = floor(($size ? log($size) : 0) / log(1024));
                        $pow = min($pow, count($units) - 1);
                        echo round($size / pow(1024, $pow), 2) . ' ' . $units[$pow];
                        ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        <?= date('M j, Y H:i', $log['modified']) ?>
                    </td>
                    <td class="px-6 py-4 text-right text-sm space-x-2">
                        <?php if ($log['readable']): ?>
                            <a href="/logs/view?file=<?= base64_encode($log['path']) ?>" class="text-blue-600 hover:text-blue-900">View</a>
                            <a href="/logs/tail?file=<?= base64_encode($log['path']) ?>" class="text-green-600 hover:text-green-900">Tail</a>
                            <a href="/logs/download?file=<?= base64_encode($log['path']) ?>" class="text-gray-600 hover:text-gray-900">Download</a>
                        <?php else: ?>
                            <span class="text-red-600 text-xs">No Permission</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
