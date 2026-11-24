<?php
// csrf.php - CSRF token input field

// Initialize centralized session
require_once __DIR__ . '/../config/session.php';
?>
<input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">