<?php
// ============================================================================
// Student Hub Portal - Admin Dashboard (Dynamic Metrics & RBAC)
// Practical 10, 14, 15
// ============================================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';

init_session();
require_admin(); // Restrict to admin users only (Practical 10 & 14)

$db = get_db_connection();
$currentUser = current_user();

// 1. Dynamic Metric Aggregation
$totalUsers = (int)$db->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()['c'];
$totalStudents = (int)$db->query("SELECT COUNT(*) AS c FROM students")->fetch_assoc()['c'];
$activeStudents = (int)$db->query("SELECT COUNT(*) AS c FROM students WHERE status = 'active'")->fetch_assoc()['c'];
$totalEvents = (int)$db->query("SELECT COUNT(*) AS c FROM events")->fetch_assoc()['c'];
$openEvents = (int)$db->query("SELECT COUNT(*) AS c FROM events WHERE status = 'open'")->fetch_assoc()['c'];
$totalRegistrations = (int)$db->query("SELECT COUNT(*) AS c FROM registrations")->fetch_assoc()['c'];
$totalAuditLogs = (int)$db->query("SELECT COUNT(*) AS c FROM audit_logs")->fetch_assoc()['c'];

// 2. Latest 5 Registrations (Join students & events)
$recentRegsSql = "
    SELECT r.id, s.student_id, s.name AS student_name, e.title AS event_title, e.category, r.registration_date, r.status
    FROM registrations r
    INNER JOIN students s ON r.student_id = s.id
    INNER JOIN events e ON r.event_id = e.id
    ORDER BY r.registration_date DESC
    LIMIT 5
";
$recentRegistrations = $db->query($recentRegsSql)->fetch_all(MYSQLI_ASSOC);

// 3. Latest 5 Audit Logs
$recentLogsSql = "
    SELECT a.id, a.action, a.details, a.ip_address, a.created_at, COALESCE(u.username, 'System') AS username
    FROM audit_logs a
    LEFT JOIN users u ON a.user_id = u.id
    ORDER BY a.created_at DESC
    LIMIT 5
";
$recentLogs = $db->query($recentLogsSql)->fetch_all(MYSQLI_ASSOC);

