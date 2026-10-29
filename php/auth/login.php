<?php
// ============================================================================
// Student Hub Portal - Secure Authentication Handler
// Practicals 10, 14, 15
// Features: password_verify(), session_regenerate_id(), RBAC redirection, audit log
// ============================================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/response.php';

init_session();
$requiredRole = defined('AUTH_REQUIRED_ROLE') ? AUTH_REQUIRED_ROLE : 'student';
$loginPage = $requiredRole === 'admin' ? '../../admin-login.html' : '../../Login.html';
$redirectUrl = $requiredRole === 'admin' ? '../admin/dashboard.php' : '../../home.html';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (is_api_request()) {
        json_response(405, false, "Method Not Allowed. Use POST.");
    }
    header("Location: " . $loginPage);
    exit;
}

$identifier = trim($_POST['studentId'] ?? $_POST['username'] ?? $_POST['email'] ?? '');
$password   = $_POST['password'] ?? '';
$csrfToken  = $_POST['csrf_token'] ?? '';

if (!verify_csrf_token($csrfToken)) {
    http_response_code(403);
    render_login_error("The form expired or could not be verified. Reload the page and try again.", $loginPage);
}

if (empty($identifier) || empty($password)) {
    if (is_api_request()) {
        json_response(400, false, "Please enter both Student ID/Username and Password.");
    }
    render_login_error("Please enter both Student ID/Username and Password.", $loginPage);
    exit;
}

try {
    $db = get_db_connection();

    // Query user by username or email
    $stmt = $db->prepare("
        SELECT u.id, u.username, u.email, u.password, u.role, u.status, s.student_id, s.name
        FROM users u
        LEFT JOIN students s ON s.user_id = u.id
        WHERE u.username = ? OR u.email = ? OR s.student_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("sss", $identifier, $identifier, $identifier);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    // Verify User Existence and Password using password_verify()
    if (!$user || !password_verify($password, $user['password'])) {
        log_audit_event("FAILED_LOGIN", "Failed login attempt for identifier '$identifier'");
        if (is_api_request()) {
            json_response(401, false, "Invalid student ID or password.");
        }
        render_login_error("Invalid student ID or password.", $loginPage);
        exit;
    }

    // Check Account Status
    if ($user['status'] !== 'active') {
        log_audit_event("BLOCKED_LOGIN", "Inactive user ID {$user['id']} tried to login");
        if (is_api_request()) {
            json_response(403, false, "Your account is currently inactive. Please contact the administrator.");
        }
        render_login_error("Your account is currently inactive. Please contact the administrator.", $loginPage);
        exit;
    }

    if ($user['role'] !== $requiredRole || ($requiredRole === 'student' && empty($user['student_id']))) {
        log_audit_event("UNAUTHORIZED_ROLE_LOGIN", "Account {$user['username']} attempted {$requiredRole} login", $user['id']);
        http_response_code(403);
        if (is_api_request()) {
            json_response(403, false, "This account is not permitted to use this login portal.");
        }
        render_login_error("This account is not permitted to use this login portal.", $loginPage);
    }

    // Login successful: Regenerate session ID and store user session data
    login_user([
        'id'         => $user['id'],
        'username'   => $user['username'],
        'email'      => $user['email'],
        'role'       => $user['role'],
        'student_id' => $user['student_id'] ?? $user['username']
    ]);

    // Update last_login timestamp
    $stmtUpdate = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmtUpdate->bind_param("i", $user['id']);
    $stmtUpdate->execute();
    $stmtUpdate->close();

    // Log Audit Event
    log_audit_event("LOGIN", "User {$user['username']} ({$user['role']}) logged in successfully", $user['id']);

    // Determine Role-Based Destination
    if (is_api_request()) {
        json_response(200, true, "Login successful", [
            'user'     => [
                'id'       => $user['id'],
                'username' => $user['username'],
                'name'     => $user['name'] ?? $user['username'],
                'role'     => $user['role']
            ],
            'redirect' => $redirectUrl
        ]);
    }

    header("Location: " . $redirectUrl);
    exit;

} catch (Exception $e) {
    error_log("Login Exception: " . $e->getMessage());
    if (is_api_request()) {
        json_response(500, false, "Server error during authentication.");
    }
    render_login_error("An error occurred during authentication. Please try again.", $loginPage);
}

function render_login_error($message, $loginPage = '../../Login.html') {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login Failed - Student Hub Portal</title>
        <link rel="stylesheet" href="../../Style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
        <style>
            .error-card { max-width: 480px; margin: 60px auto; padding: 30px; background: #fff; border-radius: 12px; border-left: 6px solid #dc2626; box-shadow: 0 4px 20px rgba(0,0,0,0.08); font-family: 'Segoe UI', Tahoma, sans-serif; text-align: center; }
            .error-card i { font-size: 3rem; color: #dc2626; margin-bottom: 15px; }
            .error-card h2 { margin: 10px 0; color: #0f172a; }
            .error-card p { color: #64748b; margin-bottom: 25px; }
            .btn { display: inline-block; padding: 10px 20px; background: #2563eb; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 600; }
        </style>
    </head>
    <body>
        <main class="error-card">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <h2>Authentication Failed</h2>
            <p><?= htmlspecialchars($message) ?></p>
            <a href="<?= htmlspecialchars($loginPage, ENT_QUOTES, 'UTF-8') ?>" class="btn"><i class="fa-solid fa-arrow-left"></i> Back to Login</a>
        </main>
    </body>
    </html>
    <?php
    exit;
}
