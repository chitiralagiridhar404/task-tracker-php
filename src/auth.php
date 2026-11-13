<?php
/**
 * Authentication and Authorization Module
 * 
 * Manages user sessions, role-based authorization guards,
 * and fine-grained task ownership checks.
 */

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

// Configure hardened session cookies before session initialization
if (session_status() === PHP_SESSION_NONE) {
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

    session_set_cookie_params([
        'lifetime' => 0,          // Expire when browser closes
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isSecure,  // Transmit over HTTPS when available
        'httponly' => true,       // Prevent JavaScript access to session cookie (mitigates XSS cookie theft)
        'samesite' => 'Lax',      // Mitigate Cross-Site Request Forgery (CSRF)
    ]);
    session_start();
}

/**
 * Returns current authenticated user array or null.
 *
 * @return array|null
 */
function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    return [
        'id'    => (int)$_SESSION['user_id'],
        'name'  => (string)($_SESSION['user_name'] ?? 'User'),
        'email' => (string)($_SESSION['user_email'] ?? ''),
        'role'  => (string)($_SESSION['user_role'] ?? 'user'),
    ];
}

/**
 * Checks if user is authenticated.
 *
 * @return bool
 */
function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

/**
 * Checks if current authenticated user has 'admin' role.
 *
 * @return bool
 */
function is_admin(): bool
{
    return is_logged_in() && ($_SESSION['user_role'] ?? '') === 'admin';
}

/**
 * Enforces that user must be logged in to access this page.
 * Redirects guest users to login page with a friendly notice.
 *
 * @return void
 */
function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('warning', 'Please sign in to access that page.');
        redirect('login.php');
    }
}

/**
 * Enforces that logged-in users cannot access guest-only pages (e.g. login/register).
 * Redirects authenticated users to the dashboard.
 *
 * @return void
 */
function require_guest(): void
{
    if (is_logged_in()) {
        redirect('dashboard.php');
    }
}

/**
 * Logs in a user, regenerates session ID to prevent session fixation attacks,
 * and sets essential session properties.
 *
 * @param array $user Database user row
 * @return void
 */
function login_user(array $user): void
{
    // Prevent session fixation attack: regenerate session identifier immediately upon login
    session_regenerate_id(true);

    $_SESSION['user_id']    = (int)$user['id'];
    $_SESSION['user_name']  = (string)$user['name'];
    $_SESSION['user_email'] = (string)$user['email'];
    $_SESSION['user_role']  = (string)$user['role'];
}

/**
 * Logs out the current user, clearing session storage and destroying the session.
 *
 * @return void
 */
function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

/**
 * Checks if a user is authorized to view a specific task.
 * Admin can view all tasks; regular users can only view their own.
 *
 * @param array $task
 * @param array $user
 * @return bool
 */
function can_view_task(array $task, array $user): bool
{
    if ($user['role'] === 'admin') {
        return true;
    }
    return (int)$task['user_id'] === (int)$user['id'];
}

/**
 * Checks if a user is authorized to edit the content (title/description/dates) of a task.
 * SPECIFICATION RULE: Admins CANNOT edit other users' task content. Only the owner can edit.
 *
 * @param array $task
 * @param array $user
 * @return bool
 */
function can_edit_task_content(array $task, array $user): bool
{
    return (int)$task['user_id'] === (int)$user['id'];
}

/**
 * Checks if a user is authorized to change the status of a task.
 * Only the owner can change status on their tasks.
 *
 * @param array $task
 * @param array $user
 * @return bool
 */
function can_change_task_status(array $task, array $user): bool
{
    return (int)$task['user_id'] === (int)$user['id'];
}

/**
 * Checks if a user is authorized to delete a task.
 * SPECIFICATION RULE: Admin can delete any task; normal user can delete ONLY their own.
 *
 * @param array $task
 * @param array $user
 * @return bool
 */
function can_delete_task(array $task, array $user): bool
{
    if ($user['role'] === 'admin') {
        return true;
    }
    return (int)$task['user_id'] === (int)$user['id'];
}
