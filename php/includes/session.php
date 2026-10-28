<?php
// ============================================================================
// Student Hub Portal - Secure Session Management
// Practicals 10, 14, 15
// Features: session_regenerate_id(), inactivity timeout, secure cookie flags
// ============================================================================

defined('SESSION_TIMEOUT_SECONDS') or define('SESSION_TIMEOUT_SECONDS', 900); // 15 minutes

/**
 * Initializes a secure session if not already started.
 */
function init_session() {
    if (session_status() === PHP_SESSION_NONE) {
        // Secure cookie parameters
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();
    }

    // Check session inactivity timeout
    if (isset($_SESSION['user_id'])) {
        $lastActivity = $_SESSION['last_activity'] ?? time();
        if ((time() - $lastActivity) > SESSION_TIMEOUT_SECONDS) {
            // Session expired due to inactivity
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            session_destroy();
            session_start();
            $_SESSION['flash_error'] = "Session timed out due to 15 minutes of inactivity. Please login again.";
            return false;
        }
        $_SESSION['last_activity'] = time();
    }

    return true;
}

/**
 * Determines whether a user is currently logged in.
 *
 * @return bool
 */
function is_logged_in() {
    init_session();
    return !empty($_SESSION['user_id']);
}

/**
 * Checks if the current user has the 'admin' role.
 *
 * @return bool
 */
function is_admin() {
    init_session();
    return is_logged_in() && (($_SESSION['role'] ?? '') === 'admin');
}

/**
 * Checks if the current user has the 'student' role.
 *
 * @return bool
 */
function is_student() {
    init_session();
    return is_logged_in() && (($_SESSION['role'] ?? '') === 'student');
}

/**
 * Returns current logged-in user array or null.
 *
 * @return array|null
 */
function current_user() {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'       => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
        'email'    => $_SESSION['email'] ?? '',
        'role'     => $_SESSION['role'] ?? 'student',
        'student_id' => $_SESSION['student_id'] ?? null
    ];
}

/**
 * Logs in a user, regenerates session ID to prevent session fixation.
 *
 * @param array $user
 */
function login_user($user) {
    init_session();
    // Regenerate session ID for security
    session_regenerate_id(true);

    $_SESSION['user_id']       = (int)$user['id'];
    $_SESSION['username']      = $user['username'];
    $_SESSION['email']         = $user['email'] ?? '';
    $_SESSION['role']          = $user['role'] ?? 'student';
    $_SESSION['student_id']    = $user['student_id'] ?? $user['username'];
    $_SESSION['last_activity'] = time();
    $_SESSION['login_time']    = time();
}

/**
 * Logs out the current user and clears session state completely.
 */
function logout_user() {
    init_session();
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    session_destroy();
}
