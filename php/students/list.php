<?php
// ============================================================================
// Student Hub Portal - Student Management: Search, Sort, Filter & Paginate
// Features: Dynamic search (ID, name, email, mobile), multi-facet filters,
// one-time temporary password flash banner, view details modal, password reset modal,
// activation/deactivation toggle, soft/hard deletion confirmation, CSV export.
// ============================================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/csrf.php';

init_session();
require_admin();

$db = get_db_connection();

// 1. Search and Filter Parameters
$search   = trim($_GET['search'] ?? '');
$course   = trim($_GET['course'] ?? '');
$semester = isset($_GET['semester']) && $_GET['semester'] !== '' ? (int)$_GET['semester'] : null;
$year     = isset($_GET['year']) && $_GET['year'] !== '' ? (int)$_GET['year'] : null;
$status   = trim($_GET['status'] ?? 'all');

// Sorting
$allowedSortCols = [
    'student_id' => 'student_id',
    'name'       => 'name',
    'course'     => 'course',
    'semester'   => 'semester',
    'year'       => 'year',
    'status'     => 'status',
    'created_at' => 'created_at'
];
$sort  = $allowedSortCols[$_GET['sort'] ?? ''] ?? 'student_id';
$order = strtoupper($_GET['order'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

// Pagination
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 10;
$offset = ($page - 1) * $limit;

// 2. Dynamic Query Construction
$whereClauses = ["1=1"];
$params = [];
$types = "";

if ($search !== '') {
    $whereClauses[] = "(student_id LIKE ? OR name LIKE ? OR email LIKE ? OR mobile LIKE ?)";
    $like = "%$search%";
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= "ssss";
}

if ($course !== '') {
    $whereClauses[] = "course = ?";
    $params[] = $course;
    $types .= "s";
}

if ($semester !== null && $semester > 0) {
    $whereClauses[] = "semester = ?";
    $params[] = $semester;
    $types .= "i";
}

if ($year !== null && $year > 0) {
    $whereClauses[] = "year = ?";
    $params[] = $year;
    $types .= "i";
}

if ($status !== 'all' && in_array($status, ['active', 'inactive'])) {
    $whereClauses[] = "status = ?";
    $params[] = $status;
    $types .= "s";
}

$whereSql = implode(" AND ", $whereClauses);

// Count Total Filtered Records
$countSql = "SELECT COUNT(*) AS total FROM students WHERE $whereSql";
$stmtCount = $db->prepare($countSql);
if ($types !== "") {
    $stmtCount->bind_param($types, ...$params);
}
$stmtCount->execute();
$totalRecords = (int)$stmtCount->get_result()->fetch_assoc()['total'];
$stmtCount->close();

$totalPages = max(1, ceil($totalRecords / $limit));
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $limit;
}

// Fetch Paginated Records
$dataSql = "
    SELECT s.id, s.user_id, s.student_id, s.name, s.email, s.mobile, s.course,
           s.semester, s.year, s.department, s.admission_date, s.gender, s.cgpa, s.status, s.created_at,
           u.last_login, u.must_change_password
    FROM students s
    LEFT JOIN users u ON s.user_id = u.id
    WHERE $whereSql
    ORDER BY s.$sort $order
    LIMIT ? OFFSET ?
";
$typesData = $types . "ii";
$paramsData = array_merge($params, [$limit, $offset]);

$stmtData = $db->prepare($dataSql);
$stmtData->bind_param($typesData, ...$paramsData);
$stmtData->execute();
$students = $stmtData->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtData->close();

// Distinct Courses for Dropdown Filter
$courseListRes = $db->query("SELECT DISTINCT course FROM students WHERE course != '' ORDER BY course ASC");
$allCourses = [];
while ($c = $courseListRes->fetch_assoc()) {
    $allCourses[] = $c['course'];
}

// Check for one-time temporary password flash
$flashCreated = $_SESSION['flash_created_student'] ?? null;
unset($_SESSION['flash_created_student']);

$flashReset = $_SESSION['flash_temp_password'] ?? null;
unset($_SESSION['flash_temp_password']);

// Build CSV URL keeping active query parameters
$csvParams = $_GET;
unset($csvParams['page']);
$csvQuery = http_build_query($csvParams);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - Student Hub Portal</title>
    <link rel="stylesheet" href="../../Style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="../../script.js" defer></script>
    <style>
        .crud-container {
            max-width: 1350px;
            margin: 30px auto 60px;
            padding: 25px 30px;
            background: var(--card-bg, #ffffff);
            border-radius: 14px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            border: 1px solid var(--border-color, #e2e8f0);
            font-family: 'Segoe UI', Tahoma, sans-serif;
        }
        .crud-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color, #e2e8f0);
        }
        .crud-header h1 {
            font-size: 1.6rem;
            margin: 0;
            color: var(--text-color, #0f172a);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .crud-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* Flash Temporary Password Banner */
        .temp-pass-banner {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border: 2px solid #3b82f6;
            border-radius: 12px;
            padding: 20px 24px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.15);
        }
        .temp-pass-header {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.15rem;
            font-weight: 700;
            color: #1e3a8a;
            margin-bottom: 8px;
        }
        .temp-pass-code-box {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: #ffffff;
            border: 1px solid #bfdbfe;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 1.2rem;
            font-family: monospace;
            font-weight: 800;
            color: #1d4ed8;
            margin: 10px 0;
        }
        .btn-copy {
            background: #2563eb;
            color: #ffffff;
            border: none;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 0.85rem;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-copy:hover { background: #1d4ed8; }

        /* Filter Card */
        .filter-card {
            background: var(--primary-light, #f8fafc);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            border: 1px solid var(--border-color, #e2e8f0);
        }
        .filter-form {
            display: grid;
            grid-template-columns: 2fr 1.5fr 1fr 1fr 1fr auto;
            gap: 14px;
            align-items: flex-end;
        }
        @media (max-width: 1024px) {
            .filter-form { grid-template-columns: 1fr 1fr 1fr; }
        }
        @media (max-width: 640px) {
            .filter-form { grid-template-columns: 1fr; }
        }
        .filter-group label {
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--text-color, #475569);
            display: block;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .filter-group input, .filter-group select {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid var(--border-color, #cbd5e1);
            border-radius: 8px;
            font-size: 0.9rem;
            background: var(--input-bg, #ffffff);
            color: var(--text-color, #0f172a);
        }

        /* Table Styling */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .student-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.92rem;
        }
        .student-table th {
            background: var(--primary-light, #f1f5f9);
            padding: 12px 14px;
            font-weight: 700;
            color: var(--text-color, #334155);
            border-bottom: 2px solid var(--border-color, #e2e8f0);
            white-space: nowrap;
        }
        .student-table th a {
            color: inherit;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .student-table th a:hover { color: var(--primary-color, #2563eb); }
        .student-table td {
            padding: 12px 14px;
            border-bottom: 1px solid var(--border-color, #f1f5f9);
            color: var(--text-color, #1e293b);
            vertical-align: middle;
        }
        .student-table tr:hover { background: var(--primary-light, rgba(241, 245, 249, 0.6)); }

        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: capitalize;
        }
        .badge-active { background: #dcfce7; color: #15803d; }
        .badge-inactive { background: #fee2e2; color: #b91c1c; }

        /* Action Buttons */
        .row-actions {
            display: flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }
        .btn-act {
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 0.82rem;
            font-weight: 600;
            text-decoration: none;
            border: 1px solid transparent;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #f8fafc;
            color: #334155;
            transition: all 0.15s ease;
        }
        .btn-act:hover { background: #e2e8f0; }
        .btn-act.btn-view { color: #0284c7; border-color: #bae6fd; background: #f0f9ff; }
        .btn-act.btn-view:hover { background: #e0f2fe; }
        .btn-act.btn-edit { color: #2563eb; border-color: #bfdbfe; background: #eff6ff; }
        .btn-act.btn-edit:hover { background: #dbeafe; }
        .btn-act.btn-reset { color: #d97706; border-color: #fde68a; background: #fffbeb; }
        .btn-act.btn-reset:hover { background: #fef3c7; }
        .btn-act.btn-toggle { color: #4b5563; border-color: #e5e7eb; }
        .btn-act.btn-del { color: #dc2626; border-color: #fecaca; background: #fef2f2; }
        .btn-act.btn-del:hover { background: #fee2e2; }

        /* Pagination */
        .pagination-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 20px;
        }
        .pagination-links {
            display: flex;
            gap: 6px;
        }
        .page-link {
            padding: 7px 12px;
            border-radius: 6px;
            background: var(--card-bg, #ffffff);
            border: 1px solid var(--border-color, #cbd5e1);
            color: var(--text-color, #334155);
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 600;
        }
        .page-link:hover { border-color: var(--primary-color, #2563eb); color: var(--primary-color, #2563eb); }
        .page-link.active {
            background: var(--primary-color, #2563eb);
            color: #ffffff;
            border-color: var(--primary-color, #2563eb);
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            display: none;
            place-items: center;
            z-index: 1000;
            padding: 20px;
        }
        .modal-card {
            background: var(--card-bg, #ffffff);
            border-radius: 14px;
            max-width: 600px;
            width: 100%;
            padding: 25px 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            border: 1px solid var(--border-color, #e2e8f0);
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border-color, #e2e8f0);
        }
        .modal-close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-muted, #64748b);
        }
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

    <main class="crud-container" id="main-content">
        <!-- Breadcrumb & Top Bar -->
        <div class="crud-header">
            <div>
                <h1><i class="fa-solid fa-user-graduate" style="color:var(--primary-color, #2563eb);"></i> Student Records Directory</h1>
                <p style="margin:4px 0 0; color:var(--text-muted); font-size:0.9rem;">
                    Administer student academic profiles, manage account states, and issue credential resets
                </p>
            </div>
            <div class="crud-actions">
                <a href="../admin/dashboard.php" class="btn-act" style="background:#f1f5f9; padding:8px 14px;">
                    <i class="fa-solid fa-gauge-high"></i> Dashboard
                </a>
                <a href="export.php?<?= htmlspecialchars($csvQuery) ?>" class="btn-act" style="background:#10b981; color:#fff; border-color:#10b981; padding:8px 16px;">
                    <i class="fa-solid fa-file-csv"></i> Export CSV
                </a>
                <a href="create.php" class="btn-act" style="background:#2563eb; color:#fff; border-color:#2563eb; padding:8px 16px;">
                    <i class="fa-solid fa-user-plus"></i> Add New Student
                </a>
            </div>
        </div>

        <!-- Flash Temporary Password Alert (Shown ONCE) -->
        <?php if ($flashCreated): ?>
            <div class="temp-pass-banner" role="status">
                <div class="temp-pass-header">
                    <i class="fa-solid fa-circle-check" style="color:#16a34a;"></i>
                    <span>Student Account Created Successfully!</span>
                </div>
                <p style="margin:0 0 8px; color:#1e293b;">
                    Student <strong><?= htmlspecialchars($flashCreated['name']) ?></strong> (ID: <code><?= htmlspecialchars($flashCreated['student_id']) ?></code>) has been enrolled.
                </p>
                <div>Default Temporary Password:</div>
                <div class="temp-pass-code-box">
                    <span id="tempPassCode"><?= htmlspecialchars($flashCreated['temp_password']) ?></span>
                    <button type="button" class="btn-copy" onclick="copyPassword('tempPassCode')">
                        <i class="fa-solid fa-copy"></i> Copy
                    </button>
                </div>
                <div style="font-size:0.85rem; color:#b91c1c; font-weight:600;">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <strong>SECURITY ADVISORY:</strong> This temporary password will be shown <strong>ONLY ONCE</strong> and is never stored in plaintext. Provide it securely to the student. They will be forced to change it upon first login.
                </div>
            </div>
        <?php endif; ?>

        <?php if ($flashReset): ?>
            <div class="temp-pass-banner" role="status">
                <div class="temp-pass-header">
                    <i class="fa-solid fa-key" style="color:#d97706;"></i>
                    <span>Temporary Password Reset Successfully!</span>
                </div>
                <p style="margin:0 0 8px; color:#1e293b;">
                    Password for student <strong><?= htmlspecialchars($flashReset['name']) ?></strong> (ID: <code><?= htmlspecialchars($flashReset['student_id']) ?></code>) has been updated to a temporary default.
                </p>
                <div>New Temporary Password:</div>
                <div class="temp-pass-code-box">
                    <span id="tempResetCode"><?= htmlspecialchars($flashReset['temp_password']) ?></span>
                    <button type="button" class="btn-copy" onclick="copyPassword('tempResetCode')">
                        <i class="fa-solid fa-copy"></i> Copy
                    </button>
                </div>
                <div style="font-size:0.85rem; color:#b91c1c; font-weight:600;">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <strong>SECURITY ADVISORY:</strong> This password is shown <strong>ONLY ONCE</strong>. The student must change it immediately on next login.
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div class="success-banner" style="display:block; margin-bottom:20px;" role="status">
                <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($_SESSION['flash_success']) ?>
            </div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['flash_error'])): ?>
            <div class="alert-error" style="background:#fee2e2; color:#991b1b; padding:12px 16px; border-radius:8px; margin-bottom:20px;" role="alert">
                <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($_SESSION['flash_error']) ?>
            </div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <!-- Search & Filter Card -->
        <section class="filter-card" aria-label="Search and Filter Students">
            <form action="list.php" method="GET" class="filter-form">
                <div class="filter-group">
                    <label for="search">Search</label>
                    <input type="text" id="search" name="search" placeholder="ID, name, email, or mobile..." value="<?= htmlspecialchars($search) ?>">
                </div>

                <div class="filter-group">
                    <label for="course">Course</label>
                    <select id="course" name="course">
                        <option value="">All Programs</option>
                        <?php foreach ($allCourses as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>" <?= ($course === $c) ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="semester">Semester</label>
                    <select id="semester" name="semester">
                        <option value="">All</option>
                        <?php for ($s = 1; $s <= 8; $s++): ?>
                            <option value="<?= $s ?>" <?= ($semester === $s) ? 'selected' : '' ?>>Sem <?= $s ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="year">Year</label>
                    <select id="year" name="year">
                        <option value="">All</option>
                        <?php for ($y = 1; $y <= 4; $y++): ?>
                            <option value="<?= $y ?>" <?= ($year === $y) ? 'selected' : '' ?>>Year <?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="all" <?= ($status === 'all') ? 'selected' : '' ?>>All Statuses</option>
                        <option value="active" <?= ($status === 'active') ? 'selected' : '' ?>>Active Only</option>
                        <option value="inactive" <?= ($status === 'inactive') ? 'selected' : '' ?>>Inactive Only</option>
                    </select>
                </div>

                <div class="filter-group" style="display:flex; gap:8px;">
                    <button type="submit" class="btn-act" style="background:#2563eb; color:#fff; border-color:#2563eb; height:40px; padding:0 16px;">
                        <i class="fa-solid fa-magnifying-glass"></i> Filter
                    </button>
                    <a href="list.php" class="btn-act" style="height:40px; padding:0 14px; display:inline-flex; align-items:center;">
                        <i class="fa-solid fa-rotate-left"></i> Reset
                    </a>
                </div>
            </form>
        </section>

        <!-- Student Data Table -->
        <div class="table-responsive">
            <table class="student-table" aria-label="Student accounts list">
                <thead>
                    <tr>
                        <th>
                            <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'student_id', 'order' => ($sort === 'student_id' && $order === 'ASC') ? 'desc' : 'asc'])) ?>">
                                Student ID <?= ($sort === 'student_id') ? ($order === 'ASC' ? '▲' : '▼') : '' ?>
                            </a>
                        </th>
                        <th>
                            <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'name', 'order' => ($sort === 'name' && $order === 'ASC') ? 'desc' : 'asc'])) ?>">
                                Full Name <?= ($sort === 'name') ? ($order === 'ASC' ? '▲' : '▼') : '' ?>
                            </a>
                        </th>
                        <th>Email & Mobile</th>
                        <th>
                            <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'course', 'order' => ($sort === 'course' && $order === 'ASC') ? 'desc' : 'asc'])) ?>">
                                Program & Department <?= ($sort === 'course') ? ($order === 'ASC' ? '▲' : '▼') : '' ?>
                            </a>
                        </th>
                        <th>
                            <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'semester', 'order' => ($sort === 'semester' && $order === 'ASC') ? 'desc' : 'asc'])) ?>">
                                Sem / Year <?= ($sort === 'semester') ? ($order === 'ASC' ? '▲' : '▼') : '' ?>
                            </a>
                        </th>
                        <th>Admission Date</th>
                        <th>
                            <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'status', 'order' => ($sort === 'status' && $order === 'ASC') ? 'desc' : 'asc'])) ?>">
                                Status <?= ($sort === 'status') ? ($order === 'ASC' ? '▲' : '▼') : '' ?>
                            </a>
                        </th>
                        <th style="text-align:center;">Administrative Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="8" style="text-align:center; padding:35px; color:var(--text-muted);">
                                <i class="fa-solid fa-user-slash" style="font-size:2rem; opacity:0.4; margin-bottom:10px; display:block;"></i>
                                No student records matching your search/filter criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $s): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($s['student_id']) ?></strong>
                                    <?php if (!empty($s['must_change_password'])): ?>
                                        <span title="Must change temporary password on first login" style="display:block; font-size:0.72rem; color:#d97706; font-weight:700;">
                                            <i class="fa-solid fa-clock"></i> Temp Pass
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="font-weight:600;"><?= htmlspecialchars($s['name']) ?></div>
                                    <small style="color:var(--text-muted); font-size:0.8rem;"><?= htmlspecialchars($s['gender']) ?> · CGPA: <?= htmlspecialchars((string)$s['cgpa']) ?></small>
                                </td>
                                <td>
                                    <div style="font-size:0.88rem;"><?= htmlspecialchars($s['email']) ?></div>
                                    <small style="color:var(--text-muted); font-size:0.8rem;"><i class="fa-solid fa-phone"></i> <?= htmlspecialchars($s['mobile']) ?></small>
                                </td>
                                <td>
                                    <div style="font-weight:500; font-size:0.88rem;"><?= htmlspecialchars($s['course']) ?></div>
                                    <small style="color:var(--text-muted); font-size:0.78rem;"><?= htmlspecialchars($s['department']) ?></small>
                                </td>
                                <td>
                                    <span style="font-weight:600;">Sem <?= (int)($s['semester'] ?? 1) ?></span>
                                    <small style="display:block; color:var(--text-muted); font-size:0.8rem;">Year <?= (int)$s['year'] ?></small>
                                </td>
                                <td>
                                    <span style="font-size:0.85rem;"><?= htmlspecialchars($s['admission_date'] ?? 'N/A') ?></span>
                                </td>
                                <td>
                                    <?php if ($s['status'] === 'active'): ?>
                                        <span class="badge badge-active"><i class="fa-solid fa-circle-check"></i> Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-inactive"><i class="fa-solid fa-circle-xmark"></i> Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="row-actions" style="justify-content:center;">
                                        <!-- View Details -->
                                        <button type="button" class="btn-act btn-view" title="View Student Profile"
                                                onclick='openViewModal(<?= json_encode($s, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                            <i class="fa-solid fa-eye"></i> View
                                        </button>

                                        <!-- Edit Administrative Details -->
                                        <a href="update.php?id=<?= (int)$s['id'] ?>" class="btn-act btn-edit" title="Edit Student Details">
                                            <i class="fa-solid fa-pen"></i> Edit
                                        </a>

                                        <!-- Reset Password -->
                                        <button type="button" class="btn-act btn-reset" title="Reset Temporary Password"
                                                onclick="openResetModal(<?= (int)$s['id'] ?>, '<?= htmlspecialchars($s['student_id'], ENT_QUOTES) ?>', '<?= htmlspecialchars($s['name'], ENT_QUOTES) ?>')">
                                            <i class="fa-solid fa-key"></i> Reset
                                        </button>

                                        <!-- Toggle Status (Activate / Deactivate) -->
                                        <form method="post" action="delete.php" style="display:inline;" onsubmit="return confirm('Toggle status for student <?= htmlspecialchars($s['student_id'], ENT_QUOTES) ?> to <?= $s['status'] === 'active' ? 'Inactive' : 'Active' ?>?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                                            <input type="hidden" name="action" value="<?= $s['status'] === 'active' ? 'soft' : 'restore' ?>">
                                            <button type="submit" class="btn-act btn-toggle" title="<?= $s['status'] === 'active' ? 'Deactivate Account' : 'Activate Account' ?>">
                                                <i class="fa-solid <?= $s['status'] === 'active' ? 'fa-ban' : 'fa-check' ?>"></i>
                                                <?= $s['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                                            </button>
                                        </form>

                                        <!-- Delete / Soft Delete -->
                                        <button type="button" class="btn-act btn-del" title="Delete Student Record"
                                                onclick="openDeleteModal(<?= (int)$s['id'] ?>, '<?= htmlspecialchars($s['student_id'], ENT_QUOTES) ?>', '<?= htmlspecialchars($s['name'], ENT_QUOTES) ?>')">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar -->
        <div class="pagination-bar">
            <div style="font-size:0.9rem; color:var(--text-muted);">
                Showing <strong><?= empty($students) ? 0 : ($offset + 1) ?></strong> to <strong><?= min($totalRecords, $offset + $limit) ?></strong> of <strong><?= $totalRecords ?></strong> student records
            </div>

            <?php if ($totalPages > 1): ?>
                <nav class="pagination-links" aria-label="Pagination">
                    <?php if ($page > 1): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" class="page-link" title="First Page">&laquo; First</a>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="page-link" title="Previous Page">&lsaquo; Prev</a>
                    <?php endif; ?>

                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage   = min($totalPages, $page + 2);
                    for ($p = $startPage; $p <= $endPage; $p++):
                    ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>" class="page-link <?= ($p === $page) ? 'active' : '' ?>">
                            <?= $p ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="page-link" title="Next Page">Next &rsaquo;</a>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>" class="page-link" title="Last Page">Last &raquo;</a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal 1: Complete Student Details Viewer -->
    <div id="viewModal" class="modal-overlay" onclick="closeOnBackdrop(event, 'viewModal')">
        <div class="modal-card">
            <div class="modal-header">
                <h3 style="margin:0; display:flex; align-items:center; gap:10px;">
                    <i class="fa-solid fa-id-card" style="color:var(--primary-color);"></i>
                    <span id="viewModalTitle">Student Profile</span>
                </h3>
                <button type="button" class="modal-close-btn" onclick="closeModal('viewModal')">&times;</button>
            </div>
            <div id="viewModalContent">
                <!-- Dynamically populated -->
            </div>
            <div style="margin-top:20px; text-align:right;">
                <button type="button" class="btn-act" style="padding:8px 18px;" onclick="closeModal('viewModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- Modal 2: Reset Temporary Password -->
    <div id="resetModal" class="modal-overlay" onclick="closeOnBackdrop(event, 'resetModal')">
        <div class="modal-card">
            <div class="modal-header">
                <h3 style="margin:0; display:flex; align-items:center; gap:10px;">
                    <i class="fa-solid fa-key" style="color:#d97706;"></i>
                    <span>Reset Temporary Password</span>
                </h3>
                <button type="button" class="modal-close-btn" onclick="closeModal('resetModal')">&times;</button>
            </div>
            <form action="reset_password.php" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="resetStudentDbId" value="">
                <p id="resetModalMsg" style="font-size:0.92rem; color:var(--text-color); margin-bottom:15px;"></p>
                
                <div style="background:var(--primary-light, #f8fafc); border:1px solid var(--border-color); border-radius:8px; padding:15px; margin-bottom:18px;">
                    <label style="display:flex; align-items:center; gap:8px; font-weight:600; cursor:pointer;">
                        <input type="checkbox" name="auto_generate" id="resetAutoGenerate" value="1" checked onchange="toggleResetCustomPass()">
                        <span>Auto-generate a secure random temporary password (Recommended)</span>
                    </label>

                    <div id="customPassBox" style="display:none; margin-top:12px;">
                        <label for="customPassword" style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:5px;">Or Specify Admin-Approved Password (min 8 chars):</label>
                        <input type="text" name="custom_password" id="customPassword" style="width:100%; padding:8px 12px; border-radius:6px; border:1px solid #cbd5e1;" placeholder="Enter temporary password">
                    </div>
                </div>

                <div style="font-size:0.82rem; color:var(--text-muted); margin-bottom:20px;">
                    <i class="fa-solid fa-circle-info"></i> The temporary password will be shown to you <strong>only once</strong> upon submission. The student must change it immediately on next login.
                </div>

                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" class="btn-act" onclick="closeModal('resetModal')">Cancel</button>
                    <button type="submit" class="btn-act" style="background:#d97706; color:#fff; border-color:#d97706; padding:8px 18px;">
                        <i class="fa-solid fa-check"></i> Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 3: Delete / Deactivation Confirmation -->
    <div id="deleteModal" class="modal-overlay" onclick="closeOnBackdrop(event, 'deleteModal')">
        <div class="modal-card">
            <div class="modal-header">
                <h3 style="margin:0; display:flex; align-items:center; gap:10px; color:#dc2626;">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>Confirm Student Account Action</span>
                </h3>
                <button type="button" class="modal-close-btn" onclick="closeModal('deleteModal')">&times;</button>
            </div>
            <form action="delete.php" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="deleteStudentDbId" value="">
                <p id="deleteModalMsg" style="font-size:0.95rem; color:var(--text-color); margin-bottom:15px;"></p>

                <div style="background:#fff1f2; border:1px solid #fecdd3; border-radius:8px; padding:15px; margin-bottom:20px; font-size:0.88rem; color:#881337;">
                    <strong>Academic Record Preservation:</strong>
                    <div style="margin-top:8px;">
                        <label style="display:flex; align-items:flex-start; gap:8px; cursor:pointer; margin-bottom:10px;">
                            <input type="radio" name="action" value="soft" checked>
                            <span><strong>Deactivate Student (Recommended):</strong> Preserves academic history, exam marks, and registrations, but blocks login access.</span>
                        </label>
                        <label style="display:flex; align-items:flex-start; gap:8px; cursor:pointer;">
                            <input type="radio" name="action" value="hard">
                            <span><strong>Permanent Deletion:</strong> Irreversibly purges the student and login account from the database.</span>
                        </label>
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" class="btn-act" onclick="closeModal('deleteModal')">Cancel</button>
                    <button type="submit" class="btn-act" style="background:#dc2626; color:#fff; border-color:#dc2626; padding:8px 18px;">
                        <i class="fa-solid fa-check"></i> Confirm Action
                    </button>
                </div>
            </form>
        </div>
    </div>

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
        function copyPassword(elementId) {
            const text = document.getElementById(elementId).innerText;
            navigator.clipboard.writeText(text).then(() => {
                alert('Temporary password copied to clipboard!');
            }).catch(() => {
                prompt('Copy the temporary password:', text);
            });
        }

        function openViewModal(s) {
            document.getElementById('viewModalTitle').innerText = `${s.name} (${s.student_id})`;
            const html = `
                <table style="width:100%; border-collapse:collapse; font-size:0.9rem;">
                    <tr style="border-bottom:1px solid var(--border-color);"><th style="text-align:left; padding:8px; color:var(--text-muted); width:40%;">Student ID</th><td style="padding:8px; font-weight:700;">${escapeHtml(s.student_id)}</td></tr>
                    <tr style="border-bottom:1px solid var(--border-color);"><th style="text-align:left; padding:8px; color:var(--text-muted);">Full Name</th><td style="padding:8px;">${escapeHtml(s.name)}</td></tr>
                    <tr style="border-bottom:1px solid var(--border-color);"><th style="text-align:left; padding:8px; color:var(--text-muted);">Email Address</th><td style="padding:8px;">${escapeHtml(s.email)}</td></tr>
                    <tr style="border-bottom:1px solid var(--border-color);"><th style="text-align:left; padding:8px; color:var(--text-muted);">Mobile Number</th><td style="padding:8px;">${escapeHtml(s.mobile)}</td></tr>
                    <tr style="border-bottom:1px solid var(--border-color);"><th style="text-align:left; padding:8px; color:var(--text-muted);">Course / Program</th><td style="padding:8px;">${escapeHtml(s.course)}</td></tr>
                    <tr style="border-bottom:1px solid var(--border-color);"><th style="text-align:left; padding:8px; color:var(--text-muted);">Department</th><td style="padding:8px;">${escapeHtml(s.department || 'Computer Science')}</td></tr>
                    <tr style="border-bottom:1px solid var(--border-color);"><th style="text-align:left; padding:8px; color:var(--text-muted);">Semester / Year</th><td style="padding:8px;">Semester ${s.semester || 1} (Year ${s.year})</td></tr>
                    <tr style="border-bottom:1px solid var(--border-color);"><th style="text-align:left; padding:8px; color:var(--text-muted);">Admission Date</th><td style="padding:8px;">${escapeHtml(s.admission_date || 'N/A')}</td></tr>
                    <tr style="border-bottom:1px solid var(--border-color);"><th style="text-align:left; padding:8px; color:var(--text-muted);">Gender</th><td style="padding:8px;">${escapeHtml(s.gender || 'Other')}</td></tr>
                    <tr style="border-bottom:1px solid var(--border-color);"><th style="text-align:left; padding:8px; color:var(--text-muted);">Cumulative CGPA</th><td style="padding:8px; font-weight:700; color:#10b981;">${escapeHtml(String(s.cgpa))} / 10.0</td></tr>
                    <tr style="border-bottom:1px solid var(--border-color);"><th style="text-align:left; padding:8px; color:var(--text-muted);">Account Status</th><td style="padding:8px;"><span class="badge ${s.status === 'active' ? 'badge-active' : 'badge-inactive'}">${escapeHtml(s.status)}</span></td></tr>
                    <tr style="border-bottom:1px solid var(--border-color);"><th style="text-align:left; padding:8px; color:var(--text-muted);">Last Login</th><td style="padding:8px;">${escapeHtml(s.last_login || 'Never logged in')}</td></tr>
                    <tr><th style="text-align:left; padding:8px; color:var(--text-muted);">Enrolled At</th><td style="padding:8px;">${escapeHtml(s.created_at)}</td></tr>
                </table>
            `;
            document.getElementById('viewModalContent').innerHTML = html;
            document.getElementById('viewModal').style.display = 'grid';
        }

        function openResetModal(id, studentId, name) {
            document.getElementById('resetStudentDbId').value = id;
            document.getElementById('resetModalMsg').innerHTML = `Reset temporary password for <strong>${escapeHtml(name)}</strong> (ID: <code>${escapeHtml(studentId)}</code>).`;
            document.getElementById('resetAutoGenerate').checked = true;
            toggleResetCustomPass();
            document.getElementById('resetModal').style.display = 'grid';
        }

        function toggleResetCustomPass() {
            const chk = document.getElementById('resetAutoGenerate');
            document.getElementById('customPassBox').style.display = chk.checked ? 'none' : 'block';
        }

        function openDeleteModal(id, studentId, name) {
            document.getElementById('deleteStudentDbId').value = id;
            document.getElementById('deleteModalMsg').innerHTML = `Select account deactivation or deletion action for student <strong>${escapeHtml(name)}</strong> (ID: <code>${escapeHtml(studentId)}</code>):`;
            document.getElementById('deleteModal').style.display = 'grid';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function closeOnBackdrop(event, modalId) {
            if (event.target.id === modalId) {
                closeModal(modalId);
            }
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/[&<>"']/g, function(m) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m];
            });
        }
    </script>
</body>
</html>
