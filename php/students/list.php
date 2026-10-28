<?php
// ============================================================================
// Student Hub Portal - Student Management: List, Search, Filter & Pagination
// Practical 11 (CRUD), Practical 14 (Admin Interface), Practical 15
// ============================================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/response.php';

init_session();
require_login('../../Login.html');

$db = get_db_connection();

// 1. Search and Filter Parameters
$search = trim($_GET['search'] ?? '');
$course = trim($_GET['course'] ?? '');
$status = trim($_GET['status'] ?? 'all');
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 10;
$offset = ($page - 1) * $limit;

// 2. Build Dynamic Query with Prepared Statements
$whereClauses = ["1=1"];
$params = [];
$types = "";

if ($search !== '') {
    $whereClauses[] = "(name LIKE ? OR student_id LIKE ? OR email LIKE ? OR city LIKE ?)";
    $like = "%$search%";
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= "ssss";
}

if ($course !== '') {
    $whereClauses[] = "course = ?";
    $params[] = $course;
    $types .= "s";
}

if ($status !== 'all' && in_array($status, ['active', 'inactive'])) {
    $whereClauses[] = "status = ?";
    $params[] = $status;
    $types .= "s";
}

$whereSql = implode(" AND ", $whereClauses);

// Count total records for pagination
$countSql = "SELECT COUNT(*) AS total FROM students WHERE $whereSql";
$stmtCount = $db->prepare($countSql);
if ($types !== "") {
    $stmtCount->bind_param($types, ...$params);
}
$stmtCount->execute();
$totalRecords = (int)$stmtCount->get_result()->fetch_assoc()['total'];
$stmtCount->close();

$totalPages = max(1, ceil($totalRecords / $limit));

// Fetch paginated records
$dataSql = "SELECT id, student_id, name, email, mobile, course, year, department, cgpa, status, created_at
            FROM students
            WHERE $whereSql
            ORDER BY id ASC
            LIMIT ? OFFSET ?";
$typesData = $types . "ii";
$paramsData = array_merge($params, [$limit, $offset]);

$stmtData = $db->prepare($dataSql);
$stmtData->bind_param($typesData, ...$paramsData);
$stmtData->execute();
$students = $stmtData->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtData->close();

