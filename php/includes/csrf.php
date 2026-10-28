<?php
// ============================================================================
// Student Hub Portal - Cross-Site Request Forgery (CSRF) Protection
// Practical 9, 10, 14, 15
// ============================================================================

require_once __DIR__ . '/session.php';

/**
 * Generates or retrieves existing CSRF token for the current session.
 *
 * @return string
 */
function generate_csrf_token() {
    init_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validates the submitted CSRF token against the session token.
 *
 * @param string|null $token
 * @return bool
 */
function verify_csrf_token($token) {
    init_session();
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Outputs a hidden input field containing the current CSRF token.
 *
 * @return string
 */
function csrf_field() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}
