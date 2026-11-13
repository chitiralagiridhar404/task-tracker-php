<?php
/**
 * Inline Task Status Controller (POST Only)
 * 
 * Allows users to swiftly transition task status from the dashboard table.
 * Enforces CSRF token, valid status enum check, and strict task ownership.
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/csrf.php';
require_once __DIR__ . '/../src/helpers.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    set_flash('danger', 'Method Not Allowed: Status update must be submitted via POST.');
    redirect('dashboard.php');
}

// Enforce CSRF token
validate_csrf_or_die();

$user = current_user();
$pdo  = get_db();

$taskId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$status = trim((string)($_POST['status'] ?? ''));

$allowedStatuses = ['Pending', 'In Progress', 'Completed'];

if (!$taskId || $taskId <= 0) {
    http_response_code(400);
    set_flash('danger', 'Invalid task ID provided.');
    redirect('dashboard.php');
}

if (!in_array($status, $allowedStatuses, true)) {
    http_response_code(400);
    set_flash('danger', 'Invalid task status selected.');
    redirect('dashboard.php');
}

// 1. Fetch task to check existence and ownership
$stmt = $pdo->prepare('SELECT id, user_id, title FROM tasks WHERE id = ? LIMIT 1');
$stmt->execute([$taskId]);
$task = $stmt->fetch();

if (!$task) {
    http_response_code(404);
    set_flash('danger', 'Task #' . $taskId . ' does not exist.');
    redirect('dashboard.php');
}

// 2. Enforce ownership: only the owner can modify task status
if (!can_change_task_status($task, $user)) {
    http_response_code(403);
    set_flash('danger', 'Access Denied: You cannot modify status on another user\'s task.');
    redirect('dashboard.php');
}

// 3. Update query bound to both ID and user_id
$updateStmt = $pdo->prepare('UPDATE tasks SET status = ? WHERE id = ? AND user_id = ?');
$updateStmt->execute([$status, $taskId, $user['id']]);

set_flash('success', 'Status for "' . $task['title'] . '" updated to ' . $status . '.');
redirect('dashboard.php');
