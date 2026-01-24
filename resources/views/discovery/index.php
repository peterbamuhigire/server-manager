<?php
$pageTitle = 'Server Discovery';
ob_start();
?>

<div class="mb-6">
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-start">
            <svg class="w-6 h-6 text-blue-600 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div>
                <h3 class="text-lg font-semibold text-blue-900">Server Discovery</h3>
                <p class="text-blue-700 mt-1">
                    This tool scans your server to discover existing applications, domains, SSL certificates, and databases.
                    You can then import them into the server manager for centralized management.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-blue-100 rounded-lg p-3">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Directories</p>
                <p class="text-2xl font-bold text-gray-800"><?= count($discovered['directories']) ?></p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-green-100 rounded-lg p-3">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Domains</p>
                <p class="text-2xl font-bold text-gray-800"><?= count($discovered['domains']) ?></p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-purple-100 rounded-lg p-3">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">SSL Certs</p>
                <p class="text-2xl font-bold text-gray-800"><?= count($discovered['ssl_certificates']) ?></p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="bg-yellow-100 rounded-lg p-3">
                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Databases</p>
                <p class="text-2xl font-bold text-gray-800"><?= $discovered['databases']['user_count'] ?? 0 ?></p>
            </div>
        </div>
    </div>
</div>

<form action="/discovery/import" method="POST" id="importForm">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

    <!-- Action Buttons -->
    <div class="flex justify-between items-center mb-6">
        <div class="flex gap-3">
            <button type="button" onclick="location.reload()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium transition flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Rescan
            </button>
        </div>
        <div class="flex gap-3">
            <button type="button" onclick="selectAll()" class="bg-blue-100 hover:bg-blue-200 text-blue-700 px-4 py-2 rounded-lg font-medium transition">
                Select All
            </button>
            <button type="button" onclick="deselectAll()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg font-medium transition">
                Deselect All
            </button>
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-medium transition flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                </svg>
                Import Selected
            </button>
        </div>
    </div>

    <!-- Directories Section -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-800 flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                </svg>
                Discovered Directories
                <span class="ml-2 text-sm font-normal text-gray-500">/var/www/html</span>
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <input type="checkbox" class="rounded" onchange="toggleSection(this, 'dir')">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Framework</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Features</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Modified</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($discovered['directories'])): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">No directories found</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($discovered['directories'] as $dir): ?>
                    <?php if ($dir['name'] === 'server-manager') continue; ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input type="checkbox" name="directories[]" value="<?= e($dir['name']) ?>" class="rounded dir-checkbox">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                                </svg>
                                <span class="font-medium text-gray-900"><?= e($dir['name']) ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-medium rounded-full
                                <?php
                                $colors = [
                                    'Laravel' => 'bg-red-100 text-red-800',
                                    'WordPress' => 'bg-blue-100 text-blue-800',
                                    'React' => 'bg-cyan-100 text-cyan-800',
                                    'Vue.js' => 'bg-green-100 text-green-800',
                                    'Node.js' => 'bg-lime-100 text-lime-800',
                                    'PHP' => 'bg-purple-100 text-purple-800',
                                    'Static HTML' => 'bg-gray-100 text-gray-800',
                                ];
                                echo $colors[$dir['framework']] ?? 'bg-gray-100 text-gray-800';
                                ?>">
                                <?= e($dir['framework']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex gap-1">
                                <?php if ($dir['is_git_repo']): ?>
                                <span class="px-2 py-1 text-xs bg-orange-100 text-orange-800 rounded">Git</span>
                                <?php endif; ?>
                                <?php if ($dir['has_composer']): ?>
                                <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded">Composer</span>
                                <?php endif; ?>
                                <?php if ($dir['has_public_dir']): ?>
                                <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded">Public</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?= $this->formatBytes($dir['size']) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?= date('Y-m-d H:i', $dir['modified']) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Domains Section -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-800 flex items-center">
                <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                </svg>
                Discovered Apache Virtual Hosts
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <input type="checkbox" class="rounded" onchange="toggleSection(this, 'domain')">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Domain</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Document Root</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Port</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SSL</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Config</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($discovered['domains'])): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">No virtual hosts found</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($discovered['domains'] as $domain): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input type="checkbox" name="domains[]" value="<?= e($domain['server_name'] ?? '') ?>" class="rounded domain-checkbox" <?= empty($domain['server_name']) ? 'disabled' : '' ?>>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="font-medium text-gray-900"><?= e($domain['server_name'] ?? 'No ServerName') ?></div>
                            <?php if (!empty($domain['server_aliases'])): ?>
                            <div class="text-xs text-gray-500">Aliases: <?= e(implode(', ', $domain['server_aliases'])) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900 truncate max-w-xs" title="<?= e($domain['document_root'] ?? '') ?>">
                                <?= e($domain['document_root'] ?? 'N/A') ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?= $domain['port'] ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($domain['ssl_enabled']): ?>
                            <span class="flex items-center text-green-600">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                                Enabled
                            </span>
                            <?php else: ?>
                            <span class="text-gray-400">No SSL</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($domain['enabled']): ?>
                            <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">Enabled</span>
                            <?php else: ?>
                            <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">Disabled</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <button type="button" onclick="viewConfig('<?= e($domain['config_file']) ?>')" class="text-blue-600 hover:text-blue-800 text-sm">
                                View Config
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- SSL Certificates Section -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-800 flex items-center">
                <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
                Discovered SSL Certificates
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Domain</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Provider</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issuer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expires</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($discovered['ssl_certificates'])): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">No SSL certificates found</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($discovered['ssl_certificates'] as $cert): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                            <?= e($cert['domain']) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-medium rounded-full
                                <?= $cert['provider'] === 'letsencrypt' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                <?= ucfirst($cert['provider']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?= e($cert['issuer'] ?? 'Unknown') ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <?php if ($cert['expires_at']): ?>
                                <?php
                                $expires = strtotime($cert['expires_at']);
                                $daysLeft = ceil(($expires - time()) / 86400);
                                $colorClass = $daysLeft > 30 ? 'text-green-600' : ($daysLeft > 7 ? 'text-yellow-600' : 'text-red-600');
                                ?>
                                <span class="<?= $colorClass ?>"><?= $cert['expires_at'] ?></span>
                                <span class="text-gray-400 text-xs">(<?= $daysLeft ?> days)</span>
                            <?php else: ?>
                                <span class="text-gray-400">Unknown</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($cert['valid']): ?>
                            <span class="text-green-600 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Valid
                            </span>
                            <?php else: ?>
                            <span class="text-red-600">Invalid</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Databases Section -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-800 flex items-center">
                <svg class="w-5 h-5 mr-2 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                </svg>
                Discovered Databases
                <?php if (isset($discovered['databases']['error'])): ?>
                <span class="ml-2 text-sm font-normal text-red-500">(Connection Error)</span>
                <?php endif; ?>
            </h3>
        </div>

        <?php if (isset($discovered['databases']['error'])): ?>
        <div class="p-6">
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-700">
                <?= e($discovered['databases']['error']) ?>
            </div>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <input type="checkbox" class="rounded" onchange="toggleSection(this, 'db')">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Database</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tables</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Charset</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($discovered['databases']['databases'])): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">No databases found</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($discovered['databases']['databases'] as $db): ?>
                    <tr class="hover:bg-gray-50 <?= $db['is_system'] ? 'bg-gray-50' : '' ?>">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input type="checkbox" name="databases[]" value="<?= e($db['name']) ?>" class="rounded db-checkbox" <?= $db['is_system'] ? 'disabled' : '' ?>>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-yellow-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                                </svg>
                                <span class="font-medium <?= $db['is_system'] ? 'text-gray-500' : 'text-gray-900' ?>"><?= e($db['name']) ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?= $db['tables_count'] ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?= $db['size_formatted'] ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?= e($db['charset']) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($db['is_system']): ?>
                            <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-600 rounded-full">System</span>
                            <?php elseif ($db['is_wordpress']): ?>
                            <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">WordPress</span>
                            <?php else: ?>
                            <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">User</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($discovered['databases']['users'])): ?>
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            <h4 class="text-sm font-medium text-gray-700 mb-2">Database Users</h4>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($discovered['databases']['users'] as $user): ?>
                <span class="px-2 py-1 text-xs bg-white border border-gray-200 rounded">
                    <?= e($user['User']) ?>@<?= e($user['Host']) ?>
                </span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</form>

