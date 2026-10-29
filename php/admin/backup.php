<?php
// ============================================================================
// Student Hub Portal - Database Backup & Export Tool (Admin Only)
// Generates complete SQL database dump with table structures and row data.
// ============================================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth_guard.php';

init_session();
require_admin();

$db = get_db_connection();

$tables = ['users', 'students', 'events', 'registrations', 'faculty', 'courses', 'notices', 'audit_logs'];

// Download SQL dump handler
if (isset($_GET['download']) && $_GET['download'] === '1') {
    $filename = "student_portal_backup_" . date('Y-m-d_His') . ".sql";

    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');

    fwrite($out, "-- ============================================================================\n");
    fwrite($out, "-- Student Hub Portal - Complete Database SQL Backup\n");
    fwrite($out, "-- Generated on: " . date('Y-m-d H:i:s') . "\n");
    fwrite($out, "-- Database: student_portal\n");
    fwrite($out, "-- ============================================================================\n\n");
    fwrite($out, "CREATE DATABASE IF NOT EXISTS `student_portal` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n");
    fwrite($out, "USE `student_portal`;\n\n");
    fwrite($out, "SET FOREIGN_KEY_CHECKS = 0;\n\n");

    foreach ($tables as $table) {
        // 1. Table structure
        $res = $db->query("SHOW CREATE TABLE `$table`");
        if ($res && $row = $res->fetch_assoc()) {
            fwrite($out, "-- ----------------------------------------------------------------------------\n");
            fwrite($out, "-- Table structure for `$table`\n");
            fwrite($out, "-- ----------------------------------------------------------------------------\n");
            fwrite($out, "DROP TABLE IF EXISTS `$table`;\n");
            fwrite($out, $row['Create Table'] . ";\n\n");
        }

        // 2. Table data
        $resData = $db->query("SELECT * FROM `$table`");
        if ($resData && $resData->num_rows > 0) {
            fwrite($out, "-- Data for `$table` (" . $resData->num_rows . " rows)\n");
            while ($dataRow = $resData->fetch_assoc()) {
                $cols = array_keys($dataRow);
                $escapedCols = array_map(function($c) { return "`$c`"; }, $cols);
                $escapedVals = array_map(function($val) use ($db) {
                    if ($val === null) return "NULL";
                    return "'" . $db->real_escape_string($val) . "'";
                }, array_values($dataRow));

                fwrite($out, "INSERT INTO `$table` (" . implode(', ', $escapedCols) . ") VALUES (" . implode(', ', $escapedVals) . ");\n");
            }
            fwrite($out, "\n");
        }
    }

    fwrite($out, "SET FOREIGN_KEY_CHECKS = 1;\n");
    fwrite($out, "-- Backup completed successfully.\n");
    fclose($out);

    log_audit_event("DATABASE_BACKUP", "Admin #{$_SESSION['user_id']} exported full SQL database backup.");
    exit;
}

// Compute Table Statistics
$tableStats = [];
$totalRows = 0;
foreach ($tables as $t) {
    $res = $db->query("SELECT COUNT(*) AS c FROM `$t`");
    $cnt = $res ? (int)$res->fetch_assoc()['c'] : 0;
    $tableStats[$t] = $cnt;
    $totalRows += $cnt;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Backup & Export - Student Hub Portal</title>
    <link rel="stylesheet" href="../../Style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="../../script.js" defer></script>
    <style>
        .admin-wrap { max-width: 950px; margin: 35px auto 60px; padding: 30px; background: var(--card-bg, #fff); border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); border: 1px solid var(--border-color, #e2e8f0); font-family: 'Segoe UI', Tahoma, sans-serif; }
        .admin-header-bar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 25px; padding-bottom: 20px; border-bottom: 1px solid var(--border-color, #e2e8f0); }
        .admin-header-bar h1 { font-size: 1.5rem; margin: 0; color: var(--text-color, #0f172a); display: flex; align-items: center; gap: 12px; }
        .btn-act { padding: 9px 18px; border-radius: 8px; font-weight: 600; text-decoration: none; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-size: 0.92rem; }
        .btn-primary { background: #0d9488; color: #fff; }
        .btn-secondary { background: #f1f5f9; color: #334155; }
        .backup-card { background: var(--primary-light, #f0fdfa); border: 1px solid #99f6e4; border-radius: 10px; padding: 25px; margin-bottom: 25px; text-align: center; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-top: 20px; }
        .stat-item { background: var(--card-bg, #fff); border: 1px solid var(--border-color); border-radius: 8px; padding: 15px; text-align: center; }
        .stat-val { font-size: 1.5rem; font-weight: 800; color: var(--primary-color, #2563eb); margin-bottom: 4px; }
        .stat-lbl { font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; }
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
                <h1><i class="fa-solid fa-database" style="color:#0d9488;"></i> Database Disaster Recovery & Backup</h1>
                <p style="margin:4px 0 0; color:var(--text-muted); font-size:0.88rem;">Export complete MariaDB / MySQL relational schema and row records</p>
            </div>
            <a href="dashboard.php" class="btn-act btn-secondary"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
        </div>

        <div class="backup-card">
            <i class="fa-solid fa-cloud-arrow-down" style="font-size:3rem; color:#0d9488; margin-bottom:12px;"></i>
            <h2 style="font-size:1.3rem; margin:0 0 8px; color:#134e4a;">Instant Relational Database Backup</h2>
            <p style="color:#115e59; font-size:0.92rem; max-width:600px; margin:0 auto 20px;">
                Download a complete, valid SQL script containing <code>CREATE TABLE</code> definitions, primary keys, foreign key constraints, and <code>INSERT INTO</code> statements for all 8 institutional tables.
            </p>
            <a href="backup.php?download=1" class="btn-act btn-primary" style="font-size:1rem; padding:12px 28px;">
                <i class="fa-solid fa-download"></i> Download Full SQL Backup (.sql)
            </a>
        </div>

        <h3 style="margin:0 0 12px; font-size:1.1rem; color:var(--text-color);"><i class="fa-solid fa-table-list"></i> Database Tables Overview (Total Records: <?= $totalRows ?>)</h3>
        <div class="stats-grid">
            <?php foreach ($tableStats as $tableName => $rowCount): ?>
                <div class="stat-item">
                    <div class="stat-val"><?= $rowCount ?></div>
                    <div class="stat-lbl"><code><?= htmlspecialchars($tableName) ?></code></div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

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
</body>
</html>
