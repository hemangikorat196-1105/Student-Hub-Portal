<?php
// ============================================================================
// Student Hub Portal - User Account Management & Status Toggle
// Practical 14 (Intermediate Extension: Active/Inactive Toggle), Practical 15
// ============================================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/response.php';

init_session();
require_admin();

$db = get_db_connection();

// Status Toggle Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    $userId = (int)($_POST['user_id'] ?? 0);
    $newStatus = in_array($_POST['new_status'] ?? '', ['active', 'inactive']) ? $_POST['new_status'] : 'active';

    if ($userId > 0) {
        $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $userId);
        $stmt->execute();
        $stmt->close();

        // Also update corresponding student status if exists
        $stmtS = $db->prepare("UPDATE students SET status = ? WHERE user_id = ?");
        $stmtS->bind_param("si", $newStatus, $userId);
        $stmtS->execute();
        $stmtS->close();

        log_audit_event("USER_STATUS_CHANGE", "Changed user ID $userId status to $newStatus");
        $_SESSION['flash_success'] = "User status updated to $newStatus.";

        if (is_api_request()) {
            json_response(200, true, "User status updated", ['userId' => $userId, 'status' => $newStatus]);
        }
    }
    header("Location: users.php");
    exit;
}

// Fetch all users
$users = $db->query("
    SELECT u.id, u.username, u.email, u.role, u.status, u.last_login, u.created_at, s.name AS student_name
    FROM users u
    LEFT JOIN students s ON s.user_id = u.id
    ORDER BY u.id ASC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Accounts - Student Hub Portal</title>
    <link rel="stylesheet" href="../../Style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        .page-container { max-width: 1100px; margin: 30px auto; padding: 25px; background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); font-family: 'Segoe UI', Tahoma, sans-serif; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
        .data-table { width: 100%; border-collapse: collapse; text-align: left; }
        .data-table th { background: #f1f5f9; padding: 12px; font-weight: 600; color: #1e293b; border-bottom: 2px solid #e2e8f0; }
        .data-table td { padding: 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
        .badge-active { background: #dcfce7; color: #166534; }
        .badge-inactive { background: #fee2e2; color: #991b1b; }
        .badge-admin { background: #e0f2fe; color: #0369a1; }
        .badge-student { background: #f1f5f9; color: #334155; }
        .btn-toggle { padding: 5px 10px; border-radius: 4px; font-size: 0.8rem; font-weight: 600; cursor: pointer; border: none; }
        .btn-deactivate { background: #fee2e2; color: #b91c1c; }
        .btn-activate { background: #dcfce7; color: #166534; }
        .btn-secondary { background: #64748b; color: #fff; padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
    </style>
</head>
<body>
    <main class="page-container">
        <div class="page-header">
            <div>
                <h2><i class="fa-solid fa-users-gear"></i> User Account Management</h2>
                <p>Manage user roles, login statuses, and active/inactive toggles.</p>
            </div>
            <a href="dashboard.php" class="btn-secondary"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
        </div>

        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div style="background: #dcfce7; color: #166534; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px;">
                <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($_SESSION['flash_success']) ?>
            </div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>

        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Linked Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= (int)$u['id'] ?></td>
                        <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                        <td><?= htmlspecialchars($u['student_name'] ?? 'System/Admin') ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><span class="badge badge-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
                        <td><span class="badge badge-<?= $u['status'] ?>"><?= ucfirst($u['status']) ?></span></td>
                        <td><small><?= $u['last_login'] ? htmlspecialchars($u['last_login']) : 'Never' ?></small></td>
                        <td>
                            <?php if ($u['id'] !== 1): // Prevent disabling root admin ?>
                                <form method="POST" action="users.php" style="display:inline;">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                    <input type="hidden" name="new_status" value="<?= ($u['status'] === 'active') ? 'inactive' : 'active' ?>">
                                    <?php if ($u['status'] === 'active'): ?>
                                        <button type="submit" class="btn-toggle btn-deactivate" onclick="return confirm('Deactivate user <?= htmlspecialchars(addslashes($u['username'])) ?>?');">
                                            <i class="fa-solid fa-ban"></i> Deactivate
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" class="btn-toggle btn-activate">
                                            <i class="fa-solid fa-check"></i> Activate
                                        </button>
                                    <?php endif; ?>
                                </form>
                            <?php else: ?>
                                <span style="color: #94a3b8; font-size: 0.8rem;">Protected</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div style="margin-top: 20px; color: #64748b; font-size: 0.9rem;">
            Total users registered: <strong><?= count($users) ?></strong>
        </div>
    </main>
</body>
</html>
