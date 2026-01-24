<?php
$pageTitle = 'WordPress Installed Successfully';
ob_start();
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="bg-white shadow sm:rounded-lg overflow-hidden">
        <div class="bg-green-50 px-4 py-5 sm:p-6 border-b border-green-100">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-12 w-12 text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-2xl font-bold text-green-900">WordPress Installed Successfully!</h3>
                    <p class="mt-1 text-sm text-green-700">
                        Your WordPress site is ready. Save the credentials below - they will only be shown once.
                    </p>
                </div>
            </div>
        </div>

        <div class="px-4 py-5 sm:p-6 space-y-6">
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">Important: Save These Credentials</h3>
                        <p class="mt-2 text-sm text-yellow-700">
                            This is the only time these credentials will be displayed. Please save them now in a secure location.
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 p-6 rounded-lg">
                <h4 class="text-lg font-medium text-gray-900 mb-4">Site Information</h4>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Site Name</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($installDetails['site_name']) ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Site URL</dt>
                        <dd class="mt-1">
                            <a href="<?= htmlspecialchars($installDetails['url']) ?>" target="_blank" class="text-sm text-indigo-600 hover:text-indigo-800">
                                <?= htmlspecialchars($installDetails['url']) ?>
                                <svg class="inline h-4 w-4 ml-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="bg-gray-50 p-6 rounded-lg">
                <h4 class="text-lg font-medium text-gray-900 mb-4">WordPress Admin Credentials</h4>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Admin URL</dt>
                        <dd class="mt-1">
                            <a href="<?= htmlspecialchars($installDetails['admin_url']) ?>" target="_blank" class="text-sm text-indigo-600 hover:text-indigo-800">
                                <?= htmlspecialchars($installDetails['admin_url']) ?>
                                <svg class="inline h-4 w-4 ml-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Username</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-mono bg-white px-3 py-2 rounded border border-gray-200">
                            <?= htmlspecialchars($installDetails['admin_user']) ?>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Password</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-mono bg-white px-3 py-2 rounded border border-gray-200">
                            <?= htmlspecialchars($installDetails['admin_password']) ?>
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="bg-gray-50 p-6 rounded-lg">
                <h4 class="text-lg font-medium text-gray-900 mb-4">Database Credentials</h4>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Database Name</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-mono bg-white px-3 py-2 rounded border border-gray-200">
                            <?= htmlspecialchars($installDetails['db_name']) ?>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Database User</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-mono bg-white px-3 py-2 rounded border border-gray-200">
                            <?= htmlspecialchars($installDetails['db_user']) ?>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Database Password</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-mono bg-white px-3 py-2 rounded border border-gray-200">
                            <?= htmlspecialchars($installDetails['db_password']) ?>
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Next Steps</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <ul class="list-disc list-inside space-y-1">
                                <li>Visit your site at <?= htmlspecialchars($installDetails['url']) ?></li>
                                <li>Log in to WordPress admin at <?= htmlspecialchars($installDetails['admin_url']) ?></li>
                                <li>Configure your theme and plugins</li>
                                <li>Add SSL certificate for HTTPS (recommended)</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-gray-50 px-4 py-4 sm:px-6 flex justify-between">
            <a href="/wordpress/install"
               class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Install Another WordPress Site
            </a>
            <a href="/apps"
               class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                View All Applications
            </a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
