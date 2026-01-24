<?php
$pageTitle = 'Dashboard';
ob_start();
?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Welcome Card -->
    <div class="lg:col-span-4 bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg shadow-lg p-6 text-white">
        <h2 class="text-3xl font-bold">Welcome back, <?= e($user['username']) ?>!</h2>
        <p class="mt-2 text-blue-100">You are logged in as <?= is_root() ? '<strong>Root User</strong> with full system access' : 'a user with limited access' ?>.</p>
        <?php if (is_root()): ?>
        <p class="mt-2 text-sm text-yellow-200">
            <svg class="inline-block w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
            </svg>
            With great power comes great responsibility. Be careful with system operations.
        </p>
        <?php endif; ?>
    </div>

    <!-- Stats Cards -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-blue-100 rounded-lg p-3">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Total Apps</p>
                <p class="text-2xl font-bold text-gray-800"><?= $stats['apps'] ?></p>
                <a href="/apps" class="text-xs text-blue-500 hover:text-blue-700 mt-1">Manage</a>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-green-100 rounded-lg p-3">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Active Domains</p>
                <p class="text-2xl font-bold text-gray-800"><?= $stats['domains'] ?></p>
                <a href="/domains" class="text-xs text-blue-500 hover:text-blue-700 mt-1">Manage</a>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-purple-100 rounded-lg p-3">
                <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">SSL Certificates</p>
                <p class="text-2xl font-bold text-gray-800"><?= $stats['ssl_active'] ?></p>
                <a href="/ssl" class="text-xs text-blue-500 hover:text-blue-700 mt-1">Manage</a>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-yellow-100 rounded-lg p-3">
                <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Databases</p>
                <p class="text-2xl font-bold text-gray-800"><?= $stats['databases'] ?></p>
                <a href="/databases" class="text-xs text-blue-500 hover:text-blue-700 mt-1">Manage</a>
            </div>
        </div>
    </div>
</div>

