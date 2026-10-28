<?php
// ============================================================================
// Student Hub Portal - Contact Us Form Submission Handler
// ============================================================================

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/response.php';

init_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../contactus.html");
    exit;
}

$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? 'General Enquiry');
$message = trim($_POST['message'] ?? '');

$errors = [];
if (!validate_full_name($name)) $errors['name'] = "Please provide your full name.";
if (!validate_email_format($email)) $errors['email'] = "Please provide a valid email address.";
if (empty($message) || strlen($message) < 5) $errors['message'] = "Please enter your message (minimum 5 characters).";

if (!empty($errors)) {
    if (is_api_request()) {
        json_response(422, false, "Validation errors", ['errors' => $errors]);
    }
    $_SESSION['flash_error'] = implode(" ", $errors);
    header("Location: ../../contactus.html");
    exit;
}

// Save message to CSV storage
$csvPath = __DIR__ . '/../../data/contact_messages.csv';
$isNew = !file_exists($csvPath) || filesize($csvPath) === 0;
$fp = fopen($csvPath, 'a');
if ($fp) {
    if ($isNew) {
        fputcsv($fp, ['Name', 'Email', 'Subject', 'Message', 'SubmittedAt']);
    }
    fputcsv($fp, [$name, $email, $subject, $message, date('Y-m-d H:i:s')]);
    fclose($fp);
}

log_audit_event("CONTACT_SUBMISSION", "Contact inquiry from $name ($email): $subject");

if (is_api_request()) {
    json_response(200, true, "Thank you! Your message has been received.");
}

$_SESSION['flash_success'] = "Thank you! Your message has been successfully sent to the college administration.";
header("Location: ../../contactus.html?status=success");
exit;
