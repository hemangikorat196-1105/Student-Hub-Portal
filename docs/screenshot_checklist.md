# Lab Manual Screenshot Checklist
## ITUE203 WDF — Student Hub Portal Practical Submission Guide

This document lists all screenshots required to assemble your formal laboratory assignment submission files (`25CS035-Practical 1.docx` through `15.docx`). Follow this checklist when capturing evidence for each practical.

---

## Practical-by-Practical Screenshot Guide

### Practical 1: Project Planning, Requirements & Wireframing
- [ ] **Screenshot 1.1**: Open `docs/requirements.md` in browser or editor showing the functional and non-functional requirements.
- [ ] **Screenshot 1.2**: Open `docs/sitemap.md` displaying the hierarchical visual sitemap.
- [ ] **Screenshot 1.3**: Open `docs/wireframes.md` showing the desktop and mobile layout wireframes.

### Practical 2: Semantic HTML5 Layout & Accessibility
- [ ] **Screenshot 2.1**: Browser view of `home.html` showing semantic `<header>`, `<nav>`, `<main>`, `<aside>`, and `<footer>` sections.
- [ ] **Screenshot 2.2**: Browser view of `aboutus.html` showing structured cards with accessibility attributes.
- [ ] **Screenshot 2.3**: Chrome DevTools Elements panel displaying semantic HTML tags and ARIA labels.

### Practical 3: Responsive Design & CSS Architecture
- [ ] **Screenshot 3.1**: Desktop layout view of `home.html` (1200px+ width) showing multi-column grid and hero banner.
- [ ] **Screenshot 3.2**: Tablet layout view (768px width) using Chrome DevTools Device Emulation.
- [ ] **Screenshot 3.3**: Mobile layout view (375px width) showing single-column layout, responsive cards, and mobile navigation.

### Practical 4: JavaScript DOM Manipulation & Interactivity
- [ ] **Screenshot 4.1**: `home.html` with light mode active, showing dynamic greeting and current timestamp.
- [ ] **Screenshot 4.2**: `home.html` after clicking Dark Mode toggle, showing inverted high-contrast dark theme.
- [ ] **Screenshot 4.3**: Mobile view with hamburger menu toggled open showing the slide-out navigation drawer.

### Practical 5: Form Validation with JavaScript
- [ ] **Screenshot 5.1**: `Register.html` initial blank form.
- [ ] **Screenshot 5.2**: `Register.html` showing inline validation error badges when clicking Submit with empty/invalid inputs.
- [ ] **Screenshot 5.3**: `Register.html` with all fields successfully validated (green checkmarks/success states).

### Practical 6: Asynchronous Fetch API & JSON Data Handling
- [ ] **Screenshot 6.1**: `json-data.html` showing student cards loaded from `students.json` (showing exactly 15 records).
- [ ] **Screenshot 6.2**: `json-data.html` with real-time search query filtering results dynamically.
- [ ] **Screenshot 6.3**: `json-data.html` displaying event cards loaded from `events.json` with category filter applied.

### Practical 7: PHP Form Processing & CSV Storage
- [ ] **Screenshot 7.1**: `Register.html` filled with student details and submitted via POST.
- [ ] **Screenshot 7.2**: Browser confirmation page from `php/forms/process.php` displaying sanitized submitted data.
- [ ] **Screenshot 7.3**: File viewer showing newly appended row in `data/Student.csv`.

### Practical 8: MySQL Database Schema & Connection
- [ ] **Screenshot 8.1**: phpMyAdmin interface showing `student_portal` database structure with all 5 tables (`users`, `students`, `events`, `registrations`, `audit_logs`).
- [ ] **Screenshot 8.2**: phpMyAdmin Designer view showing relational foreign key links between tables.
- [ ] **Screenshot 8.3**: Browser output of `http://localhost/Student%20Hub%20Portal/test_connection.php` displaying successful JSON connection and 15 records per table.

### Practical 9: User Registration with MySQL & Password Hashing
- [ ] **Screenshot 9.1**: Registration form submission on `Register.html` with new user details.
- [ ] **Screenshot 9.2**: phpMyAdmin browsing `users` table showing the new record with its 60-character Bcrypt hash (`$2y$12$...`).
- [ ] **Screenshot 9.3**: phpMyAdmin browsing `students` table showing the corresponding synchronized student profile.

### Practical 10: Session-Based Authentication & Role-Based Access Control
- [ ] **Screenshot 10.1**: `Login.html` student login screen (do not capture passwords).
- [ ] **Screenshot 10.2**: Separate `admin-login.html` screen (do not capture passwords).
- [ ] **Screenshot 10.3**: Admin dashboard after configuring a local admin account.
- [ ] **Screenshot 10.4**: Access denied for a student account requesting an admin URL.

### Practical 11: Student Record CRUD Management
- [ ] **Screenshot 11.1**: Paginated student list (`php/students/list.php`) showing first 10 students with search bar and filter controls.
- [ ] **Screenshot 11.2**: Edit student modal/page (`php/students/update.php`) updating a student's course or email.
- [ ] **Screenshot 11.3**: Student list showing soft-deleted status badge (`Inactive`) or success notification after updating.
- [ ] **Screenshot 11.4**: Exported CSV file opened in Excel/Notepad displaying exported student records.

### Practical 12: Event Management & Secure File Uploads
- [ ] **Screenshot 12.1**: Event management table (`php/events/list.php`) displaying event listings, dates, venues, and poster thumbnails.
- [ ] **Screenshot 12.2**: Event creation form (`php/events/create.php`) with image file selected for upload.
- [ ] **Screenshot 12.3**: Newly created event appearing in the list with uploaded poster thumbnail rendered.
- [ ] **Screenshot 12.4**: Directory view of `assets/uploads/event_posters/` displaying the securely renamed uploaded file.

### Practical 13: RESTful JSON Web APIs
- [ ] **Screenshot 13.1**: Browser / Postman / ThunderClient showing `GET http://localhost/Student%20Hub%20Portal/php/api/students.php` returning JSON with 200 OK status.
- [ ] **Screenshot 13.2**: `GET http://localhost/Student%20Hub%20Portal/php/api/students.php?id=1` showing single student JSON object.
- [ ] **Screenshot 13.3**: `POST` or `PUT` request showing JSON response with 200/201 status and success message.

### Practical 14: Administrative Dashboard & Audit Logging
- [ ] **Screenshot 14.1**: Full view of Admin Dashboard (`php/admin/dashboard.php`) showing 4 KPI stat cards and course breakdown chart.
- [ ] **Screenshot 14.2**: Recent Event Registrations widget on the dashboard.
- [ ] **Screenshot 14.3**: Security Audit Logs table (`php/admin/audit_logs.php`) showing chronological tracking of user logins, creations, updates, and deletes.

### Practical 15: Full Integration & Deployment
- [ ] **Screenshot 15.1**: Main Landing Page (`index.html`) running on `http://localhost/Student%20Hub%20Portal/`.
- [ ] **Screenshot 15.2**: XAMPP Control Panel showing Apache and MySQL running on ports 80 and 3306.
- [ ] **Screenshot 15.3**: GitHub repository web page showing commit history and organized folder structure.
- [ ] **Screenshot 15.4**: Terminal output from `tests/verify_all.py`, including actual passed, failed, and skipped checks.
