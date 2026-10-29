<?php
// ============================================================================
// Student Hub Portal - RESTful Events API Endpoint
// Practical 12, 13
// Methods Supported: GET, POST, PUT, DELETE
// ============================================================================

require_once __DIR__ . '/../includes/api_bootstrap.php';
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With, X-CSRF-Token");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/response.php';

init_session();
$method = $_SERVER['REQUEST_METHOD'];
if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
    require_admin();
    require_once __DIR__ . '/../includes/csrf.php';
    if (!verify_csrf_token($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
        json_response(403, false, "Invalid or missing CSRF token.");
    }
}
$db = get_db_connection();

function get_event_payload() {
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : $_POST;
}

switch ($method) {
    case 'GET':
        if (isset($_GET['id']) && (int)$_GET['id'] > 0) {
            $id = (int)$_GET['id'];
            $stmt = $db->prepare("SELECT e.*, COUNT(r.id) AS registration_count FROM events e LEFT JOIN registrations r ON r.event_id = e.id WHERE e.id = ? GROUP BY e.id LIMIT 1");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $event = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$event) {
                json_response(404, false, "Event not found");
            }
            json_response(200, true, "Event retrieved successfully", $event);
        }

        $category = trim($_GET['category'] ?? '');
        $search   = trim($_GET['search'] ?? $_GET['q'] ?? '');
        $status   = trim($_GET['status'] ?? '');
        $limit    = min(100, max(1, (int)($_GET['limit'] ?? 15)));

        $where = ["1=1"];
        $params = [];
        $types = "";

        if ($category !== '') {
            $where[] = "e.category = ?";
            $params[] = $category;
            $types .= "s";
        }
        if ($status !== '') {
            $where[] = "e.status = ?";
            $params[] = $status;
            $types .= "s";
        }
        if ($search !== '') {
            $where[] = "(e.title LIKE ? OR e.venue LIKE ? OR e.speaker LIKE ?)";
            $like = "%$search%";
            $params[] = $like; $params[] = $like; $params[] = $like;
            $types .= "sss";
        }

        $whereSql = implode(" AND ", $where);
        $sql = "SELECT e.*, COUNT(r.id) AS registration_count
                FROM events e
                LEFT JOIN registrations r ON r.event_id = e.id
                WHERE $whereSql
                GROUP BY e.id
                ORDER BY e.event_date ASC
                LIMIT ?";
        $types .= "i";
        $params[] = $limit;

        $stmt = $db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        json_response(200, true, "Events retrieved successfully", $events, ['count' => count($events)]);
        break;

    case 'POST':
        $data = get_event_payload();
        $title     = trim($data['title'] ?? '');
        $category  = trim($data['category'] ?? 'Technical');
        $eventDate = trim($data['event_date'] ?? $data['date'] ?? '');
        $time      = trim($data['time'] ?? '10:00 AM');
        $venue     = trim($data['venue'] ?? $data['location'] ?? '');
        $speaker   = trim($data['speaker'] ?? '');
        $desc      = trim($data['description'] ?? '');
        $seats     = max(1, (int)($data['seats'] ?? 100));
        $status    = in_array($data['status'] ?? '', ['open', 'closed', 'upcoming', 'cancelled']) ? $data['status'] : 'upcoming';

        if (empty($title) || empty($eventDate) || empty($venue)) {
            json_response(422, false, "Title, Event Date and Venue are required fields.");
        }

        $stmt = $db->prepare("
            INSERT INTO events (title, category, event_date, time, venue, speaker, description, seats, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("sssssssis", $title, $category, $eventDate, $time, $venue, $speaker, $desc, $seats, $status);
        $stmt->execute();
        $newId = $stmt->insert_id;
        $stmt->close();

        log_audit_event("API_CREATE_EVENT", "Created event $newId: $title");

        json_response(201, true, "Event created successfully", ['id' => $newId, 'title' => $title]);
        break;

    case 'PUT':
        $data = get_event_payload();
        $id = (int)($data['id'] ?? $_GET['id'] ?? 0);

        if ($id <= 0) {
            json_response(400, false, "Missing event ID.");
        }

        $stmt = $db->prepare("SELECT * FROM events WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$existing) {
            json_response(404, false, "Event not found.");
        }

        $title     = trim($data['title'] ?? $existing['title']);
        $category  = trim($data['category'] ?? $existing['category']);
        $eventDate = trim($data['event_date'] ?? $existing['event_date']);
        $time      = trim($data['time'] ?? $existing['time']);
        $venue     = trim($data['venue'] ?? $existing['venue']);
        $speaker   = trim($data['speaker'] ?? $existing['speaker']);
        $desc      = trim($data['description'] ?? $existing['description']);
        $seats     = max(1, (int)($data['seats'] ?? $existing['seats']));
        $status    = in_array($data['status'] ?? '', ['open', 'closed', 'upcoming', 'cancelled']) ? $data['status'] : $existing['status'];

        $stmtUp = $db->prepare("
            UPDATE events
            SET title = ?, category = ?, event_date = ?, time = ?, venue = ?, speaker = ?, description = ?, seats = ?, status = ?
            WHERE id = ?
        ");
        $stmtUp->bind_param("ssssssisi", $title, $category, $eventDate, $time, $venue, $speaker, $desc, $seats, $status, $id);
        $stmtUp->execute();
        $stmtUp->close();

        log_audit_event("API_UPDATE_EVENT", "Updated event ID $id");

        json_response(200, true, "Event updated successfully", ['id' => $id, 'title' => $title]);
        break;

    case 'DELETE':
        $data = get_event_payload();
        $id = (int)($data['id'] ?? $_GET['id'] ?? 0);

        if ($id <= 0) {
            json_response(400, false, "Invalid event ID.");
        }

        $stmtDel = $db->prepare("DELETE FROM events WHERE id = ?");
        $stmtDel->bind_param("i", $id);
        $stmtDel->execute();
        $stmtDel->close();

        log_audit_event("API_DELETE_EVENT", "Deleted event ID $id");

        json_response(200, true, "Event deleted successfully");
        break;

    default:
        json_response(405, false, "Method $method Not Allowed");
        break;
}
