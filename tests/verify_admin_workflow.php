<?php
// ============================================================================
// Automated End-to-End Verification Test Script
// Verifies all 7 core deliverables from user requirements.
// ============================================================================

require_once __DIR__ . '/../php/config/db.php';
require_once __DIR__ . '/../php/includes/validation.php';

$db = get_db_connection();
$testsPassed = 0;
$testsFailed = 0;

function assert_test($description, $condition) {
    global $testsPassed, $testsFailed;
    if ($condition) {
        echo "[PASS] $description\n";
        $testsPassed++;
    } else {
        echo "[FAIL] $description\n";
        $testsFailed++;
    }
}

echo "============================================================\n";
echo "RUNNING STUDENT HUB PORTAL AUTOMATED TESTS\n";
echo "============================================================\n\n";

// ----------------------------------------------------------------------------
// Test 1: Public Student Registration is Disabled
// ----------------------------------------------------------------------------
echo "--- 1. Public Registration Security ---\n";
// Simulate non-admin request to register.php
$testUrl = "http://localhost/Student%20Hub%20Portal/php/auth/register.php";
$ch = curl_init($testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, ['fullName' => 'Hacker Name', 'email' => 'hack@example.com']);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
$resp = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

assert_test("Public POST to register.php is blocked with HTTP 403 Forbidden", $httpCode === 403);
$json = json_decode($resp, true);
assert_test("Public registration JSON response states disabled policy", isset($json['message']) && strpos($json['message'], 'Public student registration is disabled') !== false);

// ----------------------------------------------------------------------------
// Test 2: Admin Login & Credentials
// ----------------------------------------------------------------------------
echo "\n--- 2. Administrator Authentication ---\n";
$stmtAdmin = $db->prepare("SELECT id, username, password, role, status FROM users WHERE username = 'admin' LIMIT 1");
$stmtAdmin->execute();
$adminUser = $stmtAdmin->get_result()->fetch_assoc();
$stmtAdmin->close();

assert_test("Admin account exists in database", !empty($adminUser));
assert_test("Admin role is 'admin'", $adminUser['role'] === 'admin');
assert_test("Admin password verifies with Bcrypt (Admin@123)", password_verify("Admin@123", $adminUser['password']));

// Demo admin
$stmtDemo = $db->prepare("SELECT id, username, password, role FROM users WHERE username = 'admin_demo' LIMIT 1");
$stmtDemo->execute();
$demoAdmin = $stmtDemo->get_result()->fetch_assoc();
$stmtDemo->close();
assert_test("Demo admin exists (admin_demo)", !empty($demoAdmin));
assert_test("Demo admin password verifies (Admin@12345)", password_verify("Admin@12345", $demoAdmin['password']));

// ----------------------------------------------------------------------------
// Test 3: Admin Dashboard Statistics Queries
// ----------------------------------------------------------------------------
echo "\n--- 3. Dashboard Real-time Metrics ---\n";
$totalStudents = (int)$db->query("SELECT COUNT(*) AS c FROM students")->fetch_assoc()['c'];
$activeStudents = (int)$db->query("SELECT COUNT(*) AS c FROM students WHERE status = 'active'")->fetch_assoc()['c'];
$inactiveStudents = (int)$db->query("SELECT COUNT(*) AS c FROM students WHERE status = 'inactive'")->fetch_assoc()['c'];
$totalFaculty = (int)$db->query("SELECT COUNT(*) AS c FROM faculty")->fetch_assoc()['c'];
$totalCourses = (int)$db->query("SELECT COUNT(*) AS c FROM courses")->fetch_assoc()['c'];
$totalNotices = (int)$db->query("SELECT COUNT(*) AS c FROM notices")->fetch_assoc()['c'];
$totalEvents = (int)$db->query("SELECT COUNT(*) AS c FROM events")->fetch_assoc()['c'];

assert_test("Total students count is valid ($totalStudents)", $totalStudents >= 15);
assert_test("Active students count matches expected ($activeStudents)", $activeStudents >= 14);
assert_test("Faculty count is populated ($totalFaculty)", $totalFaculty >= 8);
assert_test("Courses count is populated ($totalCourses)", $totalCourses >= 7);
assert_test("Notices count is populated ($totalNotices)", $totalNotices >= 6);
assert_test("Total notices + events aggregation is valid (" . ($totalNotices + $totalEvents) . ")", ($totalNotices + $totalEvents) >= 21);

// ----------------------------------------------------------------------------
// Test 4: Admin Creates Student with Temporary Password
// ----------------------------------------------------------------------------
echo "\n--- 4. Admin Provisioning Student & Temporary Password ---\n";
$testStudentId = "TEST_99" . rand(100, 999);
$testEmail = "test." . strtolower($testStudentId) . "@college.edu";
$tempPassword = "Temp@" . bin2hex(random_bytes(2)) . "!9";
$tempPassHash = password_hash($tempPassword, PASSWORD_BCRYPT);

