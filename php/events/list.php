<?php
// ============================================================================
// Student Hub Portal - Event Management: List & Controls
// Practical 12 (Event CRUD & Poster Uploads), Practical 14
// ============================================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/csrf.php';

init_session();
require_admin();

$db = get_db_connection();

$category = trim($_GET['category'] ?? '');
$status   = trim($_GET['status'] ?? '');
$search   = trim($_GET['search'] ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 8;

$where = ["1=1"];
$params = [];
$types = "";

if ($search !== '') {
    $where[] = "(title LIKE ? OR venue LIKE ? OR speaker LIKE ?)";
    $like = "%$search%";
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= "sss";
}

if ($category !== '') {
    $where[] = "category = ?";
    $params[] = $category;
    $types .= "s";
}

if ($status !== '') {
    $where[] = "status = ?";
    $params[] = $status;
    $types .= "s";
}

$whereSql = implode(" AND ", $where);
$countStmt = $db->prepare("SELECT COUNT(*) AS total FROM events e WHERE $whereSql");
if ($types !== "") {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalRecords = (int)$countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

$totalPages = max(1, (int)ceil($totalRecords / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$sql = "SELECT e.*, COUNT(r.id) AS registration_count
        FROM events e
        LEFT JOIN registrations r ON r.event_id = e.id
        WHERE $whereSql
        GROUP BY e.id
        ORDER BY e.event_date ASC
        LIMIT ? OFFSET ?";

$stmt = $db->prepare($sql);
$dataTypes = $types . 'ii';
$dataParams = array_merge($params, [$perPage, $offset]);
$stmt->bind_param($dataTypes, ...$dataParams);
$stmt->execute();
$events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$categories = ['Technical', 'Workshop', 'Seminar', 'Cultural', 'Sports', 'Career'];
$paginationQuery = http_build_query([
    'search' => $search,
    'category' => $category,
    'status' => $status
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Management - Student Hub Portal</title>
    <link rel="stylesheet" href="../../Style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        .crud-container { max-width: 1200px; margin: 30px auto; padding: 25px; background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); font-family: 'Segoe UI', Tahoma, sans-serif; }
        .crud-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 25px; }
        .filter-card { background: #f8fafc; padding: 18px; border-radius: 8px; margin-bottom: 25px; border: 1px solid #e2e8f0; }
        .filter-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) auto; gap: 15px; align-items: flex-end; }
        .filter-form input, .filter-form select { width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; }
        .data-table-wrapper { overflow-x: auto; }
        .data-table { width: 100%; border-collapse: collapse; text-align: left; }
        .data-table th { background: #f1f5f9; padding: 12px 14px; font-weight: 600; color: #1e293b; border-bottom: 2px solid #e2e8f0; }
        .data-table td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .data-table tr:hover { background: #f8fafc; }
        .poster-thumb { width: 48px; height: 48px; border-radius: 6px; object-fit: cover; background: #e2e8f0; display: inline-flex; align-items: center; justify-content: center; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
        .badge-open { background: #dcfce7; color: #166534; }
        .badge-upcoming { background: #e0f2fe; color: #0369a1; }
        .badge-closed { background: #f1f5f9; color: #475569; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }
        .action-btns { display: flex; gap: 6px; }
        .action-btn { padding: 6px 10px; border-radius: 4px; font-size: 0.8rem; text-decoration: none; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; }
        .btn-edit { background: #e0f2fe; color: #0369a1; }
        .btn-delete { background: #fee2e2; color: #b91c1c; }
        .btn-primary { background: #2563eb; color: #fff; padding: 9px 16px; border-radius: 6px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; border: none; }
        .btn-secondary { background: #64748b; color: #fff; padding: 9px 16px; border-radius: 6px; text-decoration: none; font-weight: 600; }
        .pagination { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-top: 20px; color: #64748b; }
        .page-links { display: flex; flex-wrap: wrap; gap: 6px; }
        .page-link { padding: 7px 11px; border: 1px solid #cbd5e1; border-radius: 6px; color: #334155; text-decoration: none; }
        .page-link.active { color: #fff; background: #2563eb; border-color: #2563eb; }
    </style>
</head>
<body>
    <main class="crud-container">
        <div class="crud-header">
            <div>
                <h2><i class="fa-solid fa-calendar-check"></i> Event Management System</h2>
                <p>Create, update, manage event posters, and track student registrations.</p>
            </div>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="create.php" class="btn-primary"><i class="fa-solid fa-plus"></i> Create New Event</a>
                <a href="../admin/dashboard.php" class="btn-secondary"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
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
                    <label for="search">Keyword Search</label>
                    <input type="text" id="search" name="search" placeholder="Event title, venue, speaker..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div>
                    <label for="category">Category</label>
                    <select id="category" name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat ?>" <?= ($category === $cat) ? 'selected' : '' ?>><?= $cat ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">All Statuses</option>
                        <option value="open" <?= ($status === 'open') ? 'selected' : '' ?>>Registrations Open</option>
                        <option value="upcoming" <?= ($status === 'upcoming') ? 'selected' : '' ?>>Upcoming</option>
                        <option value="closed" <?= ($status === 'closed') ? 'selected' : '' ?>>Closed</option>
                        <option value="cancelled" <?= ($status === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                <div style="display: flex; gap: 8px;">
                    <button type="submit" class="btn-primary" style="padding: 9px 14px;"><i class="fa-solid fa-filter"></i> Filter</button>
                    <a href="list.php" class="btn-secondary" style="padding: 9px 14px;">Reset</a>
                </div>
            </form>
        </div>

        <div class="data-table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Poster</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Date & Time</th>
                        <th>Venue</th>
                        <th>Speaker / Mentor</th>
                        <th>Capacity</th>
                        <th>Registrations</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($events)): ?>
                        <tr><td colspan="10" style="text-align: center; padding: 30px; color: #64748b;">No events found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($events as $e): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($e['poster']) && file_exists(__DIR__ . '/../../assets/uploads/event_posters/' . $e['poster'])): ?>
                                        <img src="../../assets/uploads/event_posters/<?= htmlspecialchars($e['poster']) ?>" alt="Poster" class="poster-thumb">
                                    <?php else: ?>
                                        <div class="poster-thumb"><i class="fa-solid fa-image" style="color: #94a3b8;"></i></div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= htmlspecialchars($e['title']) ?></strong></td>
                                <td><span class="badge" style="background: #f1f5f9; color: #334155;"><?= htmlspecialchars($e['category']) ?></span></td>
                                <td>
                                    <div><?= htmlspecialchars($e['event_date']) ?></div>
                                    <small style="color: #64748b;"><?= htmlspecialchars($e['time']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($e['venue']) ?></td>
                                <td><?= htmlspecialchars($e['speaker'] ?? 'TBA') ?></td>
                                <td><?= (int)$e['seats'] ?> seats</td>
                                <td><strong><?= (int)$e['registration_count'] ?></strong></td>
                                <td><span class="badge badge-<?= $e['status'] ?>"><?= ucfirst($e['status']) ?></span></td>
                                <td>
                                    <div class="action-btns">
                                        <a href="update.php?id=<?= (int)$e['id'] ?>" class="action-btn btn-edit" title="Edit Event">
                                            <i class="fa-solid fa-pen-to-square"></i> Edit
                                        </a>
                                        <form method="post" action="delete.php" onsubmit="return confirm('Delete this event?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int)$e['id'] ?>">
                                            <button type="submit" class="action-btn btn-delete" title="Delete Event">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="pagination">
            <span>Showing <?= min($totalRecords, $offset + 1) ?> to <?= min($totalRecords, $offset + count($events)) ?> of <strong><?= $totalRecords ?></strong> events</span>
            <?php if ($totalPages > 1): ?>
                <nav class="page-links" aria-label="Event list pages">
                    <?php if ($page > 1): ?>
                        <a class="page-link" href="?<?= htmlspecialchars($paginationQuery, ENT_QUOTES, 'UTF-8') ?>&amp;page=<?= $page - 1 ?>">Previous</a>
                    <?php endif; ?>
                    <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?>
                        <a class="page-link <?= $pageNumber === $page ? 'active' : '' ?>"
                           href="?<?= htmlspecialchars($paginationQuery, ENT_QUOTES, 'UTF-8') ?>&amp;page=<?= $pageNumber ?>"
                           <?= $pageNumber === $page ? 'aria-current="page"' : '' ?>><?= $pageNumber ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <a class="page-link" href="?<?= htmlspecialchars($paginationQuery, ENT_QUOTES, 'UTF-8') ?>&amp;page=<?= $page + 1 ?>">Next</a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
