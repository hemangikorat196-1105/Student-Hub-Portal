# Testing Report

## Verification performed in this workspace

- PHP source syntax checked with the XAMPP PHP 8.2 CLI.
- JSON datasets parsed and exact record counts checked: 15 students, 15 events, and 15 FAQs.
- SQL seed inserts checked for 15 rows in each of `users`, `students`, `events`, `registrations`, and `audit_logs`.
- Security-sensitive authentication, guards, mutation endpoints, and student record lookup were reviewed and changed to enforce database roles, sessions, CSRF checks, and student ownership.
- The existing `python tests\verify_all.py` script was run. Its HTTP probes failed because no web server was accepting connections on localhost.

## Blocked integration checks

MySQL/MariaDB and Apache were stopped when tested (`mysql` connection refused and the verifier could not reach its HTTP endpoints). Therefore no database import, account creation/login, authorization session, CRUD transaction, upload, live API, audit row, dashboard count, or browser viewport test is claimed as passed.

## Run the remaining checks

1. Follow `setup_guide.md` to import the schema/seeds and set a local administrator password.
2. Start Apache and MySQL in XAMPP.
3. Run `python tests\verify_all.py`, then execute the scenarios in `../tests/test_cases.md` and `../tests/api_test_cases.md`.
4. Verify SQL counts against the admin dashboard and inspect `audit_logs`.
5. Test 375px, 768px, and desktop widths; inspect browser console/network; complete `screenshot_checklist.md`.
6. Record the actual machine, PHP/MySQL versions, test results, and any failures here before submission.

## Security test cases still requiring live execution

- Student account/password at student login succeeds and lands on the student dashboard.
- Admin credentials at student login are rejected; student credentials at admin login are rejected.
- Only locally configured, database-role-verified admin reaches admin pages or mutation APIs.
- Student and guest requests to admin CRUD/APIs are denied; student detail lookup exposes only the current account's own record.
- Invalid credentials, invalid role, expired session, logout, and CSRF failure produce the expected denial.
- Registration never accepts an admin role and stores a bcrypt hash.
- Student/event CRUD and poster upload validate input, file content, file size, and audit entries.
