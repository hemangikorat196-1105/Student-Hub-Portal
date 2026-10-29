<?php
// ============================================================================
// Student Hub Portal - Course Management (Admin Module)
// Features: List, Add, Edit, Toggle Status, Delete Academic Courses
// ============================================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/csrf.php';

init_session();
require_admin();

$db = get_db_connection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = "Invalid form submission or token expired.";
        header("Location: courses.php");
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $courseCode = strtoupper(trim($_POST['course_code'] ?? ''));
        $courseName = trim($_POST['course_name'] ?? '');
        $department = trim($_POST['department'] ?? 'Computer Science & Engineering');
        $duration   = (int)($_POST['duration_years'] ?? 4);
        $semesters  = (int)($_POST['semesters'] ?? 8);
        $credits    = (int)($_POST['credits'] ?? 160);
        $status     = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

        if (empty($courseCode) || empty($courseName)) {
            $_SESSION['flash_error'] = "Course code and course name are required.";
        } else {
            $chk = $db->prepare("SELECT id FROM courses WHERE course_code = ? LIMIT 1");
            $chk->bind_param("s", $courseCode);
            $chk->execute();
            if ($chk->get_result()->num_rows > 0) {
                $_SESSION['flash_error'] = "A course with code '$courseCode' already exists.";
            } else {
                $stmt = $db->prepare("
                    INSERT INTO courses (course_code, course_name, department, duration_years, semesters, credits, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->bind_param("sssiiss", $courseCode, $courseName, $department, $duration, $semesters, $credits, $status);
                $stmt->execute();
                $stmt->close();

                log_audit_event("CREATE_COURSE", "Admin created course $courseCode ($courseName)");
                $_SESSION['flash_success'] = "Course $courseCode created successfully.";
            }
            $chk->close();
        }
        header("Location: courses.php");
        exit;
    }

    if ($action === 'update') {
        $id         = (int)($_POST['id'] ?? 0);
        $courseName = trim($_POST['course_name'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $duration   = (int)($_POST['duration_years'] ?? 4);
        $semesters  = (int)($_POST['semesters'] ?? 8);
        $credits    = (int)($_POST['credits'] ?? 160);
        $status     = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

        if ($id > 0 && !empty($courseName)) {
            $stmt = $db->prepare("
                UPDATE courses
                SET course_name = ?, department = ?, duration_years = ?, semesters = ?, credits = ?, status = ?
                WHERE id = ?
            ");
            $stmt->bind_param("ssiissi", $courseName, $department, $duration, $semesters, $credits, $status, $id);
            $stmt->execute();
            $stmt->close();

            log_audit_event("UPDATE_COURSE", "Admin updated course ID $id ($courseName)");
            $_SESSION['flash_success'] = "Course updated successfully.";
        }
        header("Location: courses.php");
        exit;
    }

    if ($action === 'toggle_status') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $db->prepare("UPDATE courses SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            log_audit_event("TOGGLE_COURSE_STATUS", "Admin toggled course ID $id status");
            $_SESSION['flash_success'] = "Course status toggled.";
        }
        header("Location: courses.php");
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $db->prepare("DELETE FROM courses WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            log_audit_event("DELETE_COURSE", "Admin deleted course ID $id");
            $_SESSION['flash_success'] = "Course deleted.";
        }
        header("Location: courses.php");
        exit;
    }
}

$coursesList = $db->query("SELECT * FROM courses ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Management - Student Hub Portal</title>
    <link rel="stylesheet" href="../../Style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="../../script.js" defer></script>
    <style>
        .admin-wrap { max-width: 1250px; margin: 30px auto 60px; padding: 25px 30px; background: var(--card-bg, #fff); border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); border: 1px solid var(--border-color, #e2e8f0); font-family: 'Segoe UI', Tahoma, sans-serif; }
        .admin-header-bar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 25px; padding-bottom: 20px; border-bottom: 1px solid var(--border-color, #e2e8f0); }
        .admin-header-bar h1 { font-size: 1.5rem; margin: 0; color: var(--text-color, #0f172a); display: flex; align-items: center; gap: 12px; }
        .btn-act { padding: 8px 16px; border-radius: 6px; font-weight: 600; text-decoration: none; border: 1px solid transparent; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-size: 0.88rem; }
        .btn-primary { background: var(--primary-color, #2563eb); color: #fff; }
        .btn-secondary { background: #f1f5f9; color: #334155; }
        .table-wrap { width: 100%; overflow-x: auto; border: 1px solid var(--border-color, #e2e8f0); border-radius: 10px; margin-top: 15px; }
        .admin-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem; }
        .admin-table th { background: var(--primary-light, #f1f5f9); padding: 12px 14px; font-weight: 700; color: var(--text-color, #334155); border-bottom: 2px solid var(--border-color, #e2e8f0); }
        .admin-table td { padding: 12px 14px; border-bottom: 1px solid var(--border-color, #f1f5f9); color: var(--text-color, #1e293b); vertical-align: middle; }
        .badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 12px; font-size: 0.78rem; font-weight: 700; }
        .badge-active { background: #dcfce7; color: #15803d; }
        .badge-inactive { background: #fee2e2; color: #b91c1c; }
        .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: none; place-items: center; z-index: 1000; padding: 20px; }
        .modal-card { background: var(--card-bg, #fff); border-radius: 14px; max-width: 580px; width: 100%; padding: 25px 30px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); border: 1px solid var(--border-color, #e2e8f0); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { font-size: 0.85rem; font-weight: 600; margin-bottom: 4px; color: var(--text-color); }
        .form-group input, .form-group select { padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.92rem; background: var(--input-bg, #fff); color: var(--text-color); }
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

    <main class="admin-wrap" id="main-content">
        <div class="admin-header-bar">
            <div>
                <h1><i class="fa-solid fa-book" style="color:#ec4899;"></i> Course & Curriculum Management</h1>
                <p style="margin:4px 0 0; color:var(--text-muted); font-size:0.88rem;">Administer degree programs, semester tracks, and syllabus modules</p>
            </div>
            <div style="display:flex; gap:10px;">
                <a href="dashboard.php" class="btn-act btn-secondary"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
                <button type="button" class="btn-act btn-primary" onclick="openCreateModal()"><i class="fa-solid fa-plus"></i> Add New Course</button>
            </div>
        </div>

        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div class="success-banner" style="display:block; margin-bottom:20px;" role="status">
                <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($_SESSION['flash_success']) ?>
            </div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['flash_error'])): ?>
            <div style="background:#fee2e2; color:#991b1b; padding:12px 16px; border-radius:8px; margin-bottom:20px;">
                <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($_SESSION['flash_error']) ?>
            </div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Course / Degree Program</th>
                        <th>Department</th>
                        <th>Duration</th>
                        <th>Semesters</th>
                        <th>Credits</th>
                        <th>Status</th>
                        <th style="text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($coursesList)): ?>
                        <tr><td colspan="8" style="text-align:center; padding:30px; color:var(--text-muted);">No academic courses configured.</td></tr>
                    <?php else: ?>
                        <?php foreach ($coursesList as $c): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($c['course_code']) ?></strong></td>
                                <td><div style="font-weight:600;"><?= htmlspecialchars($c['course_name']) ?></div></td>
                                <td><?= htmlspecialchars($c['department']) ?></td>
                                <td><?= (int)$c['duration_years'] ?> Years</td>
                                <td><?= (int)$c['semesters'] ?> Sems</td>
                                <td><?= (int)$c['credits'] ?> Credits</td>
                                <td>
                                    <span class="badge <?= $c['status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>">
                                        <?= ucfirst($c['status']) ?>
                                    </span>
                                </td>
                                <td style="text-align:center;">
                                    <div style="display:inline-flex; gap:6px;">
                                        <button type="button" class="btn-act btn-secondary" onclick='openEditModal(<?= json_encode($c) ?>)' title="Edit"><i class="fa-solid fa-pen"></i></button>
                                        <form method="POST" action="courses.php" style="display:inline;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                            <button type="submit" class="btn-act btn-secondary" title="Toggle Status"><i class="fa-solid <?= $c['status'] === 'active' ? 'fa-ban' : 'fa-check' ?>"></i></button>
                                        </form>
                                        <form method="POST" action="courses.php" style="display:inline;" onsubmit="return confirm('Delete course <?= htmlspecialchars($c['course_code'], ENT_QUOTES) ?>?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                            <button type="submit" class="btn-act" style="background:#fee2e2; color:#b91c1c;" title="Delete"><i class="fa-solid fa-trash-can"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Create Course Modal -->
    <div id="createModal" class="modal-overlay">
        <div class="modal-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid var(--border-color); padding-bottom:10px;">
                <h3 style="margin:0;"><i class="fa-solid fa-book" style="color:var(--primary-color);"></i> Add Academic Course</h3>
                <button type="button" onclick="closeModal('createModal')" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
            </div>
            <form method="POST" action="courses.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Course Code *</label>
                        <input type="text" name="course_code" required placeholder="e.g. AI201">
                    </div>
                    <div class="form-group">
                        <label>Department *</label>
                        <select name="department" required>
                            <option value="Computer Science & Engineering">Computer Science & Engineering</option>
                            <option value="Information Technology">Information Technology</option>
                            <option value="Artificial Intelligence & Data Science">Artificial Intelligence & Data Science</option>
                            <option value="Electronics & Communication">Electronics & Communication</option>
                            <option value="Computer Applications">Computer Applications</option>
                        </select>
                    </div>
                    <div class="form-group full">
                        <label>Course Title *</label>
                        <input type="text" name="course_name" required placeholder="e.g. B.Tech Artificial Intelligence">
                    </div>
                    <div class="form-group">
                        <label>Duration (Years)</label>
                        <input type="number" name="duration_years" value="4" min="1" max="5">
                    </div>
                    <div class="form-group">
                        <label>Total Semesters</label>
                        <input type="number" name="semesters" value="8" min="1" max="10">
                    </div>
                    <div class="form-group">
                        <label>Total Credits</label>
                        <input type="number" name="credits" value="160" min="20" max="250">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="active" selected>Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div style="margin-top:20px; display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" class="btn-act btn-secondary" onclick="closeModal('createModal')">Cancel</button>
                    <button type="submit" class="btn-act btn-primary">Save Course</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Course Modal -->
    <div id="editModal" class="modal-overlay">
        <div class="modal-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid var(--border-color); padding-bottom:10px;">
                <h3 style="margin:0;"><i class="fa-solid fa-pen-to-square" style="color:var(--primary-color);"></i> Edit Course</h3>
                <button type="button" onclick="closeModal('editModal')" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
            </div>
            <form method="POST" action="courses.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="editCourseId" value="">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Course Code (Fixed)</label>
                        <input type="text" id="editCourseCode" disabled style="background:#f1f5f9;">
                    </div>
                    <div class="form-group">
                        <label>Department *</label>
                        <select name="department" id="editCourseDept" required>
                            <option value="Computer Science & Engineering">Computer Science & Engineering</option>
                            <option value="Information Technology">Information Technology</option>
                            <option value="Artificial Intelligence & Data Science">Artificial Intelligence & Data Science</option>
                            <option value="Electronics & Communication">Electronics & Communication</option>
                            <option value="Computer Applications">Computer Applications</option>
                        </select>
                    </div>
                    <div class="form-group full">
                        <label>Course Title *</label>
                        <input type="text" name="course_name" id="editCourseName" required>
                    </div>
                    <div class="form-group">
                        <label>Duration (Years)</label>
                        <input type="number" name="duration_years" id="editCourseDuration" min="1" max="5">
                    </div>
                    <div class="form-group">
                        <label>Total Semesters</label>
                        <input type="number" name="semesters" id="editCourseSemesters" min="1" max="10">
                    </div>
                    <div class="form-group">
                        <label>Total Credits</label>
                        <input type="number" name="credits" id="editCourseCredits" min="20" max="250">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" id="editCourseStatus">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div style="margin-top:20px; display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" class="btn-act btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" class="btn-act btn-primary">Update Course</button>
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
        function openCreateModal() { document.getElementById('createModal').style.display = 'grid'; }
        function openEditModal(c) {
            document.getElementById('editCourseId').value = c.id;
            document.getElementById('editCourseCode').value = c.course_code;
            document.getElementById('editCourseName').value = c.course_name;
            document.getElementById('editCourseDept').value = c.department;
            document.getElementById('editCourseDuration').value = c.duration_years;
            document.getElementById('editCourseSemesters').value = c.semesters;
            document.getElementById('editCourseCredits').value = c.credits;
            document.getElementById('editCourseStatus').value = c.status;
            document.getElementById('editModal').style.display = 'grid';
        }
        function closeModal(id) { document.getElementById(id).style.display = 'none'; }
    </script>
</body>
</html>
