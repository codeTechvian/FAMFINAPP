<?php
// login.php - Login authentication handler

// Initialize centralized session
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';

// CSRF validation
$submittedToken = $_POST['_csrf_token'] ?? '';
if (!validateCsrfToken($submittedToken)) {
    http_response_code(403);
    header('Location: index.php?error=csrf_mismatch');
    exit;
}

// Get form data
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    header('Location: index.php?error=empty_fields');
    exit;
}

// Find user by email or username
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ? LIMIT 1");
$stmt->execute([$email, $email]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: index.php?error=invalid_credentials');
    exit;
}

// Verify password
if (!password_verify($password, $user['password'])) {
    header('Location: index.php?error=invalid_credentials');
    exit;
}

// Preserve CSRF token before session regeneration
$csrfToken = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));

// Set session user data
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['full_name'];
$_SESSION['user_username'] = $user['username'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_logged_in'] = true;

// Restore CSRF token then regenerate session ID
$_SESSION['csrf_token'] = $csrfToken;
session_regenerate_id(true);

// Redirect to dashboard
header('Location: dashboard/index.php');
exit;
?>