<?php
$pageTitle = 'Applications';
ob_start();
?>

<div class="flex justify-content-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Applications</h1>
        <p class="text-gray-600 mt-1">Manage and deploy your web applications</p>
    </div>
    <a href="/apps/create" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition">
        <svg class="inline-block w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        New Application
    </a>
</div>

<?php if (empty($apps)): ?>
    <div class="bg-white rounded-lg shadow p-12 text-center">
        <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
        </svg>
        <h3 class="text-xl font-bold text-gray-800 mt-4">No Applications Yet</h3>
        <p class="text-gray-600 mt-2 mb-6">Get started by creating your first application.</p>
        <a href="/apps/create" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition">
            Create Application
        </a>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($apps as $app): ?>
            <div class="bg-white rounded-lg shadow hover:shadow-lg transition">
                <div class="p-6">
                    <div class="flex justify-between items-start mb-4">
                        <h3 class="text-lg font-bold text-gray-800"><?= e($app->name) ?></h3>
                        <span class="px-3 py-1 text-xs font-medium rounded-full
                            <?php if ($app->status === 'active'): ?>bg-green-100 text-green-800
                            <?php elseif ($app->status === 'deploying'): ?>bg-yellow-100 text-yellow-800
                            <?php elseif ($app->status === 'error'): ?>bg-red-100 text-red-800
                            <?php else: ?>bg-gray-100 text-gray-800<?php endif; ?>">
                            <?= ucfirst($app->status) ?>
                        </span>
                    </div>

                    <div class="space-y-2">
                        <div>
                            <p class="text-sm font-medium text-gray-700">Subdomain:</p>
                            <?php if ($app->status === 'active'): ?>
                                <a href="http://<?= e($app->subdomain) ?>.localhost" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm">
                                    <?= e($app->subdomain) ?>.localhost
                                    <svg class="inline-block w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                    </svg>
                                </a>
                            <?php else: ?>
                                <p class="text-gray-600 text-sm"><?= e($app->subdomain) ?>.localhost</p>
                            <?php endif; ?>
                        </div>

                        <div>
                            <p class="text-sm text-gray-600">
                                <svg class="inline-block w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                                </svg>
                                Branch: <code class="text-xs bg-gray-100 px-2 py-1 rounded"><?= e($app->git_branch) ?></code>
                            </p>
                        </div>

                        <?php if ($app->deployed_at): ?>
                            <p class="text-xs text-gray-500">
                                Deployed: <?= format_date($app->deployed_at) ?>
                            </p>
                        <?php endif; ?>

                        <?php if ($app->status === 'error' && $app->error_message): ?>
                            <div class="bg-red-50 border border-red-200 rounded p-2 mt-2">
                                <p class="text-xs text-red-800"><?= e($app->error_message) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="border-t border-gray-200 px-6 py-3 bg-gray-50 rounded-b-lg">
                    <a href="/apps/<?= $app->id ?>" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        View Details →
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
