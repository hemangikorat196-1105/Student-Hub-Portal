<?php
// ============================================================================
// Student Hub Portal - Restricted Student Profile Update (Backend Enforcement)
// IDOR-Immune: Targets strictly $_SESSION['user_id'].
// Students are PERMITTED to update ONLY: (1) Mobile Number, (2) Password.
// All academic and identity details (ID, Name, Email, Course, Semester, Year,
// Admission Date, CGPA) are strictly immutable from student endpoints.
// ============================================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/csrf.php';

init_session();
require_student();

$user = current_user();
$userId = (int)($user['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (is_api_request()) {
        json_response(405, false, "Method Not Allowed. Use POST.");
    }
    header("Location: ../../profile.php");
    exit;
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    $_SESSION['flash_error'] = "Security session expired. Please reload the page and try again.";
    header("Location: ../../profile.php");
    exit;
}

$action = trim($_POST['action'] ?? '');
$db = get_db_connection();

// Verify active student record for the logged-in session user ID
$stmtStudent = $db->prepare("SELECT id, student_id, name, mobile FROM students WHERE user_id = ? AND status = 'active' LIMIT 1");
$stmtStudent->bind_param("i", $userId);
$stmtStudent->execute();
$student = $stmtStudent->get_result()->fetch_assoc();
$stmtStudent->close();

if (!$student) {
    http_response_code(403);
    if (is_api_request()) {
        json_response(403, false, "No active student profile bound to your session.");
    }
    exit("No active student profile bound to your session.");
}

// ----------------------------------------------------------------------------
// Action 1: Update Mobile Number
// ----------------------------------------------------------------------------
if ($action === 'update_mobile') {
    $mobile = trim($_POST['mobile'] ?? '');

    if (!validate_indian_mobile($mobile)) {
        $_SESSION['flash_error'] = "Mobile number must be a valid 10-digit number starting with 6, 7, 8, or 9.";
        if (is_api_request()) {
            json_response(422, false, $_SESSION['flash_error']);
        }
        header("Location: ../../profile.php");
        exit;
    }

    // Strictly update ONLY mobile for current session user ID
    $stmtUp = $db->prepare("UPDATE students SET mobile = ?, updated_at = NOW() WHERE user_id = ?");
    $stmtUp->bind_param("si", $mobile, $userId);
    $stmtUp->execute();
    $stmtUp->close();

    log_audit_event("UPDATE_STUDENT_MOBILE", "Student {$student['student_id']} updated their contact mobile number.", $userId);
    $_SESSION['flash_success'] = "Mobile number updated successfully.";

    if (is_api_request()) {
        json_response(200, true, "Mobile number updated successfully", ['mobile' => $mobile]);
    }

    header("Location: ../../profile.php");
    exit;
}

// ----------------------------------------------------------------------------
// Action 2: Change Password
// ----------------------------------------------------------------------------
if ($action === 'change_password') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword     = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($currentPassword)) {
        $_SESSION['flash_error'] = "Please enter your current password.";
        header("Location: ../../profile.php");
        exit;
    }

    if (!validate_password_strength($newPassword)) {
        $_SESSION['flash_error'] = "New password must be at least 8 characters and include uppercase, lowercase, a digit, and a special character.";
        header("Location: ../../profile.php");
        exit;
    }

    if ($newPassword !== $confirmPassword) {
        $_SESSION['flash_error'] = "New password and confirmation do not match.";
        header("Location: ../../profile.php");
        exit;
    }

    if ($currentPassword === $newPassword) {
        $_SESSION['flash_error'] = "Your new password cannot be the same as your current password.";
        header("Location: ../../profile.php");
        exit;
    }

    // Verify current password against database
    $stmtU = $db->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
    $stmtU->bind_param("i", $userId);
    $stmtU->execute();
    $userData = $stmtU->get_result()->fetch_assoc();
    $stmtU->close();

    if (!$userData || !password_verify($currentPassword, $userData['password'])) {
        $_SESSION['flash_error'] = "Current password is incorrect.";
        header("Location: ../../profile.php");
        exit;
    }

    // Hash new password using Bcrypt
    $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
    $stmtUp = $db->prepare("UPDATE users SET password = ?, must_change_password = 0, updated_at = NOW() WHERE id = ?");
    $stmtUp->bind_param("si", $newHash, $userId);
    $stmtUp->execute();
    $stmtUp->close();

    // Regenerate session ID for security and clear flag
    $_SESSION['must_change_password'] = 0;
    session_regenerate_id(true);

    log_audit_event("CHANGE_PASSWORD", "Student {$student['student_id']} changed their personal account password.", $userId);
    $_SESSION['flash_success'] = "Password changed successfully.";

    if (is_api_request()) {
        json_response(200, true, "Password changed successfully.");
    }

    header("Location: ../../profile.php");
    exit;
}

// Reject any other modification attempt
http_response_code(400);
if (is_api_request()) {
    json_response(400, false, "Invalid or unauthorized profile modification request.");
}
$_SESSION['flash_error'] = "Academic details cannot be edited by students. Contact Academic Administration.";
header("Location: ../../profile.php");
exit;
