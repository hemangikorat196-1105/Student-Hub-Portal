<?php
// ============================================================================
// Student Hub Portal - Form Processing Engine
// Practical 7 (PHP Form Processing, Server-Side Validation & CSV Storage)
// Practical 9 (Database Insertion & Duplicate Verification)
// ============================================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/response.php';

init_session();

// Check if this was a login attempt sent to Process.php
if (isset($_POST['studentId']) && isset($_POST['password']) && !isset($_POST['fullName'])) {
    require_once __DIR__ . '/../auth/login.php';
    exit;
}

// Check if this is a registration attempt
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../Register.html");
    exit;
}

$fullName        = trim($_POST['fullName'] ?? '');
$email           = trim($_POST['email'] ?? '');
$mobile          = trim($_POST['mobile'] ?? '');
$password        = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirmPassword'] ?? '';
$course          = trim($_POST['course'] ?? '');
$yearRaw         = $_POST['year'] ?? '';
$gender          = trim($_POST['gender'] ?? '');
$terms           = isset($_POST['terms']);

$errors = [];

// 1. Validation according to Practical 7 & Practical 5 specifications
if (empty($fullName)) {
    $errors['fullName'] = "Full name is required.";
} elseif (!validate_full_name($fullName)) {
    $errors['fullName'] = "Full name must contain 2 to 50 characters (letters and spaces only).";
}

if (empty($email)) {
    $errors['email'] = "Email address is required.";
} elseif (!validate_email_format($email)) {
    $errors['email'] = "Please provide a valid email address.";
}

if (empty($mobile)) {
    $errors['mobile'] = "Mobile number is required.";
} elseif (!validate_indian_mobile($mobile)) {
    $errors['mobile'] = "Mobile number must be a valid 10-digit number starting with 6, 7, 8, or 9.";
}

if (empty($password)) {
    $errors['password'] = "Password is required.";
} elseif (!validate_password_strength($password)) {
    $errors['password'] = "Password must be at least 8 characters and include uppercase, lowercase, number, and special character.";
}

if ($password !== $confirmPassword) {
    $errors['confirmPassword'] = "Confirm password does not match password.";
}

if (empty($course) || !validate_course_choice($course)) {
    $errors['course'] = "Please select a valid academic course.";
}

if (empty($yearRaw) || !validate_year_choice($yearRaw)) {
    $errors['year'] = "Please select your year of study.";
}

if (empty($gender) || !validate_gender_choice($gender)) {
    $errors['gender'] = "Please select a valid gender option.";
}

if (!$terms) {
    $errors['terms'] = "You must accept the Terms and Conditions.";
}

// 2. If errors exist, render clean error view
if (!empty($errors)) {
    render_form_result(false, "Registration Failed: All required fields must be valid.", $errors);
    exit;
}

$year = normalize_year_int($yearRaw);

