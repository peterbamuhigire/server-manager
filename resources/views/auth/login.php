<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= config('app.name') ?></title>
    <?= CsrfMiddleware::meta() ?>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-500 to-purple-600 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md">
        <!-- Login Card -->
        <div class="bg-white rounded-lg shadow-2xl overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-8 py-6">
                <h1 class="text-3xl font-bold text-white text-center"><?= config('app.name') ?></h1>
                <p class="text-blue-100 text-center mt-2">Server Management Portal</p>
            </div>

            <!-- Flash Messages -->
            <?php if (has_flash()): ?>
                <?php $flash = get_flash(); ?>
                <div class="mx-8 mt-6">
                    <div class="px-4 py-3 rounded-lg <?= $flash['type'] === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700' ?>" role="alert">
                        <p class="text-sm"><?= $flash['message'] ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <div class="px-8 py-8">
                <form method="POST" action="/login" class="space-y-6">
                    <?= CsrfMiddleware::field() ?>

                    <!-- Username -->
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                            Username
                        </label>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            required
                            autofocus
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            placeholder="Enter your username"
                        >
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            Password
                        </label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            placeholder="Enter your password"
                        >
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center">
                        <input
                            type="checkbox"
                            id="remember"
                            name="remember"
                            value="1"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        >
                        <label for="remember" class="ml-2 block text-sm text-gray-700">
                            Remember me for 30 days
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white py-3 px-4 rounded-lg font-medium hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition transform hover:scale-105"
                    >
                        Sign In
                    </button>
                </form>
            </div>

            <!-- Footer -->
            <div class="bg-gray-50 px-8 py-4 border-t border-gray-200">
                <p class="text-xs text-gray-600 text-center">
                    &copy; <?= date('Y') ?> <?= config('app.name') ?>. All rights reserved.
                </p>
            </div>
        </div>

        <!-- Environment Badge -->
        <?php if (config('app.env') !== 'production'): ?>
        <div class="mt-4 text-center">
            <span class="inline-block bg-yellow-400 text-yellow-900 px-4 py-2 rounded-lg text-sm font-bold">
                <?= strtoupper(config('app.env')) ?> ENVIRONMENT
            </span>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
