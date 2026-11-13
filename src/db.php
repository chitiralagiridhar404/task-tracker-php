<?php
/**
 * Database Connection Module (PDO)
 * 
 * Provides a secure, reusable PDO database connection singleton.
 * Enforces prepared statement safety and suppresses raw database credentials on failure.
 */

declare(strict_types=1);

/**
 * Returns a shared PDO instance configured with strict security defaults.
 *
 * @return PDO
 */
function get_db(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $configFile = __DIR__ . '/../config.php';
    if (!file_exists($configFile)) {
        // Fallback to sample config if config.php hasn't been copied yet
        $configFile = __DIR__ . '/../config.sample.php';
    }

    if (!file_exists($configFile)) {
        error_log('Database configuration file not found in ' . __DIR__);
        die('Configuration error: Unable to locate configuration file. Please contact the administrator.');
    }

    $config = require $configFile;
    $dbConfig = $config['db'] ?? [];

    $host    = $dbConfig['host'] ?? '127.0.0.1';
    $port    = (int)($dbConfig['port'] ?? 3306);
    $dbname  = $dbConfig['name'] ?? 'task_tracker';
    $user    = $dbConfig['user'] ?? 'root';
    $pass    = $dbConfig['password'] ?? '';
    $charset = $dbConfig['charset'] ?? 'utf8mb4';

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

    $options = [
        // Throw exceptions on errors so they can be caught and handled cleanly
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        // Default fetch mode as associative array for clean data mapping
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Crucial security option: Disable emulated prepared statements to ensure
        // MySQL uses real server-side prepared statements, defeating SQL injection.
        PDO::ATTR_EMULATE_PREPARES   => false,
        // Ensure connection charset is set to utf8mb4
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE utf8mb4_unicode_ci",
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Never expose raw DB error, username, or connection strings to user/browser!
        // Log the actual exception detail to server error log for developers:
        error_log('Database Connection Error: ' . $e->getMessage());

        // Display a generic, user-friendly message without internal system details
        http_response_code(500);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Service Unavailable</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body class="bg-light d-flex align-items-center min-vh-100">
            <div class="container text-center py-5">
                <div class="card shadow-sm mx-auto" style="max-width: 540px;">
                    <div class="card-body p-4">
                        <h2 class="text-danger mb-3">Database Connection Notice</h2>
                        <p class="text-muted mb-4">
                            Unable to connect to the database service at this time.
                            Please verify that your MySQL/MariaDB server is running (e.g. in XAMPP)
                            and that the <code>task_tracker</code> database has been imported.
                        </p>
                        <a href="javascript:location.reload();" class="btn btn-outline-primary">Retry Connection</a>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}
