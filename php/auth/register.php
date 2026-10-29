<?php
// ============================================================================
// Student Hub Portal - Secure Student Registration
// Practicals 7, 9, 14, 15
// Features: Server-side validation, duplicate check, bcrypt hashing, audit log
// ============================================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/response.php';

init_session();

// Enforce admin privilege check - Public self-registration is completely disabled
if (!is_admin()) {
    http_response_code(403);
    if (is_api_request()) {
        json_response(403, false, "Public student registration is disabled. Student accounts can only be provisioned by an authenticated administrator.");
    }
    render_registration_result(false, "Public student self-registration has been disabled by institutional policy. Student accounts can only be provisioned by an authenticated administrator.");
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (is_api_request()) {
        json_response(405, false, "Method Not Allowed. Use POST.");
    }

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        render_registration_result(false, "The form expired or could not be verified. Reload the page and try again.");
    }
    header("Location: ../students/create.php");
    exit;
}

// 1. Gather & Sanitize Inputs
$fullName        = trim($_POST['fullName'] ?? '');
$email           = trim($_POST['email'] ?? '');
$mobile          = trim($_POST['mobile'] ?? '');
$password        = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirmPassword'] ?? '';
$course          = trim($_POST['course'] ?? '');
$yearRaw         = $_POST['year'] ?? '';
$gender          = trim($_POST['gender'] ?? '');
$terms           = isset($_POST['terms']);
$csrfToken       = $_POST['csrf_token'] ?? '';

$errors = [];

// 2. Validate Inputs
if (!validate_full_name($fullName)) {
    $errors['fullName'] = "Full name must be 2 to 50 characters (letters and spaces only).";
}

if (!validate_email_format($email)) {
    $errors['email'] = "A valid email address is required (e.g. student@example.com).";
}

if (!validate_indian_mobile($mobile)) {
    $errors['mobile'] = "Mobile number must be a valid 10-digit number starting with 6, 7, 8, or 9.";
}

if (!validate_password_strength($password)) {
    $errors['password'] = "Password must be at least 8 characters and include uppercase, lowercase, number, and special character.";
}

if ($password !== $confirmPassword) {
    $errors['confirmPassword'] = "Confirm password does not match password.";
}

if (!validate_course_choice($course)) {
    $errors['course'] = "Please select a valid academic course.";
}

if (!validate_year_choice($yearRaw)) {
    $errors['year'] = "Please select a valid year of study.";
}

if (!validate_gender_choice($gender)) {
    $errors['gender'] = "Please select a valid gender option.";
}

if (!$terms) {
    $errors['terms'] = "You must agree to the Terms and Conditions.";
}

// Return validation errors if any
if (!empty($errors)) {
    if (is_api_request()) {
        json_response(422, false, "Validation failed", ['errors' => $errors]);
    }
    render_registration_result(false, "Please correct the form errors.", $errors);
    exit;
}

$year = normalize_year_int($yearRaw);

