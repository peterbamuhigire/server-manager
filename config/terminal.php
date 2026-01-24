<?php

/**
 * Terminal Configuration
 */

return [
    // Command execution timeout in seconds
    'timeout' => 120,

    // Default working directory for commands
    'working_dir' => '/var/www',

    // Maximum command history to keep per user
    'history_limit' => 1000,

    // Cleanup old history after N days
    'cleanup_days' => 30,
];
