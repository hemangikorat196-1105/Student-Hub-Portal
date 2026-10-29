<?php
// ============================================================================
// Student Hub Portal - Role-Based Access Control (RBAC) Guard
// Practicals 10, 12, 14, 15
// ============================================================================

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/../config/db.php';

/**
 * Enforces that a user is authenticated. Redirects to login or returns 401.
 *
 * @param string $redirectUrl
 */
function require_login($redirectUrl = '../../Login.html') {
    init_session();
    if (!is_logged_in()) {
        if (is_api_request()) {
            json_response(401, false, "Authentication required. Please login.");
        }
        $_SESSION['flash_error'] = "You must be logged in to view that page.";
        header("Location: " . $redirectUrl);
        exit;
    }
}

/**
 * Enforces that the logged-in user is an administrator.
 *
 * @param string $accessDeniedUrl
 */
function require_admin($accessDeniedUrl = '../admin/access_denied.php') {
    require_login('../../admin-login.html');

    if (!is_admin() || !verified_database_role('admin')) {
        log_audit_event("UNAUTHORIZED_ACCESS", "User " . ($_SESSION['username'] ?? 'unknown') . " attempted to access admin resource.");
        if (is_api_request()) {
            json_response(403, false, "Forbidden: Administrator privileges required.");
        }
        header("HTTP/1.1 403 Forbidden");
        header("Location: " . $accessDeniedUrl);
        exit;
    }
}

/**
 * Enforces that the logged-in user is a student.
 */
function require_student() {
    require_login();
    if (!is_student() || !verified_database_role('student')) {
        if (is_api_request()) {
            json_response(403, false, "Access restricted to students.");
        }
        header("HTTP/1.1 403 Forbidden");
        exit("Access restricted to an active student account.");
    }

    // Intercept if temporary password must be changed
    if (!empty($_SESSION['must_change_password'])) {
        $currentScript = basename($_SERVER['PHP_SELF'] ?? '');
        if ($currentScript !== 'change_password.php' && $currentScript !== 'logout.php') {
            if (is_api_request()) {
                json_response(403, false, "Password change required before accessing portal modules.", ['must_change_password' => true]);
            }
            $target = (strpos($_SERVER['PHP_SELF'] ?? '', '/php/students/') !== false)
                ? 'change_password.php'
                : ((strpos($_SERVER['PHP_SELF'] ?? '', '/php/') !== false)
                    ? '../students/change_password.php'
                    : 'php/students/change_password.php');
            header("Location: " . $target);
            exit;
        }
    }
}

function verified_database_role($role) {
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if ($userId <= 0 || ($_SESSION['auth_role'] ?? '') !== $role) {
        return false;
    }

    $db = get_db_connection();
    $stmt = $db->prepare("SELECT role, status FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $account = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$account || $account['role'] !== $role || $account['status'] !== 'active') {
        logout_user();
        return false;
    }
    return true;
}

if (!function_exists('is_api_request')) {
    /**
     * Helper to determine if the current request expects a JSON API response.
     *
     * @return bool
     */
    function is_api_request() {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';

        return (
            strpos($accept, 'application/json') !== false ||
            strpos($contentType, 'application/json') !== false ||
            strpos($uri, '/api/') !== false ||
            strtolower($requestedWith) === 'xmlhttprequest'
        );
    }
}
