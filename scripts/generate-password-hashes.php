#!/usr/bin/env php
<?php

/**
 * Generate Password Hashes
 * Creates bcrypt hashes for test user passwords
 */

$passwords = [
    'Wirtz2025@' => 'Root user password',
    'Admin123!' => 'Admin user password',
    'Dev123!' => 'Developer user password',
    'View123!' => 'Viewer user password',
];

echo "Generating password hashes...\n\n";

foreach ($passwords as $password => $description) {
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    echo "Password: {$password}\n";
    echo "Description: {$description}\n";
    echo "Hash: {$hash}\n";
    echo str_repeat('-', 80) . "\n\n";
}

echo "Done!\n";
