<?php
/**
 * Configuration Sample File
 * 
 * Instructions:
 * 1. Copy this file to 'config.php' in the same directory.
 * 2. Update the database credentials to match your local XAMPP environment.
 * 3. Never commit 'config.php' to version control (it is ignored in .gitignore).
 */

return [
    'db' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'name'     => 'task_tracker',
        'user'     => 'root',
        'password' => '', // Default XAMPP MySQL password is empty
        'charset'  => 'utf8mb4',
    ],
    'app' => [
        'name'     => 'Task Tracker',
        // Base URL relative to web root. In default XAMPP, this is '/task-tracker/public'
        'base_url' => '/task-tracker/public',
        'env'      => 'development', // 'development' or 'production'
    ],
    'session' => [
        'name'     => 'task_tracker_session',
        'lifetime' => 0, // Until browser closes
    ],
];
