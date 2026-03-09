<?php
// ============================================================
// Student Task Management System - Configuration
// ============================================================

session_start();

// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'student_task_db');
define('DB_USER', 'root');
define('DB_PASS', '');

/**
 * Get PDO database connection
 */
function getDB() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

/**
 * Check if user is logged in, redirect to login if not
 */
function requireLogin() {
    if (!isset($_SESSION['student_id'])) {
        header('Location: index.php');
        exit;
    }
}

/**
 * Redirect helper
 */
function redirect($url) {
    header("Location: $url");
    exit;
}
?>
