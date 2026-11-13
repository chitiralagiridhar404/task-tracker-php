<?php
/**
 * Application Entry Point / Router
 * 
 * Directs authenticated users straight to dashboard,
 * or guest visitors to the login screen.
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/helpers.php';

if (is_logged_in()) {
    redirect('dashboard.php');
} else {
    redirect('login.php');
}