<!-- Config Viewer Modal -->
<div id="configModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center" onclick="closeConfigModal(event)">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[80vh] overflow-hidden" onclick="event.stopPropagation()">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800" id="configModalTitle">Apache Config</h3>
            <button type="button" onclick="closeConfigModal()" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="p-6 overflow-auto max-h-[60vh]">
            <pre id="configContent" class="bg-gray-900 text-green-400 p-4 rounded-lg text-sm font-mono overflow-x-auto"></pre>
        </div>
    </div>
</div>

<script>
function toggleSection(checkbox, type) {
    const checkboxes = document.querySelectorAll('.' + type + '-checkbox:not(:disabled)');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
}

function selectAll() {
    document.querySelectorAll('input[type="checkbox"]:not(:disabled)').forEach(cb => cb.checked = true);
}

function deselectAll() {
    document.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
}

function viewConfig(filename) {
    fetch('/discovery/view-config?file=' + encodeURIComponent(filename))
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('configModalTitle').textContent = data.data.file;
                document.getElementById('configContent').textContent = data.data.content;
                document.getElementById('configModal').classList.remove('hidden');
            } else {
                alert(data.message || 'Failed to load config');
            }
        })
        .catch(err => alert('Error loading config: ' + err.message));
}

function closeConfigModal(event) {
    if (!event || event.target === document.getElementById('configModal')) {
        document.getElementById('configModal').classList.add('hidden');
    }
}

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeConfigModal();
    }
});
</script>

<?php
$content = ob_get_clean();

// Helper function for the view
function formatBytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

require __DIR__ . '/../layouts/main.php';
?>
