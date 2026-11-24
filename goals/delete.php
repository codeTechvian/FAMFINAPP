<?php
// goals/delete.php - Delete savings goal
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$id = intval($_POST['goal_id'] ?? 0);

if (!empty($id)) {
    $delStmt = $pdo->prepare("DELETE FROM savings_goals WHERE id = ? AND user_id = ?");
    $delStmt->execute([$id, $userId]);
}

header('Location: index.php');
exit;
?>