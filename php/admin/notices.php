<?php
// ============================================================================
// Student Hub Portal - Notice Management (Admin Module)
// Features: Publish, View, Edit, Archive, and Delete Campus Circulars
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
        header("Location: notices.php");
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $title       = trim($_POST['title'] ?? '');
        $content     = trim($_POST['content'] ?? '');
        $category    = trim($_POST['category'] ?? 'Academic');
        $publishDate = trim($_POST['publish_date'] ?? date('Y-m-d'));
        $importance  = in_array($_POST['importance'] ?? '', ['normal', 'urgent', 'high']) ? $_POST['importance'] : 'normal';
        $status      = in_array($_POST['status'] ?? '', ['published', 'archived', 'draft']) ? $_POST['status'] : 'published';
        $adminId     = (int)($_SESSION['user_id'] ?? 1);

        if (empty($title) || empty($content)) {
            $_SESSION['flash_error'] = "Notice title and content are required.";
        } else {
            $stmt = $db->prepare("
                INSERT INTO notices (title, content, category, publish_date, importance, status, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("ssssssi", $title, $content, $category, $publishDate, $importance, $status, $adminId);
            $stmt->execute();
            $stmt->close();

            log_audit_event("CREATE_NOTICE", "Admin published notice '$title'");
            $_SESSION['flash_success'] = "Notice published successfully.";
        }
        header("Location: notices.php");
        exit;
    }

    if ($action === 'update') {
        $id          = (int)($_POST['id'] ?? 0);
        $title       = trim($_POST['title'] ?? '');
        $content     = trim($_POST['content'] ?? '');
        $category    = trim($_POST['category'] ?? 'Academic');
        $publishDate = trim($_POST['publish_date'] ?? date('Y-m-d'));
        $importance  = in_array($_POST['importance'] ?? '', ['normal', 'urgent', 'high']) ? $_POST['importance'] : 'normal';
        $status      = in_array($_POST['status'] ?? '', ['published', 'archived', 'draft']) ? $_POST['status'] : 'published';

        if ($id > 0 && !empty($title) && !empty($content)) {
            $stmt = $db->prepare("
                UPDATE notices
                SET title = ?, content = ?, category = ?, publish_date = ?, importance = ?, status = ?
                WHERE id = ?
            ");
            $stmt->bind_param("ssssssi", $title, $content, $category, $publishDate, $importance, $status, $id);
            $stmt->execute();
            $stmt->close();

            log_audit_event("UPDATE_NOTICE", "Admin updated notice ID $id");
            $_SESSION['flash_success'] = "Notice updated successfully.";
        }
        header("Location: notices.php");
        exit;
    }

    if ($action === 'toggle_archive') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $db->prepare("UPDATE notices SET status = IF(status = 'published', 'archived', 'published') WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            log_audit_event("TOGGLE_NOTICE_ARCHIVE", "Admin changed notice ID $id status");
            $_SESSION['flash_success'] = "Notice status toggled.";
        }
        header("Location: notices.php");
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $db->prepare("DELETE FROM notices WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            log_audit_event("DELETE_NOTICE", "Admin deleted notice ID $id");
            $_SESSION['flash_success'] = "Notice deleted.";
        }
        header("Location: notices.php");
        exit;
    }
}

