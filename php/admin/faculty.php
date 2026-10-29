<?php
// ============================================================================
// Student Hub Portal - Faculty Management (Admin Module)
// Features: List, Search, Add, Edit, Status Toggle, and Delete Faculty Records
// ============================================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/csrf.php';

init_session();
require_admin();

$db = get_db_connection();
$errors = [];

// Handle POST actions (Add, Edit, Toggle, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = "Invalid form submission or token expired.";
        header("Location: faculty.php");
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $facultyId     = strtoupper(trim($_POST['faculty_id'] ?? ''));
        $name          = trim($_POST['name'] ?? '');
        $email         = strtolower(trim($_POST['email'] ?? ''));
        $mobile        = trim($_POST['mobile'] ?? '');
        $department    = trim($_POST['department'] ?? 'Computer Science & Engineering');
        $designation   = trim($_POST['designation'] ?? 'Assistant Professor');
        $qualification = trim($_POST['qualification'] ?? 'M.Tech');
        $status        = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

        if (empty($facultyId) || empty($name) || empty($email) || empty($mobile)) {
            $_SESSION['flash_error'] = "All mandatory faculty fields are required.";
        } else {
            // Check uniqueness
            $chk = $db->prepare("SELECT id FROM faculty WHERE faculty_id = ? OR email = ? LIMIT 1");
            $chk->bind_param("ss", $facultyId, $email);
            $chk->execute();
            if ($chk->get_result()->num_rows > 0) {
                $_SESSION['flash_error'] = "A faculty member with this Faculty ID or Email already exists.";
            } else {
                $stmt = $db->prepare("
                    INSERT INTO faculty (faculty_id, name, email, mobile, department, designation, qualification, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->bind_param("ssssssss", $facultyId, $name, $email, $mobile, $department, $designation, $qualification, $status);
                $stmt->execute();
                $stmt->close();

                log_audit_event("CREATE_FACULTY", "Admin created faculty $facultyId ($name)");
                $_SESSION['flash_success'] = "Faculty member $facultyId added successfully.";
            }
            $chk->close();
        }
        header("Location: faculty.php");
        exit;
    }

    if ($action === 'update') {
        $id            = (int)($_POST['id'] ?? 0);
        $name          = trim($_POST['name'] ?? '');
        $email         = strtolower(trim($_POST['email'] ?? ''));
        $mobile        = trim($_POST['mobile'] ?? '');
        $department    = trim($_POST['department'] ?? '');
        $designation   = trim($_POST['designation'] ?? '');
        $qualification = trim($_POST['qualification'] ?? '');
        $status        = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

        if ($id > 0 && !empty($name) && !empty($email)) {
            $stmt = $db->prepare("
                UPDATE faculty
                SET name = ?, email = ?, mobile = ?, department = ?, designation = ?, qualification = ?, status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->bind_param("sssssssi", $name, $email, $mobile, $department, $designation, $qualification, $status, $id);
            $stmt->execute();
            $stmt->close();

            log_audit_event("UPDATE_FACULTY", "Admin updated faculty ID $id ($name)");
            $_SESSION['flash_success'] = "Faculty record updated successfully.";
        }
        header("Location: faculty.php");
        exit;
    }

    if ($action === 'toggle_status') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $db->prepare("UPDATE faculty SET status = IF(status = 'active', 'inactive', 'active'), updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            log_audit_event("TOGGLE_FACULTY_STATUS", "Admin toggled status for faculty ID $id");
            $_SESSION['flash_success'] = "Faculty status toggled successfully.";
        }
        header("Location: faculty.php");
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $db->prepare("DELETE FROM faculty WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            log_audit_event("DELETE_FACULTY", "Admin deleted faculty ID $id");
            $_SESSION['flash_success'] = "Faculty record removed.";
        }
        header("Location: faculty.php");
        exit;
    }
}

// Fetch Faculty List with search filter
$search = trim($_GET['search'] ?? '');
$deptFilter = trim($_GET['department'] ?? '');

$where = ["1=1"];
$params = [];
$types = "";

if ($search !== '') {
    $where[] = "(faculty_id LIKE ? OR name LIKE ? OR email LIKE ? OR designation LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like, $like, $like];
    $types = "ssss";
}
if ($deptFilter !== '') {
    $where[] = "department = ?";
    $params[] = $deptFilter;
    $types .= "s";
}

