<?php
$pageTitle = 'Git Integration';
ob_start();
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Git Integration</h1>
    <p class="text-gray-600 mt-1">Manage SSH keys and GitHub connection for application deployments</p>
</div>

<!-- Git Status -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Git Installation Status -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Git Status</h3>
        <?php if ($gitInstalled): ?>
            <div class="flex items-center text-green-600 mb-2">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <span class="font-medium">Git is installed</span>
            </div>
            <p class="text-sm text-gray-600 ml-7"><?= e($gitVersion) ?></p>
        <?php else: ?>
            <div class="flex items-center text-red-600 mb-2">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <span class="font-medium">Git is not installed</span>
            </div>
            <p class="text-sm text-gray-600 ml-7">Please install Git to use this feature</p>
        <?php endif; ?>
    </div>

    <!-- SSH Key Status -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">SSH Key Status</h3>
        <?php if ($sshKeyExists): ?>
            <div class="flex items-center text-green-600 mb-2">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <span class="font-medium">SSH key exists</span>
            </div>
            <?php if ($sshFingerprint): ?>
                <p class="text-xs text-gray-600 ml-7 font-mono"><?= e($sshFingerprint) ?></p>
            <?php endif; ?>
        <?php else: ?>
            <div class="flex items-center text-yellow-600 mb-2">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <span class="font-medium">No SSH key found</span>
            </div>
            <p class="text-sm text-gray-600 ml-7">Generate an SSH key to connect to GitHub</p>
        <?php endif; ?>
    </div>
</div>

<!-- SSH Key Management -->
<div class="bg-white rounded-lg shadow mb-6">
    <div class="p-6 border-b border-gray-200">
        <h3 class="text-lg font-bold text-gray-800">SSH Key Management</h3>
        <p class="text-sm text-gray-600 mt-1">Generate and manage SSH keys for Git repository access</p>
    </div>

    <div class="p-6">
        <?php if (!$sshKeyExists): ?>
            <!-- Generate Key Form -->
            <form method="POST" action="/git/generate-key" class="mb-6">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email for SSH Key</label>
                    <input type="email" name="email" value="<?= e($user['email']) ?>"
                           class="w-full md:w-1/2 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="your@email.com">
                    <p class="text-xs text-gray-500 mt-1">This email will be used as a comment in the SSH key</p>
                </div>

                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition">
                    <svg class="inline-block w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                    </svg>
                    Generate SSH Key
                </button>
            </form>
        <?php else: ?>
            <!-- Display Public Key -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-medium text-gray-700">SSH Public Key</label>
                    <button onclick="copyPublicKey()" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                        <svg class="inline-block w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                        Copy to Clipboard
                    </button>
                </div>
                <textarea id="publicKey" readonly class="w-full h-32 px-4 py-2 border border-gray-300 rounded-lg font-mono text-xs bg-gray-50"><?= e($sshPublicKey) ?></textarea>
                <p class="text-xs text-gray-500 mt-2">Copy this key and add it to your GitHub account under Settings → SSH and GPG keys</p>
            </div>

            <!-- Delete Key Form -->
            <form method="POST" action="/git/delete-key" onsubmit="return confirm('Are you sure you want to delete the SSH key? You will need to remove it from GitHub and regenerate a new key.');">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg font-medium transition">
                    <svg class="inline-block w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Delete SSH Key
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- GitHub Connection Test -->
<?php if ($sshKeyExists): ?>
<div class="bg-white rounded-lg shadow mb-6">
    <div class="p-6 border-b border-gray-200">
        <h3 class="text-lg font-bold text-gray-800">GitHub Connection</h3>
        <p class="text-sm text-gray-600 mt-1">Test your SSH connection to GitHub</p>
    </div>

    <div class="p-6">
        <div class="mb-4">
            <h4 class="font-medium text-gray-700 mb-2">Before testing:</h4>
            <ol class="list-decimal list-inside space-y-1 text-sm text-gray-600">
                <li>Copy the SSH public key above</li>
                <li>Go to <a href="https://github.com/settings/keys" target="_blank" class="text-blue-600 hover:underline">GitHub → Settings → SSH and GPG keys</a></li>
                <li>Click "New SSH key"</li>
                <li>Paste the public key and save</li>
                <li>Click the button below to test the connection</li>
            </ol>
        </div>

        <button onclick="testGitHubConnection()" id="testBtn" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-medium transition">
            <svg class="inline-block w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Test GitHub Connection
        </button>

        <div id="testResult" class="mt-4 hidden"></div>
    </div>
</div>
<?php endif; ?>

<!-- Git Configuration -->
<div class="bg-white rounded-lg shadow">
    <div class="p-6 border-b border-gray-200">
        <h3 class="text-lg font-bold text-gray-800">Git Configuration</h3>
        <p class="text-sm text-gray-600 mt-1">Configure global Git user settings</p>
    </div>

    <div class="p-6">
        <form method="POST" action="/git/config">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Git User Name</label>
                    <input type="text" name="git_name" value="<?= e($gitConfig['user.name']) ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Your Name" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Git User Email</label>
                    <input type="email" name="git_email" value="<?= e($gitConfig['user.email']) ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="your@email.com" required>
                </div>
            </div>

            <p class="text-xs text-gray-500 mb-4">These settings will be used for all Git operations (commits, tags, etc.)</p>

            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition">
                <svg class="inline-block w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                </svg>
                Update Git Configuration
            </button>
        </form>
    </div>
</div>

<script>
function copyPublicKey() {
    const textarea = document.getElementById('publicKey');
    textarea.select();
    document.execCommand('copy');

    // Show feedback
    const btn = event.target.closest('button');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<svg class="inline-block w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>Copied!';
    setTimeout(() => {
        btn.innerHTML = originalText;
    }, 2000);
}

function testGitHubConnection() {
    const btn = document.getElementById('testBtn');
    const resultDiv = document.getElementById('testResult');

    // Disable button and show loading
    btn.disabled = true;
    btn.innerHTML = '<svg class="inline-block w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Testing...';

    // Make AJAX request
    fetch('/git/test-connection', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'csrf_token=' + encodeURIComponent('<?= $_SESSION['csrf_token'] ?? '' ?>')
    })
    .then(response => response.json())
    .then(data => {
        resultDiv.classList.remove('hidden');

        if (data.success) {
            resultDiv.innerHTML = `
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center text-green-800 mb-2">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="font-medium">${data.message}</span>
                    </div>
                    ${data.output ? `<p class="text-xs text-green-700 font-mono mt-2">${data.output}</p>` : ''}
                </div>
            `;
        } else {
            resultDiv.innerHTML = `
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex items-center text-red-800 mb-2">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="font-medium">${data.message}</span>
                    </div>
                    ${data.output ? `<p class="text-xs text-red-700 font-mono mt-2">${data.output}</p>` : ''}
                </div>
            `;
        }

        // Re-enable button
        btn.disabled = false;
        btn.innerHTML = '<svg class="inline-block w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>Test GitHub Connection';
    })
    .catch(error => {
        resultDiv.classList.remove('hidden');
        resultDiv.innerHTML = `
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <p class="text-red-800">Error: ${error.message}</p>
            </div>
        `;
        btn.disabled = false;
        btn.innerHTML = '<svg class="inline-block w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>Test GitHub Connection';
    });
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
