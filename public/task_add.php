<?php
/**
 * Create Task Controller & View
 * 
 * Handles new task submission with strict backend and frontend validation,
 * CSRF token verification, and parameterized SQL insertion.
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/csrf.php';
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/validation.php';

require_login();

$user = current_user();
$errors = [];

$title       = '';
$description = '';
$status      = 'Pending';
$priority    = 'Medium';
$dueDate     = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Enforce CSRF verification
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors['general'] = 'Invalid or expired CSRF security token. Please try again.';
    } else {
        $title       = trim((string)($_POST['title'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $status      = trim((string)($_POST['status'] ?? 'Pending'));
        $priority    = trim((string)($_POST['priority'] ?? 'Medium'));
        $dueDate     = trim((string)($_POST['due_date'] ?? ''));

        // 2. Comprehensive backend validation
        $errors = validate_task($_POST);

        if (empty($errors)) {
            $pdo = get_db();

            // 3. Prepared statement ensures all input is bound safely
            $stmt = $pdo->prepare('
                INSERT INTO tasks (user_id, title, description, status, priority, due_date)
                VALUES (?, ?, ?, ?, ?, ?)
            ');

            $dbDueDate = ($dueDate !== '') ? $dueDate : null;
            $dbDesc    = ($description !== '') ? $description : null;

            $stmt->execute([
                $user['id'],
                $title,
                $dbDesc,
                $status,
                $priority,
                $dbDueDate
            ]);

            set_flash('success', 'Task "' . $title . '" has been added successfully!');
            redirect('dashboard.php');
        }
    }
}

$pageTitle = 'Add New Task';
require_once __DIR__ . '/../templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-7">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0 fw-bold">
                    <i class="bi bi-plus-circle text-primary me-2"></i>Create New Task
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

                <form action="<?= url('task_add.php') ?>" method="POST" class="needs-validation" novalidate>
                    <?= csrf_field() ?>

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
                               placeholder="e.g. Complete quarterly security compliance checklist" 
                               required 
                               maxlength="150" 
                               autofocus>
                        <?php if (isset($errors['title'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['title']) ?></div>
                        <?php else: ?>
                            <div class="form-text">A concise summary of the task (max 150 characters).</div>
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
                                  maxlength="1000" 
                                  placeholder="Provide any detailed instructions, steps, or context..."><?= e($description) ?></textarea>
                        <?php if (isset($errors['description'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['description']) ?></div>
                        <?php else: ?>
                            <div class="form-text">Optional details (up to 1000 characters).</div>
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
                            <i class="bi bi-check-lg me-1"></i> Save Task
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
