<?php
// register.php - Registration page and handler

// Initialize centralized session BEFORE any output
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['_csrf_token'] ?? '';
    if (!validateCsrfToken($submittedToken)) {
        http_response_code(403);
        header('Location: register.php?error=csrf_mismatch');
        exit;
    }

    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validate required fields
    if (empty($fullName) || empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
        header('Location: register.php?error=empty_fields');
        exit;
    }

    // Validate password match
    if ($password !== $confirmPassword) {
        header('Location: register.php?error=password_mismatch');
        exit;
    }

    // Check for duplicate username or email
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
    $checkStmt->execute([$username, $email]);
    if ($checkStmt->rowCount() > 0) {
        header('Location: register.php?error=duplicate');
        exit;
    }

    // Hash password and insert user
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $insertStmt = $pdo->prepare("INSERT INTO users (full_name, username, email, password) VALUES (?, ?, ?, ?)");
    $insertStmt->execute([$fullName, $username, $email, $hashedPassword]);

    header('Location: login.php?success=registered');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAMFIN - Register</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/auth.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <img src="<?= BASE_URL ?>/assets/images/famfin-logo.svg" alt="FAMFIN" class="logo">
            <h2>Create Your FAMFIN Account</h2>
            <form action="register.php" method="POST">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" name="full_name" id="full_name" placeholder="Full Name" required>
                </div>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" name="username" id="username" placeholder="Username" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" placeholder="Email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" placeholder="Password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
                </div>
                <button type="submit" name="register" class="btn-primary">Create Account</button>
            </form>
            <div class="form-footer">
                <a href="login.php" class="link-text">Already have an account? Login</a>
            </div>
        </div>
    </div>
</body>
</html>