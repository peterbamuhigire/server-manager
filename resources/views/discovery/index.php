<?php
$pageTitle = 'Server Discovery';

// Helper function for the view
if (!function_exists('formatBytes')) {
    function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}

// Count registered vs unregistered
$registeredApps = count(array_filter($discovered['directories'], fn($d) => $d['registered'] ?? false));
$unregisteredApps = count($discovered['directories']) - $registeredApps - 1; // -1 for server-manager
$registeredDomains = count(array_filter($discovered['domains'], fn($d) => $d['registered'] ?? false));
$unregisteredDomains = count($discovered['domains']) - $registeredDomains;
$registeredDbs = 0;
$unregisteredDbs = 0;
if (isset($discovered['databases']['databases'])) {
    foreach ($discovered['databases']['databases'] as $db) {
        if ($db['is_system']) continue;
        if ($db['registered'] ?? false) {
            $registeredDbs++;
        } else {
            $unregisteredDbs++;
        }
    }
}

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
                    Scan your server to discover existing resources.
                    <span class="font-medium text-green-700">Green = Registered</span> in the database,
                    <span class="font-medium text-yellow-700">Yellow = Not registered</span>.
                    Register items to manage them, or unregister to remove from database (files remain intact).
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="bg-blue-100 rounded-lg p-3">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Applications</p>
                    <p class="text-2xl font-bold text-gray-800"><?= count($discovered['directories']) - 1 ?></p>
                </div>
            </div>
            <div class="text-right text-xs">
                <div class="text-green-600"><?= $registeredApps ?> registered</div>
                <div class="text-yellow-600"><?= $unregisteredApps ?> unregistered</div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center justify-between">
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
            <div class="text-right text-xs">
                <div class="text-green-600"><?= $registeredDomains ?> registered</div>
                <div class="text-yellow-600"><?= $unregisteredDomains ?> unregistered</div>
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
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="bg-yellow-100 rounded-lg p-3">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Databases</p>
                    <p class="text-2xl font-bold text-gray-800"><?= $registeredDbs + $unregisteredDbs ?></p>
                </div>
            </div>
            <div class="text-right text-xs">
                <div class="text-green-600"><?= $registeredDbs ?> registered</div>
                <div class="text-yellow-600"><?= $unregisteredDbs ?> unregistered</div>
            </div>
        </div>
    </div>
</div>

<!-- Action Buttons -->
<div class="flex justify-between items-center mb-6">
    <button type="button" onclick="location.reload()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium transition flex items-center">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
        </svg>
        Rescan Server
    </button>
    <div class="flex gap-2">
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-green-100 text-green-800">
            <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span> Registered
        </span>
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-yellow-100 text-yellow-800">
            <span class="w-2 h-2 bg-yellow-500 rounded-full mr-2"></span> Not Registered
        </span>
    </div>
</div>

