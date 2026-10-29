<?php
require_once __DIR__ . '/../config/db.php';

$db = get_db_connection();
$hash = password_hash('Admin@12345', PASSWORD_BCRYPT, ['cost' => 12]);
$stmt = $db->prepare("INSERT INTO users (username, email, password, role, status, created_at, updated_at) VALUES (?, ?, ?, 'admin', 'active', NOW(), NOW())");
$user = 'admin_demo';
$email = 'admin_demo@studenthub.edu';
$stmt->bind_param('sss', $user, $email, $hash);
if ($stmt->execute()) {
    echo "Dummy admin account created! ID: " . $stmt->insert_id . "\n";
} else {
    echo "Error: " . $stmt->error . "\n";
}