// Get list of distinct courses for filter dropdown
$courseListRes = $db->query("SELECT DISTINCT course FROM students ORDER BY course ASC");
$allCourses = [];
while ($c = $courseListRes->fetch_assoc()) {
    $allCourses[] = $c['course'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - Student Hub Portal</title>
    <link rel="stylesheet" href="../../Style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        .crud-container { max-width: 1200px; margin: 30px auto; padding: 25px; background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); font-family: 'Segoe UI', Tahoma, sans-serif; }
        .crud-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 25px; }
        .crud-actions { display: flex; gap: 10px; }
        .filter-card { background: #f8fafc; padding: 18px; border-radius: 8px; margin-bottom: 25px; border: 1px solid #e2e8f0; }
        .filter-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) auto; gap: 15px; align-items: flex-end; }
        .filter-form label { font-size: 0.85rem; font-weight: 600; color: #475569; display: block; margin-bottom: 5px; }
        .filter-form input, .filter-form select { width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; }
        .data-table-wrapper { overflow-x: auto; }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; text-align: left; }
        .data-table th { background: #f1f5f9; padding: 12px 14px; font-weight: 600; color: #1e293b; border-bottom: 2px solid #e2e8f0; }
        .data-table td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .data-table tr:hover { background: #f8fafc; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
        .badge-active { background: #dcfce7; color: #166534; }
        .badge-inactive { background: #fee2e2; color: #991b1b; }
        .action-btns { display: flex; gap: 6px; }
        .action-btn { padding: 6px 10px; border-radius: 4px; font-size: 0.8rem; text-decoration: none; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; }
        .btn-edit { background: #e0f2fe; color: #0369a1; }
        .btn-delete { background: #fee2e2; color: #b91c1c; }
        .btn-view { background: #f1f5f9; color: #334155; }
        .pagination { display: flex; justify-content: space-between; align-items: center; margin-top: 25px; flex-wrap: wrap; gap: 15px; }
        .page-links { display: flex; gap: 6px; }
        .page-link { padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 4px; text-decoration: none; color: #334155; }
        .page-link.active { background: #2563eb; color: #fff; border-color: #2563eb; font-weight: 600; }
        .btn-primary { background: #2563eb; color: #fff; padding: 9px 16px; border-radius: 6px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; border: none; }
        .btn-success { background: #16a34a; color: #fff; padding: 9px 16px; border-radius: 6px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
        .btn-secondary { background: #64748b; color: #fff; padding: 9px 16px; border-radius: 6px; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <main class="crud-container">
        <div class="crud-header">
            <div>
                <h2><i class="fa-solid fa-user-graduate"></i> Student Management System</h2>
                <p>Add, edit, search, filter, soft-delete, and export student academic records.</p>
            </div>
            <div class="crud-actions">
                <a href="create.php" class="btn-primary"><i class="fa-solid fa-user-plus"></i> Add New Student</a>
                <a href="export.php?course=<?= urlencode($course) ?>&status=<?= urlencode($status) ?>&search=<?= urlencode($search) ?>" class="btn-success"><i class="fa-solid fa-file-excel"></i> Export to CSV</a>
                <a href="../admin/dashboard.php" class="btn-secondary"><i class="fa-solid fa-shield-halved"></i> Dashboard</a>
            </div>
        </div>

        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div style="background: #dcfce7; color: #166534; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px;">
                <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($_SESSION['flash_success']) ?>
            </div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['flash_error'])): ?>
            <div style="background: #fee2e2; color: #991b1b; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px;">
                <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($_SESSION['flash_error']) ?>
            </div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <!-- Search and Filter Form -->
        <div class="filter-card">
            <form method="GET" action="list.php" class="filter-form">
                <div>
                    <label for="search">Search Keywords</label>
                    <input type="text" id="search" name="search" placeholder="Name, ID, email, or city..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div>
                    <label for="course">Filter by Course</label>
                    <select id="course" name="course">
                        <option value="">All Courses</option>
                        <?php foreach ($allCourses as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>" <?= ($course === $c) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="all" <?= ($status === 'all') ? 'selected' : '' ?>>All Statuses</option>
                        <option value="active" <?= ($status === 'active') ? 'selected' : '' ?>>Active Only</option>
                        <option value="inactive" <?= ($status === 'inactive') ? 'selected' : '' ?>>Inactive (Soft-Deleted)</option>
                    </select>
                </div>
                <div style="display: flex; gap: 8px;">
                    <button type="submit" class="btn-primary" style="padding: 9px 14px;"><i class="fa-solid fa-magnifying-glass"></i> Filter</button>
                    <a href="list.php" class="action-btn btn-view" style="padding: 9px 14px;">Reset</a>
                </div>
            </form>
        </div>

        <!-- Student Records Table -->
        <div class="data-table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Mobile</th>
                        <th>Course</th>
                        <th>Year</th>
                        <th>CGPA</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 30px; color: #64748b;">
                                No student records found matching your search and filter criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $s): ?>
                            <tr>
                                <td><?= (int)$s['id'] ?></td>
                                <td><strong><?= htmlspecialchars($s['student_id']) ?></strong></td>
                                <td><?= htmlspecialchars($s['name']) ?></td>
                                <td><?= htmlspecialchars($s['email']) ?></td>
                                <td><?= htmlspecialchars($s['mobile']) ?></td>
                                <td><?= htmlspecialchars($s['course']) ?></td>
                                <td>Year <?= (int)$s['year'] ?></td>
                                <td><?= number_format((float)$s['cgpa'], 2) ?></td>
                                <td>
                                    <span class="badge badge-<?= $s['status'] ?>">
                                        <?= htmlspecialchars($s['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <a href="update.php?id=<?= (int)$s['id'] ?>" class="action-btn btn-edit" title="Edit student">
                                            <i class="fa-solid fa-pen-to-square"></i> Edit
                                        </a>
                                        <?php if ($s['status'] === 'active'): ?>
                                            <a href="delete.php?id=<?= (int)$s['id'] ?>&action=soft" class="action-btn btn-delete" onclick="return confirm('Soft-delete student <?= htmlspecialchars(addslashes($s['name'])) ?>? Status will become inactive.');" title="Soft-delete">
                                                <i class="fa-solid fa-user-slash"></i> Soft-Delete
                                            </a>
                                        <?php else: ?>
                                            <a href="delete.php?id=<?= (int)$s['id'] ?>&action=restore" class="action-btn btn-view" style="color: #166534;" title="Restore to active">
                                                <i class="fa-solid fa-rotate-left"></i> Restore
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Information -->
        <div class="pagination">
            <div style="color: #64748b; font-size: 0.9rem;">
                Showing <?= min($totalRecords, $offset + 1) ?> to <?= min($totalRecords, $offset + count($students)) ?> of <strong><?= $totalRecords ?></strong> student records
            </div>
            <?php if ($totalPages > 1): ?>
                <div class="page-links">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&course=<?= urlencode($course) ?>&status=<?= urlencode($status) ?>" class="page-link">&laquo; Prev</a>
                    <?php endif; ?>

                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <a href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&course=<?= urlencode($course) ?>&status=<?= urlencode($status) ?>" class="page-link <?= ($p === $page) ? 'active' : '' ?>">
                            <?= $p ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&course=<?= urlencode($course) ?>&status=<?= urlencode($status) ?>" class="page-link">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
