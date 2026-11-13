<?php
/**
 * Footer Template
 * 
 * Closes main layout, includes Bootstrap 5 bundle, and loads custom vanilla JS.
 */

declare(strict_types=1);
require_once __DIR__ . '/../src/helpers.php';
?>
    </div><!-- /.container -->
</main>

<footer class="bg-white border-top py-3 mt-auto">
    <div class="container text-center text-muted small">
        <div class="row align-items-center">
            <div class="col-md-6 text-md-start mb-2 mb-md-0">
                <strong>Task Tracker</strong> &mdash; Intern Technical Assignment (Core PHP 8 + PDO + MySQL)
            </div>
            <div class="col-md-6 text-md-end">
                <span>Built with security best practices: Prepared Statements, CSRF tokens, strict RBAC &amp; XSS escaping.</span>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 Bundle with Popper CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Application Vanilla JS -->
<script src="<?= url('assets/app.js') ?>"></script>
</body>
</html>
