<?php
// config/session.php - Centralized session configuration

// Define application base URL for browser-facing resources
if (!defined('BASE_URL')) {
    define('BASE_URL', '/FAMFINAPP');
}

// Start session only if none is active
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session cookie parameters before starting
    session_set_cookie_params([
        'lifetime' => 0,           // Session cookie (expires when browser closes)
        'path' => '/FAMFINAPP/',   // Restrict to application path
        'domain' => '',            // Empty = current domain
        'secure' => false,         // Set to true in production with HTTPS
        'httponly' => true,        // Prevent JavaScript access
        'samesite' => 'Lax',       // CSRF protection
    ]);

    session_start();
}

// Generate CSRF token if not already present
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Get the current CSRF token (generates one if missing)
 */
function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate a submitted CSRF token using constant-time comparison
 */
function validateCsrfToken(?string $submittedToken): bool
{
    if (!isset($_SESSION['csrf_token']) || !is_string($submittedToken)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $submittedToken);
}
?>