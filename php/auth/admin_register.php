<?php
// ============================================================================
// Student Hub Portal - Administrator Registration Handler
// Securely registers authorized administrator accounts with role 'admin'
// ============================================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/response.php';

init_session();

$loginPage = '../../admin-login.html';
$registerPage = '../../admin-register.html';

// Accept only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (is_api_request()) {
        json_response(405, false, "Method Not Allowed. Use POST.");
    }
    header("Location: " . $registerPage);
    exit;
}

$csrfToken       = $_POST['csrf_token'] ?? '';
$fullName        = trim($_POST['fullName'] ?? '');
$email           = trim($_POST['email'] ?? '');
$mobile          = trim($_POST['mobile'] ?? '');
$department      = trim($_POST['department'] ?? '');
$username        = trim($_POST['username'] ?? '');
$passcode        = trim($_POST['passcode'] ?? '');
$password        = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirmPassword'] ?? '';
$terms           = isset($_POST['terms']);

// 1. Verify CSRF if provided
if (!empty($csrfToken) && !verify_csrf_token($csrfToken)) {
    http_response_code(403);
    if (is_api_request()) {
        json_response(403, false, "CSRF token validation failed. Please refresh the page.");
    }
    render_admin_reg_error("Form session expired or invalid. Please reload the registration page and try again.", $registerPage);
    exit;
}

// 2. Validate Inputs
$errors = [];

if (empty($fullName) || strlen($fullName) < 2 || strlen($fullName) > 50 || !preg_match("/^[a-zA-Z .]+$/", $fullName)) {
    $errors['fullName'] = "Full name must be 2 to 50 characters (letters, dots, and spaces only).";
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = "A valid institutional email address is required.";
}

if (!preg_match("/^[6-9][0-9]{9}$/", $mobile)) {
    $errors['mobile'] = "Mobile number must be a 10-digit number starting with 6, 7, 8, or 9.";
}

if (empty($department)) {
    $errors['department'] = "Please select your administrative department.";
}

if (empty($username) || strlen($username) < 4 || strlen($username) > 30 || !preg_match("/^[A-Za-z0-9_.-]+$/", $username)) {
    $errors['username'] = "Username must be 4 to 30 characters containing letters, numbers, hyphens or underscores.";
}

// Security Passcode verification (Default: ADMIN2026)
define('ADMIN_MASTER_PASSCODE', 'ADMIN2026');
if ($passcode !== ADMIN_MASTER_PASSCODE) {
    $errors['passcode'] = "Invalid Administrator Security Passcode. Authorization is restricted to verified campus officials.";
}

if (strlen($password) < 8 ||
    !preg_match('/[A-Z]/', $password) ||
    !preg_match('/[a-z]/', $password) ||
    !preg_match('/[0-9]/', $password) ||
    !preg_match('/[@$!%*?&#^_\-~]/', $password)) {
    $errors['password'] = "Password must be at least 8 characters and include uppercase, lowercase, number, and special character.";
}

if ($password !== $confirmPassword) {
    $errors['confirmPassword'] = "Password confirmation does not match.";
}

if (!$terms) {
    $errors['terms'] = "You must agree to the Administrator Security Protocols & Campus Data Protection Policy.";
}

if (!empty($errors)) {
    $firstError = reset($errors);
    if (is_api_request()) {
        json_response(400, false, $firstError, ['errors' => $errors]);
    }
    render_admin_reg_error($firstError, $registerPage);
    exit;
}

try {
    $db = get_db_connection();

    // 3. Duplicate Check
    $stmtCheck = $db->prepare("SELECT id, username, email FROM users WHERE username = ? OR email = ? LIMIT 1");
    $stmtCheck->bind_param("ss", $username, $email);
    $stmtCheck->execute();
    $existing = $stmtCheck->get_result()->fetch_assoc();
    $stmtCheck->close();

    if ($existing) {
        $duplicateMsg = ($existing['username'] === $username)
            ? "Administrator username '$username' is already registered. Please choose another username."
            : "Email address '$email' is already linked to an account.";

        if (is_api_request()) {
            json_response(409, false, $duplicateMsg);
        }
        render_admin_reg_error($duplicateMsg, $registerPage);
        exit;
    }

    // 4. Hash Password & Insert Administrator Record
    $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $role = 'admin';
    $status = 'active';

    $stmtInsert = $db->prepare("
        INSERT INTO users (username, email, password, role, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmtInsert->bind_param("sssss", $username, $email, $passwordHash, $role, $status);
    $stmtInsert->execute();
    $newUserId = $stmtInsert->insert_id;
    $stmtInsert->close();

    // 5. Audit Logging
    log_audit_event("ADMIN_REGISTER", "New administrator user '$username' ($email, $department) registered.", $newUserId);

    if (is_api_request()) {
        json_response(201, true, "Administrator account created successfully.", [
            'id'       => $newUserId,
            'username' => $username,
            'email'    => $email,
            'role'     => 'admin'
        ]);
    }

    // Redirect to Administrator Login with success flag
    header("Location: " . $loginPage . "?status=registered");
    exit;

} catch (Exception $e) {
    error_log("Admin Registration Exception: " . $e->getMessage());
    if (is_api_request()) {
        json_response(500, false, "Server error during registration.");
    }
    render_admin_reg_error("A database error occurred while registering your account. Please try again.", $registerPage);
    exit;
}

function render_admin_reg_error($message, $backUrl) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Registration Failed - Student Hub Portal</title>
        <link rel="stylesheet" href="../../Style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
        <style>
            .error-card { max-width: 520px; margin: 60px auto; padding: 35px; background: #fff; border-radius: 12px; border-left: 6px solid #dc2626; box-shadow: 0 4px 20px rgba(0,0,0,0.08); font-family: 'Segoe UI', Tahoma, sans-serif; text-align: center; }
            .error-card i { font-size: 3rem; color: #dc2626; margin-bottom: 15px; }
            .error-card h2 { margin: 10px 0; color: #0f172a; }
            .error-card p { color: #64748b; margin-bottom: 25px; line-height: 1.6; }
            .btn { display: inline-block; padding: 12px 24px; background: #2563eb; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 600; }
        </style>
    </head>
    <body>
        <main class="error-card">
            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
            <h2>Registration Error</h2>
            <p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
            <a href="<?= htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn">
                <i class="fa-solid fa-arrow-left"></i> Return to Registration
            </a>
        </main>
    </body>
    </html>
    <?php
    exit;
}
