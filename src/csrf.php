<?php
/**
 * CSRF (Cross-Site Request Forgery) Protection Module
 * 
 * Provides cryptographically secure token generation and constant-time validation.
 * All state-changing requests (POST) must supply and pass this token check.
 */

declare(strict_types=1);

/**
 * Returns the current CSRF token stored in the session, generating one if absent.
 *
 * @return string
 */
function csrf_token(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        // Generate a 32-byte cryptographically secure random token (64 hex characters)
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Generates an HTML hidden input tag containing the active CSRF token.
 *
 * @return string
 */
function csrf_field(): string
{
    $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Validates a submitted CSRF token using hash_equals to avoid timing attacks.
 *
 * @param string|null $submittedToken
 * @return bool
 */
function verify_csrf_token(?string $submittedToken): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $sessionToken = $_SESSION['csrf_token'] ?? '';
    if (!is_string($sessionToken) || $sessionToken === '') {
        return false;
    }

    if (!is_string($submittedToken) || $submittedToken === '') {
        return false;
    }

    // Constant-time string comparison prevents side-channel timing attacks
    return hash_equals($sessionToken, $submittedToken);
}

/**
 * Helper to enforce CSRF validation on POST endpoints.
 * Halts execution with 403 Forbidden if token validation fails.
 *
 * @return void
 */
function validate_csrf_or_die(): void
{
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!verify_csrf_token($token)) {
        http_response_code(403);
        if (function_exists('set_flash')) {
            set_flash('danger', 'Security validation failed: Invalid or expired CSRF token. Please try again.');
        }
        if (function_exists('redirect')) {
            redirect('dashboard.php');
        }
        die('403 Forbidden: Invalid or missing CSRF token.');
    }
}
