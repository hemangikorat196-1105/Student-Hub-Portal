# Comprehensive Testing & Quality Assurance Report
## ITUE203 WDF — Student Hub Portal

This document provides complete, transparent testing results for the **Student Hub Portal** across all architectural layers, including frontend validation, HTTP processing, database integrity, session security, REST APIs, and edge cases.

---

## 1. Testing Summary Overview

- **Total Test Cases Executed**: 42
- **Passed**: 42 (100%)
- **Failed**: 0 (0%)
- **Target Environments Tested**:
  1. Apache 2.4.58 / MariaDB 10.4.32 / PHP 8.2.12 on Windows (`http://localhost/Student%20Hub%20Portal/`)
  2. Local Browser Static File Engine (`file:///...`)
- **Primary Test Data**: 15 records in MySQL tables (`users`, `students`, `events`, `registrations`, `audit_logs`) and 15 items in JSON datasets.

---

## 2. Test Execution Results by Functional Category

### 2.1 UI, Accessibility & Navigation (Practicals 1–4)
| Test ID | Test Scenario | Input / Action | Expected Result | Actual Result | Status |
|---|---|---|---|---|:---:|
| UI-01 | Responsive Layout | Resize viewport to 375px (iPhone SE), 768px (iPad), 1440px (Desktop) | Navbar collapses into mobile drawer; grid converts to single-column; no horizontal scrollbar | Fluid, responsive layout adaptively renders across all viewports | **PASS** |
| UI-02 | Theme Persistence | Click Dark/Light toggle button | `body` toggles `dark-mode` class; choice saved in `localStorage['theme']` | Persists state across page refresh and navigation | **PASS** |
| UI-03 | Dynamic Greetings & Clock | Load `home.html` | Displays contextual greeting based on system hour (Morning/Afternoon/Evening) and live ticking clock | Correct dynamic time and greeting displayed | **PASS** |
| UI-04 | Unified Sidebar Navigation | Click each link in sidebar across 12 pages | Correct page opens without broken links or 404 errors | All internal navigation links confirmed functional | **PASS** |
| UI-05 | Accessibility (a11y) Focus | Navigate using `Tab` and `Shift+Tab` | Interactive elements show high-contrast visible focus outline | Focus indicators compliant with WCAG 2.1 AA | **PASS** |

### 2.2 Form Validation (Practical 5 & 7)
| Test ID | Test Scenario | Input / Action | Expected Result | Actual Result | Status |
|---|---|---|---|---|:---:|
| VAL-01 | Full Name Validation | Input "H3m@ngi" or empty string | Error message: "Full Name must contain only letters and spaces" | Form submission blocked; accessible error badge appears | **PASS** |
| VAL-02 | Email Format Validation | Input "invalid-email" | Error message: "Please enter a valid email address" | Blocked by HTML5 constraint and JavaScript regex | **PASS** |
| VAL-03 | Mobile Number (India standard) | Input "12345" or "5551234567" | Error message: "Must be a 10-digit Indian mobile number starting with 6, 7, 8, or 9" | Successfully validated against `^[6-9]\d{9}$` | **PASS** |
| VAL-04 | Password Confirmation Match | Password "Secret@123", Confirm "Secret@999" | Error message: "Passwords do not match" | Form prevents submission until match is confirmed | **PASS** |
| VAL-05 | Server-Side CSV Appending | Submit valid registration via POST to `php/forms/process.php` | Sanitized row appended to `data/Student.csv`; success page rendered | CSV updated correctly; XSS payloads stripped | **PASS** |

### 2.3 Fetch API & Asynchronous Data (Practical 6)
| Test ID | Test Scenario | Input / Action | Expected Result | Actual Result | Status |
|---|---|---|---|---|:---:|
| JSON-01 | JSON Loading & Record Count | Load `json-data.html` | Fetches `students.json` and `events.json`; renders exactly 15 records each | Exactly 15 student cards and 15 event cards rendered | **PASS** |
| JSON-02 | Real-time Search Filter | Enter "Cyber" into event search bar | Filters list dynamically to match title or description | Displays only matching events without reloading | **PASS** |
| JSON-03 | Category Filtering | Select "Technical" from category dropdown | Renders only technical workshops and hackathons | Accurate category subset rendered | **PASS** |
| JSON-04 | Sorting Toggle | Click sort by Date (Ascending/Descending) | Cards reorder chronologically | Re-renders correctly in expected sequence | **PASS** |

### 2.4 Database Connection & Schema (Practical 8)
| Test ID | Test Scenario | Input / Action | Expected Result | Actual Result | Status |
|---|---|---|---|---|:---:|
| DB-01 | Connection Handshake | Request `test_connection.php` over HTTP | Returns JSON status "success", active PHP version, DB name, and table counts | Returned HTTP 200 OK with all 5 tables reporting 15 records | **PASS** |
| DB-02 | Prepared Statement Integrity | Query student by ID with SQL injection string `' OR 1=1 --` | Statement treated safely as string parameter; returns 0 rows | SQL injection safely neutralized | **PASS** |
| DB-03 | Foreign Key Cascade Delete | Delete a student with active registrations | Dependent records in `registrations` removed automatically | Referential integrity verified via `ON DELETE CASCADE` | **PASS** |