try {
    $db = get_db_connection();

    // Check duplicate email
    $stmtChk = $db->prepare("SELECT id FROM users WHERE email = ? UNION SELECT id FROM students WHERE email = ? LIMIT 1");
    $stmtChk->bind_param("ss", $email, $email);
    $stmtChk->execute();
    if ($stmtChk->get_result()->num_rows > 0) {
        $stmtChk->close();
        render_form_result(false, "Registration Failed: Email address '$email' already exists in the system.", ['email' => 'Duplicate email detected']);
        exit;
    }
    $stmtChk->close();

    // Generate unique student ID
    $maxRes = $db->query("SELECT MAX(id) AS m FROM students");
    $maxId = (int)($maxRes->fetch_assoc()['m'] ?? 15) + 1;
    $studentId = sprintf("25CS%03d", $maxId);

    // Hash password with bcrypt
    $hash = password_hash($password, PASSWORD_BCRYPT);

    $db->begin_transaction();

    // Insert user
    $stmtU = $db->prepare("INSERT INTO users (username, email, password, role, status, created_at) VALUES (?, ?, ?, 'student', 'active', NOW())");
    $stmtU->bind_param("sss", $studentId, $email, $hash);
    $stmtU->execute();
    $userId = $stmtU->insert_id;
    $stmtU->close();

    // Insert student
    $stmtS = $db->prepare("
        INSERT INTO students (user_id, student_id, name, email, mobile, course, year, department, gender, cgpa, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'Computer Science & Engineering', ?, 8.00, 'active', NOW())
    ");
    $stmtS->bind_param("isssssis", $userId, $studentId, $fullName, $email, $mobile, $course, $year, $gender);
    $stmtS->execute();
    $newStudentId = $stmtS->insert_id;
    $stmtS->close();

    $db->commit();

    // 3. Store valid registration data in CSV file (Practical 7 requirement: Student.csv)
    $csvPaths = [
        __DIR__ . '/../../Student.csv',
        __DIR__ . '/../../data/students.csv'
    ];

    foreach ($csvPaths as $p) {
        $isNew = !file_exists($p) || filesize($p) === 0;
        $fp = fopen($p, 'a');
        if ($fp) {
            if ($isNew) {
                fputcsv($fp, ['ID', 'StudentID', 'Name', 'Email', 'Mobile', 'Course', 'Year', 'Gender', 'RegisteredAt']);
            }
            fputcsv($fp, [$newStudentId, $studentId, $fullName, $email, $mobile, $course, $year, $gender, date('Y-m-d H:i:s')]);
            fclose($fp);
        }
    }

    log_audit_event("REGISTER", "Student $studentId ($fullName) registered via Process.php", $userId);

    render_form_result(true, "Registration Completed Successfully!", [
        'Student ID'   => $studentId,
        'Full Name'    => $fullName,
        'Email'        => $email,
        'Mobile'       => $mobile,
        'Course'       => $course,
        'Year'         => "Year $year",
        'Gender'       => $gender,
        'Storage File' => 'Student.csv & MySQL Database (student_portal)'
    ]);

} catch (Exception $e) {
    if (isset($db)) $db->rollback();
    error_log("Process.php Error: " . $e->getMessage());
    render_form_result(false, "System error occurred while storing registration data.");
}

function render_form_result($success, $message, $details = []) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= $success ? 'Registration Success' : 'Form Validation Errors' ?> - Student Hub</title>
        <link rel="stylesheet" href="../../Style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
        <style>
            .result-card { max-width: 640px; margin: 40px auto; padding: 30px; background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); font-family: 'Segoe UI', Tahoma, sans-serif; }
            .success-card { border-top: 6px solid #16a34a; }
            .error-card { border-top: 6px solid #dc2626; }
            .icon-heading { display: flex; align-items: center; gap: 12px; margin-bottom: 15px; }
            .success-card .icon-heading { color: #16a34a; }
            .error-card .icon-heading { color: #dc2626; }
            .result-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            .result-table td { padding: 8px 12px; border-bottom: 1px solid #f1f5f9; }
            .result-table td:first-child { font-weight: 600; color: #475569; width: 35%; }
            .error-list { list-style: disc; margin-left: 20px; color: #991b1b; }
            .btn-row { display: flex; gap: 12px; margin-top: 25px; }
            .btn { padding: 10px 20px; border-radius: 6px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
            .btn-primary { background: #2563eb; color: #fff; }
            .btn-secondary { background: #f1f5f9; color: #334155; }
        </style>
    </head>
    <body>
        <main class="result-card <?= $success ? 'success-card' : 'error-card' ?>">
            <div class="icon-heading">
                <i class="fa-solid <?= $success ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>" style="font-size: 2rem;"></i>
                <h2><?= htmlspecialchars($message) ?></h2>
            </div>

            <?php if ($success): ?>
                <p>Your registration record has been validated, sanitized, and stored securely in CSV format and MySQL database.</p>
                <table class="result-table">
                    <?php foreach ($details as $k => $v): ?>
                        <tr>
                            <td><?= htmlspecialchars($k) ?></td>
                            <td><strong><?= htmlspecialchars($v) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <div class="btn-row">
                    <a href="../../Login.html" class="btn btn-primary"><i class="fa-solid fa-right-to-bracket"></i> Login Now</a>
                    <a href="../../index.html" class="btn btn-secondary"><i class="fa-solid fa-house"></i> Home</a>
                </div>
            <?php else: ?>
                <p>The following validation errors occurred during form processing:</p>
                <ul class="error-list">
                    <?php foreach ($details as $field => $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
                <div class="btn-row">
                    <a href="../../Register.html" class="btn btn-primary"><i class="fa-solid fa-arrow-left"></i> Return to Registration</a>
                </div>
            <?php endif; ?>
        </main>
    </body>
    </html>
    <?php
    exit;
}
