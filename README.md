# Student Hub Portal — ITUE203 WDF

The project contains the public Student Hub site, secure PHP/MySQL student and administrator portals, REST-style JSON endpoints, JSON datasets, SQL schema/seed files, and practical documentation.

## Run locally with XAMPP

1. Place this folder at `C:\xampp\htdocs\Student Hub Portal`.
2. Start Apache and MySQL in the XAMPP Control Panel.
3. Import `sql/schema.sql`, then `sql/seed_data.sql` in phpMyAdmin (or use the commands in [the setup guide](docs/setup_guide.md)).
4. Configure a local administrator password from the PHP CLI; there is deliberately no public/default admin password.
5. Open `http://localhost/Student%20Hub%20Portal/`.

The student login is [Login.html](Login.html), registration is [Register.html](Register.html), and administrator sign-in is [admin-login.html](admin-login.html). Successful student authentication opens `php/students/dashboard.php`; administrator authentication opens `php/admin/dashboard.php`.

Database connection settings can be supplied through `STUDENTHUB_DB_HOST`, `STUDENTHUB_DB_USER`, `STUDENTHUB_DB_PASSWORD`, `STUDENTHUB_DB_NAME`, and `STUDENTHUB_DB_PORT`. XAMPP defaults are used if the variables are unset. Never commit local credentials or registration CSV files.

## Accounts and security

- Public registration creates student-role accounts only.
- Administrator privileges are assigned only to the seeded administrator row, and its password must be set using the local-only CLI setup process.
- Student and administrator login endpoints are separate and validate the trusted database role.
- Protected pages and APIs re-check role and active status from MySQL. Student record reads are limited to the signed-in student unless the user is an administrator.
- Session IDs are regenerated on login, sessions expire after 15 minutes of inactivity, and state-changing forms/APIs require CSRF tokens.
- The seed dataset includes the sample student password `Student@123`; change demo credentials before using real student information.

## Project map

- `assets/`, root HTML pages, and mirrored root styles/scripts: frontend.
- `data/`: canonical JSON datasets (15 students, 15 events, and 15 FAQs).
- `php/auth/`, `php/students/`, `php/events/`, `php/admin/`, `php/api/`: backend workflows.
- `sql/schema.sql`: authoritative relational schema; `sql/seed_data.sql`: sample seed records.
- `docs/`: requirements, sitemap, wireframes, deployment, testing, practical status, and screenshot checklist.
- `tests/`: test cases and verification scripts.

See [docs/setup_guide.md](docs/setup_guide.md), [docs/practical_checklist.md](docs/practical_checklist.md), and [docs/testing_report.md](docs/testing_report.md) for deployment and evidence status.
