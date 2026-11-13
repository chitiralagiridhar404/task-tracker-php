<?php
/**
 * Logout Controller
 * 
 * Safely invalidates session data and cookie, preventing session reuse.
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/csrf.php';
require_once __DIR__ . '/../src/helpers.php';

// Accept logout either via POST with CSRF or direct invocation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        // If CSRF token is invalid, log error and still complete logout for user safety
        error_log('Notice: Logout attempted with invalid CSRF token.');
    }
}

logout_user();

// Re-start a clean session to deliver the goodbye flash message
session_start();
set_flash('info', 'You have been logged out successfully.');
redirect('login.php');
