<?php
// settings.php - Settings processing
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['_csrf_token'] ?? '';
    if (!validateCsrfToken($submittedToken)) {
        http_response_code(403);
        header('Location: settings.php?error=csrf_mismatch');
        exit;
    }
    
    // Update full name
    if (isset($_POST['update_name'])) {
        $fullName = trim($_POST['full_name']);
        if (!empty($fullName)) {
            $updateStmt = $pdo->prepare("UPDATE users SET full_name = ? WHERE id = ?");
            $updateStmt->execute([$fullName, $userId]);
            $_SESSION['user_name'] = $fullName;
        }
    }
    
    // Change password
    if (isset($_POST['change_password'])) {
        $currentPass = trim($_POST['current_password']);
        $newPass = trim($_POST['new_password']);
        $confirmPass = trim($_POST['confirm_password']);
        
        if (empty($currentPass) || empty($newPass) || empty($confirmPass)) {
            $error = "All password fields are required";
        } elseif ($newPass !== $confirmPass) {
            $error = "New passwords do not match";
        } else {
            // Verify current password
            $userStmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $userStmt->execute([$userId]);
            $user = $userStmt->fetch();
            
            if (password_verify($currentPass, $user['password'])) {
                $hash = password_hash($newPass, PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $updateStmt->execute([$hash, $userId]);
            } else {
                $error = "Current password is incorrect";
            }
        }
    }
    
    // Logout
    if (isset($_POST['logout'])) {
        session_unset();
        session_destroy();
        header('Location: index.php');
        exit;
    }
}
?>