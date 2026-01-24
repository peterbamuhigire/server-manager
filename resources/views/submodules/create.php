<?php
/**
 * Git Submodules - Create View
 */
$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);
?>

<!-- Header -->
<div class="mb-8">
    <div class="flex items-center space-x-4 mb-4">
        <a href="/submodules" class="text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Add Git Submodule</h1>
            <p class="mt-2 text-gray-600">Clone a Git repository to use as shared code</p>
        </div>
    </div>
</div>

<!-- Form -->
<div class="max-w-3xl">
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Submodule Information</h2>
        </div>

        <form method="POST" action="/submodules/store" class="p-6">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

            <!-- Name -->
            <div class="mb-6">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                    Submodule Name <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="<?= htmlspecialchars($formData['name'] ?? '') ?>"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="shared-components"
                    pattern="[a-z0-9-_]+"
                    required
                >
                <p class="mt-1 text-sm text-gray-500">Lowercase letters, numbers, hyphens, and underscores only</p>
            </div>

            <!-- Repository URL -->
            <div class="mb-6">
                <label for="repository_url" class="block text-sm font-medium text-gray-700 mb-2">
                    Repository URL <span class="text-red-500">*</span>
                </label>
                <input
                    type="url"
                    id="repository_url"
                    name="repository_url"
                    value="<?= htmlspecialchars($formData['repository_url'] ?? '') ?>"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono text-sm"
                    placeholder="https://github.com/username/repo.git"
                    required
                >
                <p class="mt-1 text-sm text-gray-500">Full Git repository URL (HTTPS or SSH)</p>
            </div>

            <!-- Branch -->
            <div class="mb-6">
                <label for="branch" class="block text-sm font-medium text-gray-700 mb-2">
                    Branch <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="branch"
                    name="branch"
                    value="<?= htmlspecialchars($formData['branch'] ?? 'main') ?>"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="main"
                    required
                >
                <p class="mt-1 text-sm text-gray-500">Git branch to use (default: main)</p>
            </div>

            <!-- Description -->
            <div class="mb-6">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                    Description
                </label>
                <textarea
                    id="description"
                    name="description"
                    rows="3"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Shared UI components library"
                ><?= htmlspecialchars($formData['description'] ?? '') ?></textarea>
                <p class="mt-1 text-sm text-gray-500">Brief description of this submodule (optional)</p>
            </div>

            <!-- Info Box -->
            <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-600"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-900">What happens next?</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <ul class="list-disc pl-5 space-y-1">
                                <li>The repository will be cloned to a central location</li>
                                <li>You can then link this submodule to any application</li>
                                <li>On Linux: Symlinks will be created for linked apps</li>
                                <li>On Windows: Directories will be copied (development mode)</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end space-x-4">
                <a href="/submodules" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-plus mr-2"></i>Add Submodule
                </button>
            </div>
        </form>
    </div>

    <!-- Help Section -->
    <div class="mt-8 bg-gray-50 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-question-circle text-gray-600 mr-2"></i>
            Git Submodules Help
        </h3>
        <div class="space-y-4 text-sm text-gray-700">
            <div>
                <h4 class="font-semibold text-gray-900 mb-1">What are Git Submodules?</h4>
                <p>Git submodules allow you to include and manage shared code repositories across multiple applications. They're perfect for shared components, libraries, or utilities.</p>
            </div>
            <div>
                <h4 class="font-semibold text-gray-900 mb-1">Repository Access</h4>
                <p>Ensure your server has access to the Git repository. For private repositories, you may need to set up SSH keys or access tokens.</p>
            </div>
            <div>
                <h4 class="font-semibold text-gray-900 mb-1">Branch Selection</h4>
                <p>Choose the branch that matches your environment (e.g., 'main' for production, 'develop' for staging).</p>
            </div>
        </div>
    </div>
</div>