// 4. Course Distribution Data for Analytics Chart
$courseDistSql = "SELECT course, COUNT(*) AS cnt FROM students GROUP BY course ORDER BY cnt DESC";
$courseDist = $db->query($courseDistSql)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Student Hub Portal</title>
    <link rel="stylesheet" href="../../Style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        .dashboard-container { max-width: 1280px; margin: 30px auto; padding: 25px; font-family: 'Segoe UI', Tahoma, sans-serif; }
        .dashboard-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 25px; padding-bottom: 20px; border-bottom: 1px solid #e2e8f0; }
        .user-pill { background: #f1f5f9; padding: 8px 16px; border-radius: 20px; display: inline-flex; align-items: center; gap: 8px; font-weight: 600; color: #1e293b; }
        
        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; margin-bottom: 30px; }
        .stat-card { background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-left: 5px solid #2563eb; display: flex; align-items: center; justify-content: space-between; }
        .stat-card.green { border-left-color: #16a34a; }
        .stat-card.purple { border-left-color: #9333ea; }
        .stat-card.amber { border-left-color: #d97706; }
        .stat-card.slate { border-left-color: #475569; }
        .stat-val { font-size: 2rem; font-weight: 700; color: #0f172a; line-height: 1.1; margin-top: 4px; }
        .stat-lbl { font-size: 0.85rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-icon { font-size: 2.2rem; opacity: 0.2; }

        /* Quick Action Bar */
        .quick-nav { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 30px; }
        .quick-link { background: #fff; padding: 12px 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); text-decoration: none; color: #1e293b; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; border: 1px solid #e2e8f0; transition: transform 0.15s ease; }
        .quick-link:hover { transform: translateY(-2px); border-color: #2563eb; color: #2563eb; }

        /* Tables Layout */
        .panels-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 30px; }
        @media (max-width: 900px) { .panels-grid { grid-template-columns: 1fr; } }
        .panel { background: #fff; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); padding: 20px; }
        .panel-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9; }
        .panel-title { font-size: 1.1rem; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 8px; }
        
        .mini-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem; }
        .mini-table th { background: #f8fafc; padding: 10px; color: #475569; font-weight: 600; border-bottom: 1px solid #e2e8f0; }
        .mini-table td { padding: 10px; border-bottom: 1px solid #f1f5f9; }
        
        .chart-bar-row { margin-bottom: 12px; }
        .chart-bar-lbl { display: flex; justify-content: space-between; font-size: 0.85rem; font-weight: 600; margin-bottom: 4px; color: #334155; }
        .chart-bar-track { background: #e2e8f0; height: 10px; border-radius: 5px; overflow: hidden; }
        .chart-bar-fill { background: #2563eb; height: 100%; border-radius: 5px; }

        .btn-sm { padding: 5px 10px; border-radius: 4px; font-size: 0.8rem; text-decoration: none; font-weight: 600; }
        .btn-primary { background: #2563eb; color: #fff; }
    </style>
</head>
<body>
    <main class="dashboard-container">
        <div class="dashboard-header">
            <div>
                <h1><i class="fa-solid fa-gauge-high" style="color: #2563eb;"></i> Administrator Dashboard</h1>
                <p>System Overview, Real-time Aggregated Statistics & Security Audit Trail</p>
            </div>
            <div style="display: flex; gap: 12px; align-items: center;">
                <div class="user-pill">
                    <i class="fa-solid fa-user-shield" style="color: #2563eb;"></i>
                    <?= htmlspecialchars($currentUser['username']) ?> (Admin)
                </div>
                <a href="../auth/logout.php" class="quick-link" style="color: #dc2626; border-color: #fee2e2;">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
                </a>
            </div>
        </div>

        <!-- Quick Navigation -->
        <div class="quick-nav">
            <a href="../students/list.php" class="quick-link">
                <i class="fa-solid fa-user-graduate" style="color: #2563eb;"></i> Student Records (CRUD)
            </a>
            <a href="../events/list.php" class="quick-link">
                <i class="fa-solid fa-calendar-days" style="color: #16a34a;"></i> Event Management
            </a>
            <a href="users.php" class="quick-link">
                <i class="fa-solid fa-users-gear" style="color: #9333ea;"></i> User Accounts & Status
            </a>
            <a href="audit_logs.php" class="quick-link">
                <i class="fa-solid fa-clock-rotate-left" style="color: #d97706;"></i> Security Audit Logs
            </a>
            <a href="../../events.html" class="quick-link">
                <i class="fa-solid fa-bullhorn" style="color: #0284c7;"></i> Portal Events View
            </a>
            <a href="../../json-data.html" class="quick-link">
                <i class="fa-solid fa-database" style="color: #64748b;"></i> JSON Data Explorer
            </a>
        </div>

        <!-- Metric Summary Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div>
                    <div class="stat-lbl">Registered Students</div>
                    <div class="stat-val"><?= $totalStudents ?></div>
                    <small style="color: #16a34a; font-weight: 600;"><?= $activeStudents ?> Active Records</small>
                </div>
                <i class="fa-solid fa-user-graduate stat-icon"></i>
            </div>

            <div class="stat-card green">
                <div>
                    <div class="stat-lbl">Campus Events</div>
                    <div class="stat-val"><?= $totalEvents ?></div>
                    <small style="color: #0369a1; font-weight: 600;"><?= $openEvents ?> Open for Registration</small>
                </div>
                <i class="fa-solid fa-calendar-check stat-icon"></i>
            </div>

            <div class="stat-card purple">
                <div>
                    <div class="stat-lbl">Event Registrations</div>
                    <div class="stat-val"><?= $totalRegistrations ?></div>
                    <small style="color: #64748b;">Confirmed Bookings</small>
                </div>
                <i class="fa-solid fa-ticket stat-icon"></i>
            </div>

            <div class="stat-card amber">
                <div>
                    <div class="stat-lbl">User Accounts</div>
                    <div class="stat-val"><?= $totalUsers ?></div>
                    <small style="color: #d97706;">Admin & Student Logins</small>
                </div>
                <i class="fa-solid fa-users stat-icon"></i>
            </div>

            <div class="stat-card slate">
                <div>
                    <div class="stat-lbl">Audit Log Entries</div>
                    <div class="stat-val"><?= $totalAuditLogs ?></div>
                    <small style="color: #475569;">Recorded Actions</small>
                </div>
                <i class="fa-solid fa-shield-halved stat-icon"></i>
            </div>
        </div>

        <!-- Dual Panels: Recent Registrations & Course Analytics -->
        <div class="panels-grid">
            <!-- Recent Registrations -->
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title"><i class="fa-solid fa-list-check" style="color: #2563eb;"></i> Recent Registrations</div>
                    <a href="../students/list.php" class="btn-sm btn-primary">View All</a>
                </div>
                <table class="mini-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Event</th>
                            <th>Category</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentRegistrations as $r): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($r['student_name']) ?></strong><br>
                                    <small style="color: #64748b;"><?= htmlspecialchars($r['student_id']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($r['event_title']) ?></td>
                                <td><span style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem;"><?= htmlspecialchars($r['category']) ?></span></td>
                                <td><small><?= date('M d, H:i', strtotime($r['registration_date'])) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Academic Course Distribution -->
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title"><i class="fa-solid fa-chart-pie" style="color: #16a34a;"></i> Student Enrollment by Course</div>
                    <span style="font-size: 0.85rem; color: #64748b;">Total: <?= $totalStudents ?></span>
                </div>
                <div style="margin-top: 15px;">
                    <?php foreach ($courseDist as $cd):
                        $pct = $totalStudents > 0 ? round(($cd['cnt'] / $totalStudents) * 100) : 0;
                    ?>
                        <div class="chart-bar-row">
                            <div class="chart-bar-lbl">
                                <span><?= htmlspecialchars($cd['course']) ?></span>
                                <span><?= (int)$cd['cnt'] ?> (<?= $pct ?>%)</span>
                            </div>
                            <div class="chart-bar-track">
                                <div class="chart-bar-fill" style="width: <?= $pct ?>%;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Recent Audit Log Panel -->
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title"><i class="fa-solid fa-clock-rotate-left" style="color: #d97706;"></i> Recent Security & System Audit Logs</div>
                <a href="audit_logs.php" class="btn-sm btn-primary">Full Audit Trail</a>
            </div>
            <table class="mini-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Details</th>
                        <th>IP Address</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentLogs as $log): ?>
                        <tr>
                            <td>#<?= (int)$log['id'] ?></td>
                            <td><strong><?= htmlspecialchars($log['username']) ?></strong></td>
                            <td><span style="background: #e0f2fe; color: #0369a1; padding: 2px 6px; border-radius: 4px; font-weight: 600; font-size: 0.75rem;"><?= htmlspecialchars($log['action']) ?></span></td>
                            <td><?= htmlspecialchars($log['details']) ?></td>
                            <td><code><?= htmlspecialchars($log['ip_address'] ?? '-') ?></code></td>
                            <td><small><?= htmlspecialchars($log['created_at']) ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
