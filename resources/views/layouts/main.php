<?php
$user = auth_user();
$isRoot = is_root();
$currentPath = current_url();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Dashboard' ?> - <?= config('app.name') ?></title>
    <?= CsrfMiddleware::meta() ?>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js for interactivity -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-gray-900 text-white fixed h-full overflow-y-auto">
            <div class="p-6">
                <h1 class="text-xl font-bold text-blue-400"><?= config('app.name') ?></h1>
                <p class="text-xs text-gray-400 mt-1">Server Management</p>
            </div>

            <nav class="mt-6">
                <a href="/" class="flex items-center px-6 py-3 <?= $currentPath === '/' ? 'bg-blue-600 border-r-4 border-blue-400' : 'hover:bg-gray-800' ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Dashboard
                </a>

                <?php if ($isRoot): ?>
                <a href="/users" class="flex items-center px-6 py-3 <?= strpos($currentPath, '/users') === 0 ? 'bg-blue-600 border-r-4 border-blue-400' : 'hover:bg-gray-800' ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    Users
                    <span class="ml-auto bg-red-600 px-2 py-1 text-xs rounded-full">Root</span>
                </a>
                <?php endif; ?>

                <a href="/git" class="flex items-center px-6 py-3 <?= strpos($currentPath, '/git') === 0 ? 'bg-blue-600 border-r-4 border-blue-400' : 'hover:bg-gray-800' ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                    </svg>
                    Git Integration
                    <span class="ml-auto bg-green-600 px-2 py-1 text-xs rounded-full">New</span>
                </a>

                <div class="mt-8 px-6">
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-3">Coming Soon</p>
                </div>

                <a href="/apps" class="flex items-center px-6 py-3 <?= strpos($currentPath, '/apps') === 0 ? 'bg-blue-600 border-r-4 border-blue-400' : 'hover:bg-gray-800' ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                    Apps
                    <span class="ml-auto bg-green-600 px-2 py-1 text-xs rounded-full">New</span>
                </a>

                <a href="/domains" class="flex items-center px-6 py-3 <?= strpos($currentPath, '/domains') === 0 ? 'bg-blue-600 border-r-4 border-blue-400' : 'hover:bg-gray-800' ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                    </svg>
                    Domains
                    <span class="ml-auto bg-green-600 px-2 py-1 text-xs rounded-full">New</span>
                </a>

                <a href="/ssl" class="flex items-center px-6 py-3 <?= strpos($currentPath, '/ssl') === 0 ? 'bg-blue-600 border-r-4 border-blue-400' : 'hover:bg-gray-800' ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    SSL Certificates
                    <span class="ml-auto bg-green-600 px-2 py-1 text-xs rounded-full">New</span>
                </a>

                <a href="/databases" class="flex items-center px-6 py-3 <?= strpos($currentPath, '/databases') === 0 ? 'bg-blue-600 border-r-4 border-blue-400' : 'hover:bg-gray-800' ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                    </svg>
                    Databases
                    <span class="ml-auto bg-green-600 px-2 py-1 text-xs rounded-full">New</span>
                </a>

                <a href="/wordpress/install" class="flex items-center px-6 py-3 <?= strpos($currentPath, '/wordpress') === 0 ? 'bg-blue-600 border-r-4 border-blue-400' : 'hover:bg-gray-800' ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    WordPress Installer
                    <span class="ml-auto bg-green-600 px-2 py-1 text-xs rounded-full">New</span>
                </a>

                <a href="/cron" class="flex items-center px-6 py-3 <?= strpos($currentPath, '/cron') === 0 ? 'bg-blue-600 border-r-4 border-blue-400' : 'hover:bg-gray-800' ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Cron Jobs
                    <span class="ml-auto bg-green-600 px-2 py-1 text-xs rounded-full">New</span>
                </a>

                <a href="/logs" class="flex items-center px-6 py-3 <?= strpos($currentPath, '/logs') === 0 ? 'bg-blue-600 border-r-4 border-blue-400' : 'hover:bg-gray-800' ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Log Viewer
                    <span class="ml-auto bg-green-600 px-2 py-1 text-xs rounded-full">New</span>
                </a>

                <a href="/terminal" class="flex items-center px-6 py-3 <?= strpos($currentPath, '/terminal') === 0 ? 'bg-blue-600 border-r-4 border-blue-400' : 'hover:bg-gray-800' ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    Web Terminal
                    <span class="ml-auto bg-green-600 px-2 py-1 text-xs rounded-full">New</span>
                </a>

                <a href="/submodules" class="flex items-center px-6 py-3 <?= strpos($currentPath, '/submodules') === 0 ? 'bg-blue-600 border-r-4 border-blue-400' : 'hover:bg-gray-800' ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                    Git Submodules
                    <span class="ml-auto bg-green-600 px-2 py-1 text-xs rounded-full">New</span>
                </a>
            </nav>

            <!-- User Menu -->
            <div class="absolute bottom-0 w-64 bg-gray-800 p-4">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center">
                        <span class="text-white font-bold"><?= strtoupper(substr($user['username'], 0, 1)) ?></span>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-sm font-medium"><?= e($user['username']) ?></p>
                        <?php if ($isRoot): ?>
                        <p class="text-xs text-blue-400">Root User</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="mt-3 flex gap-2">
                    <a href="/profile" class="flex-1 text-center text-xs py-2 bg-gray-700 hover:bg-gray-600 rounded">Profile</a>
                    <a href="/logout" class="flex-1 text-center text-xs py-2 bg-red-600 hover:bg-red-700 rounded">Logout</a>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 ml-64">
            <!-- Top Bar -->
            <header class="bg-white shadow-sm">
                <div class="px-8 py-4">
                    <h2 class="text-2xl font-bold text-gray-800"><?= $pageTitle ?? 'Dashboard' ?></h2>
                </div>
            </header>

            <!-- Flash Messages -->
            <?php if (has_flash()): ?>
                <?php $flash = get_flash(); ?>
                <div class="mx-8 mt-6">
                    <div class="px-4 py-3 rounded-lg <?= $flash['type'] === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700' ?>" role="alert">
                        <p><?= $flash['message'] ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Page Content -->
            <div class="p-8">
                <?php echo $content ?? ''; ?>
            </div>
        </main>
    </div>
</body>
</html>
