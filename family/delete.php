<?php
// family/delete.php - Delete family member
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$id = intval($_POST['member_id'] ?? 0);

if (!empty($id)) {
    $delStmt = $pdo->prepare("DELETE FROM family_members WHERE id = ? AND user_id = ?");
    $delStmt->execute([$id, $userId]);
}

header('Location: index.php');
exit;
?>