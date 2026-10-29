<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/db.php';

$password = getenv('STUDENTHUB_ADMIN_PASSWORD');
if ($password === false || strlen($password) < 12) {
    fwrite(STDERR, "Set STUDENTHUB_ADMIN_PASSWORD to a unique password of at least 12 characters.\n");
    exit(1);
}

$username = getenv('STUDENTHUB_ADMIN_USERNAME') ?: 'admin';
$email = getenv('STUDENTHUB_ADMIN_EMAIL') ?: 'admin@studenthub.edu';
if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Administrator username or email is invalid.\n");
    exit(1);
}

try {
    $db = get_db_connection();
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $db->prepare(
        "UPDATE users SET username = ?, email = ?, password = ?, role = 'admin', status = 'active' WHERE id = 1 AND role = 'admin'"
    );
    $stmt->bind_param('sss', $username, $email, $hash);
    $stmt->execute();
    if ($stmt->affected_rows !== 1) {
        fwrite(STDERR, "Expected seeded administrator row (id 1) was not updated. Import sql/seed_data.sql first.\n");
        exit(1);
    }
    $stmt->close();
    fwrite(STDOUT, "Administrator credentials configured successfully.\n");
} catch (Throwable $error) {
    error_log('Local administrator setup failed: ' . $error->getMessage());
    fwrite(STDERR, "Administrator setup failed. Verify the local database configuration and imported seed data.\n");
    exit(1);
}
