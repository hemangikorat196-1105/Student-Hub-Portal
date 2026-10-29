# Student Hub Portal — Backend Architecture & PHP Modules Guide

This directory contains the modular server-side architecture for the Student Hub Portal across ITUE203 Web Development Frameworks practicals.

## Directory Layout & Responsibilities

| Directory / File | Description | Practicals |
| :--- | :--- | :--- |
| `config/db.php` | Shared database connectivity using both **MySQLi** and **PDO** with prepared statements and secure credential isolation. | 8, 9, 10, 11, 12, 13, 14, 15 |
| `includes/session.php` | Secure session initialization, `session_regenerate_id()`, 15-minute inactivity timeout, and cookie security flags (`HttpOnly`, `SameSite=Lax`). | 10, 14, 15 |
| `includes/auth_guard.php` | Role-Based Access Control (RBAC) guards (`require_login()`, `require_admin()`, `require_student()`). Returns HTTP 403 / 401 when unauthenticated or unauthorized. | 10, 12, 14, 15 |
| `includes/csrf.php` | Cryptographically random CSRF token generation (`random_bytes(32)`) and validation (`hash_equals()`). | 9, 10, 14, 15 |
| `includes/validation.php` | Server-side sanitization and validation matching frontend JavaScript regex rules. | 5, 7, 9, 11, 12, 15 |
| `includes/response.php` | Standardized JSON API responses with HTTP status codes and persistent security audit logging (`audit_logs`). | 9, 11, 12, 13, 14, 15 |
| `auth/register.php` | Secure registration handler with duplicate check, `password_hash()` (Bcrypt), user/student creation, and CSV persistence. | 7, 9, 14, 15 |
| `auth/login.php` | Student-only authentication using `password_verify()`; `auth/admin_login.php` fixes the required role to administrator. | 10, 14, 15 |
| `auth/logout.php` | Secure session destruction, cookie clearance, and audit logging. | 10, 14, 15 |
| `auth/check_session.php` | Session status and timeout monitoring endpoint. | 10, 14, 15 |
| `auth/forgot_password.php` | Secure password reset request handler. | 5, 10, 15 |
| `students/list.php` | Student management page with search, course filter, status filter, pagination, soft-delete, and CSV export. | 11, 14, 15 |
| `students/create.php` | Add student record using prepared statement with validation and duplicate checks. | 11, 14, 15 |
| `students/update.php` | Edit existing student record with audit logging. | 11, 14, 15 |
| `students/delete.php` | Soft-delete (`status='inactive'`) and permanent delete with audit logging. | 11, 14, 15 |
| `students/export.php` | CSV download endpoint (`Content-Type: text/csv`) with Excel UTF-8 BOM. | 11, 15 |
| `students/search.php` | Asynchronous student search endpoint for debounced AJAX inputs. | 11, 13, 15 |
| `events/list.php` | Admin event management table with category filtering and poster preview. | 12, 14, 15 |
| `events/create.php` | Create event with poster upload, file type validation, and file size validation (max 2 MB). | 12, 14, 15 |
| `events/update.php` | Edit event details and optionally replace poster. | 12, 14, 15 |
| `events/delete.php` | Delete event and cleanup poster image from storage. | 12, 14, 15 |
| `events/upload_poster.php` | Dedicated, secure poster upload processor with MIME check and unique renaming. | 12, 15 |
| `admin/dashboard.php` | Dynamic administrator dashboard with real-time aggregated metrics, recent registrations, and audit feed. | 10, 14, 15 |
| `admin/users.php` | User management and active/inactive status toggle. | 14, 15 |
| `admin/audit_logs.php` | Security audit trail viewer with action filter and search. | 14, 15 |
| `admin/access_denied.php` | User-friendly HTTP 403 Forbidden page. | 10, 14, 15 |
| `api/students.php` | RESTful API endpoint supporting GET, POST, PUT, DELETE for asynchronous operations without page reload. | 13, 15 |
| `api/events.php` | RESTful API endpoint supporting GET, POST, PUT, DELETE for events. | 13, 15 |
| `api/faqs.php` | FAQs JSON API endpoint with category and keyword search. | 6, 13, 15 |
| `forms/process.php` | Practical 7 registration form processor with server-side validation and CSV storage. | 7, 9, 15 |
| `forms/contact_submit.php` | Contact form processor with CSV inquiry storage. | 2, 7, 15 |

## Security Best Practices Implemented

1. **Password Hashing**: Stored using `password_hash($password, PASSWORD_BCRYPT)` and verified using `password_verify()`.
2. **Prepared Statements**: All database queries use parameter binding (`bind_param` or PDO `prepare`) preventing SQL injection.
3. **Session Fixation Prevention**: Session IDs are regenerated via `session_regenerate_id(true)` upon successful authentication.
4. **Session Timeout**: 15 minutes of user inactivity triggers automatic session termination.
5. **CSRF Tokens**: Form submissions utilize cryptographically random tokens validated with `hash_equals()`.
6. **Upload Hardening**: File uploads check real MIME types via `finfo_file` (not just extensions), enforce a 2 MB size ceiling, generate random filenames, and reside in a folder protected by `.htaccess`.
7. **Role-Based Access Control**: Pages check `$_SESSION['role']` server-side; unauthorized access attempts are blocked with HTTP 403 and logged in `audit_logs`.
