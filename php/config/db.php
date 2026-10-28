<?php
// ============================================================================
// Student Hub Portal - Database Configuration & Connection Handler
// Practicals 8, 9, 10, 11, 12, 13, 14, 15
// Supports both MySQLi and PDO with prepared statements and safe error handling
// ============================================================================

// Database Credentials
defined('DB_HOST') or define('DB_HOST', 'localhost');
defined('DB_USER') or define('DB_USER', 'root');
defined('DB_PASS') or define('DB_PASS', '');
defined('DB_NAME') or define('DB_NAME', 'student_portal');
defined('DB_PORT') or define('DB_PORT', 3306);
defined('DB_CHARSET') or define('DB_CHARSET', 'utf8mb4');

/**
 * Returns a shared MySQLi connection instance.
 *
 * @return mysqli
 * @throws Exception
 */
function get_db_connection() {
    static $mysqli = null;

    if ($mysqli === null || !$mysqli->ping()) {
        mysqli_report(MYSQLI_REPORT_STRICT | MYSQLI_REPORT_ERROR);
        try {
            $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
            $mysqli->set_charset(DB_CHARSET);
        } catch (mysqli_sql_exception $e) {
            error_log("Database Connection Error (MySQLi): " . $e->getMessage());
            throw new Exception("Unable to connect to database. Please verify MySQL service in XAMPP.");
        }
    }

    return $mysqli;
}

/**
 * Returns a shared PDO connection instance (Practical 8 requirement).
 *
 * @return PDO
 * @throws Exception
 */
function get_pdo_connection() {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database Connection Error (PDO): " . $e->getMessage());
            throw new Exception("Database connection failed. Please check your database server.");
        }
    }

    return $pdo;
}

/**
 * Helper to execute a prepared SELECT statement with parameter binding.
 *
 * @param string $sql
 * @param string $types
 * @param array $params
 * @return array
 */
function db_select_all($sql, $types = "", $params = []) {
    $db = get_db_connection();
    $stmt = $db->prepare($sql);

    if ($types && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}

/**
 * Helper to execute a prepared SELECT statement returning a single row.
 *
 * @param string $sql
 * @param string $types
 * @param array $params
 * @return array|null
 */
function db_select_one($sql, $types = "", $params = []) {
    $rows = db_select_all($sql, $types, $params);
    return !empty($rows) ? $rows[0] : null;
}
