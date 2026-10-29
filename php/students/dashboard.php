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
    "SELECT student_id, name, email, mobile, course, year, department, cgpa, city, status
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
    "SELECT e.title, e.event_date, r.status
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
    <style>
        .student-dashboard { width: min(94%, 1100px); margin: 2rem auto; padding: 1.5rem; color: var(--text-color); }
        .student-dashboard-header { display: flex; justify-content: space-between; align-items: center; gap: 1rem; }
        .student-nav, .student-links { display: flex; flex-wrap: wrap; gap: .75rem; margin: 1rem 0 2rem; }
        .student-nav a, .student-nav button { padding: .7rem 1rem; border-radius: .5rem; background: var(--primary-light); color: var(--primary-color); border: 1px solid var(--border-color); text-decoration: none; font-weight: 700; cursor: pointer; }
        .student-nav form { margin: 0; }
        .student-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem; }
        .student-card { color: var(--text-color); background: var(--card-bg); border: 1px solid var(--border-color); padding: 1.25rem; border-radius: .75rem; box-shadow: var(--shadow); }
        .student-card dt { font-weight: 700; margin-top: .6rem; }
        .student-card dd { margin: .15rem 0; overflow-wrap: anywhere; }
        .student-dashboard table { width: 100%; border-collapse: collapse; }
        .student-dashboard th, .student-dashboard td { padding: .65rem; border-bottom: 1px solid var(--border-color); text-align: left; }
        @media (max-width: 600px) { .student-dashboard { padding: 1rem 0; } .student-dashboard-header { align-items: flex-start; flex-direction: column; } }
    </style>
</head>
<body>
<main class="student-dashboard">
    <header class="student-dashboard-header">
        <h1>Student Dashboard</h1>
        <div>
            <p>Welcome, <?= htmlspecialchars($student['name'], ENT_QUOTES, 'UTF-8') ?>.</p>
            <button id="themeToggle" class="theme-btn" type="button" aria-label="Change Theme">Dark Mode</button>
        </div>
    </header>
    <nav class="student-nav" aria-label="Student portal">
        <a href="../../home.php">Home</a>
        <a href="../../courses.html">Courses</a>
        <a href="../../Timetable.php">Timetable</a>
        <a href="../../notice.html">Notices</a>
        <a href="../../events.html">Events</a>
        <a href="../../Attendance.php">Attendance</a>
        <a href="../../Assignment.php">Assignments</a>
        <form method="post" action="../auth/logout.php">
            <?= csrf_field() ?>
            <button type="submit">Log out</button>
        </form>
    </nav>
    <?php if (!empty($_SESSION['flash_success'])): ?>
        <p role="status"><?= htmlspecialchars($_SESSION['flash_success'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <p role="alert"><?= htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>
    <section class="student-grid" aria-label="Student profile">
        <article class="student-card">
            <h2>My Profile</h2>
            <dl>
                <dt>Student ID</dt><dd><?= htmlspecialchars($student['student_id'], ENT_QUOTES, 'UTF-8') ?></dd>
                <dt>Name</dt><dd><?= htmlspecialchars($student['name'], ENT_QUOTES, 'UTF-8') ?></dd>
                <dt>Email</dt><dd><?= htmlspecialchars($student['email'], ENT_QUOTES, 'UTF-8') ?></dd>
                <dt>Mobile</dt><dd><?= htmlspecialchars($student['mobile'], ENT_QUOTES, 'UTF-8') ?></dd>
                <dt>Course</dt><dd><?= htmlspecialchars($student['course'], ENT_QUOTES, 'UTF-8') ?></dd>
                <dt>Year / Department</dt><dd>Year <?= (int)$student['year'] ?> · <?= htmlspecialchars($student['department'], ENT_QUOTES, 'UTF-8') ?></dd>
                <dt>CGPA</dt><dd><?= htmlspecialchars((string)$student['cgpa'], ENT_QUOTES, 'UTF-8') ?></dd>
            </dl>
        </article>
        <article class="student-card">
            <h2>My Event Registrations</h2>
            <?php if (!$registrations): ?><p>You have no event registrations yet.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($registrations as $registration): ?>
                        <li><?= htmlspecialchars($registration['title'], ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars($registration['event_date'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($registration['status'], ENT_QUOTES, 'UTF-8') ?>)</li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </article>
    </section>
    <section class="student-card" aria-labelledby="upcoming-title">
        <h2 id="upcoming-title">Upcoming Campus Events</h2>
        <?php if (!$events): ?><p>No upcoming events are currently scheduled.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($events as $event): ?>
                    <li>
                        <?= htmlspecialchars($event['event_date'], ENT_QUOTES, 'UTF-8') ?>:
                        <?= htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8') ?> —
                        <?= htmlspecialchars($event['venue'], ENT_QUOTES, 'UTF-8') ?>
                        <form method="post" action="../events/register.php">
                            <?= csrf_field() ?>
                            <input type="hidden" name="event_id" value="<?= (int)$event['id'] ?>">
                            <button type="submit">Register for event</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</main>
<script>
    (() => {
        const toggle = document.getElementById('themeToggle');
        const dark = localStorage.getItem('theme') === 'dark';
        document.documentElement.classList.toggle('dark-mode', dark);
        document.body.classList.toggle('dark-mode', dark);
        const updateLabel = () => {
            const isDark = document.body.classList.contains('dark-mode');
            toggle.innerHTML = `<i class="fa-solid ${isDark ? 'fa-sun' : 'fa-moon'}"></i> ${isDark ? 'Light Mode' : 'Dark Mode'}`;
            toggle.setAttribute('aria-label', isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode');
        };
        updateLabel();
        toggle.addEventListener('click', () => {
            const isDark = document.body.classList.toggle('dark-mode');
            document.documentElement.classList.toggle('dark-mode', isDark);
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            updateLabel();
        });
    })();
</script>
</body>
</html>
