<?php
/**
 * Task Deletion Controller (POST Only)
 * 
 * Enforces:
 * 1. HTTP POST method enforcement (rejects unsafe GET requests).
 * 2. Cryptographic CSRF token validation.
 * 3. Role-based ownership verification:
 *    - Regular users can only delete tasks they own (WHERE id = ? AND user_id = ?).
 *    - Admins are permitted to delete any task.
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/csrf.php';
require_once __DIR__ . '/../src/helpers.php';

require_login();

// Rule: Delete MUST be a POST request, never a GET link
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    set_flash('danger', 'Method Not Allowed: Delete action must be submitted via a secure POST form.');
    redirect('dashboard.php');
}

// Enforce CSRF verification
validate_csrf_or_die();

$user = current_user();
$pdo  = get_db();

$taskId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$taskId || $taskId <= 0) {
    http_response_code(400);
    set_flash('danger', 'Invalid task identifier provided for deletion.');
    redirect('dashboard.php');
}

// 1. Fetch task to check existence and current ownership
$stmt = $pdo->prepare('SELECT id, user_id, title FROM tasks WHERE id = ? LIMIT 1');
$stmt->execute([$taskId]);
$task = $stmt->fetch();

if (!$task) {
    http_response_code(404);
    set_flash('danger', 'Task #' . $taskId . ' does not exist or has already been removed.');
    redirect('dashboard.php');
}

// 2. Authorization check
if (!can_delete_task($task, $user)) {
    http_response_code(403);
    set_flash('danger', 'Access Denied: You do not have permission to delete task #' . $taskId . '.');
    redirect('dashboard.php');
}

// 3. Perform deletion with strict query constraints
if ($user['role'] === 'admin') {
    // Admin deletion privilege across system
    $deleteStmt = $pdo->prepare('DELETE FROM tasks WHERE id = ?');
    $deleteStmt->execute([$taskId]);
} else {
    // Strict regular user ownership constraint
    $deleteStmt = $pdo->prepare('DELETE FROM tasks WHERE id = ? AND user_id = ?');
    $deleteStmt->execute([$taskId, $user['id']]);
}

if ($deleteStmt->rowCount() > 0) {
    set_flash('success', 'Task "' . $task['title'] . '" was deleted successfully.');
} else {
    set_flash('warning', 'Task could not be deleted or was already removed.');
}

redirect('dashboard.php');
