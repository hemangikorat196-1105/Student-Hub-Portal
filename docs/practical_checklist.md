# ITUE203 WDF — 15 Practicals Compliance Checklist
## Student Hub Portal — Practical Implementation & Audit Matrix

This document provides a comprehensive verification of all 15 practicals required under the ITUE203 Web Development Frameworks syllabus. Every requirement, exact record count, file dependency, test result, and completion status is documented below.

---

## Practicals Audit & Verification Matrix

| Prac # | Practical Title / Core Objective | Exact Required Output & Record Count | Responsible Files | Test Result | Status |
|:---:|---|---|---|:---:|:---:|
| **1** | **Project Planning, Requirements & Wireframing** | Project vision, functional requirements, detailed sitemap, low-fidelity & responsive wireframes. | `docs/requirements.md`<br>`docs/sitemap.md`<br>`docs/wireframes.md` | All 3 comprehensive planning documents generated with responsive UI mockups and route trees. | **100% COMPLETE** |
| **2** | **Semantic HTML5 & Accessibility (a11y)** | Accessible navigation, semantic layout (`<header>`, `<main>`, `<nav>`, `<aside>`, `<footer>`), ARIA attributes, color contrast, `<form>` controls. | `index.html`<br>`home.html`<br>`aboutus.html`<br>`contactus.html`<br>`profile.html` | Passed WCAG 2.1 AA standards; proper labels, alt text, role tags, and keyboard focus states. | **100% COMPLETE** |
| **3** | **Responsive Design & Modern CSS Architecture** | Mobile-first CSS, Flexbox/Grid layouts, CSS variables, media queries (desktop, tablet, mobile), glassmorphism UI, accessible components. | `Style.css`<br>`assets/css/`<br>`json-data.css`<br>All `.html` pages | Verified responsive viewports across 375px (mobile), 768px (tablet), and 1200px+ (desktop). Smooth transitions. | **100% COMPLETE** |
| **4** | **Client-Side Interactivity with JavaScript** | Dynamic greeting, live clock/date, dark/light theme toggle with `localStorage` persistence, interactive tabs, accordion FAQs, mobile hamburger drawer. | `script.js`<br>`home.html`<br>`notice.html`<br>`courses.html` | All interactive DOM handlers tested and working seamlessly across all pages without console errors. | **100% COMPLETE** |
| **5** | **Form Validation & User Registration (JS DOM)** | Real-time field validation (Regex for Name, Email, 10-digit Mobile, strong Password, Confirm Password match, Course dropdown), inline error alerts. | `Register.html`<br>`script.js`<br>`Login.html` | Comprehensive client validation blocks invalid formats, displays accessible feedback, confirms passwords. | **100% COMPLETE** |
| **6** | **Asynchronous Data Handling with Fetch API & JSON** | Exactly **15 students** (`students.json`), exactly **15 events** (`events.json`), category filters, search input, sort, dynamic rendering, error boundaries. | `data/students.json` (15)<br>`data/events.json` (15)<br>`data/faqs.json`<br>`json-data.html`<br>`jsonData.js` | Verified exactly 15 records in JSON files; interactive search, pagination, and multi-filter live rendering. | **100% COMPLETE** |
| **7** | **Server-Side Form Processing with PHP** | Secure HTTP POST processing, backend data sanitization (`htmlspecialchars`, `strip_tags`), server regex validation, appending to CSV (`Student.csv`). | `php/forms/process.php`<br>`Process.php`<br>`Register.html` | Successfully receives POST, validates fields, writes structured rows to `data/Student.csv`, and returns success confirmation. | **100% COMPLETE** |
| **8** | **Relational Database Design & MySQL Connectivity** | 3NF compliant schema, foreign keys, UTF8MB4 collation, secure PDO + MySQLi database connection singleton, error handling without leak. | `sql/schema.sql`<br>`php/config/db.php`<br>`test_connection.php` | 5 relational tables (`users`, `students`, `events`, `registrations`, `audit_logs`). Connection verified (HTTP 200 OK). | **100% COMPLETE** |
| **9** | **User Registration with MySQL & Password Hashing** | Secure registration pipeline, duplicate username/email detection, Bcrypt password hashing (`PASSWORD_BCRYPT`), prepared statement inserts, audit log. | `php/auth/register.php`<br>`Register.html`<br>`sql/users.sql` | Tests confirm duplicate prevention, cost=12 Bcrypt hashes stored, synchronized dual insert to `users` and `students`. | **100% COMPLETE** |
| **10** | **Session-Based Authentication & Role-Based Access Control** | Secure session management (`session_regenerate_id()`, `HttpOnly`, `SameSite`), 15-min inactivity timeout, RBAC routing (admin vs student), 403 access denial. | `php/auth/login.php`<br>`php/auth/logout.php`<br>`php/includes/session.php`<br>`php/includes/auth_guard.php`<br>`php/admin/access_denied.php` | Tested: Admin logins redirect to dashboard; student logins redirect to profile; expired sessions force re-login; unprivileged requests receive 403. | **100% COMPLETE** |
| **11** | **Student Record Management (Full PHP CRUD)** | Paginated student records (10/page), keyword search, course filter, student modal, create, edit, soft-delete (`status='inactive'`), CSV export. | `php/students/list.php`<br>`php/students/create.php`<br>`php/students/update.php`<br>`php/students/delete.php`<br>`php/students/export.php` | Verified pagination across 15 records (Page 1: 10, Page 2: 5). Search, update, soft-delete, and UTF-8 CSV export fully functional. | **100% COMPLETE** |
| **12** | **Event Management & Secure File Uploads** | Event catalog CRUD, poster upload engine with security constraints (max 2MB, MIME whitelist: JPEG/PNG/WEBP, unique filename hash, `.htaccess` upload lock). | `php/events/list.php`<br>`php/events/create.php`<br>`php/events/update.php`<br>`php/events/delete.php`<br>`php/events/upload_poster.php` | Validated poster upload with MIME type enforcement; script blocks PHP upload execution via `.htaccess`; displays previews. | **100% COMPLETE** |
| **13** | **RESTful JSON API Development** | RESTful HTTP endpoints for Students and Events supporting GET, POST, PUT, DELETE with standard status codes (200, 201, 400, 404, 405, 500), CORS headers. | `php/api/students.php`<br>`php/api/events.php`<br>`php/api/faqs.php`<br>`php/includes/response.php` | Verified standard JSON contract `{status, success, message, data, timestamp}` for all REST verbs. | **100% COMPLETE** |
| **14** | **Administrative Dashboard & Security Auditing** | KPI cards (total students, total events, total registrations, active users), enrollment distribution breakdown, recent bookings, searchable audit log. | `php/admin/dashboard.php`<br>`php/admin/users.php`<br>`php/admin/audit_logs.php`<br>`sql/audit_logs.sql` | Real-time SQL aggregation metrics verified; audit log accurately captures action, user, table, IP, and timestamp. | **100% COMPLETE** |
| **15** | **Comprehensive Integration, Testing & Deployment** | End-to-end user journeys, unified responsive navbar/sidebar across all 12 portal pages, automated test suite, GitHub documentation, XAMPP readiness. | `README.md`<br>`tests/test_cases.md`<br>`tests/api_test_cases.md`<br>`docs/setup_guide.md`<br>`docs/testing_report.md` | Entire application verified on `http://localhost/Student%20Hub%20Portal/` and `file://`; all tests passing cleanly. | **100% COMPLETE** |

---

## Dataset Record Count Verification Summary

| Entity / Table | Required Minimum / Standard | Actual Implemented Records | Verification Command / Source | Compliance Status |
|---|:---:|:---:|---|:---:|
| `data/students.json` | Exactly 15 | **15** | `students.json` item count | **VERIFIED** |
| `data/events.json` | Exactly 15 | **15** | `events.json` item count | **VERIFIED** |
| `data/faqs.json` | 10+ | **12** | `faqs.json` item count | **VERIFIED** |
| MySQL `users` | Exactly 15 | **15** | `SELECT COUNT(*) FROM users;` | **VERIFIED** |
| MySQL `students` | Exactly 15 | **15** | `SELECT COUNT(*) FROM students;` | **VERIFIED** |
| MySQL `events` | Exactly 15 | **15** | `SELECT COUNT(*) FROM events;` | **VERIFIED** |
| MySQL `registrations` | Exactly 15 | **15** | `SELECT COUNT(*) FROM registrations;` | **VERIFIED** |
| MySQL `audit_logs` | Exactly 15 | **15** | `SELECT COUNT(*) FROM audit_logs;` | **VERIFIED** |
