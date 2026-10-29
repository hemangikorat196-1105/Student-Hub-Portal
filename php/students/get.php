<?php
// ============================================================================
// Student Hub Portal - Retrieve Single Student Details
// Practicals 11, 13, 15
// ============================================================================

require_once __DIR__ . '/../includes/api_bootstrap.php';
$_SERVER['HTTP_ACCEPT'] = 'application/json';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/response.php';

init_session();
require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$studentId = trim($_GET['student_id'] ?? '');

if ($id <= 0 && empty($studentId)) {
    json_response(400, false, "Missing student identifier.");
}

$db = get_db_connection();
if (is_admin()) {
    if (!verified_database_role('admin')) {
        json_response(403, false, "Administrator privileges required.");
    }
    if ($id > 0) {
    $stmt = $db->prepare("SELECT * FROM students WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    } else {
    $stmt = $db->prepare("SELECT * FROM students WHERE student_id = ? LIMIT 1");
    $stmt->bind_param("s", $studentId);
    }
} else {
    require_student();
    $user = current_user();
    $stmt = $db->prepare("SELECT * FROM students WHERE user_id = ? AND status = 'active' LIMIT 1");
    $stmt->bind_param("i", $user['id']);
}

$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    json_response(404, false, "Student record not found.");
}

json_response(200, true, "Student record retrieved successfully", $student);
