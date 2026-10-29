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
    <title>Student Hub Portal - Courses</title>
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
    <a href="courses.php" class="active"><i class="fa-solid fa-graduation-cap"></i> Courses</a>
    <a href="events.php"><i class="fa-solid fa-calendar-days"></i> Events</a>
    <a href="Attendance.php"><i class="fa-solid fa-calendar-check"></i> Attendance</a>
    <a href="Assignment.php"><i class="fa-solid fa-book-open"></i> Assignment</a>
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
        <h2><i class="fa-solid fa-graduation-cap"></i> Registered Courses</h2>
        <table>
            <tr>
                <th>Course Code</th>
                <th>Course Name</th>
                <th>Faculty</th>
                <th>Credits</th>
            </tr>

            <tr>
                <td>CSUC201</td>
                <td>Data Structures & Algorithms</td>
                <td>Dr. Amit Thakkar & Dr. Vidisha Pradhan</td>
                <td>5</td>
            </tr>

            <tr>
                <td>CEUE203</td>
                <td>OOP - Java</td>
                <td>Siddharth Shah & Brinda Patel</td>
                <td>2</td>
            </tr>

            <tr>
                <td>ITUE203</td>
                <td>Web Development Frameworks (WDF)</td>
                <td>Prof. Hitesh Patel</td>
                <td>4</td>
            </tr>

            <tr>
                <td>ITUC201</td>
                <td>Foundations of Computer Networks (FCN)</td>
                <td>Prof. Snehal Patel</td>
                <td>4</td>
            </tr>

            <tr>
                <td>MSUD203</td>
                <td>Discrete Mathematics</td>
                <td>Dr. R. K. Sharma</td>
                <td>3</td>
            </tr>
        </table>
    </div>

    <div class="card">
        <h2><i class="fa-solid fa-circle-info"></i> Course Information & Summary</h2>
        <ul>
            <li><strong>Semester:</strong> 3rd Semester</li>
            <li><strong>Total Credits:</strong> 18 Credits</li>
            <li><strong>Department:</strong> Computer Science & Engineering</li>
            <li><strong>Academic Year:</strong> 2026-27</li>
        </ul>
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