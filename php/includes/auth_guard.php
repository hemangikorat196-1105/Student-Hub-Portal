<?php
// ============================================================================
// Student Hub Portal - Role-Based Access Control (RBAC) Guard
// Practicals 10, 12, 14, 15
// ============================================================================

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/response.php';

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
    require_login();

    if (!is_admin()) {
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
    if (!is_student() && !is_admin()) {
        if (is_api_request()) {
            json_response(403, false, "Access restricted to students.");
        }
        header("HTTP/1.1 403 Forbidden");
        exit("Access restricted to students.");
    }
}

/**
 * Helper to determine if the current request expects a JSON API response.
 *
 * @return bool
 */
function is_api_request() {
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    return (strpos($accept, 'application/json') !== false) ||
           (strpos($contentType, 'application/json') !== false) ||
           (strpos($uri, '/api/') !== false);
}
