<?php
// ============================================================================
// Student Hub Portal - Security Audit Trail Viewer
// Practical 14 (Audit Logging & Traceability), Practical 15
// ============================================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';

init_session();
require_admin();

$db = get_db_connection();

$actionFilter = trim($_GET['action'] ?? '');
$search       = trim($_GET['search'] ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$limit        = 15;
$offset       = ($page - 1) * $limit;

$where = ["1=1"];
$params = [];
$types = "";

if ($actionFilter !== '') {
    $where[] = "a.action = ?";
    $params[] = $actionFilter;
    $types .= "s";
}

if ($search !== '') {
    $where[] = "(a.details LIKE ? OR a.ip_address LIKE ? OR u.username LIKE ?)";
    $like = "%$search%";
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= "sss";
}

$whereSql = implode(" AND ", $where);

// Count
$countSql = "SELECT COUNT(*) AS c FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id WHERE $whereSql";
$stmtC = $db->prepare($countSql);
if ($types !== "") {
    $stmtC->bind_param($types, ...$params);
}
$stmtC->execute();
$totalLogs = (int)$stmtC->get_result()->fetch_assoc()['c'];
$stmtC->close();
$totalPages = max(1, ceil($totalLogs / $limit));

// Fetch
$sql = "
    SELECT a.id, a.action, a.details, a.ip_address, a.user_agent, a.created_at,
           COALESCE(u.username, 'Guest / Anonymous') AS username, u.role
    FROM audit_logs a
    LEFT JOIN users u ON a.user_id = u.id
    WHERE $whereSql
    ORDER BY a.created_at DESC
    LIMIT ? OFFSET ?
";
$stmt = $db->prepare($sql);
$typesData = $types . "ii";
$paramsData = array_merge($params, [$limit, $offset]);
$stmt->bind_param($typesData, ...$paramsData);
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$distinctActions = $db->query("SELECT DISTINCT action FROM audit_logs ORDER BY action ASC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Audit Logs - Student Hub Portal</title>
    <link rel="stylesheet" href="../../Style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        .audit-container { max-width: 1200px; margin: 30px auto; padding: 25px; background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); font-family: 'Segoe UI', Tahoma, sans-serif; }
        .audit-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
        .filter-form { display: flex; gap: 12px; margin-bottom: 25px; flex-wrap: wrap; }
        .filter-form input, .filter-form select { padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; }
        .data-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem; }
        .data-table th { background: #f1f5f9; padding: 12px; font-weight: 600; color: #1e293b; border-bottom: 2px solid #e2e8f0; }
        .data-table td { padding: 12px; border-bottom: 1px solid #f1f5f9; }
        .data-table tr:hover { background: #f8fafc; }
        .badge-action { padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 700; background: #e0f2fe; color: #0369a1; }
        .badge-action.warn { background: #fee2e2; color: #991b1b; }
        .pagination { display: flex; justify-content: space-between; align-items: center; margin-top: 25px; }
        .page-links { display: flex; gap: 6px; }
        .page-link { padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 4px; text-decoration: none; color: #334155; }
        .page-link.active { background: #2563eb; color: #fff; border-color: #2563eb; }
        .btn-primary { background: #2563eb; color: #fff; padding: 8px 14px; border-radius: 6px; border: none; font-weight: 600; cursor: pointer; }
        .btn-secondary { background: #64748b; color: #fff; padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <main class="audit-container">
        <div class="audit-header">
            <div>
                <h2><i class="fa-solid fa-clock-rotate-left" style="color: #d97706;"></i> Security Audit Logs</h2>
                <p>Complete traceability of authentication events, CRUD operations, and unauthorized access attempts.</p>
            </div>
            <a href="dashboard.php" class="btn-secondary"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
        </div>

        <form method="GET" action="audit_logs.php" class="filter-form">
            <input type="text" name="search" placeholder="Search actor, details, IP..." value="<?= htmlspecialchars($search) ?>" style="flex: 1; min-width: 200px;">
            <select name="action">
                <option value="">All Action Types</option>
                <?php foreach ($distinctActions as $act): ?>
                    <option value="<?= htmlspecialchars($act['action']) ?>" <?= ($actionFilter === $act['action']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($act['action']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-primary"><i class="fa-solid fa-filter"></i> Filter Logs</button>
            <a href="audit_logs.php" class="btn-secondary">Reset</a>
        </form>

        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Timestamp</th>
                        <th>Action</th>
                        <th>User / Actor</th>
                        <th>Details</th>
                        <th>IP Address</th>
                        <th>User Agent</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="7" style="text-align: center; padding: 30px; color: #64748b;">No audit records found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($logs as $l):
                            $isWarn = (strpos($l['action'], 'UNAUTHORIZED') !== false || strpos($l['action'], 'FAILED') !== false);
                        ?>
                            <tr>
                                <td>#<?= (int)$l['id'] ?></td>
                                <td><small><?= htmlspecialchars($l['created_at']) ?></small></td>
                                <td><span class="badge-action <?= $isWarn ? 'warn' : '' ?>"><?= htmlspecialchars($l['action']) ?></span></td>
                                <td><strong><?= htmlspecialchars($l['username']) ?></strong></td>
                                <td><?= htmlspecialchars($l['details']) ?></td>
                                <td><code><?= htmlspecialchars($l['ip_address'] ?? '-') ?></code></td>
                                <td><small style="color: #64748b; font-size: 0.75rem;" title="<?= htmlspecialchars($l['user_agent'] ?? '') ?>"><?= htmlspecialchars(substr($l['user_agent'] ?? '', 0, 35)) ?>...</small></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="pagination">
            <span style="color: #64748b; font-size: 0.9rem;">Total Audit Entries: <strong><?= $totalLogs ?></strong></span>
            <?php if ($totalPages > 1): ?>
                <div class="page-links">
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <a href="?page=<?= $p ?>&action=<?= urlencode($actionFilter) ?>&search=<?= urlencode($search) ?>" class="page-link <?= ($p === $page) ? 'active' : '' ?>"><?= $p ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
