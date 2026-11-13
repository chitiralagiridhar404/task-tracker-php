<?php
/**
 * Helper Functions Module
 * 
 * Contains security-focused utilities for HTML output escaping,
 * URL resolution, flash messaging, and badge rendering.
 */

declare(strict_types=1);

/**
 * Escapes strings for safe HTML output to prevent Cross-Site Scripting (XSS).
 *
 * @param string|null $value
 * @return string
 */
function e(?string $value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Retrieves configuration values.
 *
 * @param string $key Dot-notated key e.g. 'app.base_url'
 * @param mixed $default
 * @return mixed
 */
function config(string $key, $default = null)
{
    static $config = null;
    if ($config === null) {
        $file = __DIR__ . '/../config.php';
        if (!file_exists($file)) {
            $file = __DIR__ . '/../config.sample.php';
        }
        $config = file_exists($file) ? require $file : [];
    }

    $segments = explode('.', $key);
    $current = $config;
    foreach ($segments as $segment) {
        if (!is_array($current) || !array_key_exists($segment, $current)) {
            return $default;
        }
        $current = $current[$segment];
    }
    return $current;
}

/**
 * Builds an absolute or application-relative URL.
 * Detects running environment dynamically while respecting config.
 *
 * @param string $path
 * @return string
 */
function url(string $path = ''): string
{
    $baseUrl = config('app.base_url', '/task-tracker/public');
    $baseUrl = rtrim($baseUrl, '/');
    $cleanPath = ltrim($path, '/');
    return $cleanPath === '' ? $baseUrl : $baseUrl . '/' . $cleanPath;
}

/**
 * Redirects browser safely to a destination URL and halts execution.
 *
 * @param string $path
 * @return void
 */
function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

/**
 * Sets a flash message to be displayed on the next page view.
 *
 * @param string $type Bootstrap alert type: 'success', 'danger', 'warning', 'info'
 * @param string $message
 * @return void
 */
function set_flash(string $type, string $message): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['flashes']) || !is_array($_SESSION['flashes'])) {
        $_SESSION['flashes'] = [];
    }
    $_SESSION['flashes'][] = [
        'type'    => $type,
        'message' => $message,
    ];
}

/**
 * Retrieves and clears all pending flash messages from session.
 *
 * @return array
 */
function get_flashes(): array
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $flashes = $_SESSION['flashes'] ?? [];
    unset($_SESSION['flashes']);
    return is_array($flashes) ? $flashes : [];
}

/**
 * Renders all flash messages as Bootstrap 5 dismissible alerts.
 *
 * @return void
 */
function render_flashes(): void
{
    $flashes = get_flashes();
    if (empty($flashes)) {
        return;
    }

    foreach ($flashes as $flash) {
        $type = e($flash['type'] ?? 'info');
        $message = e($flash['message'] ?? '');
        echo <<<HTML
        <div class="alert alert-{$type} alert-dismissible fade show shadow-sm mb-3" role="alert">
            <span>{$message}</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
HTML;
    }
}

/**
 * Returns HTML badge for task status.
 *
 * @param string $status
 * @return string
 */
function status_badge(string $status): string
{
    $map = [
        'Pending'     => 'bg-warning text-dark',
        'In Progress' => 'bg-info text-dark',
        'Completed'   => 'bg-success text-white',
    ];
    $cls = $map[$status] ?? 'bg-secondary text-white';
    return '<span class="badge ' . $cls . '">' . e($status) . '</span>';
}

/**
 * Returns HTML badge for task priority.
 *
 * @param string|null $priority
 * @return string
 */
function priority_badge(?string $priority): string
{
    $priority = $priority ?? 'Medium';
    $map = [
        'Low'    => 'bg-secondary text-white',
        'Medium' => 'bg-primary text-white',
        'High'   => 'bg-danger text-white',
    ];
    $cls = $map[$priority] ?? 'bg-light text-dark';
    return '<span class="badge ' . $cls . '">' . e($priority) . '</span>';
}

/**
 * Formats a YYYY-MM-DD date string into a user-friendly format.
 *
 * @param string|null $date
 * @return string
 */
function format_date(?string $date): string
{
    if (!$date || $date === '0000-00-00') {
        return '<span class="text-muted">None</span>';
    }
    $time = strtotime($date);
    if (!$time) {
        return e($date);
    }
    $formatted = date('M j, Y', $time);
    $today = strtotime('today');
    // If due date has passed and task is not finished
    if ($time < $today) {
        return '<span class="text-danger fw-semibold" title="Overdue">' . e($formatted) . ' <i class="bi bi-exclamation-circle"></i></span>';
    }
    return '<span>' . e($formatted) . '</span>';
}
