<?php
/**
 * Header Template
 * 
 * Includes Bootstrap 5 via CDN, custom stylesheets, responsive navbar,
 * user authentication status badge, and flashes.
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/csrf.php';

$user = current_user();
$pageTitle = $pageTitle ?? 'Task Tracker';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | Task Tracker</title>
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Custom Application CSS -->
    <link href="<?= url('assets/style.css') ?>" rel="stylesheet">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?= url(is_logged_in() ? 'dashboard.php' : 'login.php') ?>">
            <i class="bi bi-check2-square text-primary fs-4"></i>
            <span>Task Tracker</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php if (is_logged_in()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'dashboard.php') ? 'active fw-semibold' : '' ?>" href="<?= url('dashboard.php') ?>">
                            <i class="bi bi-speedometer2 me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'task_add.php') ? 'active fw-semibold' : '' ?>" href="<?= url('task_add.php') ?>">
                            <i class="bi bi-plus-circle me-1"></i>New Task
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <ul class="navbar-nav ms-auto align-items-lg-center gap-2">
                <?php if (is_logged_in()): ?>
                    <li class="nav-item">
                        <span class="navbar-text d-inline-flex align-items-center gap-2 text-white-50">
                            <i class="bi bi-person-circle fs-5 text-light"></i>
                            <span class="text-white fw-medium"><?= e($user['name'] ?? 'User') ?></span>
                            <?php if (($user['role'] ?? '') === 'admin'): ?>
                                <span class="badge bg-danger text-uppercase" title="Administrator Role">Admin</span>
                            <?php else: ?>
                                <span class="badge bg-secondary text-uppercase" title="Standard User Role">User</span>
                            <?php endif; ?>
                        </span>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <form action="<?= url('logout.php') ?>" method="POST" class="d-inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-outline-light btn-sm d-flex align-items-center gap-1">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </button>
                        </form>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'login.php') ? 'active' : '' ?>" href="<?= url('login.php') ?>">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-primary btn-sm px-3" href="<?= url('register.php') ?>">
                            <i class="bi bi-person-plus me-1"></i>Register
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Main Container -->
<main class="flex-grow-1 py-4">
    <div class="container">
        <!-- Flash Notification Messages -->
        <?php render_flashes(); ?>
