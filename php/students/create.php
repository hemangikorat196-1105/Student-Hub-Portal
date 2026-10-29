<?php
// ============================================================================
// Student Hub Portal - Create Student Record (Admin Only)
// Features: Dual-table sync (students & users), auto/manual temporary password,
// Bcrypt hashing, must_change_password enforcement, audit logging.
// ============================================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/csrf.php';

init_session();
require_admin();

$errors = [];
$successMsg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        $errors['general'] = "The form session expired or could not be verified. Please reload and try again.";
    }

    $studentId     = strtoupper(trim($_POST['student_id'] ?? ''));
    $name          = trim($_POST['name'] ?? '');
    $email         = strtolower(trim($_POST['email'] ?? ''));
    $mobile        = trim($_POST['mobile'] ?? '');
    $course        = trim($_POST['course'] ?? '');
    $department    = trim($_POST['department'] ?? 'Computer Science & Engineering');
    $semester      = max(1, min(8, (int)($_POST['semester'] ?? 1)));
    $year          = max(1, min(4, (int)($_POST['year'] ?? 1)));
    $admissionDate = trim($_POST['admission_date'] ?? date('Y-m-d'));
    $gender        = in_array($_POST['gender'] ?? '', ['Male', 'Female', 'Other']) ? $_POST['gender'] : 'Other';
    $cgpa          = max(0.0, min(10.0, (float)($_POST['cgpa'] ?? 8.0)));
    $status        = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';
    
    $autoGeneratePass = isset($_POST['auto_generate_password']) && $_POST['auto_generate_password'] === '1';
    $rawPassword      = trim($_POST['temp_password'] ?? '');

    // Validation
    if (empty($studentId) || !preg_match('/^[A-Za-z0-9]{4,15}$/', $studentId)) {
        $errors['student_id'] = "Valid Student ID / Enrollment Number required (4-15 alphanumeric characters).";
    }
    if (!validate_full_name($name)) {
        $errors['name'] = "Full name must be 2 to 50 characters (letters and spaces only).";
    }
    if (!validate_email_format($email)) {
        $errors['email'] = "A valid institutional or personal email address is required.";
    }
    if (!validate_indian_mobile($mobile)) {
        $errors['mobile'] = "Mobile number must be a valid 10-digit number starting with 6, 7, 8, or 9.";
    }
    if (empty($course)) {
        $errors['course'] = "Please select an academic course/program.";
    }
    if (empty($admissionDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $admissionDate)) {
        $admissionDate = date('Y-m-d');
    }

    // Determine Temporary Password
    $temporaryPassword = "";
    if ($autoGeneratePass || empty($rawPassword)) {
        // Generate a cryptographically secure 10-character temporary password
        $randomChars = bin2hex(random_bytes(3));
        $temporaryPassword = "Temp@" . strtoupper(substr($randomChars, 0, 3)) . substr($randomChars, 3) . "#9";
    } else {
        if (strlen($rawPassword) < 8) {
            $errors['temp_password'] = "Temporary password must be at least 8 characters.";
        } else {
            $temporaryPassword = $rawPassword;
        }
    }

    if (empty($errors)) {
        $db = get_db_connection();

        try {
            // Check uniqueness in both students and users tables
            $stmtChkStudent = $db->prepare("SELECT id FROM students WHERE student_id = ? OR email = ? LIMIT 1");
            $stmtChkStudent->bind_param("ss", $studentId, $email);
            $stmtChkStudent->execute();
            if ($stmtChkStudent->get_result()->num_rows > 0) {
                $errors['general'] = "A student with Enrollment ID '$studentId' or Email '$email' already exists in academic records.";
            }
            $stmtChkStudent->close();

            $stmtChkUser = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
            $stmtChkUser->bind_param("ss", $studentId, $email);
            $stmtChkUser->execute();
            if ($stmtChkUser->get_result()->num_rows > 0) {
                $errors['general'] = "A user login account with Username '$studentId' or Email '$email' already exists.";
            }
            $stmtChkUser->close();

            if (empty($errors)) {
                $db->begin_transaction();

                // 1. Create Login Account in users table
                $passwordHash = password_hash($temporaryPassword, PASSWORD_BCRYPT);
                $adminUserId  = (int)($_SESSION['user_id'] ?? 1);
                $mustChangePass = 1; // Enforce mandatory change on first login

                $stmtUser = $db->prepare("
                    INSERT INTO users (username, email, password, role, status, must_change_password, created_by, created_at)
                    VALUES (?, ?, ?, 'student', ?, ?, ?, NOW())
                ");
                $stmtUser->bind_param("ssssii", $studentId, $email, $passwordHash, $status, $mustChangePass, $adminUserId);
                $stmtUser->execute();
                $newUserId = $stmtUser->insert_id;
                $stmtUser->close();

                // 2. Create Student Profile in students table
                $stmtStudent = $db->prepare("
                    INSERT INTO students (user_id, student_id, name, email, mobile, course, semester, year, department, admission_date, gender, cgpa, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmtStudent->bind_param(
                    "isssssiisssds",
                    $newUserId, $studentId, $name, $email, $mobile, $course,
                    $semester, $year, $department, $admissionDate, $gender, $cgpa, $status
                );
                $stmtStudent->execute();
                $newStudentId = $stmtStudent->insert_id;
                $stmtStudent->close();

                $db->commit();

                // 3. Log Audit Trail
                log_audit_event("CREATE_STUDENT", "Administrator #$adminUserId created student account $studentId ($name) with temporary password.", $adminUserId);

                // 4. Flash One-Time Temporary Password for Admin
                $_SESSION['flash_created_student'] = [
                    'student_id'    => $studentId,
                    'name'          => $name,
                    'temp_password' => $temporaryPassword,
                    'email'         => $email
                ];

                if (is_api_request()) {
                    json_response(201, true, "Student account created successfully", [
                        'id'            => $newStudentId,
                        'student_id'    => $studentId,
                        'temp_password' => $temporaryPassword
                    ]);
                }

                header("Location: list.php");
                exit;
            }
        } catch (Exception $e) {
            $db->rollback();
            error_log("Student Create Exception: " . $e->getMessage());
            $errors['general'] = "Database transaction failed while creating student record: " . $e->getMessage();
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
    <title>Add Student Account - Student Hub Portal</title>
    <link rel="stylesheet" href="../../Style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="../../script.js" defer></script>
    <style>
        .form-card {
            max-width: 820px;
            margin: 35px auto 60px;
            padding: 35px;
            background: var(--card-bg, #ffffff);
            border-radius: 14px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid var(--border-color, #e2e8f0);
            font-family: 'Segoe UI', Tahoma, sans-serif;
        }
        .form-header-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
            padding-bottom: 18px;
            border-bottom: 1px solid var(--border-color, #e2e8f0);
        }
        .form-title {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0;
            color: var(--text-color, #0f172a);
            font-size: 1.4rem;
        }
        .form-title i { color: var(--primary-color, #2563eb); }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        @media (max-width: 680px) {
            .form-grid { grid-template-columns: 1fr; }
            .form-card { padding: 20px; margin: 20px 10px; }
        }
        .form-group { display: flex; flex-direction: column; }
        .form-group.full-width { grid-column: 1 / -1; }
        .form-group label {
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--text-color, #334155);
            margin-bottom: 6px;
        }
        .form-group input, .form-group select {
            padding: 10px 14px;
            border: 1px solid var(--border-color, #cbd5e1);
            border-radius: 8px;
            font-size: 0.95rem;
            background: var(--input-bg, #ffffff);
            color: var(--text-color, #0f172a);
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: var(--primary-color, #2563eb);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }
        .required-star { color: #dc2626; font-weight: bold; }
        .error-hint { color: #dc2626; font-size: 0.8rem; margin-top: 5px; font-weight: 500; }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 22px;
            border-left: 4px solid #dc2626;
            font-size: 0.9rem;
        }
        .info-callout {
            background: var(--primary-light, #eff6ff);
            border: 1px solid var(--border-color, #bfdbfe);
            border-radius: 8px;
            padding: 14px;
            font-size: 0.88rem;
            color: var(--text-color, #1e293b);
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        .info-callout i { color: var(--primary-color, #2563eb); font-size: 1.2rem; margin-top: 2px; }
        .btn-row {
            display: flex;
            gap: 12px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color, #e2e8f0);
        }
        .btn {
            padding: 11px 22px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
            transition: all 0.15s ease;
        }
        .btn-primary { background: var(--primary-color, #2563eb); color: #ffffff; }
        .btn-primary:hover { opacity: 0.92; }
        .btn-secondary { background: #f1f5f9; color: #334155; }
        .btn-secondary:hover { background: #e2e8f0; }
        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
            color: var(--text-color, #334155);
            cursor: pointer;
            user-select: none;
            margin-top: 8px;
        }
        .checkbox-container input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary-color, #2563eb);
            cursor: pointer;
        }
    </style>
</head>
<body>
    <a href="#main-content" class="skip-link">Skip to main content</a>
    <header class="main-header">
        <div class="header-left">
            <div class="portal-title">
                <h1>STUDENT HUB PORTAL</h1>
                <p>Digital Academic & Campus Management System</p>
            </div>
        </div>
        <button id="themeToggle" class="theme-btn" type="button" aria-label="Change Theme">
            <i class="fa-solid fa-moon"></i>
            <span>Dark Mode</span>
        </button>
    </header>

    <main id="main-content" class="form-card">
        <div class="form-header-bar">
            <div>
                <h2 class="form-title"><i class="fa-solid fa-user-plus"></i> Add New Student Account</h2>
                <p style="margin:4px 0 0; color:var(--text-muted); font-size:0.88rem;">Provision verified academic credentials and temporary login credentials</p>
            </div>
            <a href="list.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back to Student List</a>
        </div>

        <div class="info-callout">
            <i class="fa-solid fa-shield-halved"></i>
            <div>
                <strong>Security Policy:</strong> Student self-registration is closed. When you create an account, a temporary password is generated. The temporary password will be displayed <strong>only once</strong> upon creation. The student will be required to change it on their first login.
            </div>
        </div>

        <?php if (!empty($errors['general'])): ?>
            <div class="alert-error" role="alert"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($errors['general']) ?></div>
        <?php endif; ?>

        <form action="create.php" method="POST" id="createStudentForm" novalidate>
            <?= csrf_field() ?>
            <div class="form-grid">
                <!-- Student ID / Enrollment Number -->
                <div class="form-group">
                    <label for="student_id">Student ID / Enrollment Number <span class="required-star">*</span></label>
                    <input type="text" id="student_id" name="student_id" required maxlength="15"
                           placeholder="e.g. 25CS045" value="<?= htmlspecialchars($_POST['student_id'] ?? '') ?>">
                    <?php if (!empty($errors['student_id'])): ?><span class="error-hint"><?= $errors['student_id'] ?></span><?php endif; ?>
                </div>

                <!-- Full Name -->
                <div class="form-group">
                    <label for="name">Full Name <span class="required-star">*</span></label>
                    <input type="text" id="name" name="name" required maxlength="50"
                           placeholder="e.g. Aryan Patel" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                    <?php if (!empty($errors['name'])): ?><span class="error-hint"><?= $errors['name'] ?></span><?php endif; ?>
                </div>

                <!-- Email Address -->
                <div class="form-group">
                    <label for="email">Email Address <span class="required-star">*</span></label>
                    <input type="email" id="email" name="email" required maxlength="120"
                           placeholder="e.g. aryan.patel@studenthub.edu" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    <?php if (!empty($errors['email'])): ?><span class="error-hint"><?= $errors['email'] ?></span><?php endif; ?>
                </div>

                <!-- Mobile Number -->
                <div class="form-group">
                    <label for="mobile">Mobile Number <span class="required-star">*</span></label>
                    <input type="tel" id="mobile" name="mobile" required maxlength="10"
                           placeholder="e.g. 9876543210" value="<?= htmlspecialchars($_POST['mobile'] ?? '') ?>">
                    <?php if (!empty($errors['mobile'])): ?><span class="error-hint"><?= $errors['mobile'] ?></span><?php endif; ?>
                </div>

                <!-- Course / Department -->
                <div class="form-group">
                    <label for="course">Course / Degree Program <span class="required-star">*</span></label>
                    <select id="course" name="course" required>
                        <option value="">-- Select Degree Course --</option>
                        <option value="B.Tech Computer Science">B.Tech Computer Science</option>
                        <option value="B.Tech Information Technology">B.Tech Information Technology</option>
                        <option value="B.Tech Artificial Intelligence">B.Tech Artificial Intelligence</option>
                        <option value="B.Tech Electronics">B.Tech Electronics</option>
                        <option value="B.Tech Mechanical Engineering">B.Tech Mechanical Engineering</option>
                        <option value="B.Tech Civil Engineering">B.Tech Civil Engineering</option>
                        <option value="BCA">Bachelor of Computer Applications (BCA)</option>
                        <option value="MCA">Master of Computer Applications (MCA)</option>
                    </select>
                    <?php if (!empty($errors['course'])): ?><span class="error-hint"><?= $errors['course'] ?></span><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="department">Department <span class="required-star">*</span></label>
                    <select id="department" name="department" required>
                        <option value="Computer Science & Engineering">Computer Science & Engineering</option>
                        <option value="Information Technology">Information Technology</option>
                        <option value="Artificial Intelligence & Data Science">Artificial Intelligence & Data Science</option>
                        <option value="Electronics & Communication">Electronics & Communication</option>
                        <option value="Mechanical Engineering">Mechanical Engineering</option>
                        <option value="Civil Engineering">Civil Engineering</option>
                    </select>
                </div>

                <!-- Semester & Year -->
                <div class="form-group">
                    <label for="semester">Semester (1 to 8) <span class="required-star">*</span></label>
                    <select id="semester" name="semester" required>
                        <?php for ($s = 1; $s <= 8; $s++): ?>
                            <option value="<?= $s ?>" <?= ($s == 1) ? 'selected' : '' ?>>Semester <?= $s ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="year">Academic Year <span class="required-star">*</span></label>
                    <select id="year" name="year" required>
                        <option value="1">Year 1 (Freshman)</option>
                        <option value="2">Year 2 (Sophomore)</option>
                        <option value="3">Year 3 (Junior)</option>
                        <option value="4">Year 4 (Senior)</option>
                    </select>
                </div>

                <!-- Admission Date -->
                <div class="form-group">
                    <label for="admission_date">Admission / Joining Date <span class="required-star">*</span></label>
                    <input type="date" id="admission_date" name="admission_date" required value="<?= htmlspecialchars($_POST['admission_date'] ?? date('Y-m-d')) ?>">
                </div>

                <!-- Initial Account Status -->
                <div class="form-group">
                    <label for="status">Account Status <span class="required-star">*</span></label>
                    <select id="status" name="status" required>
                        <option value="active" selected>Active (Permitted Login)</option>
                        <option value="inactive">Inactive (Deactivated)</option>
                    </select>
                </div>

                <!-- Gender -->
                <div class="form-group">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <!-- CGPA -->
                <div class="form-group">
                    <label for="cgpa">Current CGPA (0.00 – 10.00)</label>
                    <input type="number" id="cgpa" name="cgpa" step="0.01" min="0" max="10" value="8.00">
                </div>

                <!-- Default Temporary Password -->
                <div class="form-group full-width" style="background:var(--primary-light, #f8fafc); padding:16px; border-radius:8px; border:1px solid var(--border-color, #e2e8f0);">
                    <label for="temp_password">Default Temporary Password <span class="required-star">*</span></label>
                    <input type="text" id="temp_password" name="temp_password" placeholder="Leave empty for auto-generated secure password" value="<?= htmlspecialchars($_POST['temp_password'] ?? '') ?>">
                    <?php if (!empty($errors['temp_password'])): ?><span class="error-hint"><?= $errors['temp_password'] ?></span><?php endif; ?>
                    
                    <label class="checkbox-container">
                        <input type="checkbox" id="auto_generate_password" name="auto_generate_password" value="1" checked onchange="togglePasswordField()">
                        <span>Auto-generate a cryptographically secure temporary default password</span>
                    </label>
                    <small style="display:block; margin-top:6px; color:var(--text-muted); font-size:0.82rem;">
                        <i class="fa-solid fa-lock"></i> The password will be hashed using Bcrypt before database storage. Plaintext is shown to the admin <strong>only once</strong> upon submission.
                    </small>
                </div>
            </div>

            <div class="btn-row">
                <button type="submit" class="btn btn-primary" id="btnSubmitStudent">
                    <i class="fa-solid fa-check"></i> Create Student Account
                </button>
                <a href="list.php" class="btn btn-secondary">
                    <i class="fa-solid fa-xmark"></i> Cancel
                </a>
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

    <script>
        function togglePasswordField() {
            const chk = document.getElementById('auto_generate_password');
            const passInput = document.getElementById('temp_password');
            if (chk.checked) {
                passInput.disabled = true;
                passInput.placeholder = "Auto-generated secure password will be created";
                passInput.value = "";
            } else {
                passInput.disabled = false;
                passInput.placeholder = "Enter custom temporary password (min 8 chars)";
            }
        }
        document.addEventListener('DOMContentLoaded', togglePasswordField);
    </script>
</body>
</html>