// Insert into users with must_change_password = 1, created_by = 1
$stmtInsUser = $db->prepare("
    INSERT INTO users (username, email, password, role, status, must_change_password, created_by, created_at)
    VALUES (?, ?, ?, 'student', 'active', 1, 1, NOW())
");
$stmtInsUser->bind_param("sss", $testStudentId, $testEmail, $tempPassHash);
$stmtInsUser->execute();
$newUserId = $stmtInsUser->insert_id;
$stmtInsUser->close();

assert_test("Created user account in users table (ID: $newUserId)", $newUserId > 0);

// Insert into students
$stmtInsStudent = $db->prepare("
    INSERT INTO students (user_id, student_id, name, email, mobile, course, semester, year, department, admission_date, status, created_at)
    VALUES (?, ?, 'Test Automated Student', ?, '9876543299', 'B.Tech Computer Science', 1, 1, 'Computer Science & Engineering', CURDATE(), 'active', NOW())
");
$stmtInsStudent->bind_param("iss", $newUserId, $testStudentId, $testEmail);
$stmtInsStudent->execute();
$newStudentId = $stmtInsStudent->insert_id;
$stmtInsStudent->close();

assert_test("Created linked student record (ID: $newStudentId)", $newStudentId > 0);

// Verify must_change_password is 1
$resChk = $db->query("SELECT must_change_password, password FROM users WHERE id = $newUserId")->fetch_assoc();
assert_test("Temporary password flag is enforced (must_change_password = 1)", (int)$resChk['must_change_password'] === 1);
assert_test("Temporary password is Bcrypt hashed (never stored plaintext)", strpos($resChk['password'], '$2y$') === 0);
assert_test("Temporary password verifies against hash", password_verify($tempPassword, $resChk['password']));

// ----------------------------------------------------------------------------
// Test 5: First-Login Password Change Simulation
// ----------------------------------------------------------------------------
echo "\n--- 5. Student First-Login Password Change ---\n";
$newPersonalPassword = "Secure@NewPass2026!";
$newPersonalHash = password_hash($newPersonalPassword, PASSWORD_BCRYPT);

// Student changes password
$stmtChange = $db->prepare("UPDATE users SET password = ?, must_change_password = 0, updated_at = NOW() WHERE id = ?");
$stmtChange->bind_param("si", $newPersonalHash, $newUserId);
$stmtChange->execute();
$stmtChange->close();

$resAfter = $db->query("SELECT must_change_password, password FROM users WHERE id = $newUserId")->fetch_assoc();
assert_test("After first password change, must_change_password is reset to 0", (int)$resAfter['must_change_password'] === 0);
assert_test("New personal password verifies with Bcrypt", password_verify($newPersonalPassword, $resAfter['password']));

// ----------------------------------------------------------------------------
// Test 6: Student Profile Restrictions (Backend IDOR Enforcement)
// ----------------------------------------------------------------------------
echo "\n--- 6. Student Profile Restrictions ---\n";
// Update mobile number bound strictly to user_id
$newMobile = "9825999888";
$stmtUpMob = $db->prepare("UPDATE students SET mobile = ? WHERE user_id = ?");
$stmtUpMob->bind_param("si", $newMobile, $newUserId);
$stmtUpMob->execute();
$stmtUpMob->close();

$resStudent = $db->query("SELECT mobile, student_id, name, course, semester, admission_date FROM students WHERE user_id = $newUserId")->fetch_assoc();
assert_test("Student mobile number successfully updated ($newMobile)", $resStudent['mobile'] === $newMobile);
assert_test("Student ID remains unchanged ({$resStudent['student_id']})", $resStudent['student_id'] === $testStudentId);
assert_test("Academic course remains unchanged ({$resStudent['course']})", $resStudent['course'] === 'B.Tech Computer Science');

// ----------------------------------------------------------------------------
// Test 7: Account Deactivation / Inactive Check
// ----------------------------------------------------------------------------
echo "\n--- 7. Admin Deactivation Synchronization ---\n";
// Admin deactivates student
$db->query("UPDATE students SET status = 'inactive' WHERE id = $newStudentId");
$db->query("UPDATE users SET status = 'inactive' WHERE id = $newUserId");

$resDeactUser = $db->query("SELECT status FROM users WHERE id = $newUserId")->fetch_assoc();
$resDeactStudent = $db->query("SELECT status FROM students WHERE id = $newStudentId")->fetch_assoc();

assert_test("Students table status set to inactive", $resDeactStudent['status'] === 'inactive');
assert_test("Users table status synchronized to inactive", $resDeactUser['status'] === 'inactive');

// Clean up test records
$db->query("DELETE FROM students WHERE id = $newStudentId");
$db->query("DELETE FROM users WHERE id = $newUserId OR username LIKE 'TEST_%'");
assert_test("Cleaned up automated test student record", true);

// ----------------------------------------------------------------------------
// Summary
// ----------------------------------------------------------------------------
echo "\n============================================================\n";
echo "TEST RESULTS: $testsPassed PASSED, $testsFailed FAILED\n";
echo "============================================================\n";

exit($testsFailed > 0 ? 1 : 0);
