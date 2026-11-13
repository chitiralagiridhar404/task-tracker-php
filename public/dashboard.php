<?php
/**
 * Dashboard View & Controller
 * 
 * Main task management interface. Supports role-based filtering,
 * title search (LIKE prepared statement), status filter, pagination,
 * summary metrics, and ownership-enforced actions.
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/csrf.php';
require_once __DIR__ . '/../src/helpers.php';

// Route Guard: Only authenticated users can access the dashboard
require_login();

$user    = current_user();
$isAdmin = is_admin();
$pdo     = get_db();

// -------------------------------------------------------------
// 1. Filter, Search, and Pagination Parameters
// -------------------------------------------------------------
$search       = trim((string)($_GET['search'] ?? ''));
$statusFilter = trim((string)($_GET['status'] ?? ''));
$allowedStatuses = ['Pending', 'In Progress', 'Completed'];

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset  = ($page - 1) * $perPage;

// -------------------------------------------------------------
// 2. Compute Summary Metrics (Bonus Feature)
// Regular users see stats for their own tasks; Admins see system-wide stats.
// -------------------------------------------------------------
if ($isAdmin) {
    $statsStmt = $pdo->query("
        SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) AS in_progress,
            SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed
        FROM tasks
    ");
    $stats = $statsStmt->fetch() ?: [];
} else {
    $statsStmt = $pdo->prepare("
        SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) AS in_progress,
            SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed
        FROM tasks
        WHERE user_id = ?
    ");
    $statsStmt->execute([$user['id']]);
    $stats = $statsStmt->fetch() ?: [];
}

$statTotal      = (int)($stats['total'] ?? 0);
$statPending    = (int)($stats['pending'] ?? 0);
$statInProgress = (int)($stats['in_progress'] ?? 0);
$statCompleted  = (int)($stats['completed'] ?? 0);

// -------------------------------------------------------------
// 3. Build Filtered Tasks Query with Strict Prepared Statements
// -------------------------------------------------------------
$whereClauses = [];
$params       = [];

// Ownership enforcement at query level
if (!$isAdmin) {
    $whereClauses[] = 'tasks.user_id = ?';
    $params[] = $user['id'];
}

// Search by title (Level 2: LIKE with prepared statement)
if ($search !== '') {
    $whereClauses[] = 'tasks.title LIKE ?';
    $params[] = '%' . $search . '%';
}

// Status filter
if (in_array($statusFilter, $allowedStatuses, true)) {
    $whereClauses[] = 'tasks.status = ?';
    $params[] = $statusFilter;
}

$whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// Count total matching tasks for pagination
$countSql = "SELECT COUNT(*) FROM tasks {$whereSql}";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();
$totalPages   = max(1, (int)ceil($totalRecords / $perPage));

// Fetch paginated tasks with owner details for admin auditability
$querySql = "
    SELECT 
        tasks.id,
        tasks.user_id,
        tasks.title,
        tasks.description,
        tasks.status,
        tasks.priority,
        tasks.due_date,
        tasks.created_at,
        users.name AS owner_name,
        users.email AS owner_email
    FROM tasks
    INNER JOIN users ON tasks.user_id = users.id
    {$whereSql}
    ORDER BY tasks.id DESC
    LIMIT " . (int)$perPage . " OFFSET " . (int)$offset;

$queryStmt = $pdo->prepare($querySql);
$queryStmt->execute($params);
$tasks = $queryStmt->fetchAll();

$pageTitle = 'Dashboard';
require_once __DIR__ . '/../templates/header.php';
?>

<!-- Header Title & Action Button -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h2 class="fw-bold mb-1">
            <i class="bi bi-list-check text-primary me-2"></i>Task Dashboard
        </h2>
        <p class="text-muted mb-0 small">
            <?php if ($isAdmin): ?>
                <span class="badge bg-danger text-uppercase me-1">Admin Mode</span>
                Viewing system-wide tasks from all registered users.
            <?php else: ?>
                Manage and track your individual assignments and deadlines.
            <?php endif; ?>
        </p>
    </div>
    <div>
        <a href="<?= url('task_add.php') ?>" class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm">
            <i class="bi bi-plus-lg"></i>
            <span>Create New Task</span>
        </a>
    </div>
</div>

<!-- Level 2 / Bonus: Dashboard Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card card-stat shadow-sm border-0 border-start border-primary h-100">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Total Tasks</div>
                        <div class="fs-3 fw-bold text-dark mt-1"><?= $statTotal ?></div>
                    </div>
                    <div class="rounded-3 bg-primary bg-opacity-10 p-2 text-primary">
                        <i class="bi bi-collection fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-stat shadow-sm border-0 border-start border-warning h-100">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Pending</div>
                        <div class="fs-3 fw-bold text-dark mt-1"><?= $statPending ?></div>
                    </div>
                    <div class="rounded-3 bg-warning bg-opacity-10 p-2 text-warning">
                        <i class="bi bi-clock-history fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-stat shadow-sm border-0 border-start border-info h-100">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">In Progress</div>
                        <div class="fs-3 fw-bold text-dark mt-1"><?= $statInProgress ?></div>
                    </div>
                    <div class="rounded-3 bg-info bg-opacity-10 p-2 text-info">
                        <i class="bi bi-arrow-repeat fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-stat shadow-sm border-0 border-start border-success h-100">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Completed</div>
                        <div class="fs-3 fw-bold text-dark mt-1"><?= $statCompleted ?></div>
                    </div>
                    <div class="rounded-3 bg-success bg-opacity-10 p-2 text-success">
                        <i class="bi bi-check2-circle fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Level 2: Search Box & Status Filter Bar -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <form action="<?= url('dashboard.php') ?>" method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                    <input type="text" 
                           name="search" 
                           class="form-control" 
                           placeholder="Search tasks by title..." 
                           value="<?= e($search) ?>" 
                           maxlength="150">
                </div>
            </div>

            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-funnel"></i></span>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="Pending" <?= ($statusFilter === 'Pending') ? 'selected' : '' ?>>Pending</option>
                        <option value="In Progress" <?= ($statusFilter === 'In Progress') ? 'selected' : '' ?>>In Progress</option>
                        <option value="Completed" <?= ($statusFilter === 'Completed') ? 'selected' : '' ?>>Completed</option>
                    </select>
                </div>
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="bi bi-filter me-1"></i>Apply Filter
                </button>
                <?php if ($search !== '' || $statusFilter !== ''): ?>
                    <a href="<?= url('dashboard.php') ?>" class="btn btn-outline-secondary" title="Reset Filters">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Task Table -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0 fw-bold">
            <i class="bi bi-table me-2 text-muted"></i>Tasks List
            <span class="badge bg-light text-muted border ms-2"><?= $totalRecords ?> found</span>
        </h5>
        <?php if ($totalRecords > 0): ?>
            <span class="text-muted small">Page <?= $page ?> of <?= $totalPages ?></span>
        <?php endif; ?>
    </div>

    <div class="card-body p-0">
        <?php if (empty($tasks)): ?>
            <div class="text-center py-5">
                <div class="d-inline-flex align-items-center justify-content-center bg-light text-muted rounded-circle mb-3" style="width: 64px; height: 64px;">
                    <i class="bi bi-inbox fs-1"></i>
                </div>
                <h5 class="fw-semibold">No tasks found</h5>
                <p class="text-muted small mb-3">
                    <?= ($search !== '' || $statusFilter !== '') ? 'Try adjusting your search query or status filter.' : 'You have not created any tasks yet.' ?>
                </p>
                <a href="<?= url('task_add.php') ?>" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Create Your First Task
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 25%;">Title</th>
                            <th style="width: 15%;">Status</th>
                            <th style="width: 10%;">Priority</th>
                            <th style="width: 12%;">Due Date</th>
                            <?php if ($isAdmin): ?>
                                <th style="width: 15%;">Owner</th>
                            <?php endif; ?>
                            <th style="width: 18%;" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $task): ?>
                            <?php
                                $isOwner = ((int)$task['user_id'] === (int)$user['id']);
                                $canEdit = can_edit_task_content($task, $user);
                                $canDelete = can_delete_task($task, $user);
                            ?>
                            <tr>
                                <td class="text-muted small">#<?= (int)$task['id'] ?></td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= e($task['title']) ?></div>
                                    <?php if (!empty($task['description'])): ?>
                                        <div class="text-muted small text-truncate" style="max-width: 320px;" title="<?= e($task['description']) ?>">
                                            <?= e($task['description']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($isOwner): ?>
                                        <!-- Level 2: Inline Status Dropdown with CSRF protection -->
                                        <form action="<?= url('task_status.php') ?>" method="POST" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int)$task['id'] ?>">
                                            <select name="status" class="form-select form-select-sm status-select js-status-dropdown" title="Change Status">
                                                <option value="Pending" <?= ($task['status'] === 'Pending') ? 'selected' : '' ?>>Pending</option>
                                                <option value="In Progress" <?= ($task['status'] === 'In Progress') ? 'selected' : '' ?>>In Progress</option>
                                                <option value="Completed" <?= ($task['status'] === 'Completed') ? 'selected' : '' ?>>Completed</option>
                                            </select>
                                        </form>
                                    <?php else: ?>
                                        <!-- For non-owner admin: Display badge only (admin cannot edit other users' content/status) -->
                                        <?= status_badge($task['status']) ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= priority_badge($task['priority']) ?></td>
                                <td><?= format_date($task['due_date']) ?></td>
                                <?php if ($isAdmin): ?>
                                    <td>
                                        <div class="small fw-semibold"><?= e($task['owner_name']) ?></div>
                                        <div class="text-muted" style="font-size: 0.75rem;"><?= e($task['owner_email']) ?></div>
                                    </td>
                                <?php endif; ?>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <?php if ($canEdit): ?>
                                            <a href="<?= url('task_edit.php?id=' . (int)$task['id']) ?>" 
                                               class="btn btn-outline-primary" 
                                               title="Edit Task">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                        <?php elseif ($isAdmin): ?>
                                            <!-- Visual indicator clarifying Admin role constraints -->
                                            <button type="button" class="btn btn-outline-secondary disabled" title="Admins cannot edit other users' tasks" disabled>
                                                <i class="bi bi-lock"></i>
                                            </button>
                                        <?php endif; ?>

                                        <?php if ($canDelete): ?>
                                            <!-- Level 1 & 3: POST-only Delete with CSRF and JS Confirm dialog -->
                                            <form action="<?= url('task_delete.php') ?>" 
                                                  method="POST" 
                                                  class="d-inline js-confirm-delete" 
                                                  data-task-title="<?= e($task['title']) ?>">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= (int)$task['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger" title="Delete Task">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bonus: Pagination Links Preserving Search & Filter -->
    <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white py-3 border-top d-flex justify-content-between align-items-center">
            <span class="text-muted small">
                Showing <?= min($offset + 1, $totalRecords) ?> to <?= min($offset + $perPage, $totalRecords) ?> of <?= $totalRecords ?> tasks
            </span>
            <nav aria-label="Tasks pagination">
                <ul class="pagination pagination-sm mb-0">
                    <?php
                        $queryParams = [];
                        if ($search !== '') $queryParams['search'] = $search;
                        if ($statusFilter !== '') $queryParams['status'] = $statusFilter;

                        if (!function_exists('build_page_link')) {
                            function build_page_link(int $targetPage, array $params): string {
                                $params['page'] = $targetPage;
                                return url('dashboard.php?' . http_build_query($params));
                            }
                        }
                    ?>
                    <!-- Previous Page -->
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= build_page_link($page - 1, $queryParams) ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>

                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <li class="page-item <?= ($p === $page) ? 'active' : '' ?>">
                            <a class="page-link" href="<?= build_page_link($p, $queryParams) ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>

                    <!-- Next Page -->
                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= build_page_link($page + 1, $queryParams) ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
