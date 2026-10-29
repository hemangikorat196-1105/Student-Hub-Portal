<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/csrf.php';

init_session();
require_student();

$db = get_db_connection();
$user = current_user();
$stmt = $db->prepare(
    "SELECT id, student_id, name, email, mobile, course, year, department, cgpa, city, status
     FROM students WHERE user_id = ? AND status = 'active' LIMIT 1"
);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    http_response_code(403);
    exit('No active student profile is linked to this account.');
}

$events = $db->query(
    "SELECT id, title, event_date, venue FROM events
     WHERE status IN ('open', 'upcoming') AND event_date >= CURDATE()
     ORDER BY event_date ASC LIMIT 5"
)->fetch_all(MYSQLI_ASSOC);

$stmt = $db->prepare(
    "SELECT e.id AS event_id, e.title, e.event_date, e.venue, r.status
     FROM registrations r INNER JOIN students s ON r.student_id = s.id
     INNER JOIN events e ON r.event_id = e.id
     WHERE s.user_id = ? ORDER BY e.event_date ASC"
);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$registrations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Student Hub Portal</title>
    <link rel="stylesheet" href="../../Style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="../../script.js" defer></script>
    <style>
        .dashboard-container {
            width: min(94%, 1200px);
            margin: 25px auto 50px;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .dashboard-welcome-hero {
            background: linear-gradient(135deg, #1e3a8a 0%, #0284c7 100%);
            color: #ffffff;
            padding: 30px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            box-shadow: 0 4px 20px rgba(2, 132, 199, 0.2);
        }

        body.dark-mode .dashboard-welcome-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border: 1px solid rgba(56, 189, 248, 0.25);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
        }

        .welcome-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .student-avatar {
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

        body.dark-mode .student-avatar {
            background: rgba(56, 189, 248, 0.15);
            border-color: #38bdf8;
            color: #38bdf8;
        }

        .welcome-text h1 {
            font-size: 1.8rem;
            margin-bottom: 6px;
            color: #ffffff;
        }

        body.dark-mode .welcome-text h1 {
            color: #f8fafc;
        }

        .welcome-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 8px;
        }

        .welcome-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        body.dark-mode .welcome-badge {
            background: rgba(56, 189, 248, 0.15);
            color: #38bdf8;
            border: 1px solid rgba(56, 189, 248, 0.3);
        }

        .dashboard-nav-strip {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            background: var(--card-bg);
            padding: 14px 18px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow);
            align-items: center;
        }

        .dashboard-nav-strip a, .dashboard-nav-strip button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 16px;
            border-radius: 8px;
            background: var(--primary-light);
            color: var(--primary-color);
            border: 1px solid var(--border-color);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .dashboard-nav-strip a:hover, .dashboard-nav-strip button:hover {
            background: var(--primary-color);
            color: #ffffff;
            transform: translateY(-1px);
        }

        .dashboard-nav-strip form {
            margin: 0;
            margin-left: auto;
        }

        .dashboard-nav-strip .btn-logout {
            background: #ef4444;
            color: #ffffff;
            border-color: #dc2626;
        }

        .dashboard-nav-strip .btn-logout:hover {
            background: #dc2626;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(360px, 1fr));
            gap: 24px;
        }

        .dashboard-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
            box-shadow: var(--shadow);
            color: var(--text-color);
        }

        .card-header-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border-color);
        }

        .card-header-bar h2 {
            font-size: 1.25rem;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }

        .card-header-bar h2 i {
            color: var(--primary-color);
        }

        .profile-table {
            width: 100%;
            border-collapse: collapse;
        }

        .profile-table tr {
            border-bottom: 1px solid var(--border-color);
        }

        .profile-table tr:last-child {
            border-bottom: none;
        }

        .profile-table th {
            text-align: left;
            padding: 11px 8px;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.9rem;
            width: 40%;
        }

        .profile-table td {
            padding: 11px 8px;
            color: var(--text-color);
            font-weight: 500;
            font-size: 0.95rem;
        }

        .cgpa-pill {
            display: inline-block;
            background: #10b981;
            color: #ffffff;
            padding: 2px 10px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.85rem;
        }

        .event-item-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 14px;
            border-radius: 8px;
            background: var(--primary-light);
            margin-bottom: 10px;
            border: 1px solid var(--border-color);
        }

        .event-item-info h4 {
            margin: 0 0 4px;
            font-size: 0.95rem;
            color: var(--text-color);
        }

        .event-item-info span {
            font-size: 0.82rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .status-badge-confirmed {
            background: #10b981;
            color: #ffffff;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-badge-waitlisted {
            background: #f59e0b;
            color: #ffffff;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .btn-register-mini {
            padding: 6px 14px;
            background: var(--primary-color);
            color: #ffffff;
            border: none;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-register-mini:hover {
            background: var(--primary-hover);
        }

        @media (max-width: 768px) {
            .dashboard-welcome-hero {
                flex-direction: column;
                align-items: flex-start;
            }
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            .dashboard-nav-strip form {
                margin-left: 0;
                width: 100%;
            }
            .dashboard-nav-strip .btn-logout {
                width: 100%;
                justify-content: center;
            }
        }
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
    <a href="../../home.php"><i class="fa-solid fa-house"></i> Home</a>
    <a href="../../courses.php"><i class="fa-solid fa-graduation-cap"></i> Courses</a>
    <a href="../../events.php"><i class="fa-solid fa-calendar-days"></i> Events</a>
    <a href="../../Attendance.php"><i class="fa-solid fa-calendar-check"></i> Attendance</a>
    <a href="../../Assignment.php"><i class="fa-solid fa-book-open"></i> Assignment</a>
    <a href="../../Timetable.php"><i class="fa-solid fa-calendar-days"></i> Timetable</a>
    <a href="../../faculty.php"><i class="fa-solid fa-chalkboard-user"></i> Faculty</a>
    <a href="../../notice.php"><i class="fa-solid fa-bullhorn"></i> Notice</a>
    <a href="dashboard.php" class="active"><i class="fa-solid fa-user"></i> Profile</a>
    <a href="../../contactus.php"><i class="fa-solid fa-envelope"></i> Contact</a>
    <form class="sidebar-logout" action="../auth/logout.php" method="post">
        <?= csrf_field() ?>
        <button type="submit"><i class="fa-solid fa-right-from-bracket"></i> Logout</button>
    </form>
</aside>

<main class="dashboard-container" id="main-content">
    <!-- Hero Welcome Card -->
    <section class="dashboard-welcome-hero" aria-labelledby="welcome-heading">
        <div class="welcome-left">
            <div class="student-avatar" aria-hidden="true">
                <i class="fa-solid fa-user-graduate"></i>
            </div>
            <div class="welcome-text">
                <h1 id="welcome-heading">Welcome, <?= htmlspecialchars($student['name'], ENT_QUOTES, 'UTF-8') ?>!</h1>
                <p>Academic Session 2026–2027 · Department of <?= htmlspecialchars($student['department'], ENT_QUOTES, 'UTF-8') ?></p>
                <div class="welcome-badges">
                    <span class="welcome-badge"><i class="fa-solid fa-id-badge"></i> <?= htmlspecialchars($student['student_id'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="welcome-badge"><i class="fa-solid fa-graduation-cap"></i> <?= htmlspecialchars($student['course'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="welcome-badge"><i class="fa-solid fa-calendar"></i> Year <?= (int)$student['year'] ?></span>
                    <span class="welcome-badge"><i class="fa-solid fa-circle-check"></i> Status: Active</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Navigation Toolbar -->
    <nav class="dashboard-nav-strip" aria-label="Student Quick Navigation">
        <a href="../../home.php"><i class="fa-solid fa-house"></i> Home</a>
        <a href="../../courses.php"><i class="fa-solid fa-graduation-cap"></i> Courses</a>
        <a href="../../Timetable.php"><i class="fa-solid fa-calendar-days"></i> Timetable</a>
        <a href="../../notice.php"><i class="fa-solid fa-bullhorn"></i> Notices</a>
        <a href="../../events.php"><i class="fa-solid fa-calendar-days"></i> Events</a>
        <a href="../../Attendance.php"><i class="fa-solid fa-calendar-check"></i> Attendance</a>
        <a href="../../Assignment.php"><i class="fa-solid fa-book-open"></i> Assignments</a>
        <form method="post" action="../auth/logout.php">
            <?= csrf_field() ?>
            <button type="submit" class="btn-logout"><i class="fa-solid fa-right-from-bracket"></i> Log out</button>
        </form>
    </nav>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="success-banner" style="display:block;" role="status">
            <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($_SESSION['flash_success'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="error-msg" style="display:block; padding:12px; background:var(--error-bg); border-radius:8px;" role="alert">
            <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <!-- Two-Column Grid: Profile & Event Registrations -->
    <div class="dashboard-grid">
        <!-- Academic Profile Details Card -->
        <article class="dashboard-card">
            <div class="card-header-bar">
                <h2><i class="fa-solid fa-id-card"></i> Student Profile Information</h2>
            </div>
            <table class="profile-table">
                <tbody>
                    <tr>
                        <th><i class="fa-solid fa-id-badge"></i> Student ID</th>
                        <td><strong><?= htmlspecialchars($student['student_id'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                    </tr>
                    <tr>
                        <th><i class="fa-solid fa-user"></i> Full Name</th>
                        <td><?= htmlspecialchars($student['name'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                    <tr>
                        <th><i class="fa-solid fa-envelope"></i> Email Address</th>
                        <td><?= htmlspecialchars($student['email'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                    <tr>
                        <th><i class="fa-solid fa-phone"></i> Mobile Number</th>
                        <td><?= htmlspecialchars($student['mobile'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                    <tr>
                        <th><i class="fa-solid fa-graduation-cap"></i> Program / Course</th>
                        <td><?= htmlspecialchars($student['course'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                    <tr>
                        <th><i class="fa-solid fa-building-columns"></i> Department</th>
                        <td><?= htmlspecialchars($student['department'], ENT_QUOTES, 'UTF-8') ?> (Year <?= (int)$student['year'] ?>)</td>
                    </tr>
                    <tr>
                        <th><i class="fa-solid fa-chart-line"></i> Cumulative CGPA</th>
                        <td><span class="cgpa-pill"><?= htmlspecialchars((string)$student['cgpa'], ENT_QUOTES, 'UTF-8') ?> / 10.0</span></td>
                    </tr>
                    <?php if (!empty($student['city'])): ?>
                    <tr>
                        <th><i class="fa-solid fa-location-dot"></i> Campus / City</th>
                        <td><?= htmlspecialchars($student['city'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </article>

        <!-- Event Registrations Card -->
        <article class="dashboard-card">
            <div class="card-header-bar">
                <h2><i class="fa-solid fa-calendar-check"></i> My Event Registrations</h2>
                <a href="../../events.php" style="font-size:0.85rem; font-weight:600; color:var(--primary-color); text-decoration:none;">View All Events &rarr;</a>
            </div>
            <?php if (!$registrations): ?>
                <div style="text-align:center; padding:30px 10px; color:var(--text-muted);">
                    <i class="fa-solid fa-calendar-xmark" style="font-size:2.5rem; margin-bottom:12px; opacity:0.5;"></i>
                    <p style="margin:0 0 15px;">You have not registered for any campus events yet.</p>
                    <a href="../../events.php" class="small-btn" style="display:inline-block; padding:8px 18px; text-decoration:none;">Explore Upcoming Events</a>
                </div>
            <?php else: ?>
                <div class="event-list-container">
                    <?php foreach ($registrations as $reg): ?>
                        <div class="event-item-row">
                            <div class="event-item-info">
                                <h4><?= htmlspecialchars($reg['title'], ENT_QUOTES, 'UTF-8') ?></h4>
                                <span><i class="fa-solid fa-calendar-day"></i> <?= htmlspecialchars($reg['event_date'], ENT_QUOTES, 'UTF-8') ?> · <i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($reg['venue'] ?? 'Campus', ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <span class="status-badge-<?= strtolower($reg['status']) === 'waitlisted' ? 'waitlisted' : 'confirmed' ?>">
                                <?= htmlspecialchars($reg['status'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </article>
    </div>

    <!-- Upcoming Events Section -->
    <section class="dashboard-card" aria-labelledby="upcoming-events-heading">
        <div class="card-header-bar">
            <h2 id="upcoming-events-heading"><i class="fa-solid fa-bullhorn"></i> Scheduled Campus Events & Workshops</h2>
            <a href="../../events.php" style="font-size:0.85rem; font-weight:600; color:var(--primary-color); text-decoration:none;">Browse Full Catalog &rarr;</a>
        </div>
        <?php if (!$events): ?>
            <p style="color:var(--text-muted);">No upcoming events are currently scheduled.</p>
        <?php else: ?>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:15px;">
                <?php foreach ($events as $event): ?>
                    <div class="event-item-row" style="flex-direction:column; align-items:flex-start; gap:10px;">
                        <div class="event-item-info" style="width:100%;">
                            <h4><?= htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8') ?></h4>
                            <span><i class="fa-solid fa-calendar-day"></i> <?= htmlspecialchars($event['event_date'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span style="margin-top:3px;"><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($event['venue'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <form method="post" action="../events/register.php" style="margin:0; width:100%;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="event_id" value="<?= (int)$event['id'] ?>">
                            <button type="submit" class="btn-register-mini" style="width:100%;">Register Now</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
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
