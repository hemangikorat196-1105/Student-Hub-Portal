<?php
// ============================================================================
// Student Hub Portal - Server-Side Input Validation & Sanitization
// Practicals 5, 7, 9, 11, 12, 13, 15
// Matches frontend JavaScript regex rules exactly for full consistency
// ============================================================================

/**
 * Trims and escapes string input for safe HTML output.
 *
 * @param mixed $data
 * @return string
 */
function sanitize_input($data) {
    if ($data === null) return "";
    $data = trim((string)$data);
    $data = stripslashes($data);
    return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Validates Full Name (2 to 50 letters and spaces only).
 *
 * @param string $name
 * @return bool
 */
function validate_full_name($name) {
    return (bool)preg_match('/^[A-Za-z\s]{2,50}$/', trim($name));
}

/**
 * Validates Email Address format.
 *
 * @param string $email
 * @return bool
 */
function validate_email_format($email) {
    return (bool)filter_var(trim($email), FILTER_VALIDATE_EMAIL);
}

/**
 * Validates 10-digit Indian Mobile Number starting with 6, 7, 8, or 9.
 *
 * @param string $mobile
 * @return bool
 */
function validate_indian_mobile($mobile) {
    return (bool)preg_match('/^[6-9]\d{9}$/', trim($mobile));
}

/**
 * Validates password strength (min 8 chars, 1 uppercase, 1 lowercase, 1 number, 1 special char).
 *
 * @param string $password
 * @return bool
 */
function validate_password_strength($password) {
    // Regex matching frontend: at least 8 chars, 1 lower, 1 upper, 1 digit, 1 special symbol
    return (bool)preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#^_\-~])[A-Za-z\d@$!%*?&#^_\-~]{8,64}$/', $password);
}

/**
 * Validates course selection against permitted academic programs.
 *
 * @param string $course
 * @return bool
 */
function validate_course_choice($course) {
    $validCourses = [
        'B.Tech Computer Science',
        'B.Tech Information Technology',
        'B.Tech Artificial Intelligence',
        'B.Tech Mechanical Engineering',
        'B.Tech Civil Engineering',
        'B.Tech Electronics',
        'BCA',
        'MCA'
    ];
    return in_array(trim($course), $validCourses, true);
}

/**
 * Validates year of study.
 *
 * @param mixed $year
 * @return bool
 */
function validate_year_choice($year) {
    $validYears = [1, 2, 3, 4, '1', '2', '3', '4', 'First Year', 'Second Year', 'Third Year', 'Fourth Year'];
    return in_array($year, $validYears, true);
}

/**
 * Normalizes year string or number into an integer 1-4.
 *
 * @param mixed $year
 * @return int
 */
function normalize_year_int($year) {
    if (is_numeric($year)) return (int)$year;
    $map = [
        'First Year' => 1,
        'Second Year' => 2,
        'Third Year' => 3,
        'Fourth Year' => 4
    ];
    return $map[$year] ?? 1;
}

/**
 * Validates gender selection.
 *
 * @param string $gender
 * @return bool
 */
function validate_gender_choice($gender) {
    return in_array(trim($gender), ['Male', 'Female', 'Other'], true);
}
