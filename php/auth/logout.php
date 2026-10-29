<?php
// ============================================================================
// Student Hub Portal - Secure Logout Handler
// Practicals 10, 14, 15
// Features: Session destruction, cookie cleanup, audit log entry
// ============================================================================

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/response.php';

init_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Use POST to log out.');
}
if (!verify_csrf_token($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
    http_response_code(403);
    exit('The logout request could not be verified.');
}

if (is_logged_in()) {
    $user = current_user();
    log_audit_event("LOGOUT", "User {$user['username']} logged out", $user['id']);
    $logoutDestination = defined('AUTH_LOGOUT_REDIRECT')
        ? AUTH_LOGOUT_REDIRECT
        : ($user['role'] === 'admin' ? '../../admin-login.html?logout=1' : '../../Login.html?logout=1');
} else {
    $logoutDestination = defined('AUTH_LOGOUT_REDIRECT')
        ? AUTH_LOGOUT_REDIRECT
        : '../../Login.html?logout=1';
}

// Clear remember-me cookie
if (isset($_COOKIE['studenthub_remember'])) {
    setcookie('studenthub_remember', '', time() - 3600, '/');
}

logout_user();

if (is_api_request()) {
    json_response(200, true, "Successfully logged out");
}

header("Location: " . $logoutDestination);
exit;
