<?php
$pageTitle = 'Domains';
ob_start();
?>

<div class="flex justify-content-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Domains</h1>
        <p class="text-gray-600 mt-1">Manage your custom domains</p>
    </div>
    <a href="/domains/create" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition">
        <svg class="inline-block w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        Add Domain
    </a>
</div>

<?php if (empty($domains)): ?>
    <div class="bg-white rounded-lg shadow p-12 text-center">
        <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
        </svg>
        <h3 class="text-xl font-bold text-gray-800 mt-4">No Domains Yet</h3>
        <p class="text-gray-600 mt-2 mb-6">Add your first custom domain to get started.</p>
        <a href="/domains/create" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition">
            Add Domain
        </a>
    </div>
<?php else: ?>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Domain</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">App</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">DNS</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">SSL</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($domains as $domain): ?>
                    <tr>
                        <td class="px-6 py-4">
                            <a href="/domains/<?= $domain['id'] ?>" class="text-blue-600 hover:text-blue-800 font-medium">
                                <?= e($domain['domain_name']) ?>
                            </a>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            <?= $domain['app_name'] ? e($domain['app_name']) : '<span class="text-gray-400">None</span>' ?>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs font-medium rounded-full
                                <?php if ($domain['status'] === 'active'): ?>bg-green-100 text-green-800
                                <?php elseif ($domain['status'] === 'error'): ?>bg-red-100 text-red-800
                                <?php else: ?>bg-yellow-100 text-yellow-800<?php endif; ?>">
                                <?= ucfirst($domain['status']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($domain['dns_configured']): ?>
                                <span class="text-green-600">✓ Configured</span>
                            <?php else: ?>
                                <span class="text-yellow-600">⚠ Pending</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($domain['ssl_enabled']): ?>
                                <span class="text-green-600">✓ Enabled</span>
                            <?php else: ?>
                                <span class="text-gray-400">Not configured</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <a href="/domains/<?= $domain['id'] ?>" class="text-blue-600 hover:text-blue-800">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
