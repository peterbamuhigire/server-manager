<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 Not Found - <?= config('app.name') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="text-center">
            <h1 class="text-9xl font-bold text-blue-600">404</h1>
            <h2 class="text-3xl font-bold text-gray-800 mt-4">Page Not Found</h2>
            <p class="text-gray-600 mt-2 mb-8">The page you are looking for does not exist.</p>
            <a href="/" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg font-medium transition">
                Go to Dashboard
            </a>
        </div>
    </div>
</body>
</html>
