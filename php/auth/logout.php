<?php
// ============================================================================
// Student Hub Portal - Secure Logout Handler
// Practicals 10, 14, 15
// Features: Session destruction, cookie cleanup, audit log entry
// ============================================================================

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/response.php';

init_session();

if (is_logged_in()) {
    $user = current_user();
    log_audit_event("LOGOUT", "User {$user['username']} logged out", $user['id']);
}

// Clear remember-me cookie
if (isset($_COOKIE['studenthub_remember'])) {
    setcookie('studenthub_remember', '', time() - 3600, '/');
}

logout_user();

if (is_api_request()) {
    json_response(200, true, "Successfully logged out");
}

header("Location: ../../Login.html?logout=1");
exit;