try {
    $db = get_db_connection();

    // 3. Duplicate Checks (Email and Mobile in students / users)
    $stmtCheck = $db->prepare("SELECT id FROM users WHERE email = ? UNION SELECT id FROM students WHERE email = ? LIMIT 1");
    $stmtCheck->bind_param("ss", $email, $email);
    $stmtCheck->execute();
    $resCheck = $stmtCheck->get_result();
    if ($resCheck->num_rows > 0) {
        $stmtCheck->close();
        if (is_api_request()) {
            json_response(409, false, "Email address already registered. Please login instead.");
        }
        render_registration_result(false, "Email address '$email' is already registered. Please login.", ['email' => 'Duplicate email']);
        exit;
    }
    $stmtCheck->close();

    // Generate unique student ID (e.g., 25CS036 or based on count)
    $stmtCount = $db->query("SELECT MAX(id) AS max_id FROM students");
    $rowCount = $stmtCount->fetch_assoc();
    $nextIdNum = (int)($rowCount['max_id'] ?? 0) + 1;
    do {
        $generatedStudentId = sprintf("25CS%03d", $nextIdNum++);
        $stmtIdCheck = $db->prepare("SELECT id FROM users WHERE username = ? UNION SELECT id FROM students WHERE student_id = ? LIMIT 1");
        $stmtIdCheck->bind_param("ss", $generatedStudentId, $generatedStudentId);
        $stmtIdCheck->execute();
        $idInUse = $stmtIdCheck->get_result()->num_rows > 0;
        $stmtIdCheck->close();
    } while ($idInUse);

    // 4. Secure Password Hashing using BCRYPT
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    // 5. Begin Transaction to Insert User & Student Profile
    $db->begin_transaction();

    // Insert into users
    $stmtUser = $db->prepare("INSERT INTO users (username, email, password, role, status, created_at) VALUES (?, ?, ?, 'student', 'active', NOW())");
    $stmtUser->bind_param("sss", $generatedStudentId, $email, $hashedPassword);
    $stmtUser->execute();
    $newUserId = $stmtUser->insert_id;
    $stmtUser->close();

    // Insert into students
    $dept = "Computer Science & Engineering";
    if (strpos($course, 'Information Technology') !== false) {
        $dept = "Information Technology";
    } elseif (strpos($course, 'Mechanical') !== false) {
        $dept = "Mechanical Engineering";
    } elseif (strpos($course, 'Civil') !== false) {
        $dept = "Civil Engineering";
    }

    $stmtStudent = $db->prepare("
        INSERT INTO students (user_id, student_id, name, email, mobile, course, year, department, gender, cgpa, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 8.00, 'active', NOW())
    ");
    $stmtStudent->bind_param("isssssiss", $newUserId, $generatedStudentId, $fullName, $email, $mobile, $course, $year, $dept, $gender);
    $stmtStudent->execute();
    $newStudentId = $stmtStudent->insert_id;
    $stmtStudent->close();

    // 6. Practical 7 CSV Storage
    save_to_csv([
        $newStudentId,
        $generatedStudentId,
        $fullName,
        $email,
        $mobile,
        $course,
        $year,
        $gender,
        date('Y-m-d H:i:s')
    ]);

    $db->commit();

    // 7. Audit Log
    log_audit_event("REGISTER", "New student registered: $generatedStudentId ($fullName)", $newUserId);

    if (is_api_request()) {
        json_response(201, true, "Registration successful!", [
            'studentId' => $generatedStudentId,
            'name'      => $fullName,
            'email'     => $email
        ]);
    }

    render_registration_result(true, "Registration successful! Your Student ID is $generatedStudentId.", [
        'Student ID' => $generatedStudentId,
        'Name'       => $fullName,
        'Email'      => $email,
        'Mobile'     => $mobile,
        'Course'     => $course,
        'Year'       => "Year $year",
        'Gender'     => $gender
    ]);

} catch (Exception $e) {
    if (isset($db) && $db instanceof mysqli) {
        $db->rollback();
    }
    error_log("Registration Error: " . $e->getMessage());
    if (is_api_request()) {
        json_response(500, false, "Server error processing registration. Please try again later.");
    }
    render_registration_result(false, "An error occurred while processing registration. Please try again.");
}

/**
 * Appends record to CSV file for Practical 7 requirement.
 */
function save_to_csv($fields) {
    $paths = [
        __DIR__ . '/../../Student.csv',
        __DIR__ . '/../../data/students.csv'
    ];

    foreach ($paths as $csvPath) {
        $fp = fopen($csvPath, 'a+');
        if ($fp === false) {
            throw new RuntimeException('Unable to open the registration CSV storage file.');
        }
        $locked = false;
        try {
            if (!flock($fp, LOCK_EX)) {
                throw new RuntimeException('Unable to lock the registration CSV storage file.');
            }
            $locked = true;
            fseek($fp, 0, SEEK_END);
            if (ftell($fp) === 0 && fputcsv($fp, ['ID', 'StudentID', 'Name', 'Email', 'Mobile', 'Course', 'Year', 'Gender', 'RegisteredAt']) === false) {
                throw new RuntimeException('Unable to write the registration CSV header.');
            }
            if (fputcsv($fp, $fields) === false || !fflush($fp)) {
                throw new RuntimeException('Unable to write the registration CSV record.');
            }
        } finally {
            if ($locked) {
                flock($fp, LOCK_UN);
            }
            fclose($fp);
        }
    }
}

/**
 * Renders user-facing HTML response page for registration.
 */
function render_registration_result($success, $message, $details = []) {
    $title = $success ? "Registration Successful" : "Registration Failed";
    $statusClass = $success ? "success-box" : "error-box";
    $icon = $success ? "fa-circle-check" : "fa-circle-xmark";
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($title) ?> - Student Hub Portal</title>
        <link rel="stylesheet" href="../../Style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
        <style>
            .result-wrapper { max-width: 600px; margin: 40px auto; padding: 30px; background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); font-family: 'Segoe UI', Tahoma, sans-serif; }
            .success-box { border-left: 6px solid #16a34a; }
            .error-box { border-left: 6px solid #dc2626; }
            .result-icon { font-size: 3rem; margin-bottom: 15px; }
            .success-box .result-icon { color: #16a34a; }
            .error-box .result-icon { color: #dc2626; }
            .details-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            .details-table td { padding: 8px 12px; border-bottom: 1px solid #e2e8f0; }
            .details-table td:first-child { font-weight: 600; color: #475569; width: 35%; }
            .btn-row { display: flex; gap: 12px; margin-top: 25px; }
            .btn { display: inline-block; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; text-align: center; }
            .btn-primary { background: #2563eb; color: #fff; }
            .btn-secondary { background: #f1f5f9; color: #334155; }
        </style>
    </head>
    <body>
        <main class="result-wrapper <?= $statusClass ?>">
            <div class="result-icon"><i class="fa-solid <?= $icon ?>"></i></div>
            <h2><?= htmlspecialchars($title) ?></h2>
            <p><?= htmlspecialchars($message) ?></p>

            <?php if (!empty($details) && $success): ?>
                <table class="details-table">
                    <?php foreach ($details as $k => $v): ?>
                        <tr>
                            <td><?= htmlspecialchars($k) ?></td>
                            <td><?= htmlspecialchars($v) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>

            <div class="btn-row">
                <?php if ($success): ?>
                    <a href="../../Login.html" class="btn btn-primary"><i class="fa-solid fa-right-to-bracket"></i> Proceed to Login</a>
                    <a href="../../home.html" class="btn btn-secondary">Student Home</a>
                <?php else: ?>
                    <a href="../../Login.html" class="btn btn-primary"><i class="fa-solid fa-arrow-left"></i> Return to Login</a>
                <?php endif; ?>
            </div>
        </main>
    </body>
    </html>
    <?php
    exit;
}
