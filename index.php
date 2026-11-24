<?php
// index.php - Welcome / Login page (public entry point)

// Initialize centralized session BEFORE any output
require_once __DIR__ . '/config/session.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAMFIN - Family Financial Management</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/auth.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="logo-group">
                <img src="<?= BASE_URL ?>/assets/images/famfin-logo.svg" alt="FAMFIN" class="logo-mark">
                <span class="logo-text">FAMFIN</span>
            </div>
            <h2>Welcome Back</h2>
            <form action="login.php" method="POST">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                <div class="form-group">
                    <label for="email">Email / Username</label>
                    <input type="email" name="email" id="email" placeholder="Email or Username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" placeholder="••••••••" required>
                </div>
                <button type="submit" name="login" class="btn-primary">Login</button>
            </form>
            <p class="divider">or</p>
            <a href="register.php" class="link-text">Create an Account</a>
            <a href="forgot-password.php" class="link-text">Forgot Password?</a>
        </div>
    </div>
</body>
</html>