<?php
// budget/delete.php - Delete budget
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$id = intval($_POST['budget_id'] ?? 0);

if (!empty($id)) {
    $delStmt = $pdo->prepare("DELETE FROM budgets WHERE id = ? AND user_id = ?");
    $delStmt->execute([$id, $userId]);
}

header('Location: index.php');
exit;
?>