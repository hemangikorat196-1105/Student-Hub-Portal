<?php
// ============================================================================
// Student Hub Portal - Export Student Records to CSV
// Practical 11 (Intermediate Extension Requirement)
// ============================================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';

init_session();
require_login('../../Login.html');

$course = trim($_GET['course'] ?? '');
$status = trim($_GET['status'] ?? 'all');
$search = trim($_GET['search'] ?? '');

$db = get_db_connection();

$whereClauses = ["1=1"];
$params = [];
$types = "";

if ($search !== '') {
    $whereClauses[] = "(name LIKE ? OR student_id LIKE ? OR email LIKE ?)";
    $like = "%$search%";
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= "sss";
}

if ($course !== '') {
    $whereClauses[] = "course = ?";
    $params[] = $course;
    $types .= "s";
}

if ($status !== 'all' && in_array($status, ['active', 'inactive'])) {
    $whereClauses[] = "status = ?";
    $params[] = $status;
    $types .= "s";
}

$whereSql = implode(" AND ", $whereClauses);
$sql = "SELECT id, student_id, name, email, mobile, course, year, department, gender, cgpa, status, created_at
        FROM students
        WHERE $whereSql
        ORDER BY id ASC";

$stmt = $db->prepare($sql);
if ($types !== "") {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Set HTTP headers for CSV download
$filename = "studenthub_students_" . date('Y-m-d_His') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// UTF-8 BOM for Microsoft Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV Header
fputcsv($output, [
    'Record ID',
    'Student ID',
    'Full Name',
    'Email Address',
    'Mobile Number',
    'Course',
    'Year of Study',
    'Department',
    'Gender',
    'CGPA',
    'Status',
    'Registration Date'
]);

// Write Rows
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['id'],
        $row['student_id'],
        $row['name'],
        $row['email'],
        $row['mobile'],
        $row['course'],
        'Year ' . $row['year'],
        $row['department'],
        $row['gender'],
        number_format((float)$row['cgpa'], 2),
        ucfirst($row['status']),
        $row['created_at']
    ]);
}

fclose($output);
$stmt->close();
exit;
