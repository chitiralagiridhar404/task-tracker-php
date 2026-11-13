<?php
/**
 * Validation Module
 * 
 * Provides robust backend input validation and sanitization
 * for task creation/editing and user authentication.
 */

declare(strict_types=1);

/**
 * Validates task creation or update payload.
 *
 * @param array $data Raw $_POST data
 * @return array Associative array of field => error message (empty if valid)
 */
function validate_task(array $data): array
{
    $errors = [];

    // Title validation
    $title = trim((string)($data['title'] ?? ''));
    if ($title === '') {
        $errors['title'] = 'Task title is required.';
    } elseif (mb_strlen($title) > 150) {
        $errors['title'] = 'Task title cannot exceed 150 characters (currently ' . mb_strlen($title) . ' characters).';
    }

    // Description validation (optional, max 1000 chars)
    $description = trim((string)($data['description'] ?? ''));
    if (mb_strlen($description) > 1000) {
        $errors['description'] = 'Description cannot exceed 1000 characters (currently ' . mb_strlen($description) . ' characters).';
    }

    // Status validation
    $allowedStatuses = ['Pending', 'In Progress', 'Completed'];
    $status = trim((string)($data['status'] ?? 'Pending'));
    if (!in_array($status, $allowedStatuses, true)) {
        $errors['status'] = 'Invalid status selected. Must be Pending, In Progress, or Completed.';
    }

    // Priority validation
    $allowedPriorities = ['Low', 'Medium', 'High'];
    $priority = trim((string)($data['priority'] ?? 'Medium'));
    if (!in_array($priority, $allowedPriorities, true)) {
        $errors['priority'] = 'Invalid priority selected. Must be Low, Medium, or High.';
    }

    // Due date validation (optional)
    $dueDate = trim((string)($data['due_date'] ?? ''));
    if ($dueDate !== '') {
        $d = DateTime::createFromFormat('Y-m-d', $dueDate);
        if (!$d || $d->format('Y-m-d') !== $dueDate) {
            $errors['due_date'] = 'Due date must be a valid date in YYYY-MM-DD format.';
        }
    }

    return $errors;
}

/**
 * Validates user registration data and ensures email uniqueness.
 *
 * @param array $data Raw $_POST data
 * @param PDO $pdo Active database connection
 * @return array Associative array of field => error message
 */
function validate_registration(array $data, PDO $pdo): array
{
    $errors = [];

    // Name validation
    $name = trim((string)($data['name'] ?? ''));
    if ($name === '') {
        $errors['name'] = 'Full name is required.';
    } elseif (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        $errors['name'] = 'Full name must be between 2 and 100 characters.';
    }

    // Email validation
    $email = trim((string)($data['email'] ?? ''));
    if ($email === '') {
        $errors['email'] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    } elseif (mb_strlen($email) > 191) {
        $errors['email'] = 'Email address is too long.';
    } else {
        // Check for existing email via prepared statement
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors['email'] = 'This email address is already registered. Please sign in instead.';
        }
    }

    // Password validation
    $password = (string)($data['password'] ?? '');
    $passwordConfirmation = (string)($data['password_confirmation'] ?? '');

    if ($password === '') {
        $errors['password'] = 'Password is required.';
    } elseif (mb_strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters long.';
    }

    if ($passwordConfirmation === '') {
        $errors['password_confirmation'] = 'Please confirm your password.';
    } elseif ($password !== $passwordConfirmation) {
        $errors['password_confirmation'] = 'Password confirmation does not match.';
    }

    return $errors;
}

/**
 * Validates user login input.
 *
 * @param array $data Raw $_POST data
 * @return array Associative array of field => error message
 */
function validate_login(array $data): array
{
    $errors = [];

    $email = trim((string)($data['email'] ?? ''));
    if ($email === '') {
        $errors['email'] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    $password = (string)($data['password'] ?? '');
    if ($password === '') {
        $errors['password'] = 'Password is required.';
    }

    return $errors;
}
