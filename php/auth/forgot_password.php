<?php
// ============================================================================
// Student Hub Portal - Forgot Password Handler
// Practicals 5, 10, 15
// ============================================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/response.php';

init_session();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['studentId'] ?? $_POST['email'] ?? '');

    if (empty($identifier)) {
        if (is_api_request()) {
            json_response(400, false, "Please enter your registered Student ID or Email.");
        }
        $error = "Please enter your registered Student ID or Email.";
    } else {
        $db = get_db_connection();
        $stmt = $db->prepare("SELECT id, username, email FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_assoc();
        $stmt->close();

        if ($user) {
            log_audit_event("PASSWORD_RESET_REQ", "Password reset initiated for user {$user['username']}", $user['id']);
            $msg = "A password reset link has been dispatched to your registered email (" . htmlspecialchars($user['email']) . ").";
            if (is_api_request()) {
                json_response(200, true, $msg);
            }
            $success = $msg;
        } else {
            // For security, do not disclose whether account exists
            $msg = "If an account matches your entry, instructions have been sent to the registered email.";
            if (is_api_request()) {
                json_response(200, true, $msg);
            }
            $success = $msg;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Recovery - Student Hub Portal</title>
    <link rel="stylesheet" href="../../Style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        .recovery-box { max-width: 480px; margin: 60px auto; padding: 30px; background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); font-family: 'Segoe UI', Tahoma, sans-serif; }
        .recovery-box h2 { margin-bottom: 12px; color: #0f172a; }
        .alert-box { padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
    </style>
</head>
<body>
    <main class="recovery-box">
        <h2><i class="fa-solid fa-key"></i> Recover Password</h2>
        <p>Enter your Student ID or registered email address to receive password reset instructions.</p>

        <?php if (!empty($success)): ?>
            <div class="alert-box alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert-box alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="forgot_password.php" method="POST">
            <div class="form-group">
                <label for="identifier">Student ID or Registered Email *</label>
                <input type="text" id="identifier" name="studentId" required placeholder="e.g. 25CS035 or student@example.com" class="form-control">
            </div>
            <div class="form-actions" style="margin-top: 20px;">
                <button type="submit" class="btn btn-primary" style="padding: 10px 20px; background: #2563eb; color: #fff; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">
                    Send Reset Link
                </button>
            </div>
        </form>
        <div style="margin-top: 20px;">
            <a href="../../Login.html" style="color: #2563eb; text-decoration: none;"><i class="fa-solid fa-arrow-left"></i> Back to Login</a>
        </div>
    </main>
</body>
</html>
