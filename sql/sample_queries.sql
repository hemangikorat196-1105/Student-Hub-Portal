-- ============================================================================
-- Student Hub Portal - Sample Queries (Practicals 8, 11, 12, 14)
-- Demonstrates 3NF Joins, Aggregation, RBAC Checks & Pagination
-- ============================================================================
USE `student_portal`;

-- 1. Student Count by Course
SELECT `course`, COUNT(*) AS `student_count`, ROUND(AVG(`cgpa`), 2) AS `average_cgpa`
FROM `students`
WHERE `status` = 'active'
GROUP BY `course`
ORDER BY `student_count` DESC;

-- 2. Event Registrations Report with Student and Event Details (INNER JOIN)
SELECT
    r.id AS registration_id,
    s.student_id,
    s.name AS student_name,
    s.email AS student_email,
    e.title AS event_title,
    e.category AS event_category,
    e.event_date,
    r.status AS registration_status,
    r.registration_date
FROM registrations r
INNER JOIN students s ON r.student_id = s.id
INNER JOIN events e ON r.event_id = e.id
ORDER BY r.registration_date DESC;

-- 3. Live Dashboard Overview Statistics (Single Aggregated Query)
SELECT
    (SELECT COUNT(*) FROM users) AS total_users,
    (SELECT COUNT(*) FROM students WHERE status = 'active') AS total_active_students,
    (SELECT COUNT(*) FROM events) AS total_events,
    (SELECT COUNT(*) FROM registrations WHERE status = 'confirmed') AS total_confirmed_registrations,
    (SELECT COUNT(*) FROM audit_logs) AS total_audit_logs;

-- 4. Search and Filter Students with Pagination (Offset / Limit)
SELECT id, student_id, name, email, mobile, course, year, cgpa, status
FROM students
WHERE (name LIKE '%Hemangi%' OR student_id LIKE '%25CS%' OR email LIKE '%studenthub%')
  AND course = 'B.Tech Computer Science'
  AND status = 'active'
ORDER BY name ASC
LIMIT 10 OFFSET 0;

-- 5. Role-Based Verification for Login Authentication
SELECT id, username, email, password, role, status
FROM users
WHERE (username = 'admin' OR email = 'admin@studenthub.edu')
LIMIT 1;

-- 6. Audit Trail for Security Monitoring
SELECT
    a.id,
    COALESCE(u.username, 'Anonymous/Guest') AS actor,
    a.action,
    a.details,
    a.ip_address,
    a.created_at
FROM audit_logs a
LEFT JOIN users u ON a.user_id = u.id
ORDER BY a.created_at DESC
LIMIT 15;
