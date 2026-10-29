<?php
// ============================================================================
// Student Hub Portal - Retrieve Single Event Details
// Practicals 12, 13, 15
// ============================================================================

require_once __DIR__ . '/../includes/api_bootstrap.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/response.php';

init_session();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(405, false, 'Method not allowed.');
}

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    json_response(400, false, "Invalid or missing event ID.");
}

$db = get_db_connection();
$stmt = $db->prepare("
    SELECT e.*, COUNT(r.id) AS registration_count
    FROM events e
    LEFT JOIN registrations r ON r.event_id = e.id
    WHERE e.id = ?
    GROUP BY e.id
    LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$event) {
    json_response(404, false, "Event not found.");
}

json_response(200, true, "Event retrieved successfully", $event);
