# ITUE203 WDF Practical Completion Report

This report distinguishes code inspection/static checks from tests that require a running XAMPP/MySQL installation. The local MySQL service was not running during this audit, so end-to-end authentication, CRUD, API, upload, dashboard query, and browser behavior are not reported as passed. See [testing_report.md](testing_report.md) and [setup_guide.md](setup_guide.md).

| # | Practical and required files | Data count checked | Tests/results | Status and remaining manual work |
|---|---|---|---|---|
| 1 | Requirements, sitemap, wireframes, GitHub project: `docs/requirements.md`, `docs/sitemap.md`, `docs/wireframes.md`, repository | N/A | Documents and repository structure inspected. | Planning artifacts exist; capture repository evidence and confirm GitHub submission manually. |
| 2 | Semantic HTML/accessibility: public `.html` pages, `admin-login.html`, `php/students/dashboard.php` | N/A | Existing HTML inspected; server output for PHP pages requires XAMPP. | Existing pages retained; run an accessibility/keyboard audit and validate rendered PHP pages. |
| 3 | Responsive design: `Style.css`, `assets/css/Style.css`, page templates | N/A | CSS files and breakpoints are present; viewport simulation not run. | Capture/test mobile, tablet, and desktop sizes in a browser. |
| 4 | DOM interactions: `script.js`, `assets/js/script.js`, interactive pages | N/A | JavaScript syntax checked; interactions and browser console not exercised. | Test theme, menu, forms, search/filter, and event modal in browser. |
| 5 | Registration validation: `Register.html`, `script.js`, `php/auth/register.php`, `php/includes/validation.php` | N/A | Server validation and student-only role assignment inspected; CSRF token bootstrapping added. | Submit valid/invalid forms against MySQL and verify messages/CSV output. |
| 6 | Fetch/search/filter/sort/pagination: `data/students.json`, `data/events.json`, `data/faqs.json`, `json-data.html`, `jsonData.js` | Students **15**; events **15**; FAQs **15** | JSON validity, exact counts, and root-copy consistency checked. | Exercise loading, retries, filtering, sorting, and pagination in browser. |
| 7 | PHP form processing/CSV: `Process.php`, `php/forms/process.php`, `php/auth/register.php`, `Student.csv`, `data/students.csv` | No runtime CSV created during audit | Compatibility endpoints now use the single secure registration/login handlers; CSV output checked in source only. | Test successful/failed submissions and confirm both CSV files are created locally. |
| 8 | Relational schema/ER: `sql/schema.sql`, `sql/seed_data.sql`, `php/config/db.php`, `docs/er_diagram.md` | Seed rows: users **15**, students **15**, events **15**, registrations **15**, audit logs **15** | SQL seed row counts and PHP syntax checked; schema import not run because MySQL was stopped. | Import in disposable local DB and verify constraints/foreign keys in phpMyAdmin. |
| 9 | Secure student registration: `php/auth/register.php`, `php/includes/csrf.php`, `php/includes/validation.php` | No live account created | Confirmed hard-coded role `student`, prepared statements, bcrypt cost 12, duplicate check, CSRF check in source. | Run duplicate/invalid/valid registration tests against MySQL. |
| 10 | Separate authentication/RBAC: `Login.html`, `admin-login.html`, `home.php`, `php/auth/login.php`, `php/auth/admin_login.php`, logout handlers, guards/session | N/A | Static checks confirm role-fixed login endpoints, server-side role/status verification, session regeneration and 900-second timeout. | Test wrong-role credentials, fixation, timeout, logout, direct URL and session demotion with real sessions. |
| 11 | Student CRUD/search/export: `php/students/*.php`, `php/api/students.php` | Seed students **15** | Student-management routes and API now require admin; individual student lookup is self-scoped. PHP syntax checked. | Test CRUD, validation, search, pagination, CSV export and audit entries in MySQL. |
| 12 | Event CRUD/poster upload: `php/events/*.php`, `assets/uploads/event_posters/.htaccess` | Seed events **15** | Admin guards, CSRF checks, file MIME/size/dimension validation and upload directory policy inspected. | Test event CRUD, valid image, spoofed file, executable, oversized file and cleanup. |
| 13 | JSON APIs: `php/api/*.php`, `php/includes/api_bootstrap.php` | Backed by the seed counts above | API authorization/CSRF paths and JSON error handling inspected; live HTTP methods/status codes not exercised. | Use `tests/api_test_cases.md` with Apache/MySQL running; verify JSON and all status codes. |
| 14 | Admin dashboard/users/audit: `php/admin/*.php`, `sql/schema.sql` | Live counts not available | Dashboard uses SQL counts; admin role is revalidated against active database user. | Configure admin; verify metrics match SQL and actions write audit rows. |
| 15 | Integration/docs/XAMPP: `README.md`, `docs/setup_guide.md`, `tests/`, `docs/screenshot_checklist.md` | JSON and SQL seed counts above | PHP syntax and static data checks run; integration verifier could not connect because Apache and MySQL were stopped. | Run integration tests in XAMPP, resolve machine-specific issues, capture required screenshots, submit/push to GitHub. |

## Verified dataset counts

| Dataset/table | Actual count | Source |
|---|---:|---|
| `data/students.json` | 15 | JSON parsing |
| `data/events.json` | 15 | JSON parsing |
| `data/faqs.json` | 15 | JSON parsing |
| `users` | 15 | `INSERT` seed rows |
| `students` | 15 | `INSERT` seed rows |
| `events` | 15 | `INSERT` seed rows |
| `registrations` | 15 | `INSERT` seed rows |
| `audit_logs` | 15 | `INSERT` seed rows |

The root JSON copies are retained for existing root-relative pages. `data/` is the canonical dataset location.
