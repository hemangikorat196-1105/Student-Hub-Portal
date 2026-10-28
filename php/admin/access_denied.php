<?php
// ============================================================================
// Student Hub Portal - 403 Forbidden / Access Denied Page
// Practical 10, 14, 15
// ============================================================================

require_once __DIR__ . '/../includes/session.php';
init_session();
http_response_code(403);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 Forbidden - Access Denied</title>
    <link rel="stylesheet" href="../../Style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        .denied-card { max-width: 520px; margin: 80px auto; padding: 35px; background: #fff; border-radius: 12px; border-left: 6px solid #dc2626; box-shadow: 0 4px 20px rgba(0,0,0,0.08); font-family: 'Segoe UI', Tahoma, sans-serif; text-align: center; }
        .denied-icon { font-size: 3.5rem; color: #dc2626; margin-bottom: 15px; }
        .denied-card h1 { font-size: 1.8rem; color: #0f172a; margin-bottom: 10px; }
        .denied-card p { color: #64748b; line-height: 1.6; margin-bottom: 25px; }
        .denied-code { display: inline-block; background: #fee2e2; color: #991b1b; padding: 4px 10px; border-radius: 4px; font-weight: 700; font-size: 0.9rem; margin-bottom: 15px; }
        .btn-row { display: flex; justify-content: center; gap: 12px; }
        .btn { display: inline-block; padding: 10px 18px; border-radius: 6px; font-weight: 600; text-decoration: none; }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-secondary { background: #f1f5f9; color: #334155; }
    </style>
</head>
<body>
    <main class="denied-card">
        <div class="denied-icon"><i class="fa-solid fa-shield-halved"></i></div>
        <div class="denied-code">HTTP 403 - FORBIDDEN</div>
        <h1>Access Denied</h1>
        <p>You do not have permission to access the administrative dashboard or management tools. This resource requires an authenticated Administrator account.</p>
        <div class="btn-row">
            <a href="../../Login.html" class="btn btn-primary"><i class="fa-solid fa-right-to-bracket"></i> Login as Admin</a>
            <a href="../../home.html" class="btn btn-secondary">Student Home</a>
        </div>
    </main>
</body>
</html>
