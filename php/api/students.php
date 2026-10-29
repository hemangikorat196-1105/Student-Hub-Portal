<?php
// ============================================================================
// Student Hub Portal - RESTful Student API Endpoint
// Practical 13 (AJAX / Fetch-based Asynchronous CRUD without Page Reload)
// Methods Supported: GET, POST, PUT, DELETE
// Returns standard JSON response with proper HTTP Status Codes
// ============================================================================

require_once __DIR__ . '/../includes/api_bootstrap.php';
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With, X-CSRF-Token");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/response.php';

init_session();
require_admin();
$method = $_SERVER['REQUEST_METHOD'];
$db = get_db_connection();

if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
    require_once __DIR__ . '/../includes/csrf.php';
    if (!verify_csrf_token($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
        json_response(403, false, "Invalid or missing CSRF token.");
    }
}

// Helper to decode JSON request body for PUT/POST
function get_json_payload() {
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : $_POST;
}

switch ($method) {
    // ------------------------------------------------------------------------
    // GET: Retrieve list of students OR single student by ID
    // ------------------------------------------------------------------------
    case 'GET':
        if (isset($_GET['id']) && (int)$_GET['id'] > 0) {
            $id = (int)$_GET['id'];
            $stmt = $db->prepare("SELECT * FROM students WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $student = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$student) {
                json_response(404, false, "Student record not found");
            }
            json_response(200, true, "Student record retrieved", $student);
        }

        // List with Search & Filtering
        $search = trim($_GET['search'] ?? $_GET['q'] ?? '');
        $course = trim($_GET['course'] ?? '');
        $status = trim($_GET['status'] ?? 'all');
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = min(100, max(1, (int)($_GET['limit'] ?? 15)));
        $offset = ($page - 1) * $limit;

        $where = ["1=1"];
        $params = [];
        $types = "";

        if ($search !== '') {
            $where[] = "(name LIKE ? OR student_id LIKE ? OR email LIKE ? OR city LIKE ?)";
            $like = "%$search%";
            $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
            $types .= "ssss";
        }

        if ($course !== '') {
            $where[] = "course = ?";
            $params[] = $course;
            $types .= "s";
        }

        if ($status !== 'all' && in_array($status, ['active', 'inactive'])) {
            $where[] = "status = ?";
            $params[] = $status;
            $types .= "s";
        }

        $whereSql = implode(" AND ", $where);

        // Count total
        $stmtC = $db->prepare("SELECT COUNT(*) AS total FROM students WHERE $whereSql");
        if ($types !== "") {
            $stmtC->bind_param($types, ...$params);
        }
        $stmtC->execute();
        $totalRecords = (int)$stmtC->get_result()->fetch_assoc()['total'];
        $stmtC->close();

        // Data query
        $sql = "SELECT id, student_id, name, email, mobile, course, year, department, gender, cgpa, city, status, created_at
                FROM students
                WHERE $whereSql
                ORDER BY id ASC
                LIMIT ? OFFSET ?";
        $typesData = $types . "ii";
        $paramsData = array_merge($params, [$limit, $offset]);

        $stmtD = $db->prepare($sql);
        $stmtD->bind_param($typesData, ...$paramsData);
        $stmtD->execute();
        $students = $stmtD->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmtD->close();

        json_response(200, true, "Student records retrieved successfully", $students, [
            'pagination' => [
                'totalRecords' => $totalRecords,
                'page'         => $page,
                'limit'        => $limit,
                'totalPages'   => max(1, ceil($totalRecords / $limit))
            ]
        ]);
        break;

    // ------------------------------------------------------------------------
    // POST: Create a new student record
    // ------------------------------------------------------------------------
    case 'POST':
        $data = get_json_payload();

        $studentId = trim($data['student_id'] ?? $data['studentId'] ?? '');
        $name      = trim($data['name'] ?? $data['fullName'] ?? '');
        $email     = trim($data['email'] ?? '');
        $mobile    = trim($data['mobile'] ?? '');
        $course    = trim($data['course'] ?? '');
        $year      = (int)($data['year'] ?? 1);
        $dept      = trim($data['department'] ?? 'Computer Science & Engineering');
        $gender    = trim($data['gender'] ?? 'Other');
        $cgpa      = (float)($data['cgpa'] ?? 8.0);
        $status    = in_array($data['status'] ?? '', ['active', 'inactive']) ? $data['status'] : 'active';

        $errors = [];
        if (empty($studentId)) $errors['student_id'] = "Student ID is required.";
        if (!validate_full_name($name)) $errors['name'] = "Valid name required (2-50 characters).";
        if (!validate_email_format($email)) $errors['email'] = "Valid email address required.";
        if (!validate_indian_mobile($mobile)) $errors['mobile'] = "Valid 10-digit Indian mobile required.";
        if (empty($course)) $errors['course'] = "Course selection is required.";

        if (!empty($errors)) {
            json_response(422, false, "Validation errors encountered", ['errors' => $errors]);
        }

        // Duplicate Check
        $stmtChk = $db->prepare("SELECT id FROM students WHERE student_id = ? OR email = ? LIMIT 1");
        $stmtChk->bind_param("ss", $studentId, $email);
        $stmtChk->execute();
        if ($stmtChk->get_result()->num_rows > 0) {
            $stmtChk->close();
            json_response(409, false, "Student with this Student ID or Email already exists.");
        }
        $stmtChk->close();

        // Insert
        $stmtIns = $db->prepare("
            INSERT INTO students (student_id, name, email, mobile, course, year, department, gender, cgpa, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmtIns->bind_param("sssssissds", $studentId, $name, $email, $mobile, $course, $year, $dept, $gender, $cgpa, $status);
        $stmtIns->execute();
        $newId = $stmtIns->insert_id;
        $stmtIns->close();

        log_audit_event("API_CREATE_STUDENT", "Created student $studentId via API");

        json_response(201, true, "Student record created successfully", [
            'id'         => $newId,
            'student_id' => $studentId,
            'name'       => $name,
            'email'      => $email,
            'status'     => $status
        ]);
        break;

    // ------------------------------------------------------------------------
    // PUT: Update an existing student record
    // ------------------------------------------------------------------------
    case 'PUT':
        $data = get_json_payload();
        $id = (int)($data['id'] ?? $_GET['id'] ?? 0);

        if ($id <= 0) {
            json_response(400, false, "Missing or invalid student ID for update.");
        }

        $stmtEx = $db->prepare("SELECT * FROM students WHERE id = ? LIMIT 1");
        $stmtEx->bind_param("i", $id);
        $stmtEx->execute();
        $existing = $stmtEx->get_result()->fetch_assoc();
        $stmtEx->close();

        if (!$existing) {
            json_response(404, false, "Student record not found for update.");
        }

        $name   = trim($data['name'] ?? $existing['name']);
        $email  = trim($data['email'] ?? $existing['email']);
        $mobile = trim($data['mobile'] ?? $existing['mobile']);
        $course = trim($data['course'] ?? $existing['course']);
        $year   = (int)($data['year'] ?? $existing['year']);
        $gender = trim($data['gender'] ?? $existing['gender']);
        $cgpa   = (float)($data['cgpa'] ?? $existing['cgpa']);
        $status = in_array($data['status'] ?? '', ['active', 'inactive']) ? $data['status'] : $existing['status'];

        $stmtUp = $db->prepare("
            UPDATE students
            SET name = ?, email = ?, mobile = ?, course = ?, year = ?, gender = ?, cgpa = ?, status = ?
            WHERE id = ?
        ");
        $stmtUp->bind_param("ssssisdsi", $name, $email, $mobile, $course, $year, $gender, $cgpa, $status, $id);
        $stmtUp->execute();
        $stmtUp->close();

        log_audit_event("API_UPDATE_STUDENT", "Updated student ID $id via API");

        json_response(200, true, "Student record updated successfully", [
            'id'     => $id,
            'name'   => $name,
            'email'  => $email,
            'status' => $status
        ]);
        break;

    // ------------------------------------------------------------------------
    // DELETE: Delete or Soft-Delete Student
    // ------------------------------------------------------------------------
    case 'DELETE':
        $data = get_json_payload();
        $id = (int)($data['id'] ?? $_GET['id'] ?? 0);
        $permanent = (bool)($data['permanent'] ?? $_GET['permanent'] ?? false);

        if ($id <= 0) {
            json_response(400, false, "Invalid student ID specified.");
        }

        $stmtEx = $db->prepare("SELECT id, student_id, name FROM students WHERE id = ? LIMIT 1");
        $stmtEx->bind_param("i", $id);
        $stmtEx->execute();
        $existing = $stmtEx->get_result()->fetch_assoc();
        $stmtEx->close();

        if (!$existing) {
            json_response(404, false, "Student record not found.");
        }

        if ($permanent) {
            $stmtDel = $db->prepare("DELETE FROM students WHERE id = ?");
            $stmtDel->bind_param("i", $id);
            $stmtDel->execute();
            $stmtDel->close();
            log_audit_event("API_HARD_DELETE_STUDENT", "Hard-deleted student ID $id ({$existing['student_id']})");
            json_response(200, true, "Student permanently deleted");
        } else {
            $stmtDel = $db->prepare("UPDATE students SET status = 'inactive' WHERE id = ?");
            $stmtDel->bind_param("i", $id);
            $stmtDel->execute();
            $stmtDel->close();
            log_audit_event("API_SOFT_DELETE_STUDENT", "Soft-deleted student ID $id ({$existing['student_id']})");
            json_response(200, true, "Student soft-deleted successfully (status set to Inactive)");
        }
        break;

    default:
        json_response(405, false, "Method $method Not Allowed. Use GET, POST, PUT, or DELETE.");
        break;
}
