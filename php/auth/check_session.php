<?php
// ============================================================================
// Student Hub Portal - Session Health & Identity Check Endpoint
// Practicals 10, 14, 15
// ============================================================================

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/response.php';

header('Cache-Control: no-store, private');
init_session();

if (!is_logged_in()) {
    json_response(200, false, "Not logged in", [
        'loggedIn' => false,
        'csrfToken' => generate_csrf_token()
    ]);
}

$user = current_user();
$remainingSeconds = max(0, SESSION_TIMEOUT_SECONDS - (time() - ($_SESSION['last_activity'] ?? time())));

json_response(200, true, "Active session", [
    'loggedIn'         => true,
    'user'             => $user,
    'remainingSeconds' => $remainingSeconds,
    'csrfToken'        => generate_csrf_token()
]);
