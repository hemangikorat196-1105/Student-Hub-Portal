<?php
// ============================================================================
// Student Hub Portal - Secure Event Poster Upload Validator
// Practical 12 (Poster Upload & File Validation)
// Checks: upload error, file size (<= 2MB), MIME type (image/jpeg, image/png, image/webp)
// Generates random hashed filename and stores in assets/uploads/event_posters/
// ============================================================================

defined('MAX_POSTER_SIZE_BYTES') or define('MAX_POSTER_SIZE_BYTES', 2 * 1024 * 1024); // 2 MB
defined('ALLOWED_POSTER_MIMES') or define('ALLOWED_POSTER_MIMES', [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp'
]);

/**
 * Validates and saves an uploaded event poster image.
 *
 * @param array $file $_FILES['poster']
 * @return array ['success' => bool, 'filename' => string|null, 'error' => string|null]
 */
function handle_poster_upload($file) {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'filename' => null, 'error' => null];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'filename' => null, 'error' => 'File upload error code: ' . $file['error']];
    }

    // 1. File Size Validation
    if ($file['size'] > MAX_POSTER_SIZE_BYTES) {
        return [
            'success'  => false,
            'filename' => null,
            'error'    => 'File exceeds 2 MB limit (size: ' . round($file['size'] / (1024 * 1024), 2) . ' MB).'
        ];
    }

    // 2. MIME Type Validation using finfo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    set_error_handler(function () {
        return true;
    });
    try {
        $imageInfo = getimagesize($file['tmp_name']);
    } finally {
        restore_error_handler();
    }

    if (!array_key_exists($mime, ALLOWED_POSTER_MIMES) || $imageInfo === false || $imageInfo['mime'] !== $mime) {
        return [
            'success'  => false,
            'filename' => null,
            'error'    => "Invalid image content. Only valid JPG, PNG, and WEBP image files are permitted."
        ];
    }

    if ($imageInfo[0] < 1 || $imageInfo[1] < 1 || $imageInfo[0] > 10000 || $imageInfo[1] > 10000 || $imageInfo[0] * $imageInfo[1] > 40000000) {
        return [
            'success'  => false,
            'filename' => null,
            'error'    => 'Image dimensions exceed the supported limit.'
        ];
    }

    // 3. Unique Filename Generation (prevents overwriting & directory traversal)
    $ext = ALLOWED_POSTER_MIMES[$mime];
    $uniqueName = 'poster_' . bin2hex(random_bytes(16)) . '.' . $ext;

    $targetDir = __DIR__ . '/../../assets/uploads/event_posters/';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $targetPath = $targetDir . $uniqueName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => false, 'filename' => null, 'error' => 'Failed to save uploaded file to disk.'];
    }

    return ['success' => true, 'filename' => $uniqueName, 'error' => null];
}
