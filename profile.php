<?php
// ============================================================================
// Student Hub Portal - Student Profile Page
// Read-only academic details (ID, Name, Email, Course, Semester, Year, Admission Date)
// Editable details restricted strictly to: (1) Mobile Number, (2) Password.
// ============================================================================

require_once __DIR__ . '/php/config/db.php';
require_once __DIR__ . '/php/includes/session.php';
require_once __DIR__ . '/php/includes/auth_guard.php';
require_once __DIR__ . '/php/includes/csrf.php';

init_session();
require_student();

$user = current_user();
$db = get_db_connection();

$stmt = $db->prepare("
    SELECT s.id, s.student_id, s.name, s.email, s.mobile, s.course, s.semester, s.year,
           s.department, s.admission_date, s.gender, s.cgpa, s.city, s.status, s.created_at,
           u.last_login
    FROM students s
    INNER JOIN users u ON s.user_id = u.id
    WHERE s.user_id = ? AND s.status = 'active'
    LIMIT 1
");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    http_response_code(403);
    exit("No active student profile is linked to this account.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile - Student Hub Portal</title>
    <link rel="stylesheet" href="Style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="script.js" defer></script>
    <style>
        .profile-container {
            width: min(94%, 1100px);
            margin: 30px auto 60px;
            display: flex;
            flex-direction: column;
            gap: 25px;
            font-family: 'Segoe UI', Tahoma, sans-serif;
        }
        .profile-hero {
            background: linear-gradient(135deg, #1e3a8a 0%, #0284c7 100%);
            color: #ffffff;
            padding: 30px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            box-shadow: 0 4px 20px rgba(2, 132, 199, 0.25);
        }
        body.dark-mode .profile-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border: 1px solid rgba(56, 189, 248, 0.25);
        }
        .profile-hero-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .profile-avatar {
            width: 72px;
            height: 72px;
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.4);
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-size: 2rem;
            color: #ffffff;
            flex-shrink: 0;
        }
        .profile-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 24px;
        }
        @media (max-width: 860px) {
            .profile-grid { grid-template-columns: 1fr; }
            .profile-hero { flex-direction: column; align-items: flex-start; }
        }
        .profile-card {
            background: var(--card-bg, #ffffff);
            border-radius: 12px;
            padding: 24px 28px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
        }
        .card-header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border-color);
        }
        .card-header-bar h2 {
            font-size: 1.25rem;
            margin: 0;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card-header-bar h2 i { color: var(--primary-color); }
        .readonly-badge {
            font-size: 0.75rem;
            background: #f1f5f9;
            color: #475569;
            padding: 3px 8px;
            border-radius: 6px;
            font-weight: 600;
        }
        .profile-table {
            width: 100%;
            border-collapse: collapse;
        }
        .profile-table tr {
            border-bottom: 1px solid var(--border-color);
        }
        .profile-table tr:last-child { border-bottom: none; }
        .profile-table th {
            text-align: left;
            padding: 11px 8px;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.9rem;
            width: 42%;
        }
        .profile-table td {
            padding: 11px 8px;
            color: var(--text-color);
            font-weight: 500;
            font-size: 0.92rem;
        }
        .edit-form-box {
            background: var(--primary-light, #f8fafc);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 18px 20px;
            margin-bottom: 20px;
        }
        .edit-form-box:last-child { margin-bottom: 0; }
        .edit-form-box h3 {
            font-size: 1.05rem;
            margin: 0 0 12px;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .edit-form-box label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 5px;
        }
        .edit-form-box input {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 0.92rem;
            background: var(--card-bg, #ffffff);
            color: var(--text-color);
            box-sizing: border-box;
            margin-bottom: 12px;
        }
        .btn-update {
            padding: 8px 18px;
            background: var(--primary-color);
            color: #ffffff;
            border: none;
            border-radius: 6px;
            font-size: 0.88rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-update:hover { opacity: 0.92; }
    </style>
</head>
<body>
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <header class="main-header">
        <div class="header-left">
            <button class="menu-btn" onclick="openMenu()" aria-label="Open Menu">
                <i class="fa-solid fa-bars"></i>
            </button>
            <div class="portal-title">
                <h1>STUDENT HUB PORTAL</h1>
                <p>Digital Academic & Campus Management System</p>
            </div>
        </div>
        <button id="themeToggle" class="theme-btn" aria-label="Change Theme">
            <i class="fa-solid fa-moon"></i>
            <span>Dark Mode</span>
        </button>
    </header>

    <div id="overlay" class="overlay"></div>

    <aside id="sidebar" class="sidebar" aria-label="Sidebar Navigation" aria-hidden="true">
        <div class="sidebar-header">
            <h3>StudentHub Menu</h3>
            <button class="close-btn" aria-label="Close sidebar menu">&times;</button>
        </div>
        <a href="home.php"><i class="fa-solid fa-house"></i> Home</a>
        <a href="courses.php"><i class="fa-solid fa-graduation-cap"></i> Courses</a>
        <a href="events.php"><i class="fa-solid fa-calendar-days"></i> Events</a>
        <a href="Attendance.php"><i class="fa-solid fa-calendar-check"></i> Attendance</a>
        <a href="Assignment.php"><i class="fa-solid fa-book-open"></i> Assignment</a>
        <a href="Timetable.php"><i class="fa-solid fa-calendar-days"></i> Timetable</a>
        <a href="faculty.php"><i class="fa-solid fa-chalkboard-user"></i> Faculty</a>
        <a href="notice.php"><i class="fa-solid fa-bullhorn"></i> Notice</a>
        <a href="profile.php" class="active"><i class="fa-solid fa-user"></i> Profile</a>
        <a href="contactus.php"><i class="fa-solid fa-envelope"></i> Contact</a>
        <form class="sidebar-logout" action="php/auth/logout.php" method="post">
            <?= csrf_field() ?>
            <button type="submit"><i class="fa-solid fa-right-from-bracket"></i> Logout</button>
        </form>
    </aside>

    <main class="profile-container" id="main-content">
        <!-- Hero Header -->
        <section class="profile-hero">
            <div class="profile-hero-left">
                <div class="profile-avatar"><i class="fa-solid fa-user-graduate"></i></div>
                <div>
                    <h1 style="margin:0 0 4px; font-size:1.6rem;"><?= htmlspecialchars($student['name']) ?></h1>
                    <p style="margin:0; opacity:0.9; font-size:0.95rem;">
                        Enrollment: <strong><?= htmlspecialchars($student['student_id']) ?></strong> · <?= htmlspecialchars($student['course']) ?>
                    </p>
                </div>
            </div>
            <div style="font-size:0.85rem; background:rgba(255,255,255,0.15); padding:8px 16px; border-radius:8px; border:1px solid rgba(255,255,255,0.25);">
                <i class="fa-solid fa-circle-check"></i> Status: <strong>Active Student</strong>
            </div>
        </section>

        <!-- Flash Messages -->
        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div class="success-banner" style="display:block;" role="status">
                <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($_SESSION['flash_success']) ?>
            </div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['flash_error'])): ?>
            <div class="error-msg" style="display:block; padding:12px; background:var(--error-bg, #fee2e2); border-radius:8px; color:#b91c1c;" role="alert">
                <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($_SESSION['flash_error']) ?>
            </div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <!-- Two-Column Profile Layout -->
        <div class="profile-grid">
            <!-- Left: Read-Only Academic & Identity Information -->
            <article class="profile-card">
                <div class="card-header-bar">
                    <h2><i class="fa-solid fa-id-card"></i> Academic & Identity Information</h2>
                    <span class="readonly-badge"><i class="fa-solid fa-lock"></i> Official Record · Read-Only</span>
                </div>
                <table class="profile-table">
                    <tbody>
                        <tr>
                            <th><i class="fa-solid fa-id-badge"></i> Student ID</th>
                            <td><strong><?= htmlspecialchars($student['student_id']) ?></strong></td>
                        </tr>
                        <tr>
                            <th><i class="fa-solid fa-user"></i> Full Name</th>
                            <td><?= htmlspecialchars($student['name']) ?></td>
                        </tr>
                        <tr>
                            <th><i class="fa-solid fa-envelope"></i> Email Address</th>
                            <td><?= htmlspecialchars($student['email']) ?></td>
                        </tr>
                        <tr>
                            <th><i class="fa-solid fa-graduation-cap"></i> Course / Program</th>
                            <td><?= htmlspecialchars($student['course']) ?></td>
                        </tr>
                        <tr>
                            <th><i class="fa-solid fa-building-columns"></i> Department</th>
                            <td><?= htmlspecialchars($student['department'] ?? 'Computer Science & Engineering') ?></td>
                        </tr>
                        <tr>
                            <th><i class="fa-solid fa-calendar-check"></i> Semester / Year</th>
                            <td>Semester <?= (int)($student['semester'] ?? 1) ?> (Year <?= (int)$student['year'] ?>)</td>
                        </tr>
                        <tr>
                            <th><i class="fa-solid fa-calendar-days"></i> Admission Date</th>
                            <td><?= htmlspecialchars($student['admission_date'] ?? 'N/A') ?></td>
                        </tr>
                        <tr>
                            <th><i class="fa-solid fa-chart-line"></i> Cumulative CGPA</th>
                            <td><span style="background:#10b981; color:#fff; padding:2px 8px; border-radius:10px; font-weight:700; font-size:0.85rem;"><?= htmlspecialchars((string)$student['cgpa']) ?> / 10.0</span></td>
                        </tr>
                        <tr>
                            <th><i class="fa-solid fa-clock"></i> Last Login</th>
                            <td><?= htmlspecialchars($student['last_login'] ?? 'Just now') ?></td>
                        </tr>
                    </tbody>
                </table>
                <div style="margin-top:16px; font-size:0.8rem; color:var(--text-muted); line-height:1.4;">
                    <i class="fa-solid fa-circle-info"></i> To request corrections to academic records, course enrollment, or identity details, please submit an official application to the Academic Registrar.
                </div>
            </article>

            <!-- Right: Permitted Editable Details (Mobile & Password) -->
            <article class="profile-card">
                <div class="card-header-bar">
                    <h2><i class="fa-solid fa-user-pen"></i> Student Account Settings</h2>
                    <span class="readonly-badge" style="background:#dbeafe; color:#1e40af;"><i class="fa-solid fa-shield-halved"></i> Editable Details</span>
                </div>

                <!-- Form 1: Update Mobile Number -->
                <div class="edit-form-box">
                    <h3><i class="fa-solid fa-phone" style="color:var(--primary-color);"></i> Update Mobile Number</h3>
                    <form action="php/students/update_profile.php" method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update_mobile">
                        <label for="mobile">Mobile Number (10 digits starting with 6-9):</label>
                        <input type="tel" id="mobile" name="mobile" required maxlength="10"
                               placeholder="Enter 10-digit mobile number"
                               value="<?= htmlspecialchars($student['mobile']) ?>">
                        <button type="submit" class="btn-update">
                            <i class="fa-solid fa-floppy-disk"></i> Save Mobile Number
                        </button>
                    </form>
                </div>

                <!-- Form 2: Change Password -->
                <div class="edit-form-box">
                    <h3><i class="fa-solid fa-key" style="color:#d97706;"></i> Change Password</h3>
                    <form action="php/students/update_profile.php" method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="change_password">

                        <label for="current_password">Current Password:</label>
                        <input type="password" id="current_password" name="current_password" required placeholder="Enter your current password">

                        <label for="new_password">New Password (min 8 chars with upper, lower, number, special):</label>
                        <input type="password" id="new_password" name="new_password" required placeholder="Enter new strong password">

                        <label for="confirm_password">Confirm New Password:</label>
                        <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm new password">

                        <button type="submit" class="btn-update" style="background:#d97706;">
                            <i class="fa-solid fa-lock"></i> Update Password
                        </button>
                    </form>
                </div>
            </article>
        </div>
    </main>

    <footer>
        <div class="footer-container">
            <div class="footer-section">
                <h2>StudentHub Portal</h2>
                <p>Your digital campus companion for academics, courses, assignments, attendance, and student services.</p>
            </div>
        </div>
        <p class="copyright">
            © 2026 StudentHub Portal | Developed By Hemangi Korat | All Rights Reserved.
        </p>
    </footer>
</body>
</html>