<!-- Server Metrics -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- System Health -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Server Health
        </h3>

        <!-- CPU Usage -->
        <div class="mb-4">
            <div class="flex justify-between mb-1">
                <span class="text-sm font-medium text-gray-700">CPU Usage</span>
                <span class="text-sm font-medium text-gray-700"><?= number_format($metrics['cpu'], 2) ?>%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5">
                <?php
                $cpuColor = $metrics['cpu'] > 80 ? 'bg-red-600' : ($metrics['cpu'] > 60 ? 'bg-yellow-500' : 'bg-green-600');
                ?>
                <div class="<?= $cpuColor ?> h-2.5 rounded-full transition-all duration-300" style="width: <?= $metrics['cpu'] ?>%"></div>
            </div>
        </div>

        <!-- RAM Usage -->
        <div class="mb-4">
            <div class="flex justify-between mb-1">
                <span class="text-sm font-medium text-gray-700">RAM Usage</span>
                <span class="text-sm font-medium text-gray-700"><?= number_format($metrics['ram'], 2) ?>%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5">
                <?php
                $ramColor = $metrics['ram'] > 80 ? 'bg-red-600' : ($metrics['ram'] > 60 ? 'bg-yellow-500' : 'bg-green-600');
                ?>
                <div class="<?= $ramColor ?> h-2.5 rounded-full transition-all duration-300" style="width: <?= $metrics['ram'] ?>%"></div>
            </div>
        </div>

        <!-- Disk Usage -->
        <div class="mb-4">
            <div class="flex justify-between mb-1">
                <span class="text-sm font-medium text-gray-700">Disk Usage</span>
                <span class="text-sm font-medium text-gray-700"><?= number_format($metrics['disk'], 2) ?>%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5">
                <?php
                $diskColor = $metrics['disk'] > 90 ? 'bg-red-600' : ($metrics['disk'] > 75 ? 'bg-yellow-500' : 'bg-green-600');
                ?>
                <div class="<?= $diskColor ?> h-2.5 rounded-full transition-all duration-300" style="width: <?= $metrics['disk'] ?>%"></div>
            </div>
            <p class="text-xs text-gray-500 mt-1">
                <?= $diskInfo['used'] ?> used of <?= $diskInfo['total'] ?> (<?= $diskInfo['free'] ?> free)
            </p>
        </div>

        <!-- Service Status -->
        <div class="border-t pt-4 mt-4">
            <p class="text-sm font-medium text-gray-700 mb-3">Service Status</p>
            <div class="grid grid-cols-2 gap-3">
                <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                    <span class="text-sm text-gray-700">Apache</span>
                    <?php if ($metrics['apache']): ?>
                        <span class="flex items-center text-green-600 text-xs font-medium">
                            <span class="w-2 h-2 bg-green-600 rounded-full mr-1"></span>
                            Running
                        </span>
                    <?php else: ?>
                        <span class="flex items-center text-red-600 text-xs font-medium">
                            <span class="w-2 h-2 bg-red-600 rounded-full mr-1"></span>
                            Stopped
                        </span>
                    <?php endif; ?>
                </div>
                <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                    <span class="text-sm text-gray-700">MySQL</span>
                    <?php if ($metrics['mysql']): ?>
                        <span class="flex items-center text-green-600 text-xs font-medium">
                            <span class="w-2 h-2 bg-green-600 rounded-full mr-1"></span>
                            Running
                        </span>
                    <?php else: ?>
                        <span class="flex items-center text-red-600 text-xs font-medium">
                            <span class="w-2 h-2 bg-red-600 rounded-full mr-1"></span>
                            Stopped
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- System Info -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">System Information</h3>
        <div class="space-y-3">
            <div class="flex justify-between">
                <span class="text-gray-600">Application Version</span>
                <span class="font-medium text-gray-800">1.0.0</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Environment</span>
                <span class="font-medium text-gray-800"><?= ucfirst(config('app.env')) ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">PHP Version</span>
                <span class="font-medium text-gray-800"><?= PHP_VERSION ?></span>
            </div>
            <?php if ($uptime !== 'Unknown'): ?>
            <div class="flex justify-between">
                <span class="text-gray-600">System Uptime</span>
                <span class="font-medium text-gray-800"><?= $uptime ?></span>
            </div>
            <?php endif; ?>
            <?php if ($loadAverage): ?>
            <div class="flex justify-between">
                <span class="text-gray-600">Load Average</span>
                <span class="font-medium text-gray-800 text-xs">
                    <?= number_format($loadAverage['1min'], 2) ?>,
                    <?= number_format($loadAverage['5min'], 2) ?>,
                    <?= number_format($loadAverage['15min'], 2) ?>
                </span>
            </div>
            <?php endif; ?>
            <div class="flex justify-between">
                <span class="text-gray-600">Your IP Address</span>
                <span class="font-medium text-gray-800"><?= get_client_ip() ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Last Login</span>
                <span class="font-medium text-gray-800"><?= $user['last_login'] ? format_date($user['last_login']) : 'Never' ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity & Quick Actions -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Recent Activity -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Recent Activity</h3>
        <?php if (count($recentActivities) > 0): ?>
        <div class="space-y-3">
            <?php foreach ($recentActivities as $activity): ?>
            <div class="flex items-start border-l-2 border-blue-500 pl-3 py-1">
                <div class="flex-1">
                    <p class="text-sm text-gray-800">
                        <span class="font-medium"><?= e($activity['username']) ?></span>
                        <span class="text-gray-600"><?= str_replace('_', ' ', $activity['action']) ?></span>
                        <?php if ($activity['entity_type']): ?>
                        <span class="text-gray-500">(<?= e($activity['entity_type']) ?>)</span>
                        <?php endif; ?>
                    </p>
                    <p class="text-xs text-gray-500 mt-1"><?= format_date($activity['created_at']) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-gray-500 text-sm">No recent activity to display.</p>
        <?php endif; ?>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Quick Actions</h3>
        <div class="space-y-3">
            <?php if (is_root()): ?>
            <a href="/users/create" class="block w-full bg-blue-600 hover:bg-blue-700 text-white py-3 px-4 rounded-lg font-medium text-center transition">
                <svg class="inline-block w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                </svg>
                Create New User
            </a>
            <a href="/users" class="block w-full bg-gray-600 hover:bg-gray-700 text-white py-3 px-4 rounded-lg font-medium text-center transition">
                <svg class="inline-block w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                Manage Users
            </a>
            <?php endif; ?>
            <a href="/profile" class="block w-full bg-purple-600 hover:bg-purple-700 text-white py-3 px-4 rounded-lg font-medium text-center transition">
                <svg class="inline-block w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                View Profile
            </a>
            <a href="/apps" class="block w-full bg-green-600 hover:bg-green-700 text-white py-3 px-4 rounded-lg font-medium text-center transition">
                <svg class="inline-block w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                Manage Apps
            </a>
        </div>
    </div>
</div>

<!-- Feature Overview -->
<div class="bg-green-50 border border-green-200 rounded-lg p-6">
    <h3 class="text-lg font-bold text-green-900 mb-2">All Features Available</h3>
    <p class="text-green-700 mb-4">
        Chwezi Server Manager is fully operational with all features ready to use.
    </p>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded p-3 border border-green-200">
            <p class="text-sm font-medium text-green-800">Apps & Git</p>
            <p class="text-xs text-gray-600 mt-1">Deploy from repositories</p>
        </div>
        <div class="bg-white rounded p-3 border border-green-200">
            <p class="text-sm font-medium text-green-800">Domains & SSL</p>
            <p class="text-xs text-gray-600 mt-1">Manage domains and certificates</p>
        </div>
        <div class="bg-white rounded p-3 border border-green-200">
            <p class="text-sm font-medium text-green-800">Databases</p>
            <p class="text-xs text-gray-600 mt-1">MySQL & backups</p>
        </div>
        <div class="bg-white rounded p-3 border border-green-200">
            <p class="text-sm font-medium text-green-800">Cron & Terminal</p>
            <p class="text-xs text-gray-600 mt-1">Scheduled tasks & web shell</p>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
