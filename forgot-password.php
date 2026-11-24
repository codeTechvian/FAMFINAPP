<?php
// forgot-password.php - Forgot password page

// Initialize centralized session BEFORE any output
require_once __DIR__ . '/config/session.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAMFIN - Forgot Password</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/auth.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <img src="<?= BASE_URL ?>/assets/images/famfin-logo.svg" alt="FAMFIN" class="logo">
            <h2>Forgot Password</h2>
            <p>Enter your email address below and we'll send you a link to reset your password.</p>
            <form action="forgot-password.php" method="POST">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" placeholder="Email" required>
                </div>
                <button type="submit" name="reset" class="btn-primary">Send Reset Link</button>
            </form>
            <div class="form-footer">
                <a href="login.php" class="link-text">Remember your password? Login</a>
            </div>
        </div>
    </div>
</body>
</html>