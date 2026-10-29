<?php
// ============================================================================
// Student Hub Portal - Update Student Administrative Details (Admin Only)
// Synchronizes status & email with users table, validates inputs, logs audit trail.
// ============================================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/csrf.php';

init_session();
require_admin();

$db = get_db_connection();
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if ($id <= 0) {
    if (is_api_request()) {
        json_response(400, false, "Invalid student record ID.");
    }
    header("Location: list.php");
    exit;
}

// Fetch existing record
$stmtFetch = $db->prepare("SELECT * FROM students WHERE id = ? LIMIT 1");
$stmtFetch->bind_param("i", $id);
$stmtFetch->execute();
$student = $stmtFetch->get_result()->fetch_assoc();
$stmtFetch->close();

if (!$student) {
    if (is_api_request()) {
        json_response(404, false, "Student record not found.");
    }
    $_SESSION['flash_error'] = "Student record not found.";
    header("Location: list.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        $errors['general'] = "The form expired or could not be verified. Reload and try again.";
    }

    $name          = trim($_POST['name'] ?? '');
    $email         = strtolower(trim($_POST['email'] ?? ''));
    $mobile        = trim($_POST['mobile'] ?? '');
    $course        = trim($_POST['course'] ?? '');
    $department    = trim($_POST['department'] ?? 'Computer Science & Engineering');
    $semester      = max(1, min(8, (int)($_POST['semester'] ?? 1)));
    $year          = max(1, min(4, (int)($_POST['year'] ?? 1)));
    $admissionDate = trim($_POST['admission_date'] ?? ($student['admission_date'] ?? date('Y-m-d')));
    $gender        = in_array($_POST['gender'] ?? '', ['Male', 'Female', 'Other']) ? $_POST['gender'] : 'Other';
    $cgpa          = max(0.0, min(10.0, (float)($_POST['cgpa'] ?? 8.0)));
    $status        = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

    if (!validate_full_name($name)) {
        $errors['name'] = "Name must be 2 to 50 characters (letters and spaces only).";
    }
    if (!validate_email_format($email)) {
        $errors['email'] = "Valid email address required.";
    }
    if (!validate_indian_mobile($mobile)) {
        $errors['mobile'] = "Valid 10-digit mobile number required.";
    }
    if (empty($course)) {
        $errors['course'] = "Please select an academic course.";
    }

    if (empty($errors)) {
        try {
            // Check email collision with other students
            $stmtChk = $db->prepare("SELECT id FROM students WHERE email = ? AND id != ? LIMIT 1");
            $stmtChk->bind_param("si", $email, $id);
            $stmtChk->execute();
            if ($stmtChk->get_result()->num_rows > 0) {
                $errors['email'] = "This email address is already in use by another student.";
            }
            $stmtChk->close();

            if (empty($errors)) {
                $db->begin_transaction();

                $stmtUp = $db->prepare("
                    UPDATE students
                    SET name = ?, email = ?, mobile = ?, course = ?, semester = ?, year = ?,
                        department = ?, admission_date = ?, gender = ?, cgpa = ?, status = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmtUp->bind_param(
                    "ssssiisssdsi",
                    $name, $email, $mobile, $course, $semester, $year,
                    $department, $admissionDate, $gender, $cgpa, $status, $id
                );
                $stmtUp->execute();
                $stmtUp->close();

                // Synchronize users table if user_id linked
                $userId = (int)($student['user_id'] ?? 0);
                if ($userId > 0) {
                    $stmtUserSync = $db->prepare("UPDATE users SET email = ?, status = ?, updated_at = NOW() WHERE id = ?");
                    $stmtUserSync->bind_param("ssi", $email, $status, $userId);
                    $stmtUserSync->execute();
                    $stmtUserSync->close();
                }

                $db->commit();

                log_audit_event("UPDATE_STUDENT", "Admin #{$_SESSION['user_id']} updated student ID $id ({$student['student_id']})");
                $_SESSION['flash_success'] = "Student {$student['student_id']} updated successfully!";

                if (is_api_request()) {
                    json_response(200, true, "Student updated successfully");
                }

                header("Location: list.php");
                exit;
            }
        } catch (Exception $e) {
            $db->rollback();
            error_log("Update Student Error: " . $e->getMessage());
            $errors['general'] = "Database error while updating student: " . $e->getMessage();
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
    <title>Edit Student: <?= htmlspecialchars($student['student_id']) ?> - Student Hub Portal</title>
    <link rel="stylesheet" href="../../Style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="../../script.js" defer></script>
    <style>
        .form-card { max-width: 820px; margin: 35px auto 60px; padding: 35px; background: var(--card-bg, #fff); border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: 1px solid var(--border-color, #e2e8f0); font-family: 'Segoe UI', Tahoma, sans-serif; }
        .form-title { display: flex; align-items: center; gap: 12px; margin: 0 0 20px; color: var(--text-color, #0f172a); font-size: 1.35rem; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 680px) { .form-grid { grid-template-columns: 1fr; } }
        .form-group { display: flex; flex-direction: column; }
        .form-group.full-width { grid-column: 1 / -1; }
        .form-group label { font-size: 0.88rem; font-weight: 600; color: var(--text-color, #475569); margin-bottom: 6px; }
        .form-group input, .form-group select { padding: 10px 14px; border: 1px solid var(--border-color, #cbd5e1); border-radius: 8px; font-size: 0.95rem; background: var(--input-bg, #fff); color: var(--text-color, #0f172a); }
        .error-hint { color: #dc2626; font-size: 0.8rem; margin-top: 4px; font-weight: 500; }
        .alert-error { background: #fee2e2; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #dc2626; }
        .btn-row { display: flex; gap: 12px; margin-top: 25px; padding-top: 20px; border-top: 1px solid var(--border-color, #e2e8f0); }
        .btn { padding: 10px 22px; border-radius: 8px; font-weight: 600; text-decoration: none; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: var(--primary-color, #2563eb); color: #fff; }
        .btn-secondary { background: #f1f5f9; color: #334155; }
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

    <main class="form-card" id="main-content">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:1px solid var(--border-color); padding-bottom:15px;">
            <div class="form-title">
                <i class="fa-solid fa-user-pen" style="color: var(--primary-color, #2563eb);"></i>
                <h2>Edit Student: <?= htmlspecialchars($student['student_id']) ?></h2>
            </div>
            <a href="list.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back to List</a>
        </div>

        <?php if (!empty($errors['general'])): ?>
            <div class="alert-error" role="alert"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($errors['general']) ?></div>
        <?php endif; ?>

        <form action="update.php" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$student['id'] ?>">

            <div class="form-grid">
                <div class="form-group">
                    <label>Student ID / Enrollment Number (Fixed)</label>
                    <input type="text" value="<?= htmlspecialchars($student['student_id']) ?>" disabled style="background: var(--primary-light, #f1f5f9); cursor: not-allowed;">
                </div>

                <div class="form-group">
                    <label for="name">Full Name *</label>
                    <input type="text" id="name" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? $student['name']) ?>">
                    <?php if (!empty($errors['name'])): ?><span class="error-hint"><?= $errors['name'] ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? $student['email']) ?>">
                    <?php if (!empty($errors['email'])): ?><span class="error-hint"><?= $errors['email'] ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="mobile">Mobile Number *</label>
                    <input type="tel" id="mobile" name="mobile" required maxlength="10" value="<?= htmlspecialchars($_POST['mobile'] ?? $student['mobile']) ?>">
                    <?php if (!empty($errors['mobile'])): ?><span class="error-hint"><?= $errors['mobile'] ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="course">Course / Degree Program *</label>
                    <select id="course" name="course" required>
                        <?php
                        $courses = [
                            'B.Tech Computer Science', 'B.Tech Information Technology',
                            'B.Tech Artificial Intelligence', 'B.Tech Mechanical Engineering',
                            'B.Tech Civil Engineering', 'B.Tech Electronics', 'BCA', 'MCA'
                        ];
                        $selCourse = $_POST['course'] ?? $student['course'];
                        foreach ($courses as $c):
                        ?>
                            <option value="<?= htmlspecialchars($c) ?>" <?= ($selCourse === $c) ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="department">Department *</label>
                    <select id="department" name="department" required>
                        <?php
                        $depts = [
                            'Computer Science & Engineering', 'Information Technology',
                            'Artificial Intelligence & Data Science', 'Electronics & Communication',
                            'Mechanical Engineering', 'Civil Engineering'
                        ];
                        $selDept = $_POST['department'] ?? ($student['department'] ?? 'Computer Science & Engineering');
                        foreach ($depts as $d):
                        ?>
                            <option value="<?= htmlspecialchars($d) ?>" <?= ($selDept === $d) ? 'selected' : '' ?>><?= htmlspecialchars($d) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="semester">Semester (1 to 8) *</label>
                    <select id="semester" name="semester" required>
                        <?php
                        $selSem = (int)($_POST['semester'] ?? ($student['semester'] ?? 1));
                        for ($s = 1; $s <= 8; $s++):
                        ?>
                            <option value="<?= $s ?>" <?= ($selSem === $s) ? 'selected' : '' ?>>Semester <?= $s ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="year">Academic Year *</label>
                    <select id="year" name="year" required>
                        <?php
                        $selYear = (int)($_POST['year'] ?? $student['year']);
                        for ($y = 1; $y <= 4; $y++):
                        ?>
                            <option value="<?= $y ?>" <?= ($selYear === $y) ? 'selected' : '' ?>>Year <?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="admission_date">Admission Date *</label>
                    <input type="date" id="admission_date" name="admission_date" required value="<?= htmlspecialchars($_POST['admission_date'] ?? ($student['admission_date'] ?? date('Y-m-d'))) ?>">
                </div>

                <div class="form-group">
                    <label for="status">Account Status *</label>
                    <select id="status" name="status" required>
                        <?php $curStatus = $_POST['status'] ?? $student['status']; ?>
                        <option value="active" <?= ($curStatus === 'active') ? 'selected' : '' ?>>Active (Can Sign In)</option>
                        <option value="inactive" <?= ($curStatus === 'inactive') ? 'selected' : '' ?>>Inactive (Deactivated / Blocked)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender">
                        <?php foreach (['Male', 'Female', 'Other'] as $g): ?>
                            <option value="<?= $g ?>" <?= (($student['gender'] ?? '') === $g) ? 'selected' : '' ?>><?= $g ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="cgpa">Cumulative CGPA</label>
                    <input type="number" id="cgpa" name="cgpa" step="0.01" min="0" max="10" value="<?= htmlspecialchars($_POST['cgpa'] ?? $student['cgpa']) ?>">
                </div>
            </div>

            <div class="btn-row">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
                <a href="list.php" class="btn btn-secondary"><i class="fa-solid fa-xmark"></i> Cancel</a>
            </div>
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
</body>
</html>
