<?php
// Compatibility route; all account creation uses the single secure handler.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../Login.html');
    exit;
}

if (isset($_POST['fullName'])) {
    require __DIR__ . '/../auth/register.php';
    exit;
}

if (isset($_POST['studentId']) || isset($_POST['username'])) {
    require __DIR__ . '/../auth/login.php';
    exit;
}

http_response_code(400);
echo 'Unsupported form submission.';
