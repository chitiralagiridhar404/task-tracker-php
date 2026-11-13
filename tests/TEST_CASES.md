# Task Tracker &mdash; Test Cases & Quality Verification Matrix

This test suite covers all requirements across **Level 1**, **Level 2**, **Level 3**, and **Bonus Features** for the Task Tracker intern assignment. Use this checklist during code review or technical assessment to verify each functional and security capability.

---

## 1. Test Environment Preconditions

- **Base URL**: `http://localhost/task-tracker/public/`
- **Database**: Imported from `database.sql`
- **Test Accounts**:
  - **Admin**: `admin@example.com` / `Admin@123`
  - **User 1**: `john@example.com` / `User1@123`
  - **User 2**: `jane@example.com` / `User2@123`

---

## 2. Test Execution Matrix

| Test ID | Category | Feature / Security Check | Detailed Test Steps | Expected Result | Result |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **TC-01** | Level 1 | Add Valid Task | 1. Sign in as `john@example.com`.<br>2. Click **New Task**.<br>3. Fill Title (`Deploy v1.0`), Description (`Staging tests pass`), Priority (`High`), Due Date.<br>4. Click **Save Task**. | Task is created; redirected to Dashboard; green flash message *"Task 'Deploy v1.0' has been added successfully!"*; task visible in table. | [ ] Pass |
| **TC-02** | Level 1 & 2 | Reject Empty Title (Frontend & Backend) | 1. Navigate to **New Task**.<br>2. Leave Title completely blank.<br>3. Attempt to submit form.<br>4. Also test with disabled JavaScript / cURL. | HTML5 and JS block submission with validation tooltip; backend rejects request and displays friendly error *"Task title is required."* No DB record created. | [ ] Pass |
| **TC-03** | Level 2 | Reject Title &gt; 150 Chars &amp; Desc &gt; 1000 Chars | 1. Enter a Title with 155 characters.<br>2. Enter Description with 1050 characters.<br>3. Submit form. | Frontend character counter alerts limit; backend validation halts submission with friendly error messages displaying current count. | [ ] Pass |
| **TC-04** | Level 1 | Edit Task Title and Description | 1. As John, click **Edit** on his task.<br>2. Update title to `Deploy v1.1` and edit description.<br>3. Click **Update Task**. | Redirects to Dashboard with flash success message; updated title and description reflect in table. | [ ] Pass |
| **TC-05** | Level 2 | Inline Status Transition | 1. In Dashboard table, locate John's task with status `Pending`.<br>2. Select `In Progress` from inline dropdown.<br>3. Select `Completed`. | Form auto-submits via POST with CSRF token; flash success message appears; status badge color updates accordingly. | [ ] Pass |
| **TC-06** | Level 1 | Delete Task with JS `confirm()` Dialog | 1. Locate John's task in dashboard.<br>2. Click the trash button.<br>3. In the JS confirmation popup, click **Cancel**.<br>4. Click trash again and click **OK**. | First click (Cancel) does nothing; second click (OK) submits POST request; task is deleted from database; success flash message shown. | [ ] Pass |
| **TC-07** | Level 1 | Flash Messages Display &amp; Dismissal | 1. Perform an Add, Edit, or Delete action.<br>2. Observe flash alert at top of container.<br>3. Click the close `&times;` button or wait 5 seconds. | Flash alert displays with appropriate color (`alert-success`, `alert-danger`); dismisses smoothly when clicked or after timeout. | [ ] Pass |
| **TC-08** | Level 2 | Title Search (LIKE Prepared Statement) | 1. Enter `schema` into search box and click **Apply Filter**.<br>2. Observe table contents.<br>3. Try searching for SQL wildcards like `%' OR '1'='1`. | Only matching tasks containing "schema" appear; SQL injection string searches literally for that text with no syntax errors. | [ ] Pass |
| **TC-09** | Level 2 | Status Filter Dropdown | 1. Select `Completed` in status filter.<br>2. Click **Apply Filter**. | Only tasks with status `Completed` are listed in the table; summary cards remain accurate. | [ ] Pass |
| **TC-10** | Level 2 | Combined Search and Status Filter | 1. Enter `database` in search box.<br>2. Select `Completed` in status filter.<br>3. Click **Apply Filter**. | Only tasks matching BOTH the keyword and the selected status are returned. | [ ] Pass |
| **TC-11** | Bonus | Pagination Preserves Filters | 1. Seed or create &gt; 10 tasks.<br>2. Search for a term and navigate to Page 2 (`?page=2&search=...`). | Page shows items 11&ndash;20; active search query and status filter remain persisted in the URL and form controls. | [ ] Pass |
| **TC-12** | Bonus | Dashboard Summary Cards Metrics | 1. Check top cards for Total, Pending, In Progress, Completed.<br>2. Add or transition a task status. | Counters match exact record counts in database for current scope (user tasks for regular user; all tasks for admin). | [ ] Pass |
| **TC-13** | Level 3 | Guest Visitor Blocked from Dashboard | 1. Open an incognito browser window (logged out).<br>2. Navigate directly to `http://localhost/task-tracker/public/dashboard.php`.<br>3. Try accessing `task_add.php` and `task_edit.php`. | Immediately redirected to `login.php` with a flash warning *"Please sign in to access that page."* | [ ] Pass |
| **TC-14** | Level 3 | User Registration Validation | 1. Go to `register.php`.<br>2. Try submitting with duplicate email `john@example.com`.<br>3. Try password with &lt; 8 characters.<br>4. Try mismatched confirmation. | Meaningful backend validation errors returned: unique email error, password length error, mismatch error. | [ ] Pass |
| **TC-15** | Level 3 | Passwords Stored as Bcrypt Hashes | 1. Inspect `users` table via phpMyAdmin or MySQL CLI: `SELECT email, password_hash FROM users;`. | All passwords stored as `$2y$10$...` hashes. Plain text passwords NEVER exist in database or logs. | [ ] Pass |
| **TC-16** | Level 3 | Session Fixation Defense | 1. Open DevTools Application tab &gt; Cookies.<br>2. Note PHP session cookie ID before login.<br>3. Submit valid credentials on `login.php`.<br>4. Check session cookie ID. | Cookie value changes completely due to `session_regenerate_id(true)`. Old session ID is invalidated. | [ ] Pass |
| **TC-17** | Level 3 | User Isolation: Own Tasks Only | 1. Log in as John (`john@example.com`).<br>2. Review Dashboard table. | John only sees tasks where `user_id = 2`. Jane's tasks (e.g. `Audit CSRF protection`) are completely invisible. | [ ] Pass |
| **TC-18** | Level 3 | URL Tampering on Edit (`?id=X`) Blocked | 1. Log in as John (`john@example.com`, user ID 2).<br>2. Note Jane's task ID (ID 5).<br>3. Manually navigate to `task_edit.php?id=5`. | HTTP 403 Forbidden returned; redirected to Dashboard with error *"Access Denied: You do not have permission to edit this task."* | [ ] Pass |
| **TC-19** | Level 3 | POST Tampering on Update Blocked | 1. As John, capture or forge a POST to `task_edit.php` with `id=5` (Jane's task).<br>2. Submit with valid CSRF token. | Backend check `WHERE id = ? AND user_id = ?` halts update; 403 Forbidden; Jane's task is NOT modified. | [ ] Pass |
| **TC-20** | Level 3 | POST Tampering on Delete Blocked | 1. As John, forge a POST request to `task_delete.php` with `id=5`. | Backend enforces `user_id = ?`; deletion rejected; Jane's task remains untouched. | [ ] Pass |
| **TC-21** | Level 3 | Admin Role: View All with Owner Names | 1. Log in as Admin (`admin@example.com`).<br>2. Navigate to Dashboard. | Admin sees tasks from John, Jane, and Admin, complete with Owner Name and Email columns. | [ ] Pass |
| **TC-22** | Level 3 | Admin Role: Cannot Edit Other Users' Content | 1. As Admin, attempt to edit John's task by visiting `task_edit.php?id=1`.<br>2. Attempt to POST an update to task 1. | Edit button is disabled in UI; accessing controller returns 403 Forbidden: *"Administrators can view and delete all tasks, but cannot edit other users' task content."* | [ ] Pass |
| **TC-23** | Level 3 | Admin Role: Can Delete Any Task | 1. As Admin, locate a task belonging to John or Jane.<br>2. Click delete and confirm. | Task is deleted successfully; flash message confirms removal. | [ ] Pass |
| **TC-24** | Level 3 | Prepared Statements &amp; SQL Injection Resistance | 1. In search box or task title field, enter:<br>`' OR 1=1 --`<br>`" UNION SELECT 1,2,3,4,5,6,7,8,9 --`<br>2. Submit. | Handled strictly as literal text; no SQL error occurs, no unintended records leaked or modified (`ATTR_EMULATE_PREPARES = false`). | [ ] Pass |
| **TC-25** | Level 3 | XSS Prevention with Output Escaping `e()` | 1. Create a task with Title:<br>`<script>alert('XSS')</script>`<br>and Description:<br>`<img src=x onerror=alert('PWNED')>`.<br>2. View Dashboard. | Script does NOT execute. Browser displays literal text safely encoded via `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`. | [ ] Pass |
| **TC-26** | Level 3 | CSRF Protection: Missing or Tampered Token | 1. Open `task_add.php` in browser.<br>2. Open DevTools, edit hidden `csrf_token` input value to `forged_token_123`.<br>3. Submit form. | Request is halted with HTTP 403 Forbidden; error *"Invalid or expired CSRF security token."* Task is not created. | [ ] Pass |
| **TC-27** | Level 3 | Delete via GET Link is Rejected | 1. Try to trigger task deletion via direct GET request:<br>`http://localhost/task-tracker/public/task_delete.php?id=1`. | HTTP 405 Method Not Allowed returned; delete does not occur; redirected with security notice. | [ ] Pass |
| **TC-28** | Level 3 | Secure Session Cookie Settings | 1. In browser DevTools &gt; Application &gt; Cookies.<br>2. Inspect `task_tracker_session` cookie attributes. | **HttpOnly** flag is checked (`true`); **SameSite** is set to `Lax`. | [ ] Pass |
| **TC-29** | Level 3 | Database Credentials &amp; Raw Error Suppression | 1. In `config.php`, temporarily set an invalid DB password.<br>2. Refresh page. | Generic friendly error page displayed (*"Database Connection Notice"*). No raw MySQL exception, stack trace, or credentials leaked to browser. Error logged to server log. | [ ] Pass |

---

## 3. Evaluation Sign-off

- **Evaluator Name**: __________________________
- **Date of Verification**: ____________________
- **Overall Result**: `[ ] ALL PASS` / `[ ] ISSUES IDENTIFIED`
- **Comments**: ____________________________________________________________________
