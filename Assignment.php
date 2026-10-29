<?php
require_once __DIR__ . '/php/includes/session.php';
require_once __DIR__ . '/php/includes/auth_guard.php';
init_session();
require_student();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Hub Portal - Assignments</title>
    <link rel="stylesheet" href="Style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="script.js" defer></script>
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
    <a href="aboutus.php"><i class="fa-solid fa-circle-info"></i> About Us</a>
    <a href="courses.php"><i class="fa-solid fa-graduation-cap"></i> Courses</a>
    <a href="events.php"><i class="fa-solid fa-calendar-days"></i> Events</a>
    <a href="Attendance.php"><i class="fa-solid fa-calendar-check"></i> Attendance</a>
    <a href="Assignment.php" class="active"><i class="fa-solid fa-book-open"></i> Assignment</a>
    <a href="Timetable.php"><i class="fa-solid fa-calendar-days"></i> Timetable</a>
    <a href="faculty.php"><i class="fa-solid fa-chalkboard-user"></i> Faculty</a>
    <a href="notice.php"><i class="fa-solid fa-bullhorn"></i> Notice</a>
    <a href="php/students/dashboard.php"><i class="fa-solid fa-user"></i> Profile</a>
    <a href="contactus.php"><i class="fa-solid fa-envelope"></i> Contact</a>
    <form class="sidebar-logout" action="php/auth/logout.php" method="post">
        <input type="hidden" name="csrf_token" value="">
        <button type="submit"><i class="fa-solid fa-right-from-bracket"></i> Logout</button>
    </form>
</aside>

<main id="main-content">
    <div class="card">
        <h2><i class="fa-solid fa-book-open"></i> Current Course Assignments</h2>
        <table>
            <tr>
                <th>Subject</th>
                <th>Assignment</th>
                <th>Due Date</th>
                <th>Status</th>
            </tr>

            <tr>
                <td>Data Structures</td>
                <td>Linked List Program</td>
                <td>25 July 2026</td>
                <td><span style="color: #eab308; font-weight: bold;">Pending</span></td>
            </tr>

            <tr>
                <td>WDF</td>
                <td>Web Development Project</td>
                <td>28 July 2026</td>
                <td><span style="color: #22c55e; font-weight: bold;">Submitted</span></td>
            </tr>

            <tr>
                <td>Computer Networks</td>
                <td>Network Assignment</td>
                <td>30 July 2026</td>
                <td><span style="color: #eab308; font-weight: bold;">Pending</span></td>
            </tr>
        </table>
    </div>

    <form>
        <h2><i class="fa-solid fa-cloud-arrow-up"></i> Upload Assignment</h2>

        <label for="assignSubject">Subject</label>
        <input type="text" id="assignSubject" placeholder="e.g. Data Structures" required>

        <label for="assignTitle">Assignment Title</label>
        <input type="text" id="assignTitle" placeholder="e.g. Assignment Solution" required>

        <label for="assignFile">Select File</label>
        <input type="file" id="assignFile" required>

        <div class="form-actions">
            <input type="submit" value="Upload Assignment">
            <input type="reset" value="Clear Form">
        </div>
    </form>
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