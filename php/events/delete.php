<?php
// ============================================================================
// Student Hub Portal - Delete Event Handler
// Practical 12, 14, 15
// ============================================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/response.php';

init_session();
require_admin();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if ($id <= 0) {
    if (is_api_request()) {
        json_response(400, false, "Invalid event ID.");
    }
    header("Location: list.php");
    exit;
}

try {
    $db = get_db_connection();

    $stmtF = $db->prepare("SELECT id, title, poster FROM events WHERE id = ? LIMIT 1");
    $stmtF->bind_param("i", $id);
    $stmtF->execute();
    $event = $stmtF->get_result()->fetch_assoc();
    $stmtF->close();

    if (!$event) {
        if (is_api_request()) {
            json_response(404, false, "Event not found.");
        }
        $_SESSION['flash_error'] = "Event not found.";
        header("Location: list.php");
        exit;
    }

    // Delete poster file if present
    if (!empty($event['poster'])) {
        $posterPath = __DIR__ . '/../../assets/uploads/event_posters/' . $event['poster'];
        if (file_exists($posterPath)) {
            @unlink($posterPath);
        }
    }

    // Delete event (foreign keys in registrations have ON DELETE CASCADE)
    $stmtDel = $db->prepare("DELETE FROM events WHERE id = ?");
    $stmtDel->bind_param("i", $id);
    $stmtDel->execute();
    $stmtDel->close();

    log_audit_event("DELETE_EVENT", "Admin deleted event ID $id ({$event['title']})");
    $_SESSION['flash_success'] = "Event '{$event['title']}' deleted successfully.";

    if (is_api_request()) {
        json_response(200, true, "Event deleted successfully");
    }

    header("Location: list.php");
    exit;

} catch (Exception $e) {
    error_log("Delete Event Error: " . $e->getMessage());
    if (is_api_request()) {
        json_response(500, false, "Server error during event deletion.");
    }
    $_SESSION['flash_error'] = "Server error during event deletion.";
    header("Location: list.php");
    exit;
}
