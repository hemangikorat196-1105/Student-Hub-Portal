<?php
// ============================================================================
// Student Hub Portal - Asynchronous Student Search Endpoint
// Practical 11, 13
// ============================================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/response.php';

init_session();

$q      = trim($_GET['q'] ?? $_GET['search'] ?? '');
$course = trim($_GET['course'] ?? '');
$limit  = min(50, max(1, (int)($_GET['limit'] ?? 15)));

$db = get_db_connection();

$where = ["1=1"];
$params = [];
$types = "";

if ($q !== '') {
    $where[] = "(name LIKE ? OR student_id LIKE ? OR email LIKE ? OR city LIKE ?)";
    $like = "%$q%";
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= "ssss";
}

if ($course !== '') {
    $where[] = "course = ?";
    $params[] = $course;
    $types .= "s";
}

$whereSql = implode(" AND ", $where);
$sql = "SELECT id, student_id, name, email, mobile, course, year, department, cgpa, status
        FROM students
        WHERE $whereSql
        ORDER BY name ASC
        LIMIT ?";
$types .= "i";
$params[] = $limit;

$stmt = $db->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

json_response(200, true, "Search completed", [
    'query' => $q,
    'count' => count($results),
    'items' => $results
]);
