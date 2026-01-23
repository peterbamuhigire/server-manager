<?php

/**
 * Core Helper Functions
 * Chwezi Server Manager
 */

// ============================================
// Database Functions
// ============================================

/**
 * Get PDO database connection
 * @return PDO
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $config = config('database');

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        try {
            $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
        } catch (PDOException $e) {
            if (config('app.debug')) {
                die('Database connection failed: ' . $e->getMessage());
            } else {
                die('Database connection failed. Please check your configuration.');
            }
        }
    }

    return $pdo;
}

// ============================================
// Configuration Functions
// ============================================

/**
 * Load configuration value
 * @param string $key Dot notation key (e.g., 'app.name' or 'database.host')
 * @param mixed $default Default value if key not found
 * @return mixed
 */
function config(string $key, $default = null)
{
    static $configs = [];

    $parts = explode('.', $key);
    $file = array_shift($parts);

    // Load config file if not already loaded
    if (!isset($configs[$file])) {
        $path = __DIR__ . '/../../config/' . $file . '.php';
        if (file_exists($path)) {
            $configs[$file] = require $path;
        } else {
            return $default;
        }
    }

    // Navigate through nested array
    $value = $configs[$file];
    foreach ($parts as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }

    return $value;
}

/**
 * Get environment variable
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function env(string $key, $default = null)
{
    return $_ENV[$key] ?? $default;
}

// ============================================
// Session Functions
// ============================================

/**
 * Get or set session value
 * @param string|null $key
 * @param mixed $default
 * @return mixed
 */
function session(?string $key = null, $default = null)
{
    if ($key === null) {
        return $_SESSION;
    }

    return $_SESSION[$key] ?? $default;
}

/**
 * Set session value
 * @param string $key
 * @param mixed $value
 */
function session_set(string $key, $value): void
{
    $_SESSION[$key] = $value;
}

/**
 * Check if user is authenticated
 * @return bool
 */
function auth_check(): bool
{
    return isset($_SESSION['user_id']);
}

/**
 * Get authenticated user ID
 * @return int|null
 */