$sql = "SELECT * FROM faculty WHERE " . implode(" AND ", $where) . " ORDER BY id ASC";
$stmt = $db->prepare($sql);
if ($types !== "") {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$facultyList = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Management - Student Hub Portal</title>
    <link rel="stylesheet" href="../../Style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="../../script.js" defer></script>
    <style>
        .admin-wrap { max-width: 1300px; margin: 30px auto 60px; padding: 25px 30px; background: var(--card-bg, #fff); border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); border: 1px solid var(--border-color, #e2e8f0); font-family: 'Segoe UI', Tahoma, sans-serif; }
        .admin-header-bar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 25px; padding-bottom: 20px; border-bottom: 1px solid var(--border-color, #e2e8f0); }
        .admin-header-bar h1 { font-size: 1.5rem; margin: 0; color: var(--text-color, #0f172a); display: flex; align-items: center; gap: 12px; }
        .btn-act { padding: 8px 16px; border-radius: 6px; font-weight: 600; text-decoration: none; border: 1px solid transparent; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-size: 0.88rem; }
        .btn-primary { background: var(--primary-color, #2563eb); color: #fff; }
        .btn-secondary { background: #f1f5f9; color: #334155; }
        .table-wrap { width: 100%; overflow-x: auto; border: 1px solid var(--border-color, #e2e8f0); border-radius: 10px; margin-top: 20px; }
        .admin-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem; }
        .admin-table th { background: var(--primary-light, #f1f5f9); padding: 12px 14px; font-weight: 700; color: var(--text-color, #334155); border-bottom: 2px solid var(--border-color, #e2e8f0); }
        .admin-table td { padding: 12px 14px; border-bottom: 1px solid var(--border-color, #f1f5f9); color: var(--text-color, #1e293b); vertical-align: middle; }
        .badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 12px; font-size: 0.78rem; font-weight: 700; }
        .badge-active { background: #dcfce7; color: #15803d; }
        .badge-inactive { background: #fee2e2; color: #b91c1c; }
        .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: none; place-items: center; z-index: 1000; padding: 20px; }
        .modal-card { background: var(--card-bg, #fff); border-radius: 14px; max-width: 600px; width: 100%; padding: 25px 30px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); border: 1px solid var(--border-color, #e2e8f0); }
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
                <h1><i class="fa-solid fa-chalkboard-user" style="color:#8b5cf6;"></i> Faculty Management</h1>
                <p style="margin:4px 0 0; color:var(--text-muted); font-size:0.88rem;">Manage institutional mentors, professors, department allocations, and teaching staff</p>
            </div>
            <div style="display:flex; gap:10px;">
                <a href="dashboard.php" class="btn-act btn-secondary"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
                <button type="button" class="btn-act btn-primary" onclick="openCreateModal()"><i class="fa-solid fa-plus"></i> Add Faculty Member</button>
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

        <!-- Search Bar -->
        <form method="GET" style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:20px;">
            <input type="text" name="search" placeholder="Search by name, ID, or designation..." value="<?= htmlspecialchars($search) ?>" style="flex:1; min-width:240px; padding:9px 12px; border:1px solid var(--border-color); border-radius:6px;">
            <select name="department" style="padding:9px 12px; border:1px solid var(--border-color); border-radius:6px;">
                <option value="">All Departments</option>
                <option value="Computer Science & Engineering" <?= $deptFilter === 'Computer Science & Engineering' ? 'selected' : '' ?>>Computer Science & Engineering</option>
                <option value="Information Technology" <?= $deptFilter === 'Information Technology' ? 'selected' : '' ?>>Information Technology</option>
                <option value="Artificial Intelligence & Data Science" <?= $deptFilter === 'Artificial Intelligence & Data Science' ? 'selected' : '' ?>>Artificial Intelligence & Data Science</option>
                <option value="Electronics & Communication" <?= $deptFilter === 'Electronics & Communication' ? 'selected' : '' ?>>Electronics & Communication</option>
            </select>
            <button type="submit" class="btn-act btn-primary"><i class="fa-solid fa-magnifying-glass"></i> Filter</button>
            <a href="faculty.php" class="btn-act btn-secondary">Reset</a>
        </form>

        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Faculty ID</th>
                        <th>Name</th>
                        <th>Designation</th>
                        <th>Department</th>
                        <th>Contact</th>
                        <th>Qualification</th>
                        <th>Status</th>
                        <th style="text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($facultyList)): ?>
                        <tr><td colspan="8" style="text-align:center; padding:30px; color:var(--text-muted);">No faculty records found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($facultyList as $fac): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($fac['faculty_id']) ?></strong></td>
                                <td><?= htmlspecialchars($fac['name']) ?></td>
                                <td><?= htmlspecialchars($fac['designation']) ?></td>
                                <td><?= htmlspecialchars($fac['department']) ?></td>
                                <td>
                                    <div><?= htmlspecialchars($fac['email']) ?></div>
                                    <small style="color:var(--text-muted);"><i class="fa-solid fa-phone"></i> <?= htmlspecialchars($fac['mobile']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($fac['qualification']) ?></td>
                                <td>
                                    <span class="badge <?= $fac['status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>">
                                        <?= ucfirst($fac['status']) ?>
                                    </span>
                                </td>
                                <td style="text-align:center;">
                                    <div style="display:inline-flex; gap:6px;">
                                        <button type="button" class="btn-act btn-secondary" onclick='openEditModal(<?= json_encode($fac) ?>)' title="Edit"><i class="fa-solid fa-pen"></i></button>
                                        <form method="POST" action="faculty.php" style="display:inline;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="id" value="<?= (int)$fac['id'] ?>">
                                            <button type="submit" class="btn-act btn-secondary" title="Toggle Status"><i class="fa-solid <?= $fac['status'] === 'active' ? 'fa-ban' : 'fa-check' ?>"></i></button>
                                        </form>
                                        <form method="POST" action="faculty.php" style="display:inline;" onsubmit="return confirm('Delete faculty member <?= htmlspecialchars($fac['name'], ENT_QUOTES) ?>?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$fac['id'] ?>">
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

    <!-- Modal: Create Faculty -->
    <div id="createModal" class="modal-overlay">
        <div class="modal-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid var(--border-color); padding-bottom:10px;">
                <h3 style="margin:0;"><i class="fa-solid fa-user-plus" style="color:var(--primary-color);"></i> Add Faculty Member</h3>
                <button type="button" onclick="closeModal('createModal')" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
            </div>
            <form method="POST" action="faculty.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Faculty ID *</label>
                        <input type="text" name="faculty_id" required placeholder="e.g. FAC009">
                    </div>
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="name" required placeholder="Dr. / Prof. Name">
                    </div>
                    <div class="form-group">
                        <label>Email Address *</label>
                        <input type="email" name="email" required placeholder="faculty@college.edu">
                    </div>
                    <div class="form-group">
                        <label>Mobile Number *</label>
                        <input type="tel" name="mobile" required placeholder="98XXXXXXXX">
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
                    <div class="form-group">
                        <label>Designation *</label>
                        <input type="text" name="designation" required placeholder="e.g. Associate Professor">
                    </div>
                    <div class="form-group">
                        <label>Qualification *</label>
                        <input type="text" name="qualification" required placeholder="e.g. Ph.D. in CSE">
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
                    <button type="submit" class="btn-act btn-primary">Save Faculty</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Edit Faculty -->
    <div id="editModal" class="modal-overlay">
        <div class="modal-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid var(--border-color); padding-bottom:10px;">
                <h3 style="margin:0;"><i class="fa-solid fa-user-pen" style="color:var(--primary-color);"></i> Edit Faculty Details</h3>
                <button type="button" onclick="closeModal('editModal')" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
            </div>
            <form method="POST" action="faculty.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="editFacId" value="">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Faculty ID (Fixed)</label>
                        <input type="text" id="editFacCode" disabled style="background:#f1f5f9;">
                    </div>
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="name" id="editFacName" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address *</label>
                        <input type="email" name="email" id="editFacEmail" required>
                    </div>
                    <div class="form-group">
                        <label>Mobile Number *</label>
                        <input type="tel" name="mobile" id="editFacMobile" required>
                    </div>
                    <div class="form-group">
                        <label>Department *</label>
                        <select name="department" id="editFacDept" required>
                            <option value="Computer Science & Engineering">Computer Science & Engineering</option>
                            <option value="Information Technology">Information Technology</option>
                            <option value="Artificial Intelligence & Data Science">Artificial Intelligence & Data Science</option>
                            <option value="Electronics & Communication">Electronics & Communication</option>
                            <option value="Computer Applications">Computer Applications</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Designation *</label>
                        <input type="text" name="designation" id="editFacDesig" required>
                    </div>
                    <div class="form-group">
                        <label>Qualification *</label>
                        <input type="text" name="qualification" id="editFacQual" required>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" id="editFacStatus">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div style="margin-top:20px; display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" class="btn-act btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" class="btn-act btn-primary">Update Faculty</button>
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
        function openEditModal(f) {
            document.getElementById('editFacId').value = f.id;
            document.getElementById('editFacCode').value = f.faculty_id;
            document.getElementById('editFacName').value = f.name;
            document.getElementById('editFacEmail').value = f.email;
            document.getElementById('editFacMobile').value = f.mobile;
            document.getElementById('editFacDept').value = f.department;
            document.getElementById('editFacDesig').value = f.designation;
            document.getElementById('editFacQual').value = f.qualification;
            document.getElementById('editFacStatus').value = f.status;
            document.getElementById('editModal').style.display = 'grid';
        }
        function closeModal(id) { document.getElementById(id).style.display = 'none'; }
    </script>
</body>
</html>
