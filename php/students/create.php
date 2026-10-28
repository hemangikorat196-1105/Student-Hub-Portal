<?php
// ============================================================================
// Student Hub Portal - Create Student Record
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

$errors = [];
$successMsg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = trim($_POST['student_id'] ?? '');
    $name      = trim($_POST['name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $mobile    = trim($_POST['mobile'] ?? '');
    $course    = trim($_POST['course'] ?? '');
    $year      = (int)($_POST['year'] ?? 1);
    $dept      = trim($_POST['department'] ?? 'Computer Science & Engineering');
    $gender    = trim($_POST['gender'] ?? 'Other');
    $cgpa      = (float)($_POST['cgpa'] ?? 8.0);
    $status    = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

    if (empty($studentId) || !preg_match('/^[A-Za-z0-9]{4,15}$/', $studentId)) {
        $errors['student_id'] = "Valid Student ID required (4-15 alphanumeric characters).";
    }
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
            $db = get_db_connection();

            // Duplicate checks
            $stmtChk = $db->prepare("SELECT id FROM students WHERE student_id = ? OR email = ? LIMIT 1");
            $stmtChk->bind_param("ss", $studentId, $email);
            $stmtChk->execute();
            if ($stmtChk->get_result()->num_rows > 0) {
                $errors['general'] = "A student with this Student ID or Email already exists.";
            }
            $stmtChk->close();

            if (empty($errors)) {
                $stmt = $db->prepare("
                    INSERT INTO students (student_id, name, email, mobile, course, year, department, gender, cgpa, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->bind_param("sssssissds", $studentId, $name, $email, $mobile, $course, $year, $dept, $gender, $cgpa, $status);
                $stmt->execute();
                $newId = $stmt->insert_id;
                $stmt->close();

                log_audit_event("CREATE_STUDENT", "Created student $studentId ($name)");
                $_SESSION['flash_success'] = "Student $studentId ($name) created successfully!";

                if (is_api_request()) {
                    json_response(201, true, "Student created successfully", ['id' => $newId, 'student_id' => $studentId]);
                }

                header("Location: list.php");
                exit;
            }
        } catch (Exception $e) {
            error_log("Student Create Error: " . $e->getMessage());
            $errors['general'] = "Database error while saving student.";
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
    <title>Add Student - Student Hub Portal</title>
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
        .alert-error { background: #fee2e2; color: #991b1b; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; }
        .btn-row { display: flex; gap: 12px; margin-top: 25px; }
        .btn { padding: 10px 20px; border-radius: 6px; font-weight: 600; text-decoration: none; border: none; cursor: pointer; }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-secondary { background: #f1f5f9; color: #334155; }
    </style>
</head>
<body>
    <main class="form-card">
        <div class="form-title">
            <i class="fa-solid fa-user-plus" style="font-size: 1.5rem; color: #2563eb;"></i>
            <h2>Add New Student Record</h2>
        </div>

        <?php if (!empty($errors['general'])): ?>
            <div class="alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($errors['general']) ?></div>
        <?php endif; ?>

        <form action="create.php" method="POST">
            <?= csrf_field() ?>
            <div class="form-grid">
                <div class="form-group">
                    <label for="student_id">Student ID *</label>
                    <input type="text" id="student_id" name="student_id" required placeholder="e.g. 25CS036" value="<?= htmlspecialchars($_POST['student_id'] ?? '') ?>">
                    <?php if (!empty($errors['student_id'])): ?><span class="error-hint"><?= $errors['student_id'] ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="name">Full Name *</label>
                    <input type="text" id="name" name="name" required placeholder="e.g. Rahul Patel" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                    <?php if (!empty($errors['name'])): ?><span class="error-hint"><?= $errors['name'] ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" required placeholder="rahul@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    <?php if (!empty($errors['email'])): ?><span class="error-hint"><?= $errors['email'] ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="mobile">Mobile Number *</label>
                    <input type="tel" id="mobile" name="mobile" required placeholder="9876543210" value="<?= htmlspecialchars($_POST['mobile'] ?? '') ?>">
                    <?php if (!empty($errors['mobile'])): ?><span class="error-hint"><?= $errors['mobile'] ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="course">Course / Program *</label>
                    <select id="course" name="course" required>
                        <option value="">-- Select Course --</option>
                        <option value="B.Tech Computer Science">B.Tech Computer Science</option>
                        <option value="B.Tech Information Technology">B.Tech Information Technology</option>
                        <option value="B.Tech Artificial Intelligence">B.Tech Artificial Intelligence</option>
                        <option value="B.Tech Mechanical Engineering">B.Tech Mechanical Engineering</option>
                        <option value="B.Tech Civil Engineering">B.Tech Civil Engineering</option>
                        <option value="B.Tech Electronics">B.Tech Electronics</option>
                        <option value="BCA">BCA</option>
                        <option value="MCA">MCA</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="year">Year of Study *</label>
                    <select id="year" name="year" required>
                        <option value="1">1st Year</option>
                        <option value="2" selected>2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="cgpa">CGPA (0.00 - 10.00)</label>
                    <input type="number" id="cgpa" name="cgpa" step="0.01" min="0" max="10" value="8.50">
                </div>

                <div class="form-group full-width">
                    <label for="status">Initial Status</label>
                    <select id="status" name="status">
                        <option value="active" selected>Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="btn-row">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Save Student</button>
                <a href="list.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Cancel</a>
            </div>
        </form>
    </main>
</body>
</html>