function auth_id(): ?int
{
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get authenticated user data
 * @return array|null
 */
function auth_user(): ?array
{
    if (!auth_check()) {
        return null;
    }

    static $user = null;

    if ($user === null) {
        $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([auth_id()]);
        $user = $stmt->fetch();
    }

    return $user ?: null;
}

/**
 * Check if authenticated user is root
 * @return bool
 */
function is_root(): bool
{
    $user = auth_user();
    return $user && $user['is_root'];
}

// ============================================
// HTTP Functions
// ============================================

/**
 * Redirect to URL
 * @param string $url
 * @param int $code
 */
function redirect(string $url, int $code = 302): void
{
    header('Location: ' . $url, true, $code);
    exit;
}

/**
 * Redirect back to previous page
 */
function back(): void
{
    $previous = $_SERVER['HTTP_REFERER'] ?? '/';
    redirect($previous);
}

/**
 * Get current URL
 * @return string
 */
function current_url(): string
{
    return $_SERVER['REQUEST_URI'] ?? '/';
}

/**
 * Generate URL
 * @param string $path
 * @return string
 */
function url(string $path = ''): string
{
    $baseUrl = rtrim(config('app.url'), '/');
    $path = ltrim($path, '/');
    return $baseUrl . '/' . $path;
}

// ============================================
// Response Functions
// ============================================

/**
 * Return JSON response
 * @param mixed $data
 * @param int $status
 */
function json($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Return JSON error response
 * @param string $message
 * @param int $status
 */
function json_error(string $message, int $status = 400): void
{
    json(['error' => $message], $status);
}

/**
 * Return JSON success response
 * @param string $message
 * @param mixed $data
 */
function json_success(string $message, $data = null): void
{
    $response = ['success' => true, 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    json($response);
}

// ============================================
// View Functions
// ============================================

/**
 * Render view
 * @param string $view View name (e.g., 'auth/login')
 * @param array $data Data to pass to view
 */
function view(string $view, array $data = []): void
{
    extract($data);

    $viewPath = config('paths.views') . '/' . str_replace('.', '/', $view) . '.php';

    if (!file_exists($viewPath)) {
        die("View not found: {$view}");
    }

    require $viewPath;
}

/**
 * Include a partial view
 * @param string $partial
 * @param array $data
 */
function partial(string $partial, array $data = []): void
{
    view($partial, $data);
}

// ============================================
// Security Functions
// ============================================

/**
 * Hash password
 * @param string $password
 * @return string
 */
function hash_password(string $password): string
{
    return password_hash($password, PASSWORD_BCRYPT, [
        'cost' => config('app.password.cost', 12)
    ]);
}

/**
 * Verify password
 * @param string $password
 * @param string $hash
 * @return bool
 */
function verify_password(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}

/**
 * Generate random token
 * @param int $length
 * @return string
 */
function generate_token(int $length = 32): string
{
    return bin2hex(random_bytes($length));
}

/**
 * Escape HTML
 * @param string $string
 * @return string
 */
function e(string $string): string
{
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// ============================================
// Validation & Sanitization
// ============================================

/**
 * Sanitize input
 * @param string $input
 * @return string
 */
function sanitize(string $input): string
{
    return trim(strip_tags($input));
}

/**
 * Get POST input value
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function input(string $key, $default = null)
{
    return $_POST[$key] ?? $_GET[$key] ?? $default;
}

/**
 * Get all POST input
 * @return array
 */
function all_input(): array
{
    return $_POST;
}

/**
 * Check if request method is POST
 * @return bool
 */
function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Check if request method is GET
 * @return bool
 */
function is_get(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

// ============================================
// Flash Messages
// ============================================

/**
 * Set flash message
 * @param string $type (success, error, info, warning)
 * @param string $message
 */
function flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 * @return array|null
 */
function get_flash(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Check if flash message exists
 * @return bool
 */
function has_flash(): bool
{
    return isset($_SESSION['flash']);
}

// ============================================
// Logging Functions
// ============================================

/**
 * Log message to file
 * @param string $message
 * @param string $level (info, warning, error)
 */
function log_message(string $message, string $level = 'info'): void
{
    $logDir = config('paths.logs');
    $logFile = $logDir . '/app-' . date('Y-m-d') . '.log';

    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;

    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// ============================================
// Utility Functions
// ============================================

/**
 * Dump and die
 * @param mixed ...$vars
 */
function dd(...$vars): void
{
    echo '<pre>';
    foreach ($vars as $var) {
        var_dump($var);
    }
    echo '</pre>';
    die;
}

/**
 * Format date for display
 * @param string|null $date
 * @param string $format
 * @return string
 */
function format_date(?string $date, string $format = 'Y-m-d H:i:s'): string
{
    if (!$date) {
        return 'Never';
    }
    return date($format, strtotime($date));
}

/**
 * Get client IP address
 * @return string
 */
function get_client_ip(): string
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}

/**
 * Get user agent
 * @return string
 */
function get_user_agent(): string
{
    return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
}

// ============================================
// Activity Logging
// ============================================

/**
 * Log user activity
 * @param string $action Action performed (login, create_user, etc.)
 * @param string|null $entityType Entity type (user, app, domain, etc.)
 * @param int|null $entityId Entity ID
 * @param array|null $details Additional details
 * @return bool Success status
 */
function log_activity(
    string $action,
    ?string $entityType = null,
    ?int $entityId = null,
    ?array $details = null
): bool {
    // Only log if user is authenticated
    if (!auth_check()) {
        return false;
    }

    require_once __DIR__ . '/../Models/ActivityLog.php';

    return ActivityLog::log(
        auth_id(),
        $action,
        $entityType,
        $entityId,
        $details,
        get_client_ip(),
        get_user_agent()
    );
}
