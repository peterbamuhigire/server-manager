<?php

return [
    'name' => $_ENV['APP_NAME'] ?? 'Chwezi Server Manager',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'url' => $_ENV['APP_URL'] ?? 'http://localhost',
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'Africa/Kampala',

    // Session configuration
    'session' => [
        'lifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 30), // minutes
        'name' => $_ENV['SESSION_NAME'] ?? 'chwezi_sm_session',
        'secure' => filter_var($_ENV['SESSION_SECURE'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'httponly' => true,
        'samesite' => 'Lax',
    ],

    // Security
    'csrf_enabled' => filter_var($_ENV['CSRF_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN),

    // Password hashing
    'password' => [
        'cost' => 12,
    ],
];