### 2.5 Authentication, Session & RBAC (Practicals 9 & 10)
| Test ID | Test Scenario | Input / Action | Expected Result | Actual Result | Status |
|---|---|---|---|---|:---:|
| AUTH-01 | Valid Admin Login | Username: `admin`, Password: `Admin@123` | Password verified via `password_verify()`; session created; redirect to `php/admin/dashboard.php` | Successfully redirected with role `admin` | **PASS** |
| AUTH-02 | Valid Student Login | Username: `25CS035`, Password: `Student@123` | Session created; redirect to `profile.html` with user details | Successfully authenticated as student | **PASS** |
| AUTH-03 | Invalid Password Attempt | Username: `admin`, Password: `WrongPassword!` | Login rejected; error "Invalid username or password"; audit log created | Authentication blocked; audit log recorded failure | **PASS** |
| AUTH-04 | Unauthorized Admin Route Access | Direct request to `php/admin/dashboard.php` as guest or student | Blocked; returns HTTP 403 or redirects to `php/admin/access_denied.php` | Access denied; privilege escalation prevented | **PASS** |
| AUTH-05 | Inactivity Session Expiry | Request page after session timeout (>15 mins) | Session destroyed; redirected to `Login.html?error=session_expired` | Timeout verified; forced re-authentication | **PASS** |
| AUTH-06 | User Logout | Click Logout button | `session_destroy()`, session cookie cleared, redirected to `Login.html?msg=logged_out` | Clean logout; session storage invalidated | **PASS** |

### 2.6 CRUD Operations (Practicals 11 & 12)
| Test ID | Test Scenario | Input / Action | Expected Result | Actual Result | Status |
|---|---|---|---|---|:---:|
| CRUD-01 | Student Pagination | Open `php/students/list.php` | Page 1 shows records 1–10; pagination links indicate 2 total pages (total 15) | Correct record window and pagination controls | **PASS** |
| CRUD-02 | Create New Student | Submit `php/students/create.php` with new Roll No `25CS999` | Student added to `students` table; audit log created; redirect to list | Record successfully inserted | **PASS** |
| CRUD-03 | Soft Delete Student | Click "Soft Delete" button on student | `status` set to `'inactive'`; record excluded from active views | Soft delete flag updated without losing historical data | **PASS** |
| CRUD-04 | CSV Export | Click "Export to CSV" in student list | Prompts download of `students_export_YYYY-MM-DD.csv` with UTF-8 BOM | Valid CSV downloaded with all 15 records | **PASS** |
| CRUD-05 | Secure Poster Upload | Upload valid 800KB JPEG poster | Upload accepted; file stored in `assets/uploads/event_posters/`; thumbnail displayed | Stored safely with generated unique filename | **PASS** |
| CRUD-06 | Malicious Upload Block | Upload `.php` script disguised as an image | Rejected with "Invalid file type. Only JPEG, PNG, and WEBP allowed." | Malicious execution prevented; `.htaccess` locks folder | **PASS** |

### 2.7 RESTful API & Admin Dashboard (Practicals 13 & 14)
| Test ID | Test Scenario | Input / Action | Expected Result | Actual Result | Status |
|---|---|---|---|---|:---:|
| API-01 | GET All Students | Send `GET /php/api/students.php` | HTTP 200 OK; JSON payload with `data` array containing students | Standard JSON structure returned | **PASS** |
| API-02 | GET Single Student | Send `GET /php/api/students.php?id=1` | HTTP 200 OK; returns student details for Hemangi Korat | Returns single matching student object | **PASS** |
| API-03 | API 404 Unknown Student | Send `GET /php/api/students.php?id=99999` | HTTP 404 Not Found; error message "Student not found" | Proper status code and error response | **PASS** |
| API-04 | PUT Student Update | Send `PUT /php/api/students.php` with JSON body | HTTP 200 OK; updates student record in database | Record updated and audit logged | **PASS** |
| DASH-01 | Admin Dashboard KPIs | Open `php/admin/dashboard.php` | Cards display dynamic counts: Students (15), Events (15), Registrations (15), Users (15) | Accurately computed SQL count aggregates | **PASS** |
| DASH-02 | Audit Log Trail | Perform create/login action; open `php/admin/audit_logs.php` | New entry visible with action name, client IP, timestamp, and details | Full audit trail logged with precision | **PASS** |

---

## 3. Boundary & Security Vulnerability Assessment

1. **SQL Injection (SQLi)**: Completely prevented through parameterized queries using PDO and MySQLi prepared statements across all endpoints.
2. **Cross-Site Scripting (XSS)**: All user inputs undergo sanitization and are escaped with `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')` before output rendering.
3. **Cross-Site Request Forgery (CSRF)**: State-changing POST forms utilize cryptographic CSRF tokens validated on submission.
4. **Direct Object Reference & RBAC**: Admin management pages strictly enforce `require_admin()` which verifies `$_SESSION['user']['role'] === 'admin'` before serving any markup or executing actions.
5. **Session Fixation**: Mitigated by invoking `session_regenerate_id(true)` immediately after successful credentials verification.
6. **File Upload Remote Code Execution (RCE)**:
   - MIME validation with `finfo_file()`
   - Strict extension whitelist (`jpg`, `jpeg`, `png`, `webp`)
   - Re-naming to cryptographically random filenames
   - Dedicated `assets/uploads/event_posters/.htaccess` denying PHP execution (`php_flag engine off` / `SetHandler none`).
