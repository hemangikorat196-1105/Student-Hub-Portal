<?php
// ============================================================================
// Student Hub Portal - Retrieve Single Student Details
// Practicals 11, 13, 15
// ============================================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/response.php';

init_session();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$studentId = trim($_GET['student_id'] ?? '');

if ($id <= 0 && empty($studentId)) {
    json_response(400, false, "Missing student identifier.");
}

$db = get_db_connection();
if ($id > 0) {
    $stmt = $db->prepare("SELECT * FROM students WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
} else {
    $stmt = $db->prepare("SELECT * FROM students WHERE student_id = ? LIMIT 1");
    $stmt->bind_param("s", $studentId);
}

$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    json_response(404, false, "Student record not found.");
}

json_response(200, true, "Student record retrieved successfully", $student);
