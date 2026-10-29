<?php
// ============================================================================
// Student Hub Portal - Export Student Records to CSV
// Supports search by ID, name, email, mobile; filters by course, sem, year, status.
// ============================================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';

init_session();
require_admin();

$course   = trim($_GET['course'] ?? '');
$semester = isset($_GET['semester']) && $_GET['semester'] !== '' ? (int)$_GET['semester'] : null;
$year     = isset($_GET['year']) && $_GET['year'] !== '' ? (int)$_GET['year'] : null;
$status   = trim($_GET['status'] ?? 'all');
$search   = trim($_GET['search'] ?? '');

$db = get_db_connection();

$whereClauses = ["1=1"];
$params = [];
$types = "";

if ($search !== '') {
    $whereClauses[] = "(name LIKE ? OR student_id LIKE ? OR email LIKE ? OR mobile LIKE ?)";
    $like = "%$search%";
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= "ssss";
}

if ($course !== '') {
    $whereClauses[] = "course = ?";
    $params[] = $course;
    $types .= "s";
}

if ($semester !== null && $semester > 0) {
    $whereClauses[] = "semester = ?";
    $params[] = $semester;
    $types .= "i";
}

if ($year !== null && $year > 0) {
    $whereClauses[] = "year = ?";
    $params[] = $year;
    $types .= "i";
}

if ($status !== 'all' && in_array($status, ['active', 'inactive'])) {
    $whereClauses[] = "status = ?";
    $params[] = $status;
    $types .= "s";
}

$whereSql = implode(" AND ", $whereClauses);
$sql = "SELECT id, student_id, name, email, mobile, course, semester, year, department, admission_date, gender, cgpa, status, created_at
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
    'Department',
    'Semester',
    'Academic Year',
    'Admission Date',
    'Gender',
    'CGPA',
    'Status',
    'Registration Date'
]);

// Write Rows (with formula injection sanitization)
while ($row = $result->fetch_assoc()) {
    $cleanName = $row['name'];
    if (preg_match('/^[=\+\-@]/', $cleanName)) {
        $cleanName = "'" . $cleanName;
    }

    fputcsv($output, [
        $row['id'],
        $row['student_id'],
        $cleanName,
        $row['email'],
        $row['mobile'],
        $row['course'],
        $row['department'],
        'Semester ' . ($row['semester'] ?? 1),
        'Year ' . $row['year'],
        $row['admission_date'] ?? 'N/A',
        $row['gender'],
        number_format((float)$row['cgpa'], 2),
        ucfirst($row['status']),
        $row['created_at']
    ]);
}

fclose($output);
$stmt->close();
exit;
