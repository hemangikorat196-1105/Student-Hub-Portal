# Student Hub Portal — Comprehensive System Requirements Specification (SRS)

## 1. Project Overview & Scope

The **Student Hub Portal** is an integrated academic management, student administrative, and campus event coordination platform designed for the **ITUE203 Web Development Frameworks (WDF)** course curriculum at the Department of Computer Science & Engineering, CSPIT, CHARUSAT. The portal consolidates student registration, academic tracking (attendance, assignments, courses, timetables), dynamic campus event administration, and security auditing into a unified, accessible, and responsive web application.

---

## 2. User Roles & Personas

| Role | Description | Access Rights & Privileges |
| :--- | :--- | :--- |
| **Guest / Public** | Unauthenticated visitors, prospective students, and parents. | Can browse welcome page, About Us, courses catalog, public campus notices, and FAQs. May initiate student registration or access login. |
| **Student** | Enrolled undergraduate or postgraduate student (e.g. ID `25CS035`). | Authenticates via credentials; views personalized academic profile, attendance percentages, course modules, assignments, timetables; registers for campus events; searches and filters campus directory. |
| **Administrator** | Faculty coordinator, department head, or system administrator (`admin`). | Full administrative oversight; views aggregated real-time metric dashboards; performs CRUD on student records (including soft-delete & restore); manages campus events and poster uploads; manages user accounts and active/inactive status toggles; inspects system security audit logs; exports data to CSV. |

---

## 3. Practical-by-Practical Functional Requirements

### Practical 1: Project Initiation & GitHub Repository
- **Scope & Navigation**: Define architecture for at least 10 core pages.
- **Artifacts**: Requirement specifications, sitemap, wireframes, and GitHub setup (`https://github.com/hemangikorat196-1105/Student-Hub-Portal`).

### Practical 2: Semantic HTML5 & Accessibility (WCAG 2.1 AA)
- **Semantic Structure**: Proper usage of `<header>`, `<nav>`, `<main>`, `<section>`, `<article>`, `<aside>`, `<fieldset>`, `<legend>`, and `<footer>`.
- **Accessibility**: Keyboard focus outlines (`:focus-visible`), skip-to-content links (`.skip-link`), ARIA live regions for error alerts (`aria-live="polite"`), descriptive `aria-label` tags, and valid labels for all form inputs.

### Practical 3: Responsive Layout with CSS Grid & Flexbox
- **Mobile-First Layout**: Fluid layouts adapting across mobile (<480px), tablet (481px–768px), and desktop (>768px).
- **Modern CSS**: CSS Custom Properties (variables) for consistent theming, CSS Grid for card layouts, and Flexbox for headers, navigation, and toolbars.

### Practical 4: JavaScript DOM Manipulation & Interactivity
- **Dynamic Components**:
  1. Collapsible accordion FAQs.
  2. Modal dialog popups for event registration with focus trapping and ESC key dismissal.
  3. Interactive multi-slide carousel banner with autoplay and manual controls.
  4. Dismissible notification banner.
  5. Hamburger drawer navigation for mobile viewports.
  6. Dark/Light mode theme switcher persisted in `localStorage`.

### Practical 5: Registration Form with Frontend Validation
- **Input Types**: `text`, `email`, `tel`, `password`, `select`, `radio`, `checkbox`.
- **Validation**:
  - Full Name: Regex `^[A-Za-z\s]{2,50}$`.
  - Email: Standard email regex.
  - Mobile: Indian 10-digit regex `^[6-9]\d{9}$`.
  - Password: Min 8 chars, 1 uppercase, 1 lowercase, 1 number, 1 special character.
  - Password strength meter evaluating Weak, Medium, and Strong states.
  - Confirm password matching.
  - Dynamic HTML5 Canvas CAPTCHA generation and client verification.
  - Terms and conditions checkbox acceptance.

### Practical 6: Fetch API & JSON Data Exploration
- **Datasets**: Exactly 15 valid records each in `events.json`, `students.json`, and `faqs.json`.
- **Features**: Asynchronous Fetch API loading, real-time client-side search, category/course filters, multi-field sorting, client-side pagination, loading spinner, and retry button for network errors.

