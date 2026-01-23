#!/usr/bin/env php
<?php

/**
 * Create Root User CLI Script
 * Chwezi Server Manager
 */

if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

// Check arguments
if ($argc < 6) {
    echo "Usage: php create-root-user.php <username> <email> <password> <db_name> <db_user> <db_pass>\n";
    exit(1);
}

$username = $argv[1];
$email = $argv[2];
$password = $argv[3];
$dbName = $argv[4];
$dbUser = $argv[5];
$dbPass = $argv[6];

// Validate inputs
if (empty($username) || empty($email) || empty($password)) {
    echo "Error: Username, email, and password are required.\n";
    exit(1);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "Error: Invalid email address.\n";
    exit(1);
}

if (strlen($password) < 8) {
    echo "Error: Password must be at least 8 characters.\n";
    exit(1);
}

try {
    // Connect to database
    $dsn = "mysql:host=localhost;dbname={$dbName};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    // Get root role ID
    $stmt = $pdo->query("SELECT id FROM roles WHERE slug = 'root'");
    $rootRole = $stmt->fetch();

    if (!$rootRole) {
        echo "Error: Root role not found in database. Please run migrations first.\n";
        exit(1);
    }

    // Check if user already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);

    if ($stmt->fetch()) {
        echo "Error: A user with this username or email already exists.\n";
        exit(1);
    }

    // Create root user
    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password, role_id, is_root, status)
        VALUES (?, ?, ?, ?, 1, 'active')
    ");

    $stmt->execute([$username, $email, $hashedPassword, $rootRole['id']]);

    echo "Root user created successfully!\n";
    echo "Username: {$username}\n";
    echo "Email: {$email}\n";

    exit(0);

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
