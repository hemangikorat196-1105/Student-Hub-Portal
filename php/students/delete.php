<?php
// ============================================================================
// Student Hub Portal - Delete / Soft-Delete Student Record
// Practical 11 (CRUD), Practical 14, 15
// ============================================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/csrf.php';

init_session();
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Use POST to change student status.');
}
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    exit('The request could not be verified. Reload the page and try again.');
}

$id     = (int)($_POST['id'] ?? 0);
$action = trim($_POST['action'] ?? 'soft'); // 'soft', 'restore', 'hard'

if ($id <= 0) {
    if (is_api_request()) {
        json_response(400, false, "Invalid student ID specified.");
    }
    header("Location: list.php");
    exit;
}

try {
    $db = get_db_connection();

    // Verify record exists
    $stmtChk = $db->prepare("SELECT id, user_id, student_id, name, status FROM students WHERE id = ? LIMIT 1");
    $stmtChk->bind_param("i", $id);
    $stmtChk->execute();
    $student = $stmtChk->get_result()->fetch_assoc();
    $stmtChk->close();

    if (!$student) {
        if (is_api_request()) {
            json_response(404, false, "Student record not found.");
        }
        $_SESSION['flash_error'] = "Student record not found.";
        header("Location: list.php");
        exit;
    }

    if ($action === 'restore') {
        // Restore soft-deleted student to active
        $stmt = $db->prepare("UPDATE students SET status = 'active' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        if (!empty($student['user_id'])) {
            $stmtU = $db->prepare("UPDATE users SET status = 'active' WHERE id = ?");
            $stmtU->bind_param("i", $student['user_id']);
            $stmtU->execute();
            $stmtU->close();
        }

        log_audit_event("RESTORE_STUDENT", "Restored student {$student['student_id']} ({$student['name']}) to active");
        $_SESSION['flash_success'] = "Student {$student['student_id']} restored to Active status.";
    } elseif ($action === 'hard') {
        // Permanent delete (admin only)
        $stmt = $db->prepare("DELETE FROM students WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        if (!empty($student['user_id'])) {
            $stmtU = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmtU->bind_param("i", $student['user_id']);
            $stmtU->execute();
            $stmtU->close();
        }

        log_audit_event("HARD_DELETE_STUDENT", "Permanently removed student {$student['student_id']}");
        $_SESSION['flash_success'] = "Student {$student['student_id']} permanently deleted.";
    } else {
        // Default: Soft Delete (status = 'inactive')
        $stmt = $db->prepare("UPDATE students SET status = 'inactive' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        if (!empty($student['user_id'])) {
            $stmtU = $db->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
            $stmtU->bind_param("i", $student['user_id']);
            $stmtU->execute();
            $stmtU->close();
        }

        log_audit_event("SOFT_DELETE_STUDENT", "Soft-deleted student {$student['student_id']} ({$student['name']})");
        $_SESSION['flash_success'] = "Student {$student['student_id']} has been deactivated (status set to Inactive).";
    }

    if (is_api_request()) {
        json_response(200, true, $_SESSION['flash_success']);
    }

    header("Location: list.php");
    exit;

} catch (Exception $e) {
    error_log("Delete Student Error: " . $e->getMessage());
    if (is_api_request()) {
        json_response(500, false, "Server error during student deletion.");
    }
    $_SESSION['flash_error'] = "Failed to process student deletion.";
    header("Location: list.php");
    exit;
}
