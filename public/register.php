<?php
/**
 * User Registration Controller & View
 * 
 * Handles new user signups, enforces unique email checks via prepared statements,
 * and securely hashes passwords with password_hash(..., PASSWORD_DEFAULT).
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/csrf.php';
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/validation.php';

require_guest();

$errors = [];
$name  = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Validate CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors['general'] = 'Session expired or invalid security token. Please try again.';
    } else {
        $name  = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        $pdo = get_db();

        // 2. Validate input and check email uniqueness using prepared statement
        $errors = validate_registration($_POST, $pdo);

        if (empty($errors)) {
            // 3. Hash password using bcrypt / Argon2 standard default
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // 4. Insert user record with default 'user' role
            $stmt = $pdo->prepare('
                INSERT INTO users (name, email, password_hash, role)
                VALUES (?, ?, ?, "user")
            ');
            $stmt->execute([$name, $email, $passwordHash]);

            $newUserId = (int)$pdo->lastInsertId();

            // 5. Automatically log the user in securely
            login_user([
                'id'    => $newUserId,
                'name'  => $name,
                'email' => $email,
                'role'  => 'user',
            ]);

            set_flash('success', 'Account created successfully! Welcome to Task Tracker.');
            redirect('dashboard.php');
        }
    }
}

$pageTitle = 'Create an Account';
require_once __DIR__ . '/../templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm border-0 mt-3">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle mb-3" style="width: 56px; height: 56px;">
                        <i class="bi bi-person-plus fs-2"></i>
                    </div>
                    <h3 class="fw-bold">Create Account</h3>
                    <p class="text-muted small">Join Task Tracker to organize and manage your daily tasks.</p>
                </div>

                <?php if (!empty($errors['general'])): ?>
                    <div class="alert alert-danger" role="alert">
                        <?= e($errors['general']) ?>
                    </div>
                <?php endif; ?>

                <form action="<?= url('register.php') ?>" method="POST" class="needs-validation" novalidate>
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Full Name</label>
                        <input type="text" 
                               class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" 
                               id="name" 
                               name="name" 
                               value="<?= e($name) ?>" 
                               placeholder="e.g. Alex Morgan" 
                               required 
                               maxlength="100">
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['name']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email Address</label>
                        <input type="email" 
                               class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                               id="email" 
                               name="email" 
                               value="<?= e($email) ?>" 
                               placeholder="name@example.com" 
                               required 
                               maxlength="191">
                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['email']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">Password (min. 8 characters)</label>
                        <input type="password" 
                               class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" 
                               id="password" 
                               name="password" 
                               placeholder="At least 8 characters" 
                               required 
                               minlength="8">
                        <?php if (isset($errors['password'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['password']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label fw-semibold">Confirm Password</label>
                        <input type="password" 
                               class="form-control <?= isset($errors['password_confirmation']) ? 'is-invalid' : '' ?>" 
                               id="password_confirmation" 
                               name="password_confirmation" 
                               placeholder="Re-enter password" 
                               required 
                               minlength="8">
                        <?php if (isset($errors['password_confirmation'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['password_confirmation']) ?></div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold shadow-sm">
                        <i class="bi bi-person-check me-1"></i> Register Account
                    </button>
                </form>

                <div class="mt-4 pt-3 border-top text-center text-muted small">
                    Already registered? 
                    <a href="<?= url('login.php') ?>" class="text-primary fw-semibold text-decoration-none">Sign In</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