<!-- Applications Section -->
<div class="bg-white rounded-lg shadow mb-6">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-bold text-gray-800 flex items-center">
            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
            </svg>
            Discovered Applications
            <span class="ml-2 text-sm font-normal text-gray-500">/var/www/html</span>
        </h3>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Framework</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Features</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($discovered['directories'] as $dir): ?>
                <?php if ($dir['name'] === 'server-manager') continue; ?>
                <tr class="hover:bg-gray-50 <?= ($dir['registered'] ?? false) ? 'bg-green-50' : 'bg-yellow-50' ?>" id="app-row-<?= e($dir['name']) ?>">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if ($dir['registered'] ?? false): ?>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                            Registered
                        </span>
                        <?php else: ?>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                            Not Registered
                        </span>
                        <?php endif; ?>
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
                                'PHP MVC' => 'bg-indigo-100 text-indigo-800',
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
                        <?= formatBytes($dir['size']) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if ($dir['registered'] ?? false): ?>
                        <button type="button" onclick="unregisterApp(<?= $dir['db_id'] ?>, '<?= e($dir['name']) ?>')"
                                class="text-red-600 hover:text-red-800 text-sm font-medium">
                            Unregister
                        </button>
                        <a href="/apps/<?= $dir['db_id'] ?>" class="ml-3 text-blue-600 hover:text-blue-800 text-sm font-medium">
                            View
                        </a>
                        <?php else: ?>
                        <button type="button" onclick="registerApp('<?= e($dir['name']) ?>')"
                                class="text-green-600 hover:text-green-800 text-sm font-medium">
                            Register
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Domain</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Document Root</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SSL</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Enabled</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($discovered['domains'])): ?>
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">No virtual hosts found</td>
                </tr>
                <?php else: ?>
                <?php foreach ($discovered['domains'] as $domain): ?>
                <?php $serverName = $domain['server_name'] ?? ''; ?>
                <?php if (empty($serverName)) continue; ?>
                <tr class="hover:bg-gray-50 <?= ($domain['registered'] ?? false) ? 'bg-green-50' : 'bg-yellow-50' ?>" id="domain-row-<?= e($serverName) ?>">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if ($domain['registered'] ?? false): ?>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                            Registered
                        </span>
                        <?php else: ?>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                            Not Registered
                        </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="font-medium text-gray-900"><?= e($serverName) ?></div>
                        <?php if (!empty($domain['server_aliases'])): ?>
                        <div class="text-xs text-gray-500">Aliases: <?= e(implode(', ', $domain['server_aliases'])) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900 truncate max-w-xs" title="<?= e($domain['document_root'] ?? '') ?>">
                            <?= e($domain['document_root'] ?? 'N/A') ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if ($domain['ssl_enabled']): ?>
                        <span class="flex items-center text-green-600">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            Yes
                        </span>
                        <?php else: ?>
                        <span class="text-gray-400">No</span>
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
                        <?php if ($domain['registered'] ?? false): ?>
                        <button type="button" onclick="unregisterDomain(<?= $domain['db_id'] ?>, '<?= e($serverName) ?>')"
                                class="text-red-600 hover:text-red-800 text-sm font-medium">
                            Unregister
                        </button>
                        <a href="/domains/<?= $domain['db_id'] ?>" class="ml-3 text-blue-600 hover:text-blue-800 text-sm font-medium">
                            View
                        </a>
                        <?php else: ?>
                        <button type="button" onclick="registerDomain('<?= e($serverName) ?>', '<?= e($domain['document_root'] ?? '') ?>', <?= $domain['ssl_enabled'] ? 1 : 0 ?>)"
                                class="text-green-600 hover:text-green-800 text-sm font-medium">
                            Register
                        </button>
                        <?php endif; ?>
                        <button type="button" onclick="viewConfig('<?= e($domain['config_file']) ?>')" class="ml-3 text-blue-600 hover:text-blue-800 text-sm">
                            Config
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
                            <span class="<?= $colorClass ?>"><?= date('Y-m-d', $expires) ?></span>
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Database</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tables</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($discovered['databases']['databases'])): ?>
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">No databases found</td>
                </tr>
                <?php else: ?>
                <?php foreach ($discovered['databases']['databases'] as $db): ?>
                <?php if ($db['is_system']) continue; ?>
                <tr class="hover:bg-gray-50 <?= ($db['registered'] ?? false) ? 'bg-green-50' : 'bg-yellow-50' ?>" id="db-row-<?= e($db['name']) ?>">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if ($db['registered'] ?? false): ?>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                            Registered
                        </span>
                        <?php else: ?>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                            Not Registered
                        </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-yellow-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                            </svg>
                            <span class="font-medium text-gray-900"><?= e($db['name']) ?></span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?= $db['tables_count'] ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?= $db['size_formatted'] ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if ($db['is_wordpress']): ?>
                        <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">WordPress</span>
                        <?php else: ?>
                        <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">User</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if ($db['registered'] ?? false): ?>
                        <button type="button" onclick="unregisterDatabase(<?= $db['db_id'] ?>, '<?= e($db['name']) ?>')"
                                class="text-red-600 hover:text-red-800 text-sm font-medium">
                            Unregister
                        </button>
                        <a href="/databases/<?= $db['db_id'] ?>" class="ml-3 text-blue-600 hover:text-blue-800 text-sm font-medium">
                            View
                        </a>
                        <?php else: ?>
                        <button type="button" onclick="registerDatabase('<?= e($db['name']) ?>')"
                                class="text-green-600 hover:text-green-800 text-sm font-medium">
                            Register
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

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
const csrfToken = '<?= $_SESSION['csrf_token'] ?? '' ?>';

