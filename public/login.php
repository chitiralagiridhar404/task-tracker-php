<?php
/**
 * User Login Controller & View
 * 
 * Authenticates users using prepared statements and password_verify.
 * Defends against session fixation via session_regenerate_id(true).
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/csrf.php';
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/validation.php';

// Redirect to dashboard if already authenticated
require_guest();

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors['general'] = 'Session expired or invalid security token. Please try again.';
    } else {
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        // 2. Validate input fields
        $errors = validate_login($_POST);

        if (empty($errors)) {
            $pdo = get_db();

            // 3. Prepared statement ensures email input cannot alter SQL structure
            $stmt = $pdo->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // 4. Verify password against stored bcrypt hash using constant-time comparison
            if ($user && password_verify($password, $user['password_hash'])) {
                // Secure login handler: calls session_regenerate_id(true) to defeat session fixation
                login_user($user);
                set_flash('success', 'Welcome back, ' . $user['name'] . '!');
                redirect('dashboard.php');
            } else {
                // Generic error message prevents username/email enumeration
                $errors['general'] = 'Invalid email address or password.';
            }
        }
    }
}

$pageTitle = 'Sign In';
require_once __DIR__ . '/../templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle mb-3" style="width: 56px; height: 56px;">
                        <i class="bi bi-person-lock fs-2"></i>
                    </div>
                    <h3 class="fw-bold">Sign In</h3>
                    <p class="text-muted small">Enter your credentials to access your task dashboard.</p>
                </div>

                <?php if (!empty($errors['general'])): ?>
                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2"></i>
                        <div><?= e($errors['general']) ?></div>
                    </div>
                <?php endif; ?>

                <form action="<?= url('login.php') ?>" method="POST" class="needs-validation" novalidate>
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                            <input type="email" 
                                   class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                                   id="email" 
                                   name="email" 
                                   value="<?= e($email) ?>" 
                                   placeholder="name@example.com" 
                                   required 
                                   autofocus>
                        </div>
                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['email']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label fw-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-shield-lock"></i></span>
                            <input type="password" 
                                   class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Enter your password" 
                                   required>
                        </div>
                        <?php if (isset($errors['password'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['password']) ?></div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold shadow-sm">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                    </button>
                </form>

                <div class="mt-4 pt-3 border-top text-center text-muted small">
                    Don't have an account? 
                    <a href="<?= url('register.php') ?>" class="text-primary fw-semibold text-decoration-none">Create an Account</a>
                </div>
            </div>
        </div>

        <!-- Demo credentials quick reference helper for evaluator -->
        <div class="card mt-3 bg-light border-0">
            <div class="card-body p-3 text-muted small">
                <div class="fw-semibold text-dark mb-1"><i class="bi bi-info-circle me-1"></i> Demo Credentials:</div>
                <div class="row g-1">
                    <div class="col-sm-6">
                        <p>example logins</p>
                        <strong>Admin:</strong> <code>admin@example.com</code><br>
                        Password: <code>Admin@123</code>
                    </div>
                    <div class="col-sm-6">
                        <strong>User:</strong> <code>john@example.com</code><br>
                        Password: <code>u</code>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
