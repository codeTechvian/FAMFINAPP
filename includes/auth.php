<?php
// auth.php - Authentication check and session management

// Initialize centralized session
require_once __DIR__ . '/../config/session.php';

// Check if user is logged in
if (!isset($_SESSION['user_logged_in'])) {
    header('Location: index.php');
    exit;
}

// CSRF token validation for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['_csrf_token'] ?? '';
    if (!validateCsrfToken($submittedToken)) {
        http_response_code(403);
        header('Location: ' . basename($_SERVER['PHP_SELF']) . '?error=csrf_mismatch');
        exit;
    }
}

// Database connection
require_once __DIR__ . '/../config/database.php';

// User ID availability
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
} else {
    header('Location: index.php');
    exit;
}
?>