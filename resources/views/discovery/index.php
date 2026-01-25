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

// Count registered/unregistered SSL certs
$registeredSSL = count(array_filter($discovered['ssl_certificates'], fn($c) => $c['registered'] ?? false));
$unregisteredSSL = count($discovered['ssl_certificates']) - $registeredSSL;

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

<!-- System Setup Commands -->
<div class="mb-6">
    <div class="bg-gray-800 rounded-lg overflow-hidden">
        <button type="button" onclick="toggleSetupCommands()" class="w-full px-4 py-3 flex items-center justify-between text-left hover:bg-gray-700 transition">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-green-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <span class="text-white font-medium">System Setup Commands</span>
                <span class="ml-2 text-xs text-gray-400">(Run these in terminal for full functionality)</span>
            </div>
            <svg id="setupCommandsChevron" class="w-5 h-5 text-gray-400 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>
        <div id="setupCommandsContent" class="hidden">
            <div class="border-t border-gray-700 p-4">
                <!-- Apache Control Permissions -->
                <div class="mb-4">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-sm font-medium text-green-400">Apache Control Permissions</h4>
                        <button onclick="copyAllCommands('apache')" class="text-xs text-blue-400 hover:text-blue-300 flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path>
                            </svg>
                            Copy All
                        </button>
                    </div>
                    <p class="text-xs text-gray-400 mb-2">Allow the web server to control Apache (restart/reload):</p>
                    <div class="space-y-1" id="apache-commands">
                        <div class="command-line group">
                            <code class="text-green-300 text-xs">sudo rm -f /etc/sudoers.d/apache-control</code>
                            <button onclick="copyCommand(this)" class="copy-btn">Copy</button>
                        </div>
                        <div class="command-line group">
                            <code class="text-green-300 text-xs">echo "www-data ALL=(ALL) NOPASSWD: /usr/sbin/apache2ctl configtest" | sudo tee /etc/sudoers.d/apache-control</code>
                            <button onclick="copyCommand(this)" class="copy-btn">Copy</button>
                        </div>
                        <div class="command-line group">
                            <code class="text-green-300 text-xs">echo "www-data ALL=(ALL) NOPASSWD: /bin/systemctl restart apache2" | sudo tee -a /etc/sudoers.d/apache-control</code>
                            <button onclick="copyCommand(this)" class="copy-btn">Copy</button>
                        </div>
                        <div class="command-line group">
                            <code class="text-green-300 text-xs">echo "www-data ALL=(ALL) NOPASSWD: /bin/systemctl reload apache2" | sudo tee -a /etc/sudoers.d/apache-control</code>
                            <button onclick="copyCommand(this)" class="copy-btn">Copy</button>
                        </div>
                        <div class="command-line group">
                            <code class="text-green-300 text-xs">echo "www-data ALL=(ALL) NOPASSWD: /bin/systemctl is-active apache2" | sudo tee -a /etc/sudoers.d/apache-control</code>
                            <button onclick="copyCommand(this)" class="copy-btn">Copy</button>
                        </div>
                        <div class="command-line group">
                            <code class="text-green-300 text-xs">echo "www-data ALL=(ALL) NOPASSWD: /bin/systemctl status apache2" | sudo tee -a /etc/sudoers.d/apache-control</code>
                            <button onclick="copyCommand(this)" class="copy-btn">Copy</button>
                        </div>
                        <div class="command-line group">
                            <code class="text-green-300 text-xs">sudo chmod 440 /etc/sudoers.d/apache-control</code>
                            <button onclick="copyCommand(this)" class="copy-btn">Copy</button>
                        </div>
                        <div class="command-line group">
                            <code class="text-green-300 text-xs">sudo visudo -c</code>
                            <button onclick="copyCommand(this)" class="copy-btn">Copy</button>
                        </div>
                    </div>
                </div>

                <!-- Git SSH Setup -->
                <div class="mb-4">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-sm font-medium text-green-400">Git SSH Key Setup (if using SSH URLs)</h4>
                        <button onclick="copyAllCommands('git-ssh')" class="text-xs text-blue-400 hover:text-blue-300 flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path>
                            </svg>
                            Copy All
                        </button>
                    </div>
                    <p class="text-xs text-gray-400 mb-2">Configure SSH for www-data to use git with SSH URLs:</p>
                    <div class="space-y-1" id="git-ssh-commands">
                        <div class="command-line group">
                            <code class="text-green-300 text-xs">sudo mkdir -p /var/www/.ssh</code>
                            <button onclick="copyCommand(this)" class="copy-btn">Copy</button>
                        </div>
                        <div class="command-line group">
                            <code class="text-green-300 text-xs">sudo chown -R www-data:www-data /var/www/.ssh</code>
                            <button onclick="copyCommand(this)" class="copy-btn">Copy</button>
                        </div>
                        <div class="command-line group">
                            <code class="text-green-300 text-xs">sudo chmod 700 /var/www/.ssh</code>
                            <button onclick="copyCommand(this)" class="copy-btn">Copy</button>
                        </div>
                        <div class="command-line group">
                            <code class="text-green-300 text-xs">sudo -u www-data ssh-keygen -t ed25519 -C "server-manager" -f /var/www/.ssh/id_ed25519 -N ""</code>
                            <button onclick="copyCommand(this)" class="copy-btn">Copy</button>
                        </div>
                        <div class="command-line group">
                            <code class="text-green-300 text-xs">sudo cat /var/www/.ssh/id_ed25519.pub</code>
                            <button onclick="copyCommand(this)" class="copy-btn">Copy</button>
                        </div>
                    </div>
                    <p class="text-xs text-yellow-400 mt-2">After running, add the public key to your GitHub account's SSH keys.</p>
                </div>

                <!-- Directory Permissions -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-sm font-medium text-green-400">Web Directory Permissions</h4>
                        <button onclick="copyAllCommands('dir-perms')" class="text-xs text-blue-400 hover:text-blue-300 flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path>
                            </svg>
                            Copy All
                        </button>
                    </div>
                    <p class="text-xs text-gray-400 mb-2">Ensure www-data can write to web directories:</p>
                    <div class="space-y-1" id="dir-perms-commands">
                        <div class="command-line group">
                            <code class="text-green-300 text-xs">sudo chown -R www-data:www-data /var/www/html</code>
                            <button onclick="copyCommand(this)" class="copy-btn">Copy</button>
                        </div>
                        <div class="command-line group">
                            <code class="text-green-300 text-xs">sudo chmod -R 755 /var/www/html</code>
                            <button onclick="copyCommand(this)" class="copy-btn">Copy</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.command-line {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: rgba(0,0,0,0.3);
    padding: 0.5rem 0.75rem;
    border-radius: 0.375rem;
    font-family: monospace;
}
.command-line code {
    flex: 1;
    overflow-x: auto;
    white-space: nowrap;
}
.copy-btn {
    margin-left: 0.5rem;
    padding: 0.25rem 0.5rem;
    font-size: 0.625rem;
    background: rgba(59, 130, 246, 0.2);
    color: #93c5fd;
    border-radius: 0.25rem;
    opacity: 0;
    transition: opacity 0.2s;
}
.group:hover .copy-btn {
    opacity: 1;
}
.copy-btn:hover {
    background: rgba(59, 130, 246, 0.4);
}
</style>

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
        <div class="flex items-center justify-between">
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
            <div class="text-right text-xs">
                <div class="text-green-600"><?= $registeredSSL ?> registered</div>
                <div class="text-yellow-600"><?= $unregisteredSSL ?> unregistered</div>
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
<div class="flex flex-wrap justify-between items-center mb-6 gap-4">
    <div class="flex flex-wrap gap-2">
        <button type="button" onclick="location.reload()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium transition flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Rescan
        </button>
        <button type="button" onclick="cleanupOrphaned()" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg font-medium transition flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
            Cleanup DB
        </button>
    </div>

    <!-- Apache Controls -->
    <div class="flex items-center gap-2">
        <span id="apacheStatus" class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-gray-100 text-gray-600">
            <span class="w-2 h-2 bg-gray-400 rounded-full mr-2 animate-pulse"></span> Checking...
        </span>
        <button type="button" onclick="reloadApache()" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg font-medium transition flex items-center text-sm">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Reload
        </button>
        <button type="button" onclick="restartApache()" class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-lg font-medium transition flex items-center text-sm">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Restart Apache
        </button>
    </div>

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
    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
        <h3 class="text-lg font-bold text-gray-800 flex items-center">
            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
            </svg>
            Discovered Applications
            <span class="ml-2 text-sm font-normal text-gray-500">/var/www/html</span>
        </h3>
        <button type="button" onclick="showCloneModal()" class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded-lg font-medium transition flex items-center text-sm">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Clone Repository
        </button>
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
                        <div class="flex items-center gap-2">
                            <?php if ($dir['is_git_repo']): ?>
                            <button type="button" onclick="gitPull('<?= e($dir['name']) ?>')"
                                    class="text-orange-600 hover:text-orange-800 text-sm font-medium" title="Pull latest changes">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                Pull
                            </button>
                            <?php endif; ?>
                            <?php if ($dir['registered'] ?? false): ?>
                            <button type="button" onclick="unregisterApp(<?= $dir['db_id'] ?>, '<?= e($dir['name']) ?>')"
                                    class="text-red-600 hover:text-red-800 text-sm font-medium">
                                Unregister
                            </button>
                            <a href="/apps/<?= $dir['db_id'] ?>" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                View
                            </a>
                            <?php else: ?>
                            <button type="button" onclick="registerApp('<?= e($dir['name']) ?>')"
                                    class="text-green-600 hover:text-green-800 text-sm font-medium">
                                Register
                            </button>
                            <?php endif; ?>
                        </div>
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Domain</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Provider</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issuer</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expires</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valid</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($discovered['ssl_certificates'])): ?>
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">No SSL certificates found</td>
                </tr>
                <?php else: ?>
                <?php foreach ($discovered['ssl_certificates'] as $cert): ?>
                <tr class="hover:bg-gray-50 <?= ($cert['registered'] ?? false) ? 'bg-green-50' : 'bg-yellow-50' ?>" id="ssl-row-<?= e($cert['domain']) ?>">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if ($cert['registered'] ?? false): ?>
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
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if ($cert['registered'] ?? false): ?>
                        <button type="button" onclick="unregisterSSL(<?= $cert['db_id'] ?>, '<?= e($cert['domain']) ?>')"
                                class="text-red-600 hover:text-red-800 text-sm font-medium">
                            Unregister
                        </button>
                        <a href="/ssl/<?= $cert['db_id'] ?>" class="ml-3 text-blue-600 hover:text-blue-800 text-sm font-medium">
                            View
                        </a>
                        <?php else: ?>
                        <button type="button" onclick="registerSSL('<?= e($cert['domain']) ?>', '<?= e($cert['provider']) ?>', '<?= e($cert['cert_file'] ?? '') ?>', '<?= e($cert['key_file'] ?? '') ?>', '<?= e($cert['chain_file'] ?? '') ?>', '<?= e($cert['issued_at'] ?? '') ?>', '<?= e($cert['expires_at'] ?? '') ?>')"
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

