<?php
$pageTitle = 'Add Domain';
ob_start();
?>

<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="/domains" class="text-blue-600 hover:text-blue-800 text-sm font-medium">← Back to Domains</a>
    </div>

    <div class="bg-white rounded-lg shadow p-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Add Custom Domain</h2>

        <form method="POST" action="/domains">
            <?= CsrfMiddleware::field() ?>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Domain Name *</label>
                <input type="text" name="domain_name" value="<?= e(old('domain_name')) ?>" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="example.com">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Link to App (Optional)</label>
                <select name="app_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">None</option>
                    <?php foreach ($apps as $app): ?>
                        <option value="<?= $app['id'] ?>"><?= e($app['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Custom Document Root (Optional)</label>
                <input type="text" name="document_root" value="<?= e(old('document_root')) ?>"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                    placeholder="/var/www/example.com/public">
            </div>

            <div class="flex gap-3">
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg">
                    Add Domain
                </button>
                <a href="/domains" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 py-3 rounded-lg text-center">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>
