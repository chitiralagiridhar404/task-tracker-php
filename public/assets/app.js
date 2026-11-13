/**
 * Task Tracker - Vanilla JavaScript
 * Handles delete confirmations, client-side validation, and inline status updates.
 */

document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    // -------------------------------------------------------------
    // 1. Delete Confirmation Dialog (Level 1 Requirement)
    // -------------------------------------------------------------
    const deleteForms = document.querySelectorAll('.js-confirm-delete');
    deleteForms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            const taskTitle = form.getAttribute('data-task-title') || 'this task';
            const confirmation = window.confirm('Are you sure you want to delete "' + taskTitle + '"?\nThis action cannot be undone.');
            if (!confirmation) {
                event.preventDefault();
            }
        });
    });

    // -------------------------------------------------------------
    // 2. Client-side Form Validation (Level 2 Requirement)
    // -------------------------------------------------------------
    const forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            // Check HTML5 validity
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }

            // Custom checks for title length and description length if present
            const titleInput = form.querySelector('input[name="title"]');
            if (titleInput) {
                const val = titleInput.value.trim();
                if (val.length === 0) {
                    titleInput.setCustomValidity('Title is required.');
                    event.preventDefault();
                } else if (val.length > 150) {
                    titleInput.setCustomValidity('Title cannot exceed 150 characters.');
                    event.preventDefault();
                } else {
                    titleInput.setCustomValidity('');
                }
            }

            const descInput = form.querySelector('textarea[name="description"]');
            if (descInput) {
                const val = descInput.value;
                if (val.length > 1000) {
                    descInput.setCustomValidity('Description cannot exceed 1000 characters.');
                    event.preventDefault();
                } else {
                    descInput.setCustomValidity('');
                }
            }

            form.classList.add('was-validated');
        }, false);
    });

    // -------------------------------------------------------------
    // 3. Dynamic Character Counters for Title and Description
    // -------------------------------------------------------------
    function setupCharCounter(inputId, counterId, maxLen) {
        const input = document.getElementById(inputId);
        const counter = document.getElementById(counterId);
        if (!input || !counter) return;

        function update() {
            const currentLen = input.value.length;
            counter.textContent = currentLen + ' / ' + maxLen + ' characters';

            if (currentLen > maxLen) {
                counter.className = 'char-counter limit-reached';
            } else if (currentLen > maxLen * 0.9) {
                counter.className = 'char-counter limit-near';
            } else {
                counter.className = 'char-counter';
            }
        }

        input.addEventListener('input', update);
        update(); // Initial count
    }

    setupCharCounter('task-title', 'title-counter', 150);
    setupCharCounter('task-desc', 'desc-counter', 1000);

    // -------------------------------------------------------------
    // 4. Auto-submit Inline Status Dropdowns (Level 2 Quick Update)
    // -------------------------------------------------------------
    const inlineStatusSelects = document.querySelectorAll('.js-status-dropdown');
    inlineStatusSelects.forEach(function (select) {
        select.addEventListener('change', function () {
            // The select is inside a form; submit immediately on user selection
            if (this.form) {
                this.form.submit();
            }
        });
    });

    // -------------------------------------------------------------
    // 5. Auto-dismiss Flash Alerts after 5 seconds (Optional polish)
    // -------------------------------------------------------------
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            try {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                bsAlert.close();
            } catch (e) {
                // Ignore if bootstrap is still loading
            }
        }, 5000);
    });
});
