# Task Tracker

A secure, role-based task management application built with Core PHP 8, MySQL, and Bootstrap 5.

This project was designed for an intern technical assignment and includes authentication, authorization, task workflows, validation, and security-focused patterns such as prepared statements, CSRF checks, ownership validation, and output escaping.

## Highlights

- Add, edit, delete, and filter tasks
- Task status workflow: Pending, In Progress, Completed
- Search by title and status
- Dashboard summary cards and pagination
- Role-based access control for admin and regular users
- Secure login and registration flow
- CSRF protection and session hardening
- Bootstrap-based responsive UI

## Tech Stack

- PHP 8.0+
- MySQL / MariaDB
- PDO for database access
- HTML, CSS, JavaScript
- Bootstrap 5

## Project Structure

```text
task-tracker/
├── public/                 # Web entry points
├── src/                    # Core PHP logic
├── templates/              # Shared layout files
├── tests/                  # Verification checklist
├── .gitignore              # Ignored local config and temp files
├── config.sample.php       # Example configuration
├── database.sql            # Database schema and seeded demo data
├── README.md               # Project documentation
└── LICENSE                 # MIT license
```

## Local Setup

### 1) Requirements

- XAMPP or a local PHP + MySQL stack
- PHP 8.0+
- MySQL 5.7+ or MariaDB 10.4+

### 2) Install and Run

1. Place the project in your web root, for example:

```text
C:\xampp\htdocs\task-tracker
```

2. Start Apache and MySQL in XAMPP.

3. Import the schema and seed data:

```bash
mysql -u root -p < C:\xampp\htdocs\task-tracker\database.sql
```

4. Copy the sample config file to a local config:

```bash
copy config.sample.php config.php
```

5. Update the database settings in `config.php` if needed.

6. Open the app in a browser:

```text
http://localhost/task-tracker/public/
```

## Default Demo Accounts

| Role | Email | Password |
| --- | --- | --- |
| Admin | admin@example.com | Admin@123 |
| User | john@example.com | User1@123 |
| User | jane@example.com | User2@123 |

## Security Features

- Prepared SQL statements to prevent injection
- CSRF validation on state-changing requests
- Role-based authorization
- Ownership checks for task actions
- Session fixation prevention
- Escaping for safe output rendering
- Secure password hashing with `password_hash()`

## Roles and Permissions

- Regular users can manage only their own tasks.
- Admin users can view all tasks and delete any task, but cannot edit another user's task content.
- Guests are redirected to the login screen.

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Notes

The app is designed to be easy to understand and extend without using a framework, making it a suitable example for learning secure PHP web development and task management design.


---

## 8. Database Backup & Restore Guide

### Using Command Line (mysqldump)

#### Backup:
```powershell
cd C:\xampp\mysql\bin
.\mysqldump.exe -u root -p task_tracker > "C:\xampp\htdocs\task-tracker\backup.sql"
```

#### Restore:
```powershell
cd C:\xampp\mysql\bin
.\mysql.exe -u root -p task_tracker < "C:\xampp\htdocs\task-tracker\backup.sql"
```

### Using phpMyAdmin

#### Backup (Export):
1. Open `http://localhost/phpmyadmin/`.
2. Select `task_tracker` from the left sidebar.
3. Click the **Export** tab in the top navigation.
4. Choose **Quick** export method and Format **SQL**.
5. Click **Export** to save the `.sql` backup file.

#### Restore (Import):
1. In phpMyAdmin, click `task_tracker` (or create a new database).
2. Click the **Import** tab.
3. Click **Choose File**, select your backup `.sql` file, and click **Import**.

---

## 9. Known Limitations

- **File Attachments**: Tasks do not currently support uploading document or image attachments.
- **Email Delivery**: Password reset via email is not implemented (requires an active SMTP mail server configuration in XAMPP `sendmail.ini`).
- **REST API**: All interactions are rendered server-side; a dedicated JSON REST API endpoint is not included.

---

## 10. AI Assistance

- **Tool Used**: Google Antigravity
- **Code Review Modification**: `[Fill in: e.g. Adjusted session cookie lifetime and refined the table badge colors for enhanced dark mode contrast.]`
