# Local XAMPP Setup

## Requirements

- XAMPP for Windows with Apache, PHP 8.1+, and MariaDB/MySQL.
- PHP extensions `mysqli`, `pdo_mysql`, `fileinfo`, `mbstring`, `session`, and `json`.
- The project folder located at `C:\xampp\htdocs\Student Hub Portal`.

## Database installation

Start MySQL in XAMPP, then import the authoritative schema and sample data from the project root:

```powershell
Get-Content "sql\schema.sql" | C:\xampp\mysql\bin\mysql.exe -u root
Get-Content "sql\seed_data.sql" | C:\xampp\mysql\bin\mysql.exe -u root student_portal
```

`schema.sql` creates and replaces the project tables; only run it against a disposable/local database. The sample seed contains 15 users, 15 students, 15 events, 15 registrations, and 15 audit-log rows. The administrator's seeded password is deliberately unusable until configured locally.

### Configure the administrator (required)

Set a unique local password of at least 12 characters in a temporary environment variable and run the CLI setup script. This does not expose a default password in the web application or repository:

```powershell
$securePassword = Read-Host "New local administrator password" -AsSecureString
$env:STUDENTHUB_ADMIN_PASSWORD = [System.Net.NetworkCredential]::new("", $securePassword).Password
& C:\xampp\php\php.exe "php\setup\create_admin.php"
Remove-Item Env:\STUDENTHUB_ADMIN_PASSWORD
```

Optional environment variables `STUDENTHUB_ADMIN_USERNAME` and `STUDENTHUB_ADMIN_EMAIL` set the admin username and email. Database settings are read from `STUDENTHUB_DB_HOST`, `STUDENTHUB_DB_USER`, `STUDENTHUB_DB_PASSWORD`, `STUDENTHUB_DB_NAME`, and `STUDENTHUB_DB_PORT`; defaults match a standard local XAMPP install. Do not commit credentials.

## Start and open

1. In XAMPP Control Panel, start **Apache** and **MySQL**.
2. Open `http://localhost/Student%20Hub%20Portal/`.
3. Public student registration is at `/Register.html`; student sign-in is `/Login.html`; admin sign-in is `/admin-login.html`.
4. Admin setup, authentication and all protected CRUD/dashboard routes require MySQL availability.

Students use `/php/students/dashboard.php` after sign-in. Administrators use `/php/admin/dashboard.php`. Admin tools live under `/php/students/`, `/php/events/`, and `/php/admin/`. JSON APIs are `/php/api/students.php`, `/php/api/events.php`, and `/php/api/faqs.php`.

The legacy `Process.php` and `php/forms/process.php` paths remain as compatibility routes to the authoritative `php/auth/register.php`/student-login handlers; they do not have a separate account creation implementation.

## Validation and demo data

- `http://localhost/Student%20Hub%20Portal/test_connection.php` reports live database connectivity and counts.
- Student demo accounts in `sql/seed_data.sql` use `Student@123`; change these sample credentials before any use with non-demo data.
- Sample JSON datasets are in `data/`. The matching root JSON copies are retained for existing pages that reference root-relative files.
- Student registration appends personal information to `Student.csv` and `data/students.csv`; these runtime files are ignored by Git.
- Uploaded posters are limited to verified JPEG, PNG, or WEBP images of at most 2 MB and stored under `assets/uploads/event_posters/`.

## Testing

Run the static verifier with `python tests\verify_all.py`. It includes HTTP/database integration probes and those require Apache and MySQL to be running with the schema imported. Check PHP syntax with:

```powershell
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { & C:\xampp\php\php.exe -l $_.FullName }
```

After setup, execute the manual journeys in `tests/test_cases.md` and `tests/api_test_cases.md`, capture the items in `docs/screenshot_checklist.md`, and update `docs/testing_report.md` with the actual machine-specific results. Do not report browser, database, upload, or cross-device tests as passed unless performed.