function registerApp(name) {
    SwalUtil.confirm({
        title: 'Register Application?',
        text: `Register "${name}" as an application in the system?`,
        icon: 'question',
        confirmText: 'Yes, register it',
        confirmColor: '#10b981'
    }).then((result) => {
        if (result.isConfirmed) {
            SwalUtil.loading('Registering application...');
            fetch('/discovery/register-app', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `name=${encodeURIComponent(name)}&csrf_token=${encodeURIComponent(csrfToken)}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Registered!',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => location.reload());
                } else {
                    SwalUtil.error(data.message);
                }
            })
            .catch(err => SwalUtil.error('Error: ' + err.message));
        }
    });
}

function unregisterApp(id, name) {
    SwalUtil.confirm({
        title: 'Unregister Application?',
        text: `Remove "${name}" from management? The files will remain intact.`,
        icon: 'warning',
        confirmText: 'Yes, unregister',
        confirmColor: '#dc2626'
    }).then((result) => {
        if (result.isConfirmed) {
            SwalUtil.loading('Unregistering...');
            fetch('/discovery/unregister-app', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${id}&csrf_token=${encodeURIComponent(csrfToken)}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Unregistered!',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => location.reload());
                } else {
                    SwalUtil.error(data.message);
                }
            })
            .catch(err => SwalUtil.error('Error: ' + err.message));
        }
    });
}

function registerDomain(serverName, documentRoot, sslEnabled) {
    SwalUtil.confirm({
        title: 'Register Domain?',
        text: `Register "${serverName}" as a managed domain?`,
        icon: 'question',
        confirmText: 'Yes, register it',
        confirmColor: '#10b981'
    }).then((result) => {
        if (result.isConfirmed) {
            SwalUtil.loading('Registering domain...');
            fetch('/discovery/register-domain', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `server_name=${encodeURIComponent(serverName)}&document_root=${encodeURIComponent(documentRoot)}&ssl_enabled=${sslEnabled}&csrf_token=${encodeURIComponent(csrfToken)}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Registered!',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => location.reload());
                } else {
                    SwalUtil.error(data.message);
                }
            })
            .catch(err => SwalUtil.error('Error: ' + err.message));
        }
    });
}

function unregisterDomain(id, name) {
    SwalUtil.confirm({
        title: 'Unregister Domain?',
        text: `Remove "${name}" from management? The Apache config will remain intact.`,
        icon: 'warning',
        confirmText: 'Yes, unregister',
        confirmColor: '#dc2626'
    }).then((result) => {
        if (result.isConfirmed) {
            SwalUtil.loading('Unregistering...');
            fetch('/discovery/unregister-domain', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${id}&csrf_token=${encodeURIComponent(csrfToken)}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Unregistered!',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => location.reload());
                } else {
                    SwalUtil.error(data.message);
                }
            })
            .catch(err => SwalUtil.error('Error: ' + err.message));
        }
    });
}

function registerDatabase(name) {
    SwalUtil.confirm({
        title: 'Register Database?',
        text: `Register "${name}" as a managed database?`,
        icon: 'question',
        confirmText: 'Yes, register it',
        confirmColor: '#10b981'
    }).then((result) => {
        if (result.isConfirmed) {
            SwalUtil.loading('Registering database...');
            fetch('/discovery/register-database', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `name=${encodeURIComponent(name)}&csrf_token=${encodeURIComponent(csrfToken)}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Registered!',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => location.reload());
                } else {
                    SwalUtil.error(data.message);
                }
            })
            .catch(err => SwalUtil.error('Error: ' + err.message));
        }
    });
}

function unregisterDatabase(id, name) {
    SwalUtil.confirm({
        title: 'Unregister Database?',
        text: `Remove "${name}" from management? The actual database will remain intact.`,
        icon: 'warning',
        confirmText: 'Yes, unregister',
        confirmColor: '#dc2626'
    }).then((result) => {
        if (result.isConfirmed) {
            SwalUtil.loading('Unregistering...');
            fetch('/discovery/unregister-database', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${id}&csrf_token=${encodeURIComponent(csrfToken)}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Unregistered!',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => location.reload());
                } else {
                    SwalUtil.error(data.message);
                }
            })
            .catch(err => SwalUtil.error('Error: ' + err.message));
        }
    });
}

function viewConfig(filename) {
    SwalUtil.loading('Loading configuration...');
    fetch('/discovery/view-config?file=' + encodeURIComponent(filename))
        .then(response => response.json())
        .then(data => {
            Swal.close();
            if (data.success) {
                Swal.fire({
                    title: data.data.file,
                    html: `<pre class="bg-gray-900 text-green-400 p-4 rounded-lg text-sm font-mono overflow-x-auto text-left max-h-96 overflow-y-auto">${escapeHtml(data.data.content)}</pre>`,
                    width: '80%',
                    showCloseButton: true,
                    showConfirmButton: false,
                    customClass: {
                        popup: 'swal-wide'
                    }
                });
            } else {
                SwalUtil.error(data.message || 'Failed to load config');
            }
        })
        .catch(err => {
            Swal.close();
            SwalUtil.error('Error loading config: ' + err.message);
        });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function closeConfigModal(event) {
    if (!event || event.target === document.getElementById('configModal')) {
        document.getElementById('configModal').classList.add('hidden');
    }
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        Swal.close();
    }
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
