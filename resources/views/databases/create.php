<?php
$pageTitle = 'Create Database';
ob_start();
?>

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <a href="/databases" class="text-sm text-indigo-600 hover:text-indigo-800">
            &larr; Back to Databases
        </a>
    </div>

    <div class="bg-white shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                Create New MySQL Database
            </h3>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="mb-4 bg-red-50 border-l-4 border-red-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700"><?= htmlspecialchars($_SESSION['error']) ?></p>
                        </div>
                    </div>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <form method="POST" action="/databases" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                <div>
                    <label for="database_name" class="block text-sm font-medium text-gray-700">
                        Database Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           id="database_name"
                           name="database_name"
                           required
                           pattern="[a-zA-Z0-9_]{1,64}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                           placeholder="my_database">
                    <p class="mt-2 text-sm text-gray-500">
                        1-64 characters. Use only letters, numbers, and underscores.
                    </p>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">
                        Description
                    </label>
                    <textarea id="description"
                              name="description"
                              rows="3"
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                              placeholder="Optional description"></textarea>
                </div>

                <div>
                    <label for="app_id" class="block text-sm font-medium text-gray-700">
                        Link to Application (Optional)
                    </label>
                    <select id="app_id"
                            name="app_id"
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        <option value="">None</option>
                        <?php foreach ($apps as $app): ?>
                            <option value="<?= $app['id'] ?>"><?= htmlspecialchars($app['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input id="create_user"
                                   name="create_user"
                                   type="checkbox"
                                   value="1"
                                   checked
                                   class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="create_user" class="font-medium text-gray-700">Create dedicated database user</label>
                            <p class="text-gray-500">Automatically create a user with the same name and full privileges</p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-3">
                    <a href="/databases"
                       class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Cancel
                    </a>
                    <button type="submit"
                            class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Create Database
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
