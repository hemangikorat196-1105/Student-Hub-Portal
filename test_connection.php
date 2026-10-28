<?php
// ============================================================================
// Student Hub Portal - Database Connection Test Script
// Practical 8: MySQL Schema Design, ER Model, PDO Connectivity & Prepared Statements
// Output: Verifies PDO connection, MySQL driver, database version and table counts
// ============================================================================

require_once __DIR__ . '/php/config/db.php';

$title = "Database Connection Test";
$status = "error";
$message = "";
$details = [];

try {
    // 1. Establish secure connection using PDO (Practical 8 requirement)
    $pdo = get_pdo_connection();
    $status = "success";
    $message = "Database connection established successfully using PHP PDO!";

    // Retrieve database metadata
    $stmtVer = $pdo->query("SELECT VERSION() AS ver, DATABASE() AS db_name");
    $meta = $stmtVer->fetch();

    $details['Driver']           = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $details['Server Version']   = $meta['ver'];
    $details['Database Name']    = $meta['db_name'];
    $details['Host']             = DB_HOST . ':' . DB_PORT;
    $details['Connection Status']= $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS);

    // Retrieve record counts via prepared statements
    $tables = ['users', 'students', 'events', 'registrations', 'audit_logs'];
    $counts = [];
    foreach ($tables as $t) {
        $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM `$t`");
        $stmt->execute();
        $counts[$t] = (int)$stmt->fetchColumn();
    }
    $details['Table Counts'] = $counts;

} catch (Exception $e) {
    $status = "error";
    $message = "Connection Failed: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - Student Hub Portal</title>
    <link rel="stylesheet" href="Style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        .test-card { max-width: 640px; margin: 40px auto; padding: 30px; background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); font-family: 'Segoe UI', Tahoma, sans-serif; }
        .success-bar { border-left: 6px solid #16a34a; }
        .error-bar { border-left: 6px solid #dc2626; }
        .status-header { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
        .status-header i { font-size: 2.2rem; }
        .success-bar i { color: #16a34a; }
        .error-bar i { color: #dc2626; }
        .kv-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .kv-table td { padding: 10px 14px; border-bottom: 1px solid #f1f5f9; }
        .kv-table td:first-child { font-weight: 600; color: #475569; width: 35%; }
        .counts-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 10px; margin-top: 15px; }
        .count-box { background: #f8fafc; padding: 10px; border-radius: 6px; text-align: center; border: 1px solid #e2e8f0; }
        .count-val { font-size: 1.4rem; font-weight: 700; color: #2563eb; }
        .count-name { font-size: 0.75rem; color: #64748b; text-transform: uppercase; }
        .btn { display: inline-block; padding: 10px 18px; background: #2563eb; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 600; margin-top: 20px; }
    </style>
</head>
<body>
    <main class="test-card <?= ($status === 'success') ? 'success-bar' : 'error-bar' ?>">
        <div class="status-header">
            <i class="fa-solid <?= ($status === 'success') ? 'fa-database' : 'fa-triangle-exclamation' ?>"></i>
            <div>
                <h2 style="margin: 0; color: #0f172a;">Database Connection Test (PDO)</h2>
                <small style="color: #64748b;">Practical 8: MySQL Schema &amp; PDO Connectivity</small>
            </div>
        </div>

        <div style="background: <?= ($status === 'success') ? '#dcfce7' : '#fee2e2' ?>; color: <?= ($status === 'success') ? '#166534' : '#991b1b' ?>; padding: 12px 16px; border-radius: 6px; font-weight: 600;">
            <i class="fa-solid <?= ($status === 'success') ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i> <?= htmlspecialchars($message) ?>
        </div>

        <?php if ($status === 'success'): ?>
            <table class="kv-table">
                <tr><td>Database Driver</td><td><?= htmlspecialchars($details['Driver']) ?></td></tr>
                <tr><td>Database Name</td><td><strong><?= htmlspecialchars($details['Database Name']) ?></strong></td></tr>
                <tr><td>MySQL Server Version</td><td><?= htmlspecialchars($details['Server Version']) ?></td></tr>
                <tr><td>Host Connection</td><td><?= htmlspecialchars($details['Host']) ?></td></tr>
            </table>

            <h3 style="margin-top: 25px; font-size: 1rem; color: #334155;">Verified Table Record Counts (3NF Normalized Schema):</h3>
            <div class="counts-grid">
                <?php foreach ($details['Table Counts'] as $tbl => $cnt): ?>
                    <div class="count-box">
                        <div class="count-val"><?= (int)$cnt ?></div>
                        <div class="count-name"><?= htmlspecialchars($tbl) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <a href="index.html" class="btn"><i class="fa-solid fa-house"></i> Return to Portal Home</a>
    </main>
</body>
</html>
