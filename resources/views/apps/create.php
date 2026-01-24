<?php
$pageTitle = 'Create Application';
ob_start();
?>

<div class="max-w-3xl mx-auto">
    <div class="mb-6">
        <a href="/apps" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
            ← Back to Applications
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Create New Application</h2>

        <form method="POST" action="/apps" class="space-y-6">
            <?= CsrfMiddleware::field() ?>

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                    Application Name *
                </label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="<?= e(old('name')) ?>"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <p class="text-xs text-gray-500 mt-1">A friendly name for your application</p>
            </div>

            <div>
                <label for="subdomain" class="block text-sm font-medium text-gray-700 mb-2">
                    Subdomain *
                </label>
                <div class="flex">
                    <input
                        type="text"
                        id="subdomain"
                        name="subdomain"
                        value="<?= e(old('subdomain')) ?>"
                        pattern="[a-z0-9\-]+"
                        required
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-l-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <span class="bg-gray-100 border border-l-0 border-gray-300 px-4 py-2 rounded-r-lg text-gray-700">
                        .localhost
                    </span>
                </div>
                <p class="text-xs text-gray-500 mt-1">Lowercase letters, numbers, and hyphens only</p>
            </div>

            <div>
                <label for="git_repo" class="block text-sm font-medium text-gray-700 mb-2">
                    Git Repository URL *
                </label>
                <input
                    type="url"
                    id="git_repo"
                    name="git_repo"
                    value="<?= e(old('git_repo')) ?>"
                    placeholder="https://github.com/username/repo.git"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <p class="text-xs text-gray-500 mt-1">The Git repository URL to clone</p>
            </div>

            <div>
                <label for="git_branch" class="block text-sm font-medium text-gray-700 mb-2">
                    Git Branch *
                </label>
                <input
                    type="text"
                    id="git_branch"
                    name="git_branch"
                    value="<?= e(old('git_branch', 'main')) ?>"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <p class="text-xs text-gray-500 mt-1">The branch to deploy</p>
            </div>

            <div>
                <label for="document_root" class="block text-sm font-medium text-gray-700 mb-2">
                    Document Root (Optional)
                </label>
                <input
                    type="text"
                    id="document_root"
                    name="document_root"
                    value="<?= e(old('document_root')) ?>"
                    placeholder="public"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <p class="text-xs text-gray-500 mt-1">
                    Relative path to document root (e.g., "public"). Leave empty for repository root.
                </p>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex">
                    <svg class="w-5 h-5 text-blue-600 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="text-sm text-blue-800">
                        <strong>Note:</strong> After creating the application, you'll need to deploy it to make it accessible.
                    </div>
                </div>
            </div>

            <div class="flex gap-3">
                <button
                    type="submit"
                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-3 px-4 rounded-lg font-medium transition">
                    Create Application
                </button>
                <a
                    href="/apps"
                    class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 py-3 px-4 rounded-lg font-medium text-center transition">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
