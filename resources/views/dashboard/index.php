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

    <!-- Stats Cards (Coming Soon) -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-blue-100 rounded-lg p-3">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Total Apps</p>
                <p class="text-2xl font-bold text-gray-800">0</p>
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
                <p class="text-2xl font-bold text-gray-800">0</p>
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
                <p class="text-2xl font-bold text-gray-800">0</p>
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
                <p class="text-2xl font-bold text-gray-800">0</p>
            </div>
        </div>
    </div>
</div>

<!-- Info Cards -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- System Info -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">System Information</h3>
        <div class="space-y-3">
            <div class="flex justify-between">
                <span class="text-gray-600">Application Version</span>
                <span class="font-medium text-gray-800">1.0.0 (Phase 1)</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Environment</span>
                <span class="font-medium text-gray-800"><?= ucfirst(config('app.env')) ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">PHP Version</span>
                <span class="font-medium text-gray-800"><?= PHP_VERSION ?></span>
            </div>
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
            <div class="block w-full bg-gray-200 text-gray-500 py-3 px-4 rounded-lg font-medium text-center cursor-not-allowed">
                <span class="text-sm">More features coming in Phase 2+</span>
            </div>
        </div>
    </div>
</div>

<!-- Coming Soon Notice -->
<div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
    <h3 class="text-lg font-bold text-blue-900 mb-2">Phase 1: Authentication & RBAC Complete</h3>
    <p class="text-blue-700 mb-4">
        The foundation of Chwezi Server Manager is now ready! You can now manage users and their permissions.
    </p>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded p-3 border border-blue-200">
            <p class="text-sm font-medium text-gray-800">Coming in Phase 2</p>
            <p class="text-xs text-gray-600 mt-1">Dashboard Stats & Server Monitoring</p>
        </div>
        <div class="bg-white rounded p-3 border border-blue-200">
            <p class="text-sm font-medium text-gray-800">Coming in Phase 3</p>
            <p class="text-xs text-gray-600 mt-1">Git Integration & SSH Keys</p>
        </div>
        <div class="bg-white rounded p-3 border border-blue-200">
            <p class="text-sm font-medium text-gray-800">Coming in Phase 4</p>
            <p class="text-xs text-gray-600 mt-1">Application Deployment</p>
        </div>
        <div class="bg-white rounded p-3 border border-blue-200">
            <p class="text-sm font-medium text-gray-800">Coming in Phase 5-13</p>
            <p class="text-xs text-gray-600 mt-1">Domains, SSL, Databases, WordPress & More</p>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