### Practical 7: PHP Form Processing & CSV Storage
- **Server-Side Validation**: Validate and sanitize all POST input using `htmlspecialchars`, `trim`, and `filter_var`.
- **File Storage**: Append valid student registrations to `Student.csv` and `data/students.csv`.
- **Feedback**: Render detailed success or failure feedback pages.

### Practical 8: 3NF Database Design & PDO Connectivity
- **Schema**: Fully normalized 3NF schema with tables `users`, `students`, `events`, `registrations`, and `audit_logs` enforcing primary keys, foreign keys, and indexes.
- **Connectivity**: Established via PHP PDO and MySQLi with `try-catch` exception handling and parameter binding.

### Practical 9: Secure Registration with Password Hashing & Duplicate Checks
- **Security**: Hash passwords with `password_hash($pwd, PASSWORD_BCRYPT)`.
- **Integrity**: Verify duplicate email and username before inserting into MySQL database.
- **Traceability**: Record `REGISTER` event in `audit_logs`.

### Practical 10: Authentication, Sessions, RBAC & Timeout
- **Verification**: Authenticate credentials via `password_verify()`.
- **Session Protection**: Call `session_regenerate_id(true)` to defeat session fixation; enforce 15-minute inactivity session timeout.
- **RBAC**: Enforce server-side role check; route admin to `php/admin/dashboard.php` and student to profile. Block unauthorized dashboard access with HTTP 403.
- **Tokens**: Support secure remember-me token stored as a SHA-256 hash in the database.

### Practical 11: Student Management CRUD, Soft Delete & CSV Export
- **Operations**: Create, Read (paginated), Update, and Delete students.
- **Soft Deletion**: Flag status as `inactive` instead of permanent deletion, with restore option.
- **Export**: Stream student records to downloadable CSV file with Excel UTF-8 BOM.

### Practical 12: Event Management CRUD & Secure Poster Uploads
- **Operations**: Admin event creation, viewing, updating, and deletion.
- **File Upload Security**:
  - Enforce max 2 MB file size.
  - Inspect true MIME type via `finfo` (`image/jpeg`, `image/png`, `image/webp`).
  - Generate cryptographically random unique filenames (`poster_[hash].[ext]`).
  - Disable script execution in upload directory via `.htaccess`.

### Practical 13: RESTful PHP JSON API & Asynchronous CRUD
- **Endpoints**: `php/api/students.php`, `php/api/events.php`, `php/api/faqs.php`.
- **HTTP Methods**: GET (read/list), POST (create), PUT (update), DELETE (delete/soft-delete).
- **Status Codes**: 200 OK, 201 Created, 400 Bad Request, 401 Unauthorized, 403 Forbidden, 404 Not Found, 409 Conflict, 422 Unprocessable Entity, 500 Internal Error.

### Practical 14: Dynamic Admin Dashboard & Security Audit Trail
- **Live Aggregations**: Dynamic SQL count queries for total students, active students, events, registrations, users, and audit logs.
- **Role Menus**: Navigation dynamically tailored to user role.
- **Audit Trail**: View and filter system events (`LOGIN`, `LOGOUT`, `REGISTER`, `CREATE_STUDENT`, `UPLOAD_POSTER`, `UNAUTHORIZED_ACCESS`).
- **User Toggle**: Active/Inactive user status toggle.

### Practical 15: End-to-End Integration, Documentation & XAMPP Deployment
- **Integration**: Seamless execution across all HTML, CSS, JavaScript, PHP, and MySQL components.
- **Deployment**: Configured to run on `http://localhost/Student%20Hub%20Portal/` via XAMPP Apache & MariaDB.

---

## 4. Non-Functional Requirements

1. **Security**:
   - Zero raw credential leakage.
   - Protection against SQL injection via prepared statements.
   - Protection against Cross-Site Scripting (XSS) via `htmlspecialchars`.
   - Protection against Cross-Site Request Forgery (CSRF) via random tokens.
   - Protection against session fixation and session hijacking.
2. **Performance**: Page loads under 800ms on localhost; asynchronous operations without page flicker.
3. **Data Integrity**: Exactly 15 valid records in each dataset (`students.json`, `events.json`, `faqs.json`, `users` table, `students` table, `events` table, `registrations` table, and `audit_logs` table).
4. **Compatibility**: Fully compatible with Chrome, Firefox, Edge, and Safari; responsive across mobile, tablet, and desktop viewports.
