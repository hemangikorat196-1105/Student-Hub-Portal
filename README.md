# Student Hub Portal — ITUE203 WDF

The project contains the public Student Hub site, secure PHP/MySQL student and administrator portals, REST-style JSON endpoints, JSON datasets, SQL schema/seed files, and complete practical documentation.

## Run locally with XAMPP

1. Place this folder at `C:\xampp\htdocs\Student Hub Portal`.
2. Start Apache and MySQL in the XAMPP Control Panel.
3. Import `sql/schema.sql`, then `sql/seed_data.sql` in phpMyAdmin (or use the commands in [the setup guide](docs/setup_guide.md)).
4. Open `http://localhost/Student%20Hub%20Portal/`.

### Verified Authentication Credentials

- **Primary Administrator:** Username: `admin` | Password: `Admin@123`
- **Demo Administrator:** Username: `admin_demo` | Password: `Admin@12345`
- **Administrator Master Passcode:** `ADMIN2026`
- **Sample Student Account:** Student ID: `25CS035` | Password: `Student@123`

The student login is [Login.html](Login.html) and administrator sign-in is [admin-login.html](admin-login.html). Successful student authentication redirects to `home.php` / [profile.php](profile.php); administrator authentication opens [php/admin/dashboard.php](php/admin/dashboard.php).

Database connection settings can be supplied through `STUDENTHUB_DB_HOST`, `STUDENTHUB_DB_USER`, `STUDENTHUB_DB_PASSWORD`, `STUDENTHUB_DB_NAME`, and `STUDENTHUB_DB_PORT`. XAMPP defaults are used if the variables are unset.

## Accounts and Security Architecture

- **Public Self-Registration Decommissioned:** Public student self-registration has been completely disabled in accordance with institutional policy. Student accounts can only be provisioned by authenticated administrators.
- **Temporary Password Enforcement:** When an administrator creates a student or resets a password, a cryptographically secure temporary password is generated and displayed **only once** to the admin. The student is forced to change their password on first login (`must_change_password = 1`) before gaining access to portal modules.
- **Student Profile Restrictions:** Academic and identity details (Student ID, Full Name, Email, Course, Department, Semester, Year, Admission Date, CGPA) are strictly read-only and immutable for students. Students are permitted to edit **only** their contact mobile number and personal account password. All updates are IDOR-immune and strictly bound to `$_SESSION['user_id']`.
- **Role-Based Access Control (RBAC):** Roles (`admin`, `student`) are verified securely from MariaDB using `password_verify()` and prepared statements. Browser-submitted roles are never trusted.
- **Session Security:** Session IDs are regenerated upon login and password changes (`session_regenerate_id(true)`), sessions expire after 15 minutes of inactivity, and all state-changing actions require CSRF validation tokens.

## Admin Dashboard Modules

- **Dashboard Real-Time KPI Statistics:** Live counts for Total Students, Active Students, Inactive Students, Total Faculty Members, Total Courses, and Total Notices & Events.
- **Student Management:** Search (by ID, Name, Email, Mobile), multi-facet filters (Course, Semester, Year, Status), sortable columns, paginated directory, view details modal, edit administrative details, one-click activate/deactivate toggle, temporary password reset, and RFC-4180 compliant CSV export.
- **Add Student Account:** Admin-only provisioning with Student ID, Name, Email, Mobile, Course, Department, Semester, Year, Admission Date, Status, and Default Temporary Password generation.
- **Faculty Management (`php/admin/faculty.php`):** Directory, add new faculty, edit designations/departments, and toggle active status.
- **Course Management (`php/admin/courses.php`):** Degree tracks, credits, semester structures, and curriculum status.
- **Notice Management (`php/admin/notices.php`):** Official announcements, importance priority levels, draft/publish/archive workflows.
- **Database Backup (`php/admin/backup.php`):** One-click disaster recovery generating a complete `.sql` dump with foreign key safeguards.
- **Audit Logs (`php/admin/audit_logs.php`):** Traceability tracking of all administrative and authentication events.

## Project Structure

- `assets/`, root HTML pages, and styles/scripts: frontend user interface.
- `data/`: canonical JSON datasets (15 students, 15 events, and 15 FAQs).
- `php/auth/`: login, admin login, logout, and credential workflows.
- `php/students/`: student directory, create, edit, update profile, first-login password change, and CSV export.
- `php/admin/`: dashboard, faculty, courses, notices, database backup, users, and audit logs.
- `sql/schema.sql`: authoritative relational database schema (3NF).
- `sql/seed_data.sql`: sample records for all tables.
- `tests/verify_admin_workflow.php`: automated test suite.

See [docs/setup_guide.md](docs/setup_guide.md), [docs/practical_checklist.md](docs/practical_checklist.md), and [docs/testing_report.md](docs/testing_report.md) for deployment and evidence status.
