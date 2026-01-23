<?php

/**
 * Front Controller
 * Chwezi Server Manager
 */

// ============================================
// Bootstrap Application
// ============================================

// Load helper functions
require_once __DIR__ . '/../app/Helpers/functions.php';
require_once __DIR__ . '/../app/Helpers/validators.php';

// Load environment variables from .env file
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

// Set timezone
date_default_timezone_set(config('app.timezone', 'UTC'));

// ============================================
// Session Configuration
// ============================================

$sessionConfig = config('app.session');

ini_set('session.cookie_httponly', $sessionConfig['httponly'] ? '1' : '0');
ini_set('session.cookie_secure', $sessionConfig['secure'] ? '1' : '0');
ini_set('session.cookie_samesite', $sessionConfig['samesite']);
ini_set('session.use_strict_mode', '1');
ini_set('session.gc_maxlifetime', $sessionConfig['lifetime'] * 60);

session_name($sessionConfig['name']);
session_start();

// ============================================
// Load Middleware
// ============================================

require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Middleware/RootMiddleware.php';
require_once __DIR__ . '/../app/Middleware/PermissionMiddleware.php';
require_once __DIR__ . '/../app/Middleware/CsrfMiddleware.php';

// ============================================
// Check Remember Me
// ============================================

require_once __DIR__ . '/../app/Controllers/AuthController.php';
AuthController::checkRememberMe();

// ============================================
// Parse Request
// ============================================

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// ============================================
// Define Routes
// ============================================

$routes = [
    'GET' => [
        '/' => ['DashboardController', 'index', ['auth']],
        '/login' => ['AuthController', 'showLogin', []],
        '/logout' => ['AuthController', 'logout', []],
        '/profile' => ['AuthController', 'showProfile', ['auth']],

        // User management (root only)
        '/users' => ['UserController', 'index', ['auth', 'root']],
        '/users/create' => ['UserController', 'create', ['auth', 'root']],
        '/users/edit' => ['UserController', 'edit', ['auth', 'root']],
    ],
    'POST' => [
        '/login' => ['AuthController', 'login', []],
        '/profile/password' => ['AuthController', 'updatePassword', ['auth', 'csrf']],

        // User management (root only)
        '/users/create' => ['UserController', 'store', ['auth', 'root', 'csrf']],
        '/users/update' => ['UserController', 'update', ['auth', 'root', 'csrf']],
        '/users/delete' => ['UserController', 'delete', ['auth', 'root', 'csrf']],
        '/users/toggle-status' => ['UserController', 'toggleStatus', ['auth', 'root', 'csrf']],
    ],
];

// ============================================
// Route Matching
// ============================================

/**
 * Match route and return handler
 * @param string $uri
 * @param string $method
 * @param array $routes
 * @return array|null
 */
function matchRoute(string $uri, string $method, array $routes): ?array
{
    // Direct match
    if (isset($routes[$method][$uri])) {
        return $routes[$method][$uri];
    }

    // Pattern matching for dynamic routes
    foreach ($routes[$method] ?? [] as $pattern => $handler) {
        // Convert route pattern to regex
        $regex = preg_replace('/\{[a-zA-Z0-9_]+\}/', '([a-zA-Z0-9_-]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (preg_match($regex, $uri, $matches)) {
            array_shift($matches); // Remove full match
            return array_merge($handler, [$matches]);
        }
    }

    return null;
}

$handler = matchRoute($uri, $method, $routes);

// ============================================
// Handle 404
// ============================================

if (!$handler) {
    http_response_code(404);

    if (file_exists(__DIR__ . '/../resources/views/errors/404.php')) {
        require __DIR__ . '/../resources/views/errors/404.php';
    } else {
        echo '<!DOCTYPE html>
<html>
<head>
    <title>404 Not Found</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        h1 { color: #e74c3c; }
    </style>
</head>
<body>
    <h1>404 Not Found</h1>
    <p>The page you are looking for does not exist.</p>
    <a href="/">Go to Dashboard</a>
</body>
</html>';
    }
    exit;
}

// ============================================
// Apply Middleware & Dispatch
// ============================================

[$controllerName, $action, $middleware] = $handler;

try {
    // Apply middleware
    foreach ($middleware as $m) {
        if ($m === 'auth') {
            AuthMiddleware::handle();
        } elseif ($m === 'root') {
            RootMiddleware::handle();
        } elseif ($m === 'csrf') {
            CsrfMiddleware::verify();
        }
    }

    // Load and instantiate controller
    $controllerFile = __DIR__ . "/../app/Controllers/{$controllerName}.php";

    if (!file_exists($controllerFile)) {
        throw new Exception("Controller not found: {$controllerName}");
    }

    require_once $controllerFile;

    if (!class_exists($controllerName)) {
        throw new Exception("Controller class not found: {$controllerName}");
    }

    $controller = new $controllerName();

    if (!method_exists($controller, $action)) {
        throw new Exception("Action not found: {$controllerName}@{$action}");
    }

    // Dispatch
    call_user_func([$controller, $action]);

} catch (Exception $e) {
    // Log error
    log_message("Application error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine(), 'error');

    // Show error
    if (config('app.debug')) {
        echo '<h1>Application Error</h1>';
        echo '<pre>' . $e->getMessage() . '</pre>';
        echo '<pre>' . $e->getTraceAsString() . '</pre>';
    } else {
        http_response_code(500);
        echo '<!DOCTYPE html>
<html>
<head>
    <title>500 Internal Server Error</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        h1 { color: #e74c3c; }
    </style>
</head>
<body>
    <h1>500 Internal Server Error</h1>
    <p>Something went wrong. Please try again later.</p>
    <a href="/">Go to Dashboard</a>
</body>
</html>';
    }
}