$notices = $db->query("SELECT * FROM notices ORDER BY publish_date DESC, id DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notice Management - Student Hub Portal</title>
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
        .badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 12px; font-size: 0.78rem; font-weight: 700; text-transform: capitalize; }
        .badge-urgent { background: #fee2e2; color: #b91c1c; }
        .badge-high { background: #fef3c7; color: #b45309; }
        .badge-normal { background: #f1f5f9; color: #475569; }
        .badge-published { background: #dcfce7; color: #15803d; }
        .badge-archived { background: #e2e8f0; color: #64748b; }
        .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: none; place-items: center; z-index: 1000; padding: 20px; }
        .modal-card { background: var(--card-bg, #fff); border-radius: 14px; max-width: 620px; width: 100%; padding: 25px 30px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); border: 1px solid var(--border-color, #e2e8f0); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { font-size: 0.85rem; font-weight: 600; margin-bottom: 4px; color: var(--text-color); }
        .form-group input, .form-group select, .form-group textarea { padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.92rem; background: var(--input-bg, #fff); color: var(--text-color); }
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
                <h1><i class="fa-solid fa-bullhorn" style="color:#f59e0b;"></i> Campus Circulars & Notice Management</h1>
                <p style="margin:4px 0 0; color:var(--text-muted); font-size:0.88rem;">Broadcast official college announcements, exam notices, and event alerts</p>
            </div>
            <div style="display:flex; gap:10px;">
                <a href="dashboard.php" class="btn-act btn-secondary"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
                <button type="button" class="btn-act btn-primary" onclick="openCreateModal()"><i class="fa-solid fa-plus"></i> Publish New Notice</button>
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
                        <th>Title</th>
                        <th>Category</th>
                        <th>Publish Date</th>
                        <th>Importance</th>
                        <th>Status</th>
                        <th style="text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($notices)): ?>
                        <tr><td colspan="6" style="text-align:center; padding:30px; color:var(--text-muted);">No campus notices published yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($notices as $n): ?>
                            <tr>
                                <td>
                                    <div style="font-weight:600; color:var(--text-color);"><?= htmlspecialchars($n['title']) ?></div>
                                    <small style="color:var(--text-muted);"><?= htmlspecialchars(substr($n['content'], 0, 90)) ?>...</small>
                                </td>
                                <td><span style="background:var(--primary-light, #f1f5f9); padding:3px 8px; border-radius:6px; font-size:0.8rem;"><?= htmlspecialchars($n['category']) ?></span></td>
                                <td><?= htmlspecialchars($n['publish_date']) ?></td>
                                <td>
                                    <span class="badge badge-<?= htmlspecialchars($n['importance']) ?>">
                                        <?= ucfirst($n['importance']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?= htmlspecialchars($n['status']) ?>">
                                        <?= ucfirst($n['status']) ?>
                                    </span>
                                </td>
                                <td style="text-align:center;">
                                    <div style="display:inline-flex; gap:6px;">
                                        <button type="button" class="btn-act btn-secondary" onclick='openEditModal(<?= json_encode($n) ?>)' title="Edit"><i class="fa-solid fa-pen"></i></button>
                                        <form method="POST" action="notices.php" style="display:inline;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="toggle_archive">
                                            <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                                            <button type="submit" class="btn-act btn-secondary" title="Archive / Unarchive"><i class="fa-solid <?= $n['status'] === 'published' ? 'fa-box-archive' : 'fa-bullhorn' ?>"></i></button>
                                        </form>
                                        <form method="POST" action="notices.php" style="display:inline;" onsubmit="return confirm('Delete notice: <?= htmlspecialchars($n['title'], ENT_QUOTES) ?>?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
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

    <!-- Create Modal -->
    <div id="createModal" class="modal-overlay">
        <div class="modal-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid var(--border-color); padding-bottom:10px;">
                <h3 style="margin:0;"><i class="fa-solid fa-bullhorn" style="color:var(--primary-color);"></i> Publish New Notice</h3>
                <button type="button" onclick="closeModal('createModal')" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
            </div>
            <form method="POST" action="notices.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">
                <div class="form-grid">
                    <div class="form-group full">
                        <label>Notice Title *</label>
                        <input type="text" name="title" required placeholder="e.g. Mid-Semester Examination Schedule">
                    </div>
                    <div class="form-group">
                        <label>Category *</label>
                        <select name="category" required>
                            <option value="Academic">Academic</option>
                            <option value="Examination">Examination</option>
                            <option value="Event">Event</option>
                            <option value="Holiday">Holiday</option>
                            <option value="Sports">Sports</option>
                            <option value="General">General</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Publish Date *</label>
                        <input type="date" name="publish_date" required value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label>Importance Level</label>
                        <select name="importance">
                            <option value="normal" selected>Normal</option>
                            <option value="high">High Priority</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="published" selected>Published</option>
                            <option value="draft">Draft</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                    <div class="form-group full">
                        <label>Notice Content *</label>
                        <textarea name="content" rows="4" required placeholder="Write the circular announcement body here..."></textarea>
                    </div>
                </div>
                <div style="margin-top:20px; display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" class="btn-act btn-secondary" onclick="closeModal('createModal')">Cancel</button>
                    <button type="submit" class="btn-act btn-primary">Publish Notice</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal-overlay">
        <div class="modal-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid var(--border-color); padding-bottom:10px;">
                <h3 style="margin:0;"><i class="fa-solid fa-pen-to-square" style="color:var(--primary-color);"></i> Edit Notice</h3>
                <button type="button" onclick="closeModal('editModal')" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
            </div>
            <form method="POST" action="notices.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="editNoticeId" value="">
                <div class="form-grid">
                    <div class="form-group full">
                        <label>Notice Title *</label>
                        <input type="text" name="title" id="editNoticeTitle" required>
                    </div>
                    <div class="form-group">
                        <label>Category *</label>
                        <select name="category" id="editNoticeCategory" required>
                            <option value="Academic">Academic</option>
                            <option value="Examination">Examination</option>
                            <option value="Event">Event</option>
                            <option value="Holiday">Holiday</option>
                            <option value="Sports">Sports</option>
                            <option value="General">General</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Publish Date *</label>
                        <input type="date" name="publish_date" id="editNoticeDate" required>
                    </div>
                    <div class="form-group">
                        <label>Importance Level</label>
                        <select name="importance" id="editNoticeImportance">
                            <option value="normal">Normal</option>
                            <option value="high">High Priority</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" id="editNoticeStatus">
                            <option value="published">Published</option>
                            <option value="draft">Draft</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                    <div class="form-group full">
                        <label>Notice Content *</label>
                        <textarea name="content" id="editNoticeContent" rows="4" required></textarea>
                    </div>
                </div>
                <div style="margin-top:20px; display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" class="btn-act btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" class="btn-act btn-primary">Update Notice</button>
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
        function openEditModal(n) {
            document.getElementById('editNoticeId').value = n.id;
            document.getElementById('editNoticeTitle').value = n.title;
            document.getElementById('editNoticeCategory').value = n.category;
            document.getElementById('editNoticeDate').value = n.publish_date;
            document.getElementById('editNoticeImportance').value = n.importance;
            document.getElementById('editNoticeStatus').value = n.status;
            document.getElementById('editNoticeContent').value = n.content;
            document.getElementById('editModal').style.display = 'grid';
        }
        function closeModal(id) { document.getElementById(id).style.display = 'none'; }
    </script>
</body>
</html>
