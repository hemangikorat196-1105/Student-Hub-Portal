<?php
// ============================================================================
// Student Hub Portal - Update Student Record
// Practical 11, 14, 15
// ============================================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/csrf.php';

init_session();
require_login('../../Login.html');

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
    $name   = trim($_POST['name'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $year   = (int)($_POST['year'] ?? 1);
    $gender = trim($_POST['gender'] ?? 'Other');
    $cgpa   = (float)($_POST['cgpa'] ?? 8.0);
    $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

    if (!validate_full_name($name)) {
        $errors['name'] = "Name must be 2 to 50 characters (letters and spaces only).";
    }
    if (!validate_email_format($email)) {
        $errors['email'] = "Valid email address required.";
    }
    if (!validate_indian_mobile($mobile)) {
        $errors['mobile'] = "Valid 10-digit mobile number required.";
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
                $stmtUp = $db->prepare("
                    UPDATE students
                    SET name = ?, email = ?, mobile = ?, course = ?, year = ?, gender = ?, cgpa = ?, status = ?
                    WHERE id = ?
                ");
                $stmtUp->bind_param("ssssisdsi", $name, $email, $mobile, $course, $year, $gender, $cgpa, $status, $id);
                $stmtUp->execute();
                $stmtUp->close();

                log_audit_event("UPDATE_STUDENT", "Updated student ID $id ({$student['student_id']})");
                $_SESSION['flash_success'] = "Student {$student['student_id']} updated successfully!";

                if (is_api_request()) {
                    json_response(200, true, "Student updated successfully");
                }

                header("Location: list.php");
                exit;
            }
        } catch (Exception $e) {
            error_log("Update Student Error: " . $e->getMessage());
            $errors['general'] = "Database error while updating student.";
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
    <title>Edit Student - <?= htmlspecialchars($student['student_id']) ?></title>
    <link rel="stylesheet" href="../../Style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        .form-card { max-width: 700px; margin: 40px auto; padding: 30px; background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); font-family: 'Segoe UI', Tahoma, sans-serif; }
        .form-title { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; color: #0f172a; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group.full-width { grid-column: span 2; }
        .form-group label { font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 6px; }
        .form-group input, .form-group select { padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; }
        .error-hint { color: #dc2626; font-size: 0.8rem; margin-top: 4px; }
        .btn-row { display: flex; gap: 12px; margin-top: 25px; }
        .btn { padding: 10px 20px; border-radius: 6px; font-weight: 600; text-decoration: none; border: none; cursor: pointer; }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-secondary { background: #f1f5f9; color: #334155; }
    </style>
</head>
<body>
    <main class="form-card">
        <div class="form-title">
            <i class="fa-solid fa-pen-to-square" style="font-size: 1.5rem; color: #0284c7;"></i>
            <h2>Edit Student: <?= htmlspecialchars($student['student_id']) ?></h2>
        </div>

        <?php if (!empty($errors['general'])): ?>
            <div style="background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 6px; margin-bottom: 15px;">
                <?= htmlspecialchars($errors['general']) ?>
            </div>
        <?php endif; ?>

        <form action="update.php" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$student['id'] ?>">

            <div class="form-grid">
                <div class="form-group">
                    <label>Student ID (Read-only)</label>
                    <input type="text" value="<?= htmlspecialchars($student['student_id']) ?>" disabled style="background: #f1f5f9; cursor: not-allowed;">
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
                    <input type="tel" id="mobile" name="mobile" required value="<?= htmlspecialchars($_POST['mobile'] ?? $student['mobile']) ?>">
                    <?php if (!empty($errors['mobile'])): ?><span class="error-hint"><?= $errors['mobile'] ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="course">Course *</label>
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
                    <label for="year">Year of Study *</label>
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
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender">
                        <?php foreach (['Male', 'Female', 'Other'] as $g): ?>
                            <option value="<?= $g ?>" <?= (($student['gender'] ?? '') === $g) ? 'selected' : '' ?>><?= $g ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="cgpa">CGPA</label>
                    <input type="number" id="cgpa" name="cgpa" step="0.01" min="0" max="10" value="<?= htmlspecialchars($_POST['cgpa'] ?? $student['cgpa']) ?>">
                </div>

                <div class="form-group full-width">
                    <label for="status">Account Status</label>
                    <select id="status" name="status">
                        <option value="active" <?= ($student['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($student['status'] === 'inactive') ? 'selected' : '' ?>>Inactive (Soft-Deleted)</option>
                    </select>
                </div>
            </div>

            <div class="btn-row">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Update Changes</button>
                <a href="list.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </main>
</body>
</html>
