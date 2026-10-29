<?php
// ============================================================================
// Student Hub Portal - Mandatory First-Login Password Change
// Features: Current password verification, password strength rules,
// Bcrypt hashing, session regeneration, audit logging, reset must_change_password.
// ============================================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/csrf.php';

init_session();
require_login();

$user = current_user();
if (!$user) {
    header("Location: ../../Login.html");
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        $errors['general'] = "The security token expired or is invalid. Please refresh the page.";
    }

    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword     = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($currentPassword)) {
        $errors['current_password'] = "Please enter your current temporary password.";
    }
    if (!validate_password_strength($newPassword)) {
        $errors['new_password'] = "New password must be at least 8 characters and include uppercase, lowercase, a number, and a special character.";
    }
    if ($newPassword !== $confirmPassword) {
        $errors['confirm_password'] = "Password confirmation does not match the new password.";
    }
    if ($currentPassword !== '' && $currentPassword === $newPassword) {
        $errors['new_password'] = "Your new password must be different from your current temporary password.";
    }

    if (empty($errors)) {
        $db = get_db_connection();
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row || !password_verify($currentPassword, $row['password'])) {
            $errors['current_password'] = "The current password you entered is incorrect.";
        } else {
            $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmtUp = $db->prepare("UPDATE users SET password = ?, must_change_password = 0, updated_at = NOW() WHERE id = ?");
            $stmtUp->bind_param("si", $newHash, $user['id']);
            $stmtUp->execute();
            $stmtUp->close();

            // Clear temporary password flag and regenerate session
            $_SESSION['must_change_password'] = 0;
            session_regenerate_id(true);

            log_audit_event("PASSWORD_CHANGE", "User ID {$user['id']} ({$user['username']}) successfully changed temporary password to personal password.", $user['id']);

            $_SESSION['flash_success'] = "Password changed successfully! You now have full access to your student portal.";
            
            if (is_api_request()) {
                json_response(200, true, "Password changed successfully", ['redirect' => '../../home.php']);
            }

            header("Location: ../../home.php");
            exit;
        }
    }

    if (is_api_request() && !empty($errors)) {
        json_response(422, false, "Validation errors", ['errors' => $errors]);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Student Hub Portal</title>
    <link rel="stylesheet" href="../../Style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="../../script.js" defer></script>
    <style>
        .change-pass-card {
            max-width: 520px;
            margin: 40px auto 60px;
            padding: 35px 30px;
            background: var(--card-bg, #ffffff);
            border-radius: 14px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid var(--border-color, #e2e8f0);
            font-family: 'Segoe UI', Tahoma, sans-serif;
        }
        .change-pass-hero {
            text-align: center;
            margin-bottom: 25px;
        }
        .change-pass-hero i.hero-icon {
            font-size: 2.5rem;
            color: #d97706;
            margin-bottom: 12px;
        }
        .change-pass-hero h1 {
            font-size: 1.4rem;
            color: var(--text-color, #0f172a);
            margin: 0 0 6px;
        }
        .change-pass-hero p {
            font-size: 0.9rem;
            color: var(--text-muted, #64748b);
            margin: 0;
            line-height: 1.5;
        }
        .policy-box {
            background: #fffbeb;
            border: 1px solid #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 12px 14px;
            border-radius: 6px;
            font-size: 0.85rem;
            color: #92400e;
            margin-bottom: 22px;
            line-height: 1.4;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--text-color, #334155);
            margin-bottom: 6px;
        }
        .form-group input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--border-color, #cbd5e1);
            border-radius: 8px;
            font-size: 0.95rem;
            background: var(--input-bg, #ffffff);
            color: var(--text-color, #0f172a);
            box-sizing: border-box;
        }
        .form-group input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }
        .error-msg {
            color: #dc2626;
            font-size: 0.82rem;
            margin-top: 4px;
            display: block;
            font-weight: 500;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            border-left: 4px solid #dc2626;
        }
        .strength-meter-wrap {
            margin-top: 6px;
        }
        .strength-bar-track {
            height: 6px;
            background: #e2e8f0;
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 4px;
        }
        .strength-bar-fill {
            height: 100%;
            width: 0;
            transition: width 0.3s, background 0.3s;
        }
        .strength-label {
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-muted, #64748b);
        }
        .btn-submit {
            width: 100%;
            padding: 12px;
            background: #2563eb;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 10px;
            transition: background 0.15s;
        }
        .btn-submit:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <header class="main-header">
        <div class="header-left">
            <div class="portal-title">
                <h1>STUDENT HUB PORTAL</h1>
                <p>Digital Academic & Campus Management System</p>
            </div>
        </div>
        <button id="themeToggle" class="theme-btn" type="button" aria-label="Change Theme">
            <i class="fa-solid fa-moon"></i><span>Dark Mode</span>
        </button>
    </header>

    <main class="change-pass-card" id="main-content">
        <div class="change-pass-hero">
            <i class="fa-solid fa-shield-halved hero-icon"></i>
            <h1>Personalize Your Password</h1>
            <p>Welcome to Student Hub Portal. An initial temporary password was assigned to your account. Please set a new private password to continue.</p>
        </div>

        <div class="policy-box">
            <i class="fa-solid fa-circle-exclamation"></i>
            <strong>First-Login Requirement:</strong> For your security, this password change is mandatory. You cannot access assignments, attendance, or course resources until a secure permanent password is set.
        </div>

        <?php if (!empty($errors['general'])): ?>
            <div class="alert-error" role="alert"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($errors['general']) ?></div>
        <?php endif; ?>

        <form action="change_password.php" method="POST" id="changePasswordForm" novalidate>
            <?= csrf_field() ?>

            <!-- Current / Temporary Password -->
            <div class="form-group">
                <label for="current_password">Current Temporary Password <span style="color:#dc2626;">*</span></label>
                <input type="password" id="current_password" name="current_password" required autocomplete="current-password" placeholder="Enter default temporary password">
                <?php if (!empty($errors['current_password'])): ?><span class="error-msg"><?= $errors['current_password'] ?></span><?php endif; ?>
            </div>

            <!-- New Password -->
            <div class="form-group">
                <label for="new_password">New Permanent Password <span style="color:#dc2626;">*</span></label>
                <input type="password" id="new_password" name="new_password" required autocomplete="new-password" placeholder="At least 8 chars (upper, lower, digit, special)">
                <div class="strength-meter-wrap" id="passwordStrengthBox">
                    <div class="strength-bar-track">
                        <div class="strength-bar-fill" id="strengthFill"></div>
                    </div>
                    <span class="strength-label" id="strengthText">Password Strength: Empty</span>
                </div>
                <?php if (!empty($errors['new_password'])): ?><span class="error-msg"><?= $errors['new_password'] ?></span><?php endif; ?>
            </div>

            <!-- Confirm New Password -->
            <div class="form-group">
                <label for="confirm_password">Confirm New Password <span style="color:#dc2626;">*</span></label>
                <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password" placeholder="Re-type your new password">
                <span class="error-msg" id="confirmError"></span>
                <?php if (!empty($errors['confirm_password'])): ?><span class="error-msg"><?= $errors['confirm_password'] ?></span><?php endif; ?>
            </div>

            <button type="submit" class="btn-submit" id="btnSubmitChange">
                <i class="fa-solid fa-lock"></i> Update Password & Proceed
            </button>
        </form>

        <form method="post" action="../auth/logout.php" style="margin-top:16px; text-align:center;">
            <?= csrf_field() ?>
            <button type="submit" style="background:none; border:none; color:var(--text-muted); cursor:pointer; font-size:0.85rem; text-decoration:underline;">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> Sign out
            </button>
        </form>
    </main>

    <footer>
        <div class="footer-container">
            <div class="footer-section">
                <h2>StudentHub Portal</h2>
                <p>Your digital campus companion for academics, courses, assignments, attendance, and student services.</p>
            </div>
        </div>
        <p class="copyright">
            © 2026 StudentHub Portal | Developed By Hemangi Korat | All Rights Reserved.
        </p>
    </footer>

    <script>
        const newPassInput = document.getElementById('new_password');
        const confirmPassInput = document.getElementById('confirm_password');
        const fill = document.getElementById('strengthFill');
        const text = document.getElementById('strengthText');
        const confirmError = document.getElementById('confirmError');

        newPassInput.addEventListener('input', function() {
            const val = newPassInput.value;
            let score = 0;
            if (val.length >= 8) score++;
            if (/[A-Z]/.test(val)) score++;
            if (/[a-z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;

            if (val.length === 0) {
                fill.style.width = '0';
                text.innerText = 'Password Strength: Empty';
                text.style.color = '#64748b';
            } else if (score <= 2) {
                fill.style.width = '25%';
                fill.style.background = '#dc2626';
                text.innerText = 'Password Strength: Weak';
                text.style.color = '#dc2626';
            } else if (score === 3 || score === 4) {
                fill.style.width = '65%';
                fill.style.background = '#f59e0b';
                text.innerText = 'Password Strength: Medium';
                text.style.color = '#d97706';
            } else {
                fill.style.width = '100%';
                fill.style.background = '#16a34a';
                text.innerText = 'Password Strength: Strong';
                text.style.color = '#16a34a';
            }
            checkMatch();
        });

        confirmPassInput.addEventListener('input', checkMatch);

        function checkMatch() {
            if (confirmPassInput.value && confirmPassInput.value !== newPassInput.value) {
                confirmError.innerText = 'Passwords do not match.';
            } else {
                confirmError.innerText = '';
            }
        }
    </script>
</body>
</html>
