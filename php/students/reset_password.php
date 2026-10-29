<?php
// ============================================================================
// Student Hub Portal - Reset Student Password (Admin Only)
// Generates or assigns temporary password, enforces must_change_password = 1,
// flashes temporary password to admin once, logs audit event.
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
    if (is_api_request()) {
        json_response(405, false, "Method Not Allowed. Use POST.");
    }
    header("Location: list.php");
    exit;
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    $_SESSION['flash_error'] = "The request could not be verified. Please reload and try again.";
    header("Location: list.php");
    exit;
}

$id               = (int)($_POST['id'] ?? 0);
$autoGenerate     = isset($_POST['auto_generate']) && $_POST['auto_generate'] === '1';
$customPassword   = trim($_POST['custom_password'] ?? '');

if ($id <= 0) {
    if (is_api_request()) {
        json_response(400, false, "Invalid student record ID.");
    }
    $_SESSION['flash_error'] = "Invalid student record ID.";
    header("Location: list.php");
    exit;
}

$db = get_db_connection();

try {
    $stmt = $db->prepare("SELECT id, user_id, student_id, name, email FROM students WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$student) {
        if (is_api_request()) {
            json_response(404, false, "Student record not found.");
        }
        $_SESSION['flash_error'] = "Student record not found.";
        header("Location: list.php");
        exit;
    }

    // Determine temporary password
    $tempPassword = "";
    if ($autoGenerate || empty($customPassword)) {
        $rnd = bin2hex(random_bytes(3));
        $tempPassword = "Temp@" . strtoupper(substr($rnd, 0, 3)) . substr($rnd, 3) . "#8";
    } else {
        if (strlen($customPassword) < 8) {
            $_SESSION['flash_error'] = "Custom temporary password must be at least 8 characters.";
            header("Location: list.php");
            exit;
        }
        $tempPassword = $customPassword;
    }

    $passwordHash = password_hash($tempPassword, PASSWORD_BCRYPT);
    $userId = (int)($student['user_id'] ?? 0);

    // If no linked users row exists, create one
    if ($userId <= 0) {
        $adminId = (int)($_SESSION['user_id'] ?? 1);
        $stmtUser = $db->prepare("
            INSERT INTO users (username, email, password, role, status, must_change_password, created_by, created_at)
            VALUES (?, ?, ?, 'student', 'active', 1, ?, NOW())
        ");
        $stmtUser->bind_param("sssi", $student['student_id'], $student['email'], $passwordHash, $adminId);
        $stmtUser->execute();
        $userId = $stmtUser->insert_id;
        $stmtUser->close();

        $stmtLink = $db->prepare("UPDATE students SET user_id = ? WHERE id = ?");
        $stmtLink->bind_param("ii", $userId, $id);
        $stmtLink->execute();
        $stmtLink->close();
    } else {
        $stmtUp = $db->prepare("UPDATE users SET password = ?, must_change_password = 1, updated_at = NOW() WHERE id = ?");
        $stmtUp->bind_param("si", $passwordHash, $userId);
        $stmtUp->execute();
        $stmtUp->close();
    }

    // Log Audit Event
    $adminId = (int)($_SESSION['user_id'] ?? 1);
    log_audit_event("RESET_STUDENT_PASSWORD", "Admin #$adminId reset password for student {$student['student_id']} ({$student['name']}). Must change on next login.", $adminId);

    // Flash One-Time Temporary Password for Admin
    $_SESSION['flash_temp_password'] = [
        'student_id'    => $student['student_id'],
        'name'          => $student['name'],
        'temp_password' => $tempPassword
    ];

    if (is_api_request()) {
        json_response(200, true, "Temporary password reset successfully.", [
            'student_id'    => $student['student_id'],
            'temp_password' => $tempPassword
        ]);
    }

    header("Location: list.php");
    exit;

} catch (Exception $e) {
    error_log("Reset Password Exception: " . $e->getMessage());
    $_SESSION['flash_error'] = "Server error while resetting password: " . $e->getMessage();
    header("Location: list.php");
    exit;
}
