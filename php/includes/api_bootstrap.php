<?php
ini_set('display_errors', '0');

set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function (Throwable $error) {
    error_log('API request failed: ' . $error->getMessage());
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode([
        'status' => 500,
        'success' => false,
        'message' => 'An internal server error occurred.',
        'data' => null,
        'timestamp' => date('c')
    ], JSON_UNESCAPED_SLASHES);
    exit;
});
