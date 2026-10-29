<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/response.php';

init_session();
require_student();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Use POST to register for an event.');
}
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    exit('The request could not be verified. Reload the page and try again.');
}

$eventId = filter_var($_POST['event_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($eventId === false || $eventId === null) {
    http_response_code(400);
    exit('A valid event is required.');
}

$db = get_db_connection();
$user = current_user();
$transactionStarted = false;
try {
    $db->begin_transaction();
    $transactionStarted = true;

    $studentStmt = $db->prepare("SELECT id FROM students WHERE user_id = ? AND status = 'active' LIMIT 1 FOR UPDATE");
    $studentStmt->bind_param('i', $user['id']);
    $studentStmt->execute();
    $student = $studentStmt->get_result()->fetch_assoc();
    $studentStmt->close();
    if (!$student) {
        throw new RuntimeException('An active student profile is required.');
    }

    $eventStmt = $db->prepare(
        "SELECT id, title, seats FROM events WHERE id = ? AND status IN ('open', 'upcoming') AND event_date >= CURDATE() LIMIT 1 FOR UPDATE"
    );
    $eventStmt->bind_param('i', $eventId);
    $eventStmt->execute();
    $event = $eventStmt->get_result()->fetch_assoc();
    $eventStmt->close();
    if (!$event) {
        throw new DomainException('This event is unavailable for registration.');
    }

    $duplicateStmt = $db->prepare("SELECT id FROM registrations WHERE student_id = ? AND event_id = ? LIMIT 1");
    $duplicateStmt->bind_param('ii', $student['id'], $eventId);
    $duplicateStmt->execute();
    $alreadyRegistered = $duplicateStmt->get_result()->num_rows > 0;
    $duplicateStmt->close();
    if ($alreadyRegistered) {
        throw new DomainException('You are already registered for this event.');
    }

    $capacityStmt = $db->prepare("SELECT COUNT(*) AS total FROM registrations WHERE event_id = ? AND status != 'cancelled'");
    $capacityStmt->bind_param('i', $eventId);
    $capacityStmt->execute();
    $registrationCount = (int)$capacityStmt->get_result()->fetch_assoc()['total'];
    $capacityStmt->close();
    if ($registrationCount >= (int)$event['seats']) {
        throw new DomainException('This event has reached its registration capacity.');
    }

    $insertStmt = $db->prepare(
        "INSERT INTO registrations (student_id, event_id, status) VALUES (?, ?, 'confirmed')"
    );
    $insertStmt->bind_param('ii', $student['id'], $eventId);
    $insertStmt->execute();
    $insertStmt->close();
    $db->commit();
    $transactionStarted = false;

    log_audit_event('EVENT_REGISTRATION', 'Registered for event ' . $eventId, $user['id']);
    $_SESSION['flash_success'] = 'You are registered for ' . $event['title'] . '.';
} catch (DomainException $error) {
    if ($transactionStarted) {
        $db->rollback();
        $transactionStarted = false;
    }
    http_response_code(409);
    $_SESSION['flash_error'] = $error->getMessage();
} catch (Throwable $error) {
    if ($transactionStarted) {
        $db->rollback();
    }
    error_log('Student event registration failed: ' . $error->getMessage());
    http_response_code(500);
    $_SESSION['flash_error'] = 'Event registration could not be completed. Please try again.';
}

header('Location: ../students/dashboard.php');
exit;
