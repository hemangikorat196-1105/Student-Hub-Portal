<?php
// ============================================================================
// Student Hub Portal - Standard Response & Audit Logging Utilities
// Practicals 9, 10, 11, 12, 13, 14, 15
// ============================================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/session.php';

/**
 * Sends a standardized JSON API response with HTTP status code.
 *
 * @param int $statusCode
 * @param bool $success
 * @param string $message
 * @param mixed $data
 * @param array $extra
 */
function json_response($statusCode, $success, $message, $data = null, $extra = []) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');

    $response = array_merge([
        'status'  => $statusCode,
        'success' => (bool)$success,
        'message' => (string)$message,
        'data'    => $data,
        'timestamp' => date('c')
    ], $extra);

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Inserts an event into the audit_logs table for traceability.
 *
 * @param string $action
 * @param string $details
 * @param int|null $userId
 * @return bool
 */
function log_audit_event($action, $details, $userId = null) {
    try {
        init_session();
        if ($userId === null && isset($_SESSION['user_id'])) {
            $userId = (int)$_SESSION['user_id'];
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $agent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Agent', 0, 255);

        $db = get_db_connection();
        $stmt = $db->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("issss", $userId, $action, $details, $ip, $agent);
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    } catch (Exception $e) {
        error_log("Failed to write audit log: " . $e->getMessage());
        return false;
    }
}
