# Student Hub Portal — Detailed Test Cases
## ITUE203 WDF — Test Specifications & Execution Procedures

This document outlines the test cases covering UI interactivity, form validation, authentication, authorization, session lifecycle, file uploads, and data consistency.

---

## 1. Test Case Specifications

### TC-UI-01: Responsive Layout & Mobile Drawer
- **Objective**: Verify that the portal UI reflows gracefully across mobile, tablet, and desktop viewports without clipping or horizontal overflow.
- **Preconditions**: Browser window open on `home.html`.
- **Steps**:
  1. Set viewport to 1280px (Desktop): observe multi-column grid, horizontal navigation bar.
  2. Set viewport to 768px (Tablet): observe responsive grid adjusting to 2 columns.
  3. Set viewport to 375px (Mobile): observe hamburger menu icon, click hamburger.
- **Expected Result**: Drawer slides out smoothly; navbar links are accessible and clickable; no horizontal scrollbar appears.
- **Status**: Not run in current environment.

### TC-UI-02: Dark/Light Mode Switcher with LocalStorage
- **Objective**: Ensure the theme toggle persists the user's preference across pages and reloads.
- **Preconditions**: Navigate to any page (e.g. `index.html`).
- **Steps**:
  1. Click theme toggle button in navbar.
  2. Verify dark theme styles applied (background color `#111827`, text `#f9fafb`).
  3. Refresh the page (`F5`).
  4. Navigate to `aboutus.html`.
- **Expected Result**: Dark theme remains active on reload and across different pages.
- **Status**: Not run in current environment.

### TC-VAL-01: Client-Side Registration Validation
- **Objective**: Validate all constraints on `Register.html` prior to server submission.
- **Preconditions**: Open `Register.html`.
- **Test Scenarios**:
  - **Full Name**: Enter `Hemangi123` -> Error: "Full Name must contain only letters and spaces".
  - **Email**: Enter `student@` -> Error: "Please enter a valid email address".
  - **Mobile**: Enter `12345` -> Error: "Must be a 10-digit Indian mobile number starting with 6-9".
  - **Password**: Enter `weak` -> Error: "Must be at least 8 chars with uppercase, lowercase, number, and special character".
  - **Confirm Password**: Enter mismatched password -> Error: "Passwords do not match".
- **Expected Result**: Submit button blocked until all field constraints are satisfied.
- **Status**: Not run in current environment.

### TC-AUTH-01: Secure Login & Role-Based Redirection
- **Objective**: Verify separate student and administrator login endpoints and fixed role checks.
- **Test Data**:
  - Student: `25CS035` / `Student@123`
  - Admin: configure a unique local password by following `docs/setup_guide.md`; do not store it in this repository.
- **Steps**:
  1. Navigate to `Login.html`.
  2. Enter student credentials and submit.
  3. Verify landing page is `php/students/dashboard.php`.
  4. Attempt the student credentials on `admin-login.html`; verify denial.
  5. Sign out; use the local administrator credentials on `admin-login.html` and verify `php/admin/dashboard.php`.
- **Expected Result**: Each login accepts only its database role; sessions are regenerated on success.
- **Status**: Not run in current environment.

### TC-AUTH-02: Privilege Escalation Prevention (RBAC)
- **Objective**: Ensure non-admin users cannot access administrative endpoints.
- **Preconditions**: Log in as student `25CS035` or remain logged out as guest.
- **Steps**:
  1. Attempt direct navigation to `http://localhost/Student%20Hub%20Portal/php/admin/dashboard.php`.
  2. Attempt direct navigation to `http://localhost/Student%20Hub%20Portal/php/admin/users.php`.
- **Expected Result**: Server responds with HTTP 403 Forbidden or redirects to `php/admin/access_denied.php`.
- **Status**: Not run in current environment.

### TC-AUTH-03: Session Inactivity Timeout
- **Objective**: Confirm that idle sessions expire after 15 minutes.
- **Preconditions**: Log in as any authenticated user.
- **Steps**:
  1. Let session idle for greater than 900 seconds (15 minutes).
  2. Attempt to navigate or refresh an authenticated page.
- **Expected Result**: `php/includes/session.php` identifies `time() - $_SESSION['last_activity'] > 900`, destroys the session, and requires re-authentication.
- **Status**: Not run in current environment.

### TC-CRUD-01: Student Soft-Delete
- **Objective**: Verify soft deletion preserves record for audit while hiding from active lists.
- **Preconditions**: Logged in as administrator.
- **Steps**:
  1. Navigate to `php/students/list.php`.
  2. Click "Soft Delete" on student record.
  3. Inspect MySQL table `students` for that record.
- **Expected Result**: `status` field updated to `'inactive'`; record excluded from active queries; related registration and audit records remain intact.
- **Status**: Not run in current environment.

### TC-UPLOAD-01: Secure File Upload Constraints
- **Objective**: Test event poster file upload validation for size, MIME type, and executable protection.
- **Preconditions**: Access `php/events/create.php` as administrator.
- **Test Scenarios**:
  - **Oversized File (> 2MB)**: Upload 3.5MB image -> Error: "File size exceeds 2 MB limit".
  - **Invalid Extension**: Upload `test.exe` or `shell.php` -> Error: "Invalid file type. Only JPEG, PNG, and WEBP allowed."
  - **Spoofed Extension**: Upload a text file named `test.png` -> MIME inspection via `finfo_file()` detects non-image payload -> Error: "File content does not match image type".
  - **Valid JPEG (800KB)**: Upload valid poster -> Stored as `poster_<unique_id>.jpg` in `assets/uploads/event_posters/`.
- **Expected Result**: Only verified images under 2MB accepted; `.htaccess` prevents execution of uploaded scripts.
- **Status**: Not run in current environment.
