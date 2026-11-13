<?php
/**
 * Edit Task Controller & View
 * 
 * Enforces strict backend authorization rules:
 * - Regular users can ONLY edit their own tasks (WHERE id = ? AND user_id = ?).
 * - Admins cannot edit other users' tasks (enforcing the assignment specification).
 * - URL and POST parameter tampering attempts are safely blocked with 403/404.
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/csrf.php';
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/validation.php';

require_login();

$user = current_user();
$pdo  = get_db();
$errors = [];

// Determine task ID from GET or POST
$taskId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) 
       ?: filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$taskId || $taskId <= 0) {
    http_response_code(404);
    set_flash('danger', 'Invalid task identifier specified.');
    redirect('dashboard.php');
}

// ---------------------------------------------------------------------
// 1. Fetch Task and Perform Backend Authorization Checks
// ---------------------------------------------------------------------
$stmt = $pdo->prepare('SELECT * FROM tasks WHERE id = ? LIMIT 1');
$stmt->execute([$taskId]);
$task = $stmt->fetch();

if (!$task) {
    http_response_code(404);
    set_flash('danger', 'Task #' . $taskId . ' was not found.');
    redirect('dashboard.php');
}

// Strictly enforce: ONLY the task owner may edit task content!
if (!can_edit_task_content($task, $user)) {
    http_response_code(403);
    if ($user['role'] === 'admin') {
        set_flash('danger', 'Access Denied: Administrators can view and delete all tasks, but cannot edit other users\' task content.');
    } else {
        set_flash('danger', 'Access Denied: You do not have permission to edit this task.');
    }
    redirect('dashboard.php');
}

// ---------------------------------------------------------------------
// 2. Handle POST Update Request
// ---------------------------------------------------------------------
$title       = (string)($task['title'] ?? '');
$description = (string)($task['description'] ?? '');
$status      = (string)($task['status'] ?? 'Pending');
$priority    = (string)($task['priority'] ?? 'Medium');
$dueDate     = (string)($task['due_date'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // A. Validate CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors['general'] = 'Invalid or expired CSRF security token. Please try again.';
    } else {
        $title       = trim((string)($_POST['title'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $status      = trim((string)($_POST['status'] ?? 'Pending'));
        $priority    = trim((string)($_POST['priority'] ?? 'Medium'));
        $dueDate     = trim((string)($_POST['due_date'] ?? ''));

        // B. Comprehensive backend validation
        $errors = validate_task($_POST);

        if (empty($errors)) {
            // C. UPDATE query strictly bounded by user_id for bulletproof ownership enforcement
            $updateStmt = $pdo->prepare('
                UPDATE tasks 
                SET title = ?, description = ?, status = ?, priority = ?, due_date = ?
                WHERE id = ? AND user_id = ?
            ');

            $dbDueDate = ($dueDate !== '') ? $dueDate : null;
            $dbDesc    = ($description !== '') ? $description : null;

            $updateStmt->execute([
                $title,
                $dbDesc,
                $status,
                $priority,
                $dbDueDate,
                $taskId,
                $user['id']
            ]);

            set_flash('success', 'Task #' . $taskId . ' updated successfully!');
            redirect('dashboard.php');
        }
    }
}

$pageTitle = 'Edit Task #' . $taskId;
require_once __DIR__ . '/../templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-7">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0 fw-bold">
                    <i class="bi bi-pencil-square text-primary me-2"></i>Edit Task #<?= (int)$task['id'] ?>
                </h5>
                <a href="<?= url('dashboard.php') ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
                </a>
            </div>

            <div class="card-body p-4">
                <?php if (!empty($errors['general'])): ?>
                    <div class="alert alert-danger" role="alert">
                        <?= e($errors['general']) ?>
                    </div>
                <?php endif; ?>

                <form action="<?= url('task_edit.php?id=' . (int)$task['id']) ?>" method="POST" class="needs-validation" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$task['id'] ?>">

                    <!-- Task Title -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <label for="task-title" class="form-label fw-semibold">
                                Task Title <span class="text-danger">*</span>
                            </label>
                            <span id="title-counter" class="char-counter">0 / 150 characters</span>
                        </div>
                        <input type="text" 
                               class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>" 
                               id="task-title" 
                               name="title" 
                               value="<?= e($title) ?>" 
                               required 
                               maxlength="150" 
                               autofocus>
                        <?php if (isset($errors['title'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['title']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Task Description -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <label for="task-desc" class="form-label fw-semibold">Description</label>
                            <span id="desc-counter" class="char-counter">0 / 1000 characters</span>
                        </div>
                        <textarea class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>" 
                                  id="task-desc" 
                                  name="description" 
                                  rows="4" 
                                  maxlength="1000"><?= e($description) ?></textarea>
                        <?php if (isset($errors['description'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['description']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="row g-3 mb-4">
                        <!-- Status -->
                        <div class="col-md-4">
                            <label for="status" class="form-label fw-semibold">Status</label>
                            <select class="form-select <?= isset($errors['status']) ? 'is-invalid' : '' ?>" id="status" name="status">
                                <option value="Pending" <?= ($status === 'Pending') ? 'selected' : '' ?>>Pending</option>
                                <option value="In Progress" <?= ($status === 'In Progress') ? 'selected' : '' ?>>In Progress</option>
                                <option value="Completed" <?= ($status === 'Completed') ? 'selected' : '' ?>>Completed</option>
                            </select>
                            <?php if (isset($errors['status'])): ?>
                                <div class="invalid-feedback d-block"><?= e($errors['status']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Priority -->
                        <div class="col-md-4">
                            <label for="priority" class="form-label fw-semibold">Priority</label>
                            <select class="form-select <?= isset($errors['priority']) ? 'is-invalid' : '' ?>" id="priority" name="priority">
                                <option value="Low" <?= ($priority === 'Low') ? 'selected' : '' ?>>Low</option>
                                <option value="Medium" <?= ($priority === 'Medium') ? 'selected' : '' ?>>Medium</option>
                                <option value="High" <?= ($priority === 'High') ? 'selected' : '' ?>>High</option>
                            </select>
                            <?php if (isset($errors['priority'])): ?>
                                <div class="invalid-feedback d-block"><?= e($errors['priority']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Due Date -->
                        <div class="col-md-4">
                            <label for="due_date" class="form-label fw-semibold">Due Date</label>
                            <input type="date" 
                                   class="form-control <?= isset($errors['due_date']) ? 'is-invalid' : '' ?>" 
                                   id="due_date" 
                                   name="due_date" 
                                   value="<?= e($dueDate) ?>">
                            <?php if (isset($errors['due_date'])): ?>
                                <div class="invalid-feedback d-block"><?= e($errors['due_date']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                        <a href="<?= url('dashboard.php') ?>" class="btn btn-outline-secondary px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold shadow-sm">
                            <i class="bi bi-save me-1"></i> Update Task
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