// Setup Commands Toggle
function toggleSetupCommands() {
    const content = document.getElementById('setupCommandsContent');
    const chevron = document.getElementById('setupCommandsChevron');

    if (content.classList.contains('hidden')) {
        content.classList.remove('hidden');
        chevron.classList.add('rotate-180');
    } else {
        content.classList.add('hidden');
        chevron.classList.remove('rotate-180');
    }
}

// Copy single command
function copyCommand(btn) {
    const code = btn.parentElement.querySelector('code');
    const text = code.textContent;

    navigator.clipboard.writeText(text).then(() => {
        const originalText = btn.textContent;
        btn.textContent = 'Copied!';
        btn.style.background = 'rgba(34, 197, 94, 0.3)';
        btn.style.color = '#86efac';

        setTimeout(() => {
            btn.textContent = originalText;
            btn.style.background = '';
            btn.style.color = '';
        }, 1500);
    }).catch(err => {
        SwalUtil.error('Failed to copy: ' + err.message);
    });
}

// Copy all commands in a section
function copyAllCommands(section) {
    const container = document.getElementById(section + '-commands');
    const codes = container.querySelectorAll('code');
    const commands = Array.from(codes).map(c => c.textContent).join('\n');

    navigator.clipboard.writeText(commands).then(() => {
        Swal.fire({
            icon: 'success',
            title: 'Commands Copied!',
            text: `${codes.length} commands copied to clipboard`,
            timer: 1500,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    }).catch(err => {
        SwalUtil.error('Failed to copy: ' + err.message);
    });
}

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

// SSL Certificate Registration
function registerSSL(domain, provider, certFile, keyFile, chainFile, issuedAt, expiresAt) {
    SwalUtil.confirm({
        title: 'Register SSL Certificate?',
        text: `Register the SSL certificate for "${domain}" in the system?`,
        icon: 'question',
        confirmText: 'Yes, register it',
        confirmColor: '#10b981'
    }).then((result) => {
        if (result.isConfirmed) {
            SwalUtil.loading('Registering SSL certificate...');
            const body = new URLSearchParams({
                domain: domain,
                provider: provider,
                cert_file: certFile,
                key_file: keyFile,
                chain_file: chainFile,
                issued_at: issuedAt,
                expires_at: expiresAt,
                csrf_token: csrfToken
            });
            fetch('/discovery/register-ssl', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString()
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

function unregisterSSL(id, domain) {
    SwalUtil.confirm({
        title: 'Unregister SSL Certificate?',
        text: `Remove "${domain}" SSL certificate from management? The certificate files will remain intact.`,
        icon: 'warning',
        confirmText: 'Yes, unregister',
        confirmColor: '#dc2626'
    }).then((result) => {
        if (result.isConfirmed) {
            SwalUtil.loading('Unregistering...');
            fetch('/discovery/unregister-ssl', {
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

// Cleanup orphaned entries
function cleanupOrphaned() {
    SwalUtil.confirm({
        title: 'Cleanup Database?',
        html: `<p>This will remove database entries for resources that no longer exist on the server.</p>
               <p class="mt-2 text-sm text-gray-500">Useful after server migration or manual cleanup of files.</p>`,
        icon: 'warning',
        confirmText: 'Yes, clean up',
        confirmColor: '#f97316'
    }).then((result) => {
        if (result.isConfirmed) {
            SwalUtil.loading('Scanning and cleaning up...');
            fetch('/discovery/cleanup', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `csrf_token=${encodeURIComponent(csrfToken)}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const results = data.data;
                    let html = '<div class="text-left">';

                    if (results.apps.removed > 0) {
                        html += `<p class="mb-2"><strong>Apps removed:</strong> ${results.apps.removed}</p>`;
                        if (results.apps.items.length > 0) {
                            html += `<ul class="text-sm text-gray-600 ml-4 mb-2">${results.apps.items.map(i => `<li>- ${i}</li>`).join('')}</ul>`;
                        }
                    }
                    if (results.domains.removed > 0) {
                        html += `<p class="mb-2"><strong>Domains removed:</strong> ${results.domains.removed}</p>`;
                        if (results.domains.items.length > 0) {
                            html += `<ul class="text-sm text-gray-600 ml-4 mb-2">${results.domains.items.map(i => `<li>- ${i}</li>`).join('')}</ul>`;
                        }
                    }
                    if (results.databases.removed > 0) {
                        html += `<p class="mb-2"><strong>Databases removed:</strong> ${results.databases.removed}</p>`;
                        if (results.databases.items.length > 0) {
                            html += `<ul class="text-sm text-gray-600 ml-4 mb-2">${results.databases.items.map(i => `<li>- ${i}</li>`).join('')}</ul>`;
                        }
                    }
                    if (results.ssl.removed > 0) {
                        html += `<p class="mb-2"><strong>SSL Certs removed:</strong> ${results.ssl.removed}</p>`;
                        if (results.ssl.items.length > 0) {
                            html += `<ul class="text-sm text-gray-600 ml-4 mb-2">${results.ssl.items.map(i => `<li>- ${i}</li>`).join('')}</ul>`;
                        }
                    }

                    const totalRemoved = results.apps.removed + results.domains.removed + results.databases.removed + results.ssl.removed;
                    if (totalRemoved === 0) {
                        html = '<p>No orphaned entries found. Your database is clean!</p>';
                    }

                    html += '</div>';

                    Swal.fire({
                        icon: totalRemoved > 0 ? 'success' : 'info',
                        title: data.message,
                        html: html,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        if (totalRemoved > 0) location.reload();
                    });
                } else {
                    SwalUtil.error(data.message);
                }
            })
            .catch(err => SwalUtil.error('Error: ' + err.message));
        }
    });
}

// Apache Controls
function checkApacheStatus() {
    fetch('/discovery/apache-status')
        .then(r => r.json())
        .then(data => {
            const statusEl = document.getElementById('apacheStatus');
            if (data.success && data.data.running) {
                statusEl.innerHTML = '<span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span> Apache Running';
                statusEl.className = 'inline-flex items-center px-3 py-1 rounded-full text-sm bg-green-100 text-green-800';
            } else {
                statusEl.innerHTML = '<span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span> Apache Stopped';
                statusEl.className = 'inline-flex items-center px-3 py-1 rounded-full text-sm bg-red-100 text-red-800';
            }
        })
        .catch(() => {
            const statusEl = document.getElementById('apacheStatus');
            statusEl.innerHTML = '<span class="w-2 h-2 bg-gray-400 rounded-full mr-2"></span> Unknown';
            statusEl.className = 'inline-flex items-center px-3 py-1 rounded-full text-sm bg-gray-100 text-gray-600';
        });
}

function reloadApache() {
    SwalUtil.confirm({
        title: 'Reload Apache?',
        text: 'This will gracefully reload Apache configuration without dropping connections.',
        icon: 'question',
        confirmText: 'Yes, reload',
        confirmColor: '#3b82f6'
    }).then((result) => {
        if (result.isConfirmed) {
            SwalUtil.loading('Reloading Apache...');
            fetch('/discovery/reload-apache', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `csrf_token=${encodeURIComponent(csrfToken)}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Apache Reloaded',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    checkApacheStatus();
                } else {
                    SwalUtil.error(data.message, 'Reload Failed');
                }
            })
            .catch(err => SwalUtil.error('Error: ' + err.message));
        }
    });
}

function restartApache() {
    SwalUtil.confirm({
        title: 'Restart Apache?',
        html: `<p>This will fully restart Apache service.</p>
               <p class="mt-2 text-sm text-yellow-600">Active connections may be briefly interrupted.</p>`,
        icon: 'warning',
        confirmText: 'Yes, restart',
        confirmColor: '#dc2626'
    }).then((result) => {
        if (result.isConfirmed) {
            SwalUtil.loading('Restarting Apache...');
            fetch('/discovery/restart-apache', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `csrf_token=${encodeURIComponent(csrfToken)}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Apache Restarted',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    checkApacheStatus();
                } else {
                    SwalUtil.error(data.message, 'Restart Failed');
                }
            })
            .catch(err => SwalUtil.error('Error: ' + err.message));
        }
    });
}

// Check Apache status on page load
document.addEventListener('DOMContentLoaded', function() {
    checkApacheStatus();
});

// Git Clone Modal
function showCloneModal() {
    Swal.fire({
        title: 'Clone Repository',
        html: `
            <div class="text-left">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Repository URL *</label>
                    <input type="text" id="clone-repo-url" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="https://github.com/user/repo.git or git@github.com:user/repo.git">
                    <p class="text-xs text-gray-500 mt-1">Supports HTTPS and SSH URLs</p>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Application Name *</label>
                    <input type="text" id="clone-app-name" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="my-app">
                    <p class="text-xs text-gray-500 mt-1">Will be created in /var/www/html/</p>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Branch (optional)</label>
                    <input type="text" id="clone-branch" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="main">
                    <p class="text-xs text-gray-500 mt-1">Leave empty for default branch</p>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Clone',
        confirmButtonColor: '#10b981',
        cancelButtonText: 'Cancel',
        width: '500px',
        preConfirm: () => {
            const repoUrl = document.getElementById('clone-repo-url').value.trim();
            const appName = document.getElementById('clone-app-name').value.trim();
            const branch = document.getElementById('clone-branch').value.trim();

            if (!repoUrl) {
                Swal.showValidationMessage('Repository URL is required');
                return false;
            }
            if (!appName) {
                Swal.showValidationMessage('Application name is required');
                return false;
            }
            if (!/^[a-zA-Z0-9_-]+$/.test(appName)) {
                Swal.showValidationMessage('Application name can only contain letters, numbers, hyphens, and underscores');
                return false;
            }

            return { repoUrl, appName, branch };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            gitClone(result.value.repoUrl, result.value.appName, result.value.branch);
        }
    });
}

function gitClone(repoUrl, appName, branch) {
    SwalUtil.loading('Cloning repository... This may take a moment.');

    const body = new URLSearchParams({
        repo_url: repoUrl,
        app_name: appName,
        branch: branch || '',
        csrf_token: csrfToken
    });

    fetch('/discovery/git-clone', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString()
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Repository Cloned!',
                html: `<p>${data.message}</p>
                       <p class="text-sm text-gray-500 mt-2">Path: ${data.data?.path || '/var/www/html/' + appName}</p>
                       ${data.data?.output ? '<pre class="text-xs bg-gray-100 p-2 mt-2 rounded max-h-32 overflow-auto text-left">' + escapeHtml(data.data.output) + '</pre>' : ''}`,
                confirmButtonText: 'OK'
            }).then(() => location.reload());
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Clone Failed',
                html: `<p>${escapeHtml(data.message)}</p>`,
                confirmButtonColor: '#dc2626'
            });
        }
    })
    .catch(err => SwalUtil.error('Error: ' + err.message));
}

function gitPull(appName) {
    SwalUtil.confirm({
        title: 'Pull Latest Changes?',
        text: `Pull the latest changes for "${appName}" from the remote repository?`,
        icon: 'question',
        confirmText: 'Yes, pull',
        confirmColor: '#f97316'
    }).then((result) => {
        if (result.isConfirmed) {
            SwalUtil.loading('Pulling latest changes...');

            fetch('/discovery/git-pull', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `app_name=${encodeURIComponent(appName)}&csrf_token=${encodeURIComponent(csrfToken)}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Pull Successful!',
                        html: `<p>${data.message}</p>
                               ${data.data?.branch ? '<p class="text-sm text-gray-500">Branch: ' + escapeHtml(data.data.branch) + '</p>' : ''}
                               ${data.data?.output ? '<pre class="text-xs bg-gray-100 p-2 mt-2 rounded max-h-32 overflow-auto text-left">' + escapeHtml(data.data.output) + '</pre>' : ''}`,
                        confirmButtonText: 'OK'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Pull Failed',
                        html: `<p>${escapeHtml(data.message)}</p>`,
                        confirmButtonColor: '#dc2626'
                    });
                }
            })
            .catch(err => SwalUtil.error('Error: ' + err.message));
        }
    });
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
